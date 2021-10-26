<?php

$object = ['make', 'model', 'colour', 'capacity', 'network', 'grade', 'condition'];
$nativeobject = array();
$entities = array();

$val = getopt(null, ["file:"]);
if(!file_exists($val['file'])) {
    throw New Exception("File is missing");
}
$fp = fopen($val['file'], "r");
function parceString($file, $line){
    switch (pathinfo($file, PATHINFO_EXTENSION)){
        case 'tsv': return str_getcsv($line, "\t", '"');
        case 'json': return str_getcsv($line, "\n", '"');
        case 'csv': default: return str_getcsv($line, ",", '"');
    }
}

echo "Do you want to apply new headings?  Type 'yes' or 'no': ";
$handle = fopen ("php://stdin","r");
$console = fgets($handle);
$sorting = true;
if(trim($console) != 'yes'){
    $sorting == false;
}
$exc = false;
$all = array();
$result = array();
$lastitem;

function sortArray($a) {
    $tmp = $a[2];
    $a[2] = $a[5];
    $a[5] = $tmp;

    $tmp = $a[3];
    $a[3] = $a[4];
    $a[4] = $tmp;

    $tmp = $a[4];
    $a[4] = $a[6];
    $a[6] = $tmp;

    $tmp = $a[5];
    $a[5] = $a[6];
    $a[6] = $tmp;

    return $a;
}

function init($arr) {
    global $nativeobject; 
    global $entities; 
    global $lastitem; 
    $nativeobject = $arr;
    for ($i = 0; $i < count($nativeobject); $i++) {
        array_push($entities, array());
    }
    $lastitem = count($nativeobject);
}

function checkExistence($input, $array) {
    for ($i = count($array) - 1; $i >= 0; $i--) {
        for ($u = 0; $u < count($array[$i]) - 1; $u++) {
            if($array[$i][$u] !== $input[$u]) {
                break;
            }
            if ($u == count($array[$i]) - 2) return $i;
        }
    }
    return -1;
}
function cleanArray($input) {
    for ($i = 0; $i < count($input); $i++) {
        $input[$i] = strval($input[$i]);
        if(!isset($input[$i])) $input[$i] = "No data";
    }
    return $input;
}

if ($fp) {
    echo "\nReading file...";
    $line = 0;
    while (!feof($fp)) {
        $line++;
        $onestring = trim(fgets($fp, 999));
        if ($onestring[0] == NULL) {$line++; continue;};
        $arr = array_map('trim', parceString($val['file'], $onestring));
        $arr = cleanArray($arr);
        if($line == 1) {
            init($arr);
            $line++;
            continue;
        }
        if(count($arr) != $lastitem) {
            print_r($arr);
            echo "\nException at line ", $line, " of an input file";
            $exc = true;
        }
        
        if($sorting == true) {
            $arrsorted = sortArray($arr);
            array_push($result, $arrsorted);
        } else {
            array_push($result, $arr);
        }
        
    }

    echo "\nCreating entities db...";
    foreach($result as $key=>$value){
        for ($i = 0; $i < count($entities); $i++) {
            array_push($entities[$i], $value[$i]);
        } 
    }
    for ($i = 0; $i < count($entities); $i++) {
        $entities[$i] = array_values(array_filter(array_unique($entities[$i], SORT_REGULAR)));
    }
    if($sorting == true) {
        $entities = array_combine($object, $entities);
   } else {
        $entities = array_combine($nativeobject, $entities);
   }
    

    echo "\nCreating objects db...";
    array_push($object, 'count');
    array_push($nativeobject, '"count"');
    for ($i = 0; $i < count($result); $i++) {
        $key = checkExistence($result[$i], $all);
        if ($key == -1) {
            array_push($result[$i], 1);
            array_push($all, $result[$i]);
        } else {
            $all[$key][$lastitem]++;
        }
    }

    echo "\nWriting files...";
    $final = array();
    for ($i = 0; $i < count($all); $i++) {
        if($sorting == true) {
             array_push($final, array_combine($object,$all[$i]));
        } else {
            array_push($final, array_combine($nativeobject, $all[$i]));
        }
    }

    echo "\nDo you want to export as csv or as array?  Type 'csv' or 'arr': ";
    $handle = fopen ("php://stdin","r");
    $console = fgets($handle);
    $final = array_filter($final);
    if(trim($console) != 'csv'){
        file_put_contents('output_objects.txt', print_r($final, true));
        file_put_contents('output_entities.txt', print_r($entities, true));
    } else {
        $fp = fopen('output_objects.csv', 'w');
        foreach ($final as $fields) {
            fputcsv($fp, $fields);
        }
        $fp = fopen('output_entities.csv', 'w');
        foreach ($entities as $fields) {
            fputcsv($fp, $fields);
        }
    }

    if($exc == true) {
        echo "\nHandled with errors: output file missing some broken input objects!";
    } else {
        echo "\nDone!";
    }
    
}
else echo "Error opening file";
fclose($fp);
?>

