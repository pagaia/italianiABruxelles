<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function mylog($text, $level = 3) {
    $today = date("Y-m-d H:i:s");
    $log = "[$today] ";

    switch ($level) {
        case 1:
            $log .= "[ERROR] ";
            break;
        case 2:
            $log .= "[WARN] ";
            break;
        case 3:
            $log .= "[INFO] ";
            break;
        case 4:
            $log .= "[DEBUG] ";
            break;
        default:
            $log .= "[INFO] ";
            break;
    }

    $log .= "$text \n";
    file_put_contents(FOLDERLOG . '/italianiAbruxelles_' . date("j.n.Y") . '.log', $log, FILE_APPEND);
}

function convertGjsonDateToString($gdate) {
    $matches = array();
    preg_match('/(\d{4}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2})/', $gdate, $matches);
    return $matches[1] . "/" . $matches[2] . "/" . $matches[3] . " " . $matches[4] . ":" . $matches[5] . ":" . $matches[6];
}

function parseGjson(
$data) {

    $data = substr($data, strpos($data, "{"));
    $data = substr($data, 0, -2);


    $contentArr = json_decode($data, true);

    $columns = array();
    $result = array();
    $row_length = 0;
    $value = "";

    $column_length = count($contentArr['table']['cols']);
    if ($column_length == 0 || count($contentArr['table']['rows']) == 0) {
        mylog(print_r("Column lenght 0 o rows 0 ", TRUE));
        throw new Exception('Impossible to parse the json');
    }

    mylog("Foreach columns");
    foreach ($contentArr['table']['cols'] as $columnidx) {
        array_push($columns, $columnidx['label']);
        mylog(print_r($columnidx, TRUE));
    }

    mylog(print_r($columns, TRUE));

    $countR = 0;
    mylog("Foreach rows");
    foreach ($contentArr['table']['rows'] as $rowsidx) {
        $row_length = count($rowsidx['c']);
        if ($column_length != $row_length) {
            // Houston, we have a problem!
            //return false;
            throw new Exception('column_length != row_length');
        }

        $count = 0;
        mylog("Foreach row");

        foreach ($rowsidx['c'] as $rowidx) {
            mylog(print_r($rowidx, TRUE));
            mylog("CountR: " . $countR);

            if (!isset($result[$countR])) {
                $result[$countR] = array();
            }
            mylog(print_r($result, TRUE));

            $value = ($rowidx['v']) ? $rowidx['v'] : null;
            $result[$countR][$columns[$count]] = $value;
            mylog("value: $value");
            $count++;
        }

        $countR++;
    }

    mylog(print_r($result, TRUE));
    return $result;
}

?>
