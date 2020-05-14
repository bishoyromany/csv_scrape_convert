<?php 
ini_set('max_execution_time', 0);
ini_set('memory_limit', '500M');

require __DIR__."/Limonte/AdblockParser.php";
require __DIR__."/Limonte/AdblockRule.php";
require __DIR__."/Limonte/InvalidRuleException.php";
require __DIR__."/Limonte/Str.php";

use Limonte\AdblockParser;

$rules = file("easylist.txt");
$adblockParser = new AdblockParser($rules);


function throwErrorException($errstr = null,$code = null, $errno = null, $errfile = null, $errline = null) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function warning_handler($errno, $errstr, $errfile, $errline, array $errcontext) {
    return false && throwErrorException($errstr, 0, $errno, $errfile, $errline);
}

set_error_handler('warning_handler', E_WARNING);

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function dd($data){
    echo "<pre>";
        print_r($data);
    echo "</pre>";
    exit;
}

function toArray($data){
    $returned = [];
    foreach($data as $d){
        $returned[] = json_decode($d);
    }
    return $returned;
}

function toJson($data){
    $returned = [];
    foreach($data as $d){
        $returned[] = json_encode($d);
    }
    return $returned;
}

function get_web_page($url){
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(
        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
        CURLOPT_POST           =>false,        //set to GET
        CURLOPT_USERAGENT      => $user_agent, //set user agent
        CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
        CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}

function is_html($string){
  return preg_match("/<[^<]+>/",$string,$m) != 0;
}

function checkIfCompany($url){
    $url = 'http://'.$url.'ads.text';
    $data = get_web_page($url);
    if(empty($data['content'])){
        return false;
    }else{
        if(is_html($data['content'])){
            return false;
        }else{
            return true;
        }
    }

    // try{
    //     if(!file_exists($url)){
    //         dd("here");
    //         return false;
    //     }
    //     $data = file_get_contents($url);
    //     if(is_html($data)){
    //         return false;
    //     }
    //     return true;
    // }catch(Exception $e){
    //     dd('here');
    //     return false;
    // }
}


/**
 * get variables
 */
$fileTypes = ['COMPANIES','PUBLISHERS', 'UNKNOWN'];

$url = isset($_GET['url']) && !empty($_GET['url']) ? filter_var($_GET['url'], FILTER_SANITIZE_STRING) : false;
if(!$url){ echo "Make sure to Write The URL"; exit; }

$columns = isset($_GET['columns']) && !empty($_GET['columns']) ? explode(',',filter_var($_GET['columns'], FILTER_SANITIZE_STRING)) : false;
if(!$columns){ echo "Make sure to Write The Columsn"; exit; }
$filterDuplicates = isset($_GET['filterDuplicates']) ? filter_var($_GET['filterDuplicates'], FILTER_SANITIZE_STRING) : false;
$allInOneFile = isset($_GET['allInOneFile']) ? filter_var($_GET['allInOneFile'], FILTER_SANITIZE_STRING) : false;
$fileName = isset($_GET['fileName']) && !empty($_GET['fileName']) ? filter_var($_GET['fileName'], FILTER_SANITIZE_STRING).'.csv' : 'fileName.csv';


$TEST = false;

$cColumns = [];
foreach($columns as $column){
    $cc = explode('-', $column);
    $cColumns[] = [
        'id' => $cc[0],
        'name' => $cc[1],
    ];
}

if(isset($_GET['serve'])){
    // get urls
    $data = file_get_contents($url);
    $data = get_string_between($data, 'style="border:none;" frameborder="0"></iframe>', '</div>');
    
    // filter all urls and store them in files variable
    $files = [];
    $filesSub = explode("<a href=", $data);
    foreach($filesSub as $file){
        if(!empty(get_string_between($file, '"', '"'))){
            $files[] = get_string_between($file, '"', '"');
        }
    }
    
    // clear memory
    unset($data);
    unset($filesSub);

    // the final result
    $finalCsvCOMPANIES = [];
    $finalCsvPUBLISHERS = [];
    $finalCsvUNKNOWN = [];

    // loop the files to get the csv files
    $x = 0;

    foreach($files as $file){
        $hash = $x.'.csv';
        if(!file_exists(__DIR__.'/files/'.$hash)){
            $csvData = file_get_contents($file);
            file_put_contents(__DIR__.'/files/'.$hash, $csvData);
        }

        $file = fopen(__DIR__.'/files/'.$hash, "r");
        $row = 0;

        foreach($fileTypes as $fType){
            $aa = "headers$fType";
            $$aa = [];
        }
        
        $subFinal = [];

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if($row > 0){
                $ddd = [];
                foreach($cColumns as $cc){
                    $ddd[$cc['name']] = $data[$cc['id']];
                }

                if(!checkIfCompany($ddd['domain'])){
                    $output = json_decode(exec("python scrape.py http://".$ddd['domain']));
                    $done = false;
                    foreach($output as $url){
                        if(!$adblockParser->shouldBlock($url)){
                            $done = true;
                        }
                    }

                    if($done){
                        $finalCsvPUBLISHERS[] = $ddd;
                    }else{
                        $finalCsvUNKNOWN[] = $ddd;
                    }
                }else{
                    $finalCsvCOMPANIES[] = $ddd;
                }



                // if($allInOneFile){
                //     $finalCsv[] = $ddd;
                // }else{
                //     $subFinal[] = $ddd;
                //     dd("stop");
                // }

            }else{
                $num = count($data);
                for ($c=0; $c < $num; $c++) {
                    for($xss = 0; $xss < count($cColumns); $xss++){
                        $cc = $cColumns[$xss];
                        if($data[$c] == $cc['name']){
                            $cColumns[$xss]['id'] = $c;
                        }
                    }
                    $headers[] = $data[$c];
                } 
            }
            $row ++;

            if($row > 2 && $TEST){
                break;
            }
        }

        fclose($file);

        if($TEST){
            break;
        }
        $x ++;
    }

    if($allInOneFile){
        // Open a file in write mode ('w') 
        $zipname = $fileName.'.zip';
        // $zip = new ZipArchive;
        // $zip->open($zipname, ZipArchive::CREATE);
        foreach($fileTypes as $type){
            $ff = "finalCsv$type";
            $newFileName = $type.'_'.$fileName;
            $fileData = fopen('download/'.$newFileName, 'w'); 
            $data = toArray(array_unique(toJson($$ff)));
            $header = [];
            foreach($cColumns as $c){
                $header[] = $c['name'];
            }
            fputcsv($fileData, $header); 
            foreach($data as $cs){
                fputcsv($fileData, (array)$cs); 
            }
            fclose($fileData);

            echo "<a href='http://".$_SERVER['HTTP_HOST']."/csv_scrape_convert/backend/download/"."$newFileName'>$newFileName</a><br />";

            // download the file
            if (file_exists('download/'.$newFileName)) {
                // $zip->addFile($newFileName);
                // header('Content-Description: File Transfer');
                // header('Content-Type: application/octet-stream');
                // header('Content-Disposition: attachment; filename='.basename($newFileName));
                // header('Content-Transfer-Encoding: binary');
                // header('Expires: 0');
                // header('Cache-Control: must-revalidate');
                // header('Pragma: public');
                // header('Content-Length: ' . filesize($newFileName));
                // ob_clean();
                // flush();
                // readfile($newFileName);
                // header('Content-Description: File Transfer');
            }
        }
        // $zip->close();
        // header('Content-Type: application/zip');
        // header('Content-disposition: attachment; filename='.$zipname);
        // header('Content-Length: ' . filesize($zipname));
        // readfile($zipname);
        exit;
    }else{
        $subFinal[] = $ddd;
        dd("stop");
    }

}else{
    echo "You are not allowd to view this page"; exit;
}