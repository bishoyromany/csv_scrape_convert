<?php 
    ini_set('max_execution_time', 3600);
    ini_set('memory_limit', '500M');

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


/**
 * get variables
 */
$url = isset($_GET['url']) && !empty($_GET['url']) ? filter_var($_GET['url'], FILTER_SANITIZE_STRING) : false;
if(!$url){ echo "Make sure to Write The URL"; exit; }

$columns = isset($_GET['columns']) && !empty($_GET['columns']) ? explode(',',filter_var($_GET['columns'], FILTER_SANITIZE_STRING)) : false;
if(!$columns){ echo "Make sure to Write The Columsn"; exit; }
$filterDuplicates = isset($_GET['filterDuplicates']) ? filter_var($_GET['filterDuplicates'], FILTER_SANITIZE_STRING) : false;
$allInOneFile = isset($_GET['allInOneFile']) ? filter_var($_GET['allInOneFile'], FILTER_SANITIZE_STRING) : false;
$fileName = isset($_GET['fileName']) && !empty($_GET['fileName']) ? filter_var($_GET['fileName'], FILTER_SANITIZE_STRING).'.csv' : 'fileName.csv';

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
    $finalCsv = [];

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
        $headers = [];
        
        $subFinal = [];

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if($row > 0){
                $ddd = [];
                foreach($cColumns as $cc){
                    $ddd[$cc['name']] = $data[$cc['id']];
                }

                if($allInOneFile){
                    $finalCsv[] = $ddd;
                }else{
                    $subFinal[] = $ddd;
                    dd("stop");
                }
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
        }

        fclose($file);
        
        $x ++;
    }

    if($allInOneFile){
        // Open a file in write mode ('w') 
        $csvFile = fopen($fileName, 'w'); 
        $finalCsv = toArray(array_unique(toJson($finalCsv)));
        $header = [];
        foreach($cColumns as $c){
            $header[] = $c['name'];
        }
        fputcsv($csvFile, $header); 
        foreach($finalCsv as $cs){
            fputcsv($csvFile, (array)$cs); 
        }
        fclose($csvFile);

        // download the file
        if (file_exists($fileName)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($fileName));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileName));
            ob_clean();
            flush();
            readfile($fileName);
            exit;
        }

    }else{
        $subFinal[] = $ddd;
        dd("stop");
    }

}else{
    echo "You are not allowd to view this page"; exit;
}