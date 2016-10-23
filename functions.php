<?php

/**
 * This function log the data into a file defined into the settings_t.php
 * @param text $text The informationt log
 * @param integer $level Define the level of debug
 * 1=ERROR
 * 2=WARN
 * 3=INFO
 * 4=DEBUG
 * DEFAULT=INFO
 */
function mylog($text, $level = LOGINFO) {
    $today = date("Y-m-d H:i:s");
    $log = "[$today] ";

    if ($level <= LOGLEVEL) {
        switch ($level) {
            case LOGERROR:
                $log .= "[ERROR] ";
                break;
            case LOGWARN:
                $log .= "[WARN] ";
                break;
            case LOGINFO:
                $log .= "[INFO] ";
                break;
            case LOGDEBUG:
                $log .= "[DEBUG] ";
                break;
            default:
                $log .= "[INFO] ";
                break;
        }

        $log .= (is_array($text) ? print_r($text, TRUE) : $text) . "\n";
        file_put_contents(FOLDERLOG . '/italianiAbruxelles_' . date("j.n.Y") . '.log', $log, FILE_APPEND);
    }
}

function convertGjsonDateToString($gdate) {
    $matches = array();
    preg_match('/(\d{4}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2})/', $gdate, $matches);
    return $matches[1] . "/" . $matches[2] . "/" . $matches[3] . " " . $matches[4] . ":" . $matches[5] . ":" . $matches[6];
}

function parseGjson($data) {

    mylog($data, LOGDEBUG);
    $data = substr($data, strpos($data, "{"));
    $data = substr($data, 0, -2);
    $contentArr = json_decode($data, true);

    $columns = array();
    $result = array();
    $row_length = 0;
    $value = "";

    $column_length = count($contentArr['table']['cols']);
    if ($column_length == 0 || count($contentArr['table']['rows']) == 0) {
        mylog("Column lenght 0 or rows 0 ", LOGDEBUG);
        throw new Exception('Impossible to parse the json');
    }

    mylog("Foreach columns", LOGDEBUG);
    foreach ($contentArr['table']['cols'] as $columnidx) {
        array_push($columns, $columnidx['label']);
        mylog(print_r($columnidx, TRUE), LOGDEBUG);
    }

    mylog(print_r($columns, TRUE), LOGDEBUG);

    $countR = 0;
    mylog("Foreach rows", LOGDEBUG);
    foreach ($contentArr['table']['rows'] as $rowsidx) {
        $row_length = count($rowsidx['c']);
        if ($column_length != $row_length) {
            // Houston, we have a problem!
            //return false;
            throw new Exception('column_length != row_length');
        }

        $count = 0;
        mylog("Foreach row", LOGDEBUG);

        foreach ($rowsidx['c'] as $rowidx) {
            mylog(print_r($rowidx, TRUE), LOGDEBUG);
            mylog("CountR: " . $countR, LOGDEBUG);

            if (!isset($result[$countR])) {
                $result[$countR] = array();
            }
            mylog(print_r($result, TRUE), LOGDEBUG);

            $value = ($rowidx['v']) ? $rowidx['v'] : null;
            $result[$countR][$columns[$count]] = $value;
            mylog("value: $value", LOGDEBUG);
            $count++;
        }

        $countR++;
    }

    mylog(print_r($result, TRUE), LOGDEBUG);
    return $result;
}
?>
