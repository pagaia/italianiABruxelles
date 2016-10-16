<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function mylog($text) {
    $today = date("Y-m-d H:i:s");
    $log = "[$today] $text \n";
    file_put_contents(FOLDERLOG . '/log_' . date("j.n.Y") . '.txt', $log, FILE_APPEND);
}

?>