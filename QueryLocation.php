<?php

/**
 *  autor @piersoft https://raw.githubusercontent.com/piersoft/defibrillatoribot/master/QueryLocation.php
 */
//gestisce la query Overpass di Openstreetmap 
//include('settings_t.php');

function give_osm_data($lat, $lon) {

    $around = AROUND;     //Number of meters to calculate radius to search
    $max = MAX;      //max number of points to search
    $tag = TAG;       //tag to search accoring to Overpass_API Query Language
    //inserire qui la query Overpass modificando i paramentri
    $query = 'node(around:' . $around . ',' . $lat . ',' . $lon . ')[' . $tag . '];out ' . $max . ';';
    mylog("query: " . $query);

    $context = stream_context_create(array('http' => array(
            'method' => 'POST',
            'header' => array('Content-Type: application/x-www-form-urlencoded'), //comment out headers for attachment to app engine
            'content' => 'data=' . urlencode($query),
    )));
    $endpoint = 'http://overpass-api.de/api/interpreter';
    mylog("context: " . $context);
    $json = file_get_contents($endpoint, false, $context);
    //echo $json;
    //var_dump(json_decode($json));

    if (false === $json) {
        $error = error_get_last();
        mylog($error['message']);
        // throw new ClientException($error['message']);
    }
    mylog("Result: " . $json);
    //ritorno il dato
    return $json;
}

?>