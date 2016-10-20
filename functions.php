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
//Date(2016,9,14,23,6,56)
    $matches = array();
    preg_match('/(\d{4}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2})/', $gdate, $matches);

    return $matches[1] . "/" . $matches[2] . "/" . $matches[3] . " " . $matches[4] . ":" . $matches[5] . ":" . $matches[6];
}

function parseGjson(
$data) {
//$data = '/*O_o*/ google.visualization.Query.setResponse({"version":"0.6","reqId":"0","status":"ok","sig":"2116319921","table":{"cols":[{"id":"A","label":"ID","type":"number","pattern":"General"},{"id":"B","label":"update","type":"datetime","pattern":"dd/MM/yyyy H.mm.ss"},{"id":"C","label":"Key","type":"string"},{"id":"D","label":"Name","type":"string"},{"id":"E","label":"Email","type":"string"},{"id":"F","label":"Phone","type":"string"},{"id":"G","label":"Mobile1","type":"string"},{"id":"H","label":"Mobile2","type":"string"},{"id":"I","label":"Address","type":"string"},{"id":"J","label":"Description","type":"string"},{"id":"K","label":"web","type":"string"},{"id":"L","label":"profession","type":"string"},{"id":"M","label":"lat","type":"string"},{"id":"N","label":"lng","type":"string"}],"rows":[{"c":[{"v":1.0,"f":"1"},{"v":"Date(2016, 9, 14, 23, 6, 56)","f":"14/10/2016 23.06.57"},{"v":"elettricista"},{"v":"SCIALLA MASSIMO"},{"v":"massimoscialla.be@gmail.com"},null,{"v":"+32 0491 29 21 60"},{"v":"0499 328 629"},null,{"v":"Impianti elettrici generali, civili e industriali (nuove installazioni e manutenzione) - Riparazione guasti - Messa in conformità impianto elettrico - Impianti tv via satellite - Impianti citofonici e video - Reti informatiche per trasmissione dati - Impianti di videosorveglianza - Impianti di allarme - Automazioni \u2013 Domotica."},null,{"v":"elettricista"},null,{"v":null}]},{"c":[{"v":2.0,"f":"2"},{"v":"Date(2016, 9, 14, 23, 6, 56)","f":"14/10/2016 23.06.57"},{"v":"Elettricista"},{"v":"MONTEDORO MIRKO"},null,null,{"v":"+32 (0)470390440"},null,null,{"v":"Installazioni e riparazioni elettriche di impianti civili;Disponibilità immediata."},null,{"v":"elettricista"},null,{"v":null}]},{"c":[{"v":3.0,"f":"3"},{"v":"Date(2016, 9, 14, 23, 6, 56)","f":"14/10/2016 23.06.57"},{"v":"Elettricista"},{"v":"NICOLA LEONZIO"},{"v":"nicolaleonzio83@gmail.com"},null,{"v":"+32 0488 80 24 58"},null,null,{"v":"Installazioni, ristrutturazioni, riparazioni, Impianti elettrici - Quadri elettrici, tv-sat, Audio-video, Illuminazione, Domotica, Sicurezza (tvcc e allarmi), Riscaldamento elettrico e Caldaie domestiche, Elettrodomestici."},null,{"v":"elettricista"},null,{"v":null}]}]}});';

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
      //  return false;
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
