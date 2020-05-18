<?php 
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1000M');

require __DIR__."/Limonte/AdblockParser.php";
require __DIR__."/Limonte/AdblockRule.php";
require __DIR__."/Limonte/InvalidRuleException.php";
require __DIR__."/Limonte/Str.php";
require_once __DIR__.'/phpDom.php';

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

function dd(...$data){
    foreach($data as $d){
        echo "<pre>";
            var_dump($d);
        echo "</pre>"; 
    }
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

/**
 * get page 
 */
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

/**
 * check if response is HTML or no
 */
function is_html($string){
  return preg_match("/<[^<]+>/",$string,$m) != 0;
}

/**
 * check if url for company or no
 */
function checkIfCompany($url){
    if(isset(explode('/', explode('.', $url)[count(explode('.', $url)) - 1])[1])){
        $url = $url.'ads.txt';
    }else{
        $url = $url.'/ads.txt';
    }
    $data = get_web_page($url);
    if(empty($data['content'])){
        return false;
    }else{
        return is_html($data['content']);
    }
}

/**
 * clean up temp file
 */
function cleanUpTemp(){
    $files = glob('temp/*'); // get all file names
    foreach($files as $file){ // iterate files
    if(is_file($file))
        if(isset(explode('log', $file)[1]) || isset(explode('git', $file)[1])){
            continue;
        }
        unlink($file); // delete file
    }
    if(!file_exists('temp/log.txt')){
        file_put_contents('temp/log.txt', '');
    }
}


/**
 * get links
 */
function getLinks($url){
    $data = get_web_page($url);
    $links = [];
    if(empty($data['content'])){
        return [];
    }else{
        $html = str_get_html($data['content']);
        if($html){
            foreach($html->find('a') as $a){
                if(isset(explode('http', $a->href)[1])){
                   $links[] = $a->href;
                }
            }
            foreach($html->find('iframe') as $a){
                if(isset(explode('http', $a->src)[1])){
                   $links[] = $a->src;
                }
            }
            foreach($html->find('script') as $a){
                if(isset(explode('http', $a->src)[1])){
                   $links[] = $a->src;
                }
            }
        }
        return $links;
    }
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
$startFrom = isset($_GET['startFrom']) ? filter_var($_GET['startFrom'], FILTER_SANITIZE_NUMBER_INT) : -1;
$stopAt = isset($_GET['stopAt']) ? filter_var($_GET['stopAt'], FILTER_SANITIZE_NUMBER_INT) : -1;

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
    cleanUpTemp();

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

        if($startFrom != -1 && $x < $startFrom){
            $x ++;
            continue;
        }

        if(!file_exists(__DIR__.'/files/'.$hash)){
            $csvData = file_get_contents($file);
            file_put_contents(__DIR__.'/files/'.$hash, $csvData);
        }

        $file = fopen(__DIR__.'/files/'.$hash, "r");

        foreach($fileTypes as $fType){
            $aa = "headers$fType";
            $$aa = [];
        }
        
        
        /**
         * the records of each csv file
         */
        $subFinalCOMPANIES = [];
        $subFinalPUBLISHERS = [];
        $subFinalUNKNOWN = [];

        $row = 0;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if($row > 0){
                // start time
                $tt = time();

                $ddd = [];
                foreach($cColumns as $cc){
                    $ddd[$cc['name']] = $data[$cc['id']];
                }

                $domain = isset(explode('http', $ddd['domain'])[1]) ? '' : 'http://';

                $type = ''; 

                if(!checkIfCompany($domain.$ddd['domain'])){
                    // dd(exec("python scrape.py $domain".$ddd['domain']), "python scrape.py $domain".$ddd['domain']);
                    // $output = json_decode(exec("python scrape.py $domain".$ddd['domain']));
                    $output = getLinks($domain.$ddd['domain']);
                    $done = false;
                    foreach($output as $url){
                        if(!$adblockParser->shouldBlock($url)){
                            $done = true;
                        }
                    }
                    if($done){
                        $type = "PUBLISHER";
                        $subFinalPUBLISHERS[] = $ddd;
                    }else{
                        $type = "UNKNOWN";
                        $subFinalUNKNOWN[] = $ddd;
                    }
                }else{
                    $type = "COMPANY";
                    $subFinalCOMPANIES[] = $ddd;
                }

                $damnData = file_get_contents('temp/log.txt');
                $endTime = time() - $tt;
                file_put_contents('temp/log.txt', $damnData."Domain $ddd[domain] Is Done For File $x And It Took $endTime Seconds $type \n");

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

        /**
         * save the sub files 
         */
        file_put_contents(__DIR__."/temp/$x"."_subFinalCOMPANIES.txt", json_encode($subFinalCOMPANIES));
        file_put_contents(__DIR__."/temp/$x"."_subFinalPUBLISHERS.txt", json_encode($subFinalPUBLISHERS));
        file_put_contents(__DIR__."/temp/$x"."_subFinalUNKNOWN.txt", json_encode($subFinalUNKNOWN));

        /**
         * the csv data
         */
        foreach($subFinalCOMPANIES as $comp){
            $finalCsvCOMPANIES[] = $comp;
        }
        foreach($subFinalPUBLISHERS as $ccc){
            $finalCsvPUBLISHERS[] = $ccc;
        }
        foreach($subFinalUNKNOWN as $unn){
            $finalCsvUNKNOWN[] = $unn;
        }
        
        /**
         * echo that files are done
         */
        var_dump("<a href='http://".$_SERVER['HTTP_HOST']."/csv_scrape_convert/backend/temp/"."$x"."_subFinalCOMPANIES.txt'>CSV File Number $x Companies is Done</a>. <br />");
        var_dump("<a href='http://".$_SERVER['HTTP_HOST']."/csv_scrape_convert/backend/temp/"."$x"."_subFinalPUBLISHERS.txt'>CSV File Number $x Publishers is Done</a>. <br />");
        var_dump("<a href='http://".$_SERVER['HTTP_HOST']."/csv_scrape_convert/backend/temp/"."$x"."_subFinalUNKNOWN.txt'>CSV File Number $x Unknown is Done</a>. <br />");

        if($TEST){
            break;
        }
        $x ++;


        if($stopAt != -1 && $x > $stopAt){
            break;
        }

    }

    if($allInOneFile){
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
        }
        exit;
    }else{
        $subFinal[] = $ddd;
        dd("stop");
    }

}else{
    echo "You are not allowd to view this page"; exit;
}