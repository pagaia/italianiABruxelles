<?php

/**
 * This function log the data into a file defined into the settings_t.php
 * @param text $text The information log
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

        //$log .= (is_array($text) ? print_r($text, TRUE) : $text) . "\n";
        $log .= print_r($text, TRUE) . "\n";
        file_put_contents(FOLDERLOG . '/'.LOGFILE.'_' . date("j.n.Y") . '.log', $log, FILE_APPEND);
    }
}

function convertGjsonDateToString($gdate) {
    $matches = array();
    mylog("Date conversion: $gdate");
    preg_match('/(\d{4}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2}),(\d{1,2})/', $gdate, $matches);
    return sprintf('%04d', $matches[1]) . "/" . sprintf('%02d', $matches[2]) . "/" . sprintf('%02d', $matches[3]) . " " . sprintf('%02d', $matches[4]) . ":" . sprintf('%02d', $matches[5]);
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
        array_push($columns, trim($columnidx['label']));
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

/* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */
/* ::                                                                         : */
/* ::  This routine calculates the distance between two points (given the     : */
/* ::  latitude/longitude of those points). It is being used to calculate     : */
/* ::  the distance between two locations using GeoDataSource(TM) Products    : */
/* ::                                                                         : */
/* ::  Definitions:                                                           : */
/* ::    South latitudes are negative, east longitudes are positive           : */
/* ::                                                                         : */
/* ::  Passed to function:                                                    : */
/* ::    lat1, lon1 = Latitude and Longitude of point 1 (in decimal degrees)  : */
/* ::    lat2, lon2 = Latitude and Longitude of point 2 (in decimal degrees)  : */
/* ::    unit = the unit you desire for results                               : */
/* ::           where: 'M' is statute miles (default)                         : */
/* ::                  'K' is kilometers                                      : */
/* ::                  'N' is nautical miles                                  : */
/* ::  Worldwide cities and other features databases with latitude longitude  : */
/* ::  are available at http://www.geodatasource.com                          : */
/* ::                                                                         : */
/* ::  For enquiries, please contact sales@geodatasource.com                  : */
/* ::                                                                         : */
/* ::  Official Web site: http://www.geodatasource.com                        : */
/* ::                                                                         : */
/* ::         GeoDataSource.com (C) All Rights Reserved 2015		   		     : */
/* ::                                                                         : */
/* :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::: */

/**
 * 
 * @param type $lat1 the latitude of first point
 * @param type $lon1 the longitude of first point
 * @param type $lat2 the latitude of second point
 * @param type $lon2 the longitude of second point
 * @param type $unit the unit of distance: M for miles, K for kilometer and N for nautical miles
 * @return type
 */
function distance($lat1 = -1, $lon1, $lat2, $lon2, $unit = 'K') {

    $result = -1;
    mylog('Calculating distance ($lat1, $lon1) and ($lat2, $lon2): ' . "($lat1, $lon1), ($lat2, $lon2)");
    if (!isset($lat1) || !isset($lon1) || !isset($lat2) || !isset($lon2) || $lat1 == -1 || $lon1 == -1 || $lat2 == -1 || $lon2 == -1) {
        return $result;
    }

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        return ($miles * 1.609344);
    } else if ($unit == "N") {
        return ($miles * 0.8684);
    } else {
        return $miles;
    }
}

?>
