<?php

/**
 * Telegram Bot LibreItalia
 * @author Francesco Piero Paolicelli @piersoft
 */
include("Telegram.php");
include("QueryLocation.php");
include_once './mongo.php';

/**
 * List of commands:
 * /start or Informazioni - to show the information about the BOT
 * /howto - to show how to use the BOT
 * /l - to list all the keywords
 * /s word - to perform a search on all DB
 * /c #ID - to get the phone of the element identified by the #ID
 * /p #ID - to get the position of the element identified by the #ID
 * 
 */
class mainloop {

    const MAX_LENGTH = 4096;

    var $text;
    var $chat_id;
    var $user_id;
    var $username;
    var $firstName;
    var $lastName;
    var $location;
    var $reply_to_msg;
    // mongoDB collection
    var $collection;
    // mongoDB user
    var $user;

    function getCollectionUsers() {
        return (new MongoDB\Client)->IaBxL->users;
    }

    function start($telegram, $update) {


        $this->collection = $this->getCollectionUsers();

        mylog("Update: " . print_r($update, TRUE), LOGDEBUG);

        $this->text = (isset($update["message"]["text"]) ? $update["message"]["text"] : NULL);
        $this->chat_id = $update["message"]["chat"]["id"];
        $this->user_id = $update["message"]["from"]["id"];
        $this->username = $update["message"]["from"]["username"];
        $this->firstName = $update["message"]["from"]["first_name"];
        $this->lastName = $update["message"]["from"]["last_name"];
        $this->location = (isset($update["message"]["location"]) ? $update["message"]["location"] : NULL);
        $this->reply_to_msg = (isset($update["message"]["reply_to_message"]) ? $update["message"]["reply_to_message"] : NULL);

        $request = "The user $this->username ($this->firstName $this->lastName) with id: $this->user_id has sent this msg : '$this->text' ";
        mylog($request);
        mylog("Location: " . print_r($this->location, TRUE));
        mylog("Reply to message: " . print_r($this->reply_to_msg, TRUE));


        $this->user = new User($this->user_id, $update["message"]["from"]);
        $this->collection = (new MongoDB\Client)->IaBxL->users;
        $access = (new MongoDB\Client)->IaBxL->access;
        
        $user = $this->collection->findOne(['tgUserId' => $this->user_id]);
        $access->insertOne(['user_id' => $this->user_id, 'date' => date('m/d/Y h:i:s a', time())]);
        if (!$user) {
            mylog("Inserting a new user");
            $this->user = $this->collection->insertOne($this->user);

        } else {
            mylog("Getting the user user");
            
            $this->user = $this->collection->findOne(['tgUserId' => $this->user_id]);
        //    mylog("USER: " . print_r($this->user, TRUE));
        }

        $this->shell($telegram);

//  $db = NULL;
    }

//gestisce l'interfaccia utente
    function shell($telegram) {
        date_default_timezone_set('Europe/Rome');

// check if a position has been given
        if (isset($this->location)) {
            mylog($this->location);

            $this->location_manager($telegram);
            return;


//prelevo dati da OSM sulla base della mia posizione
            $osm_data = give_osm_data($this->location['latitude'], $this->location['longitude']);
            $this->create_keyboard_temp($telegram);
//            $osm_data = give_osm_data($lat, $lon);
        } elseif ($this->reply_to_msg != null) {

            $this->collection = $this->collection->findOne(['tgUserId' => $this->user_id]);
           // mylog("Found User: " . print_r($this->collection, TRUE));
            $this->location = $this->collection->user->location;

          //  $this->reply($telegram, "This is your location: " . print_r($this->collection->location, TRUE));
            //mylog($this->location);
//            $reply = "Segnalazione Registrata. Grazie!";
//            $content = array('chat_id' => $this->chat_id, 'text' => $reply);
//            $telegram->sendMessage($content);
            $this->sendListResult($telegram);
            $this->create_keyboard_temp($telegram);
            // $this->create_keyboard_temp($telegram);
//aggiorno dati mappa
// exec('sqlite3 -header -csv db.sqlite "select * from segnalazioni;" > map_data.csv');
        } elseif ($this->text == "online") {
            $lat = 41.86265535999481;
            $lon = 12.485689999302197;
//prelevo dati da OSM sulla base della mia posizione
            $osm_data = give_osm_data($lat, $lon);

            mylog("Hai selezionato online");
            try {

                $inline_keyboard = [
                        ['text' => 'numero', 'callback_data' => 'getNumber']
                ];

                $this->create_inline_keyboard($telegram, "recupera", $inline_keyboard);
            } catch (Exception $e) {
                mylog("Error: " . $e->getMessage(), LOGERROR);
            }
//        } elseif ($this->text == "Posizione/Position") {
//            $this->update_mess($telegram);
        }
//first message
        elseif ($this->text == "/start" || $this->text == "Informazioni") {
            $this->sendInformazioni($telegram);
        }
// send the help message
        elseif ($this->text == "/help" || $this->text == "Help") {
            $this->sendHelp($telegram);
        } elseif ($this->location != null) {
//	$this->location_manager($telegram,$user_id,$chat_id,$location);
//	return;
        }
// get all the information about the element by ID
        elseif (preg_match('/^\/i /', $this->text)) {
            $this->sendInfoByID($telegram);
        }
// if /p +ID command is received, the position of the row with ID is returned if there is a position
        elseif (preg_match('/^\/p /', $this->text) || preg_match('/^\/pos_/', $this->text)) {
            $this->sendPosition($telegram);
        }
//elseif (strpos($this->text, '/') === false) {
// the following word after ? is the key for the search
        elseif (preg_match('/^\?/', $this->text) || "Pizzerie" == $this->text) {
            $this->sendListResult($telegram);
        }
// if "PAROLE CHIAVE" is provided, a list with all keys and number of are sent
        else if ($this->text == '/l' || $this->text == 'KEYWORDS') {
            $this->sendListKey($telegram);
        }
// if a number is provided, the contact information (Name and mobile number) of that row is sent
// elseif (is_numeric($this->text) || preg_match('/^\/c /', $this->text)) {
        elseif (preg_match('/^\/id_(\d)+/', $this->text)) {
            $this->sendContactInfo($telegram);
        }
// Otherwise ask to resend the command/search
        else {
            $this->notUnderstand($telegram);
        }

        /*
          else{
          $location="Sto cercando le sedi nel Comune di: ".$text;
          $content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
          $telegram->sendMessage($content);
          $text=str_replace(" ","%20",$text);
          $text=strtoupper($text);
          $urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(A)%20LIKE%20%27%25";
          $urlgd .=$text;
          $urlgd .="%25%27&key=".GDRIVEKEY."&gid=".GDRIVEGID3;
          $inizio=1;
          $homepage ="";
          //$comune="Lecce";

          //echo $urlgd;
          $csv = array_map('str_getcsv',file($urlgd));
          //var_dump($csv[1][0]);
          $count = 0;
          foreach($csv as $data=>$csv1){
          $count = $count+1;
          }
          if ($count ==0 || $count ==1){
          $location="Nessun risultato trovato";
          $content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
          $telegram->sendMessage($content);
          }
          if ($count >40){
          $location="Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
          $content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
          $telegram->sendMessage($content);
          return;
          }
          function decode_entities($text) {

          $text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
          $text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
          $text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
          $text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
          return $text;
          }
          for ($i=$inizio;$i<$count;$i++){


          $homepage .="\n";
          $homepage .="Comune: ".$csv[$i][0]."\n";
          $homepage .="Indirizzo: ".$csv[$i][1]."\n";
          $homepage .="CAP: ".$csv[$i][2]."\n";
          if($csv[$i][3] !=NULL) $homepage .="Segretario/Referente: ".$csv[$i][3]."\n";
          if($csv[$i][4] !=NULL) $homepage .="Tel: ".$csv[$i][4]."\n";
          if($csv[$i][5] !=NULL) $homepage .="Email: ".$csv[$i][5]."\n";
          if($csv[$i][6] !=NULL){
          $homepage .="Guardala sulla mappa	:\n";
          $homepage .= "http://www.openstreetmap.org/?mlat=".$csv[$i][6]."&mlon=".$csv[$i][7]."#map=19/".$csv[$i][6]."/".$csv[$i][7]."/".$_POST['qrname'];
          }
          $homepage .="\n____________\n";
          }




          }



          //}

          //	echo $alert;

          $chunks = str_split($homepage, self::MAX_LENGTH);
          foreach($chunks as $chunk) {
          $content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
          $telegram->sendMessage($content);

          }
         */
//$this->create_keyboard_temp($telegram, $chat_id);
        return;
//}
    }

    /**
     * This function get as input the telegram library and the option array
     * @param type $telegram
     * @param type $option this is an array with the following elements:
     * msg: the text to show in the message
     * text: the text of the button
     * url: the url to use for the button
     */
    function create_inline_keyboard($telegram, $msg, array $option) {
        mylog(print_r($option, TRUE),LOGDEBUG);

        $keyb = json_encode(['inline_keyboard' => [$option]]);
        mylog($keyb,LOGDEBUG);
        $content = array(
            'chat_id' => $this->chat_id,
            'reply_markup' => $keyb,
            'text' => $msg
        );
//return $content;
        $telegram->sendMessage($content);
    }

    function create_keyboard_temp($telegram) {
        $option = array(["KEYWORDS", "Pizzerie"], ["Help"]);
        $keyb = $telegram->buildKeyBoard($option, $onetime = false);
        $content = array(
            'chat_id' => $this->chat_id,
            'reply_markup' => $keyb,
            'text' => "[Digita o fai una Scelta]"
        );
        $telegram->sendMessage($content);
    }

    function notUnderstand($telegram) {
        $location = "Non ho capito la ricerca";
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
        $this->create_keyboard_temp($telegram);
    }

    function reply($telegram, $msg) {
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
    }

    function sendContactInfo($telegram) {
        if (strpos($this->text, '/id_') !== false) {
            $this->text = substr($this->text, 4);
        }

        $this->reply($telegram, "Sto raccogliendo l'informazione N°: " . $this->text);

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20A%20%3D%20";
        $urlgd .= rawurlencode("SELECT * WHERE " . ID . " = ");
        $urlgd .= $this->text;
        $urlgd .= rawurlencode(" ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        $inizio = 1;
        $res = "";

        $json = file_get_contents($urlgd);


        try {
            $myContent = parseGjson($json);
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Impossibile trovare il numero di telefono');
            return;
        }

        $count = count($myContent);


        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        if (!isset($myContent[0]['Mobile1']) && !isset($myContent[0]['Mobile2']) && !isset($myContent[0]['Phone'])) {
            $this->reply($telegram, "Non esiste un numero di telefono per l'elemento ricercato");
            return;
        }

        $phoneN = ( isset($myContent[0]['Mobile1']) ? $myContent[0]['Mobile1'] :
                (isset($myContent[0]['Phone']) ? $myContent[0]['Phone'] :
                (isset($myContent[0]['Mobile2']) ? $myContent[0]['Mobile2'] : "")));

        $chunks = str_split($res, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $contact = array(
                'chat_id' => $this->chat_id,
                'phone_number' => $phoneN,
                'first_name' => $myContent[0]['Name'],
                'last_name' => $myContent[0]['profession']
            );
            mylog("phone_number: " . $phoneN . ", first_name: " . $myContent[0]['first_name'] . ", last_name: " . $myContent[0]['last_name'], LOGDEBUG);
            $telegram->sendContact($contact);
        }
    }

    function sendInformazioni($telegram) {
        $img = curl_file_create(LOGO, 'image/png');
        $contentp = array(
            'chat_id' => $this->chat_id,
            'photo' => $img
        );
        $telegram->sendPhoto($contentp);

        $msg = WELCOME;

        $this->reply($telegram, $msg);
        mylog("new chat started with " . $this->chat_id);
        $this->create_keyboard_temp($telegram);

        return;
    }

    function sendHelp($telegram) {
        $helpMessage = "Commands List:\n"
                . "/start - to show the information about the BOT\n"
                . "/help - to show this menu\n"
                . "/l - to list all the keywords\n"
                . "? - to perform a search on all Database(e.g. ?doctor )\n"
                . "/c #ID - to get the phone of the element identified by the #ID (e.g. /c 123)\n"
                . "/p #ID - to get the position of the element identified by the #ID (e.g. /p 134)\n";

        $this->reply($telegram, $helpMessage);
        $this->create_keyboard_temp($telegram);
        return;
    }

    function countElements($telegram, $query) {

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20A%20IS%20NOT%20NULL";
        $urlgd .= rawurlencode($query);
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;


        $inizio = 1;
        $res = "";
//$comune="Lecce";
//echo $urlgd;
        $json = file_get_contents($urlgd);
// mylog(print_r($json, TRUE));

        try {
            $myContent = parseGjson($json);
//  mylog(print_r($myContent, TRUE));
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        $count = count($myContent);

        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');
        }

        $tot;
        foreach ($myContent as $value) {
            $tot = $value["count"];
        }

        return $tot;
    }

    function sendListKey($telegram) {

        $this->reply($telegram, "Sto interrogando il database:\n");

//        $query = "SELECT  count(*) WHERE " . ID . " IS NOT NULL group by " . Key . "  ORDER BY count(" . ID . ") DESC ";
//        
//        $this->countElements($telegram, $query);
        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20A%20IS%20NOT%20NULL";
        $urlgd .= rawurlencode("SELECT " . Key . ", count(" . ID . ") WHERE " . ID . " IS NOT NULL group by " . Key . "  ORDER BY count(" . ID . ") DESC ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;


        $inizio = 1;
        $res = "";
//$comune="Lecce";
//echo $urlgd;
        $json = file_get_contents($urlgd);
// mylog(print_r($json, TRUE));

        try {
            $myContent = parseGjson($json);
//  mylog(print_r($myContent, TRUE));
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        $csv = array_map('str_getcsv', file($urlgd));
        $count = count($myContent);

        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');
        }

        foreach ($myContent as $value) {
            $res .= "N°: " . $value["count ID"] . " - " . $value["Key"] . "\n";
        }

        $chunks = str_split($res, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $this->reply($telegram, $chunk);
        }
    }

    function sendListResult($telegram) {
        $text = (preg_match('/^\?/', $this->text) ) ? substr($this->text, 1) : $this->text;

        $this->reply($telegram, "Sto cercando argomenti con parola chiave: " . $text);

        $text = str_replace(" ", "%20", $text);
        $text = strtoupper($text);
        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20upper(C)%20contains%20%27";
        $urlgd .= rawurlencode("SELECT * WHERE ");
        $urlgd .= rawurlencode(" upper(" . Key . ") contains '") . $text . rawurlencode("' ");
        $urlgd .= rawurlencode(" OR upper(" . Name . ") contains '") . $text . rawurlencode("' ");
        $urlgd .= rawurlencode(" OR upper(" . Email . ") contains '") . $text . rawurlencode("' ");
        $urlgd .= rawurlencode(" OR upper(" . Description . ") contains '") . $text . rawurlencode("' ");
        $urlgd .= rawurlencode(" OR upper(" . profession . ") contains '") . $text . rawurlencode("' ");

        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        $homepage = "";

        $json = file_get_contents($urlgd);

        try {
            $myContent = parseGjson($json);
//  mylog(print_r($myContent, TRUE));
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        $count = count($myContent);

        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');

            return;
        }
        if ($count > 30) {
            $this->reply($telegram, "Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta");

            return;
        }

        $msg = (1 == $count ? "Trovato 1 elemento" : "Trovati " . $count . " elementi");
        $this->reply($telegram, $msg);

        $elements = array();
        $count = 0;
        foreach ($myContent as $v) {
            $location = $this->resolveAddress($telegram, $v['Address']);
            $elements[$count]['distance'] = distance($this->collection->location['latitude'], $this->collection->location['longitude'], $location['latitude'], $location['longitude']);

            $result = "\n";
            $result .= "N°: /id_" . $v["ID"] . "\n";
            $result .= "<b>Last update:</b> " . convertGjsonDateToString($v['update']) . "\n";
            $result .= (isset($v['profession']) && $v['profession'] != "") ? "<b>Profession:</b> " . $v['profession'] . "\n" : "";
            $result .= (isset($v['Name']) && $v['Name'] != "") ? "<b>Name:</b> " . $v['Name'] . "\n" : "";
            $result .= (isset($v['Email']) && $v['Email'] != "") ? "<b>Email:</b>  " . $v['Email'] . "\n" : "";
            $result .= (isset($v['Phone']) && $v['Phone'] != "") ? "<b>Phone:</b> " . $v['Phone'] . "\n" : "";
            $result .= (isset($v['Mobile1']) && $v['Mobile1'] != "") ? "<b>Mobile1:</b> " . $v['Mobile1'] . "\n" : "";
            $result .= (isset($v['Mobile2']) && $v['Mobile2'] != "") ? "<b>Mobile2:</b> " . $v['Mobile2'] . "\n" : "";
            $result .= (isset($v['Address']) && $v['Address'] != "") ? "<b>Address:</b> " . $v['Address'] . "\n" : "";
            $result .= (isset($v['Description']) && $v['Description'] != "") ? "<b>Description:</b> " . $v['Description'] . "\n" : "";
            $result .= (isset($v['web']) && $v['web'] != "") ? "<b>URL:</b> " . $v['web'] . "\n" : "";
            $result .= (isset($v['web']) && $v['web'] != "") ? "<b>URL:</b> " . $v['web'] . "\n" : "";
            $result .= ($elements[$count]['distance'] != -1) ? "<b>Distance:</b> " . number_format($elements[$count]['distance'], 2, '.', '') . " km \n" : "";
            $result .= (isset($v['Address']) || (isset($v['lat']) && isset($v['lng']))) ? "<b>GetPosition:</b> /pos_" . $v['ID'] . "\n" : "";
            $result .= "_____________\n";

            //$homepage .= $result;
            $elements[$count]['object'] = $result;

           // mylog($result);
            $count++;
        }

      //  mylog($elements);
        sort($elements);
      //  mylog("AFTER SORT:" . print_r($elements, TRUE));

        $allMessage = "";
        for ($i = 0; $i < $count; $i++) {
            $allMessage .= $elements[$i]['object'];
        }

        $chunks = str_split($allMessage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            mylog("Chunk: " . $chunk,LOGDEBUG);
            $this->reply($telegram, $chunk);
        }
    }

    function sendInfoByID($telegram) {
        $text = substr($this->text, 3);

        $this->reply($telegram, "Sto cercando le informazionio per l'elemento N° " . $text);

        $text = str_replace(" ", "%20", $text);
        $text = strtoupper($text);
        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20upper(C)%20contains%20%27";
        $urlgd .= rawurlencode("SELECT * WHERE " . ID . " = ");
        $urlgd .= $text;
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;

        $homepage = "";

        $json = file_get_contents($urlgd);

        try {
            $myContent = parseGjson($json);
//    mylog(print_r($myContent, TRUE));
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        $count = count($myContent);

        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');
            return;
        }

        foreach ($myContent as $v) {
            $result = "\n";
            $result .= "N°: /id_" . $v["ID"] . "\n";
            $result .= "<b>Last update:</b> " . convertGjsonDateToString($v['update']) . "\n";
            $result .= isset($v['profession']) ? "<b>Profession:</b> " . $v['profession'] . "\n" : "";
            $result .= isset($v['Name']) ? "<b>Name:</b> " . $v['Name'] . "\n" : "";
            $result .= isset($v['Email']) ? "<b>Email:</b>  " . $v['Email'] . "\n" : "";
            $result .= isset($v['Phone']) ? "<b>Phone:</b> " . $v['Phone'] . "\n" : "";
            $result .= isset($v['Mobile1']) ? "<b>Mobile1:</b> " . $v['Mobile1'] . "\n" : "";
            $result .= isset($v['Mobile2']) ? "<b>Mobile2:</b> " . $v['Mobile2'] . "\n" : "";
            $result .= isset($v['Address']) ? "<b>Address:</b> " . $v['Address'] . "\n" : "";
            $result .= (isset($v['Address']) || (isset($v['lat']) && isset($v['lng']))) ? "<b>GetPosition:</b> /pos_" . $v['ID'] . "\n" : "";
            $result .= isset($v['Description']) ? "<b>Description:</b> " . $v['Description'] . "\n" : "";
            $result .= isset($v['web']) ? "<b>URL:</b> " . $v['web'] . "\n" : "";
            $result .= "_____________\n";

            $homepage .= $result;
            mylog($result,LOGDEBUG);
        }



        $chunks = str_split($homepage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $this->reply($telegram, $chunk);
        }
    }

    /**
     * Use the google API to resolve an address to coordinates
     * @param type $telegram
     * @param type $address
     * @param boolean TRUE or FALSE
     * @return array actually the point of the position with lat and long $point = array(
      'latitude' => 0,
      'longitude' => 0
      );
     */
    function resolveAddress($telegram, $address, $sendAddress = FALSE) {
        $point = array(
            'latitude' => -1,
            'longitude' => -1
        );

        try {
            if (isset($address) && $address != "") {
                $urlDecoder = "https://maps.googleapis.com/maps/api/geocode/json?";
                $urlDecoder .= "key=" . GKEY . "&address=";
                $urlDecoder .= rawurlencode($address);
                $json = json_decode(file_get_contents($urlDecoder), TRUE);

                if (!isset($json['results'][0]['geometry']['location']['lat']) ||
                        !isset($json['results'][0]['geometry']['location']['lng'])) {
                    return;
                }
                $point = array(
                    'latitude' => $json['results'][0]['geometry']['location']['lat'],
                    'longitude' => $json['results'][0]['geometry']['location']['lng'],
                );

                if ($sendAddress) {
                    $venue = array(
                        'chat_id' => $this->chat_id,
                        'latitude' => $point['latitude'],
                        'longitude' => $point['longitude'],
                        'title' => "Address",
                        'address' => $address
                    );

                    mylog($venue, LOGDEBUG);
                    $telegram->sendVenue($venue);
                }
            }
        } catch (Exception $e) {
            mylog("Exception: " . $e, LOGERROR);
            mylog("Impossibile recuperare l'indirizzo corretto",LOGWARN);
            $this->reply($telegram, "Impossibile recuperare l'indirizzo corretto");
        }

        return $point;
    }

    function sendPosition($telegram) {
        $text = preg_match('/^\/pos_/', $this->text) ? substr($this->text, 5) : substr($this->text, 3);

        $msg = "Sto elaborando la posizione per il N°: " . $text;
        $this->reply($telegram, $msg);

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20A%20%3D%20";
        $urlgd .= rawurlencode("SELECT * WHERE " . ID . " = ");
        $urlgd .= $text;
//$urlgd .= rawurlencode(" AND " . lat . " IS NOT NULL AND " . lng . " IS NOT NULL ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        $inizio = 1;
        $homepage = "";

        $json = file_get_contents($urlgd);
//mylog(print_r($json, TRUE));

        try {
            $myContent = parseGjson($json);
// mylog(print_r($myContent, TRUE));
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
            $this->reply($telegram, 'Impossibile trovare la posizione');
            return;
        }

        $count = count($myContent);

        if ($count == 0) {
            $this->reply($telegram, 'Nessun elemento trovato');

            return;
        }

        $this->resolveAddress($telegram, $myContent[0]['Address'], TRUE);
    }

// Crea la tastiera
    function update_mess($telegram) {
        $option = array(array($telegram->buildKeyboardButton("Invia la tua posizione / send your location", false, true)),
            array($telegram->buildKeyboardButton("Disabilita posizione / disable your location", false, false)) //this work
        );
// Create a permanent custom keyboard
        $keyb = $telegram->buildKeyBoard($option, $onetime = true);
        $content = array(
            'chat_id' => $this->chat_id,
            'reply_markup' => $keyb,
            'text' => "Attiva la localizzazione sul tuo smartphone / Turn on your GPS");
        $telegram->sendMessage($content);
    }

    function location_manager($telegram) {
        $this->user->addLocation($this->location);
        
        // save location into DB
//        $result = $this->collection->updateOne(['tgUserId' => $this->user_id], $this->user);
        $result = $this->collection->updateMany(
                ['tgUserId' => $this->user_id], ['$set' => ['location' =>  $this->location] ], ['upsert' => true]
        );


//            $result = $this->collection->updateOne(
//                    ['tgUserId' => $this->user_id], ['$set' => ['location' => $this->location]]
//            );

        $this->user = $this->collection->findOne(['tgUserId' => $this->user_id]);
    //    mylog("USER found: " . print_r($this->user, TRUE));
    //    $this->reply($telegram, "You are the user: " . print_r($this->collection->findOne(['tgUserId' => $this->user_id]), TRUE));

//rispondo
        $response = $telegram->getData();
        $bot_request_message_id = $response["message"]["message_id"];

//nascondo la tastiera e forzo l'utente a darmi una risposta
        $forcehide = $telegram->buildForceReply(true);

//chiedo cosa sta accadendo nel luogo
        $content = array('chat_id' => $this->chat_id, 'text' => "[Posizione ricevuta. Fai ora una ricerca]", 'reply_markup' => $forcehide, 'reply_to_message_id' => $bot_request_message_id);
//        $content = array('chat_id' => $this->chat_id, 'text' => "[Cosa vuoi cercare?]", 'reply_markup' => $forcehide);
    //    mylog("return response: " . print_r($content, TRUE),LOGDEBUG);
        $bot_request_message = $telegram->sendMessage($content);

//memorizzare nel DB
        $obj = json_decode($bot_request_message);
    //    mylog("return response: " . print_r($obj, TRUE));
//        $id = $obj->result;
//        $id = $id->message_id;
//        //print_r($id);
//        $statement = "INSERT INTO " . DB_TABLE_GEO . " (lat,lng,user,text,bot_request_message) VALUES ('" . $lat . "','" . $lng . "','" . $user_id . "',' ','" . $id . "')";
//        $db->exec($statement);
    }

    /*
      function location_manager($telegram, $user_id, $chat_id, $location) {

      $lon = $location["longitude"];
      $lat = $location["latitude"];
      $response = $telegram->getData();
      $response = str_replace(" ", "%20", $response);

      $reply = "http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=" . $lat . "&lon=" . $lon . "&zoom=18&addressdetails=1";
      $json_string = file_get_contents($reply);
      $parsed_json = json_decode($json_string);
      //var_dump($parsed_json);
      $comune = "";
      $temp_c1 = $parsed_json->{'display_name'};


      if ($parsed_json->{'address'}->{'town'}) {
      $temp_c1 .="\nCittà: " . $parsed_json->{'address'}->{'town'};
      $comune .=$parsed_json->{'address'}->{'town'};
      } else
      $comune .=$parsed_json->{'address'}->{'city'};

      if ($parsed_json->{'address'}->{'village'})
      $comune .=$parsed_json->{'address'}->{'village'};
      $location = "Comune di: " . $comune . " tramite le coordinate che hai inviato: " . $lat . "," . $lon;
      $content = array('chat_id' => $chat_id, 'text' => $location, 'disable_web_page_preview' => true);
      $telegram->sendMessage($content);

      $alert = "";
      //echo $comune;
      $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(A)%20LIKE%20%27%25";
      $urlgd .= rawurlencode("SELECT * WHERE upper(A) LIKE '%");
      $urlgd .= strtoupper($comune);
      $urlgd .= rawurlencode("%'");

      $urlgd .="&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID3;

      sleep(1);

      $inizio = 1;
      $homepage = "";
      //$comune="Lecce";
      //echo $urlgd;
      $csv = array_map('str_getcsv', file($urlgd));
      //var_dump($csv[1][0]);
      $count = 0;
      foreach ($csv as $data => $csv1) {
      $count = $count + 1;
      }
      if ($count == 0 || $count == 1) {
      $location = "Nessun risultato trovato";
      $content = array('chat_id' => $chat_id, 'text' => $location, 'disable_web_page_preview' => true);
      $telegram->sendMessage($content);
      }

      function decode_entities($text) {

      $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
      $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
      $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text);  #hex notation
      $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
      return $text;
      }

      //echo $count;
      //  $count=3;
      for ($i = $inizio; $i < $count; $i++) {

      $homepage .="\n";
      $homepage .="Comune: " . $csv[$i][0] . "\n";
      $homepage .="Indirizzo: " . $csv[$i][1] . "\n";
      $homepage .="CAP: " . $csv[$i][2] . "\n";
      if ($csv[$i][3] != NULL)
      $homepage .="Segretario/Referente: " . $csv[$i][3] . "\n";
      if ($csv[$i][4] != NULL)
      $homepage .="Tel: " . $csv[$i][4] . "\n";
      if ($csv[$i][5] != NULL)
      $homepage .="Email: " . $csv[$i][5] . "\n";
      if ($csv[$i][6] != NULL) {
      $homepage .= "http://www.openstreetmap.org/?mlat=" . $csv[$i][6] . "&mlon=" . $csv[$i][7] . "#map=19/" . $csv[$i][6] . "/" . $csv[$i][7] . "/" . $_POST['qrname'];
      }
      $homepage .="\n____________\n";
      }

      //}
      //	echo $alert;

      $chunks = str_split($homepage, self::MAX_LENGTH);
      foreach ($chunks as $chunk) {
      $content = array('chat_id' => $chat_id, 'text' => $chunk, 'disable_web_page_preview' => true);
      $telegram->sendMessage($content);
      }
      $this->create_keyboard_temp($telegram, $chat_id);
      }
     */
}
?>

