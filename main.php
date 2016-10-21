<?php

/**
 * Telegram Bot LibreItalia
 * @author Francesco Piero Paolicelli @piersoft
 */
include("Telegram.php");

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

    function start($telegram, $update) {

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

        $this->shell($telegram);

//  $db = NULL;
    }

//gestisce l'interfaccia utente
    function shell($telegram) {
        date_default_timezone_set('Europe/Rome');
//$today = date("Y-m-d H:i:s");
        // $log = "TEXT: $this->text, CHATID: $this->chat_id, USERID: $this->user_id, LOCATION: " . print_r($this->location, TRUE);
        // mylog($log);
        // check if a position has been given
        if (isset($this->location)) {
            
        }
//first message
        if ($this->text == "/start" || $this->text == "Informazioni") {
            $this->sendInformazioni($telegram);
        }
// send the help message
        elseif ($this->text == "/help" || $this->text == "Ricerca") {
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
        elseif (preg_match('/^\/s /', $this->text)) {
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

    function create_keyboard_temp($telegram) {
        $option = array(["KEYWORDS", "Ricerca"], ["/help"]);
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
            mylog("phone_number: " . $phoneN . ", first_name: " . $myContent[0]['first_name'] . ", last_name: " . $myContent[0]['last_name']);
            $telegram->sendContact($contact);
        }
    }

    function sendInformazioni($telegram) {
        $img = curl_file_create('logo.png', 'image/png');
        $contentp = array(
            'chat_id' => $this->chat_id,
            'photo' => $img
        );
        $telegram->sendPhoto($contentp);

        $msg = "Benvenuto. Questo è un servizio automatico (bot da Robot) di " . NAME . ". "
                . "Puoi ricercare gli argomenti per parola chiave anteponendo il carattere ?, "
                . "oppure cliccare su FAQ per avere l'elenco delle FAQ predefinite "
                . "e quindi fare una ricerca per numero domanda corrispondente. "
                . "In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\n"
                . "Questo bot è stato realizzato da @pagaia per Italiani a Bruxelles. "
                . "Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.";

        $this->reply($telegram, $msg);
        mylog("new chat started with " . $this->chat_id);
        $this->create_keyboard_temp($telegram);

        return;
    }

    function sendHelp($telegram) {
        $helpMessage = "Commands List:\n"
                . "/start or Informazioni - to show the information about the BOT\n"
                . "/help - to show this menu\n"
                . "/l - to list all the keywords\n"
                . "/s word - to perform a search on all Database(e.g. /s doctor )\n"
                . "/c #ID - to get the phone of the element identified by the #ID (e.g. /c 123)\n"
                . "/p #ID - to get the position of the element identified by the #ID (e.g. /p 134)\n";

        $this->reply($telegram, $helpMessage);

        return;
    }

    function sendListKey($telegram) {

        $this->reply($telegram, "Ecco la lista delle Keywords disponibili:\n");

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:json&tq="; //SELECT%20%2A%20WHERE%20A%20IS%20NOT%20NULL";
        $urlgd .= rawurlencode("SELECT " . Key . ", count(" . ID . ") WHERE " . ID . " IS NOT NULL group by " . Key . " ");
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
        $text = substr($this->text, 3);

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
        if ($count > 40) {
            $this->reply($telegram, "Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta");

            return;
        }

        $location = (1 == $count ? "Trovato 1 elemento" : "Trovati " . $count . " elementi");
        $this->reply($telegram, $location);

        foreach ($myContent as $v) {
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
            $result .= (isset($v['Address']) || (isset($v['lat']) && isset($v['lng']))) ? "<b>GetPosition:</b> /pos_" . $v['ID'] . "\n" : "";
            $result .= (isset($v['Description']) && $v['Description'] != "") ? "<b>Description:</b> " . $v['Description'] . "\n" : "";
            $result .= (isset($v['web']) && $v['web'] != "") ? "<b>URL:</b> " . $v['web'] . "\n" : "";
            $result .= "_____________\n";

            $homepage .= $result;
            mylog($result);
        }



        $chunks = str_split($homepage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
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
            mylog($result);
        }



        $chunks = str_split($homepage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $this->reply($telegram, $chunk);
        }
    }

    function resolveAddress($telegram, $address) {
        try {
            if (isset($address) && $address != "") {
                $urlDecoder = "https://maps.googleapis.com/maps/api/geocode/json?";
                $urlDecoder .= "key=" . GKEY . "&address=";
                $urlDecoder .= rawurlencode($address);
                $json = json_decode(file_get_contents($urlDecoder), TRUE);

                $venue = array(
                    'chat_id' => $this->chat_id,
                    'latitude' => $json['results'][0]['geometry']['location']['lat'],
                    'longitude' => $json['results'][0]['geometry']['location']['lng'],
                    'title' => "Address",
                    'address' => $address
                );
                mylog("latitude => " . $json['results'][0]['geometry']['location']['lat'] . ",
                                longitude => " . $json['results'][0]['geometry']['location']['lng'] . ",
                                title => Address,
                                address => " . $address
                );
                $telegram->sendVenue($venue);
            }
        } catch (Exception $e) {
            mylog("Exception: " . $e);
            mylog("Impossibile recuperare l'indirizzo corretto");
            $this->reply($telegram, "Impossibile recuperare l'indirizzo corretto");
        }
    }

    function sendPosition($telegram) {
        $text = preg_match('/^\/pos_/', $this->text) ? substr($this->text, 5) : substr($this->text, 3);

        $msg = "Sto elaborando la posizione per il N^: " . $text;
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

        $this->resolveAddress($telegram, $myContent[0]['Address']);
    }

    /*
      function location_manager($telegram,$user_id,$chat_id,$location)
      {

      $lon=$location["longitude"];
      $lat=$location["latitude"];
      $response=$telegram->getData();
      $response=str_replace(" ","%20",$response);

      $reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lon."&zoom=18&addressdetails=1";
      $json_string = file_get_contents($reply);
      $parsed_json = json_decode($json_string);
      //var_dump($parsed_json);
      $comune="";
      $temp_c1 =$parsed_json->{'display_name'};


      if ($parsed_json->{'address'}->{'town'}) {
      $temp_c1 .="\nCittà: ".$parsed_json->{'address'}->{'town'};
      $comune .=$parsed_json->{'address'}->{'town'};
      }else 	$comune .=$parsed_json->{'address'}->{'city'};

      if ($parsed_json->{'address'}->{'village'}) $comune .=$parsed_json->{'address'}->{'village'};
      $location="Comune di: ".$comune." tramite le coordinate che hai inviato: ".$lat.",".$lon;
      $content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
      $telegram->sendMessage($content);

      $alert="";
      //echo $comune;
      $urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(A)%20LIKE%20%27%25";
      $urlgd .= rawurlencode("SELECT * WHERE upper(A) LIKE '%");
      $urlgd .= strtoupper($comune);
      $urlgd .= rawurlencode("%'");

      $urlgd .="&key=".GDRIVEKEY."&gid=".GDRIVEGID3;

      sleep (1);

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
      function decode_entities($text) {

      $text=htmlentities($text, ENT_COMPAT,'ISO-8859-1', true);
      $text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
      $text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
      $text= html_entity_decode($text,ENT_COMPAT,"UTF-8"); #NOTE: UTF-8 does not work!
      return $text;
      }
      //echo $count;
      //  $count=3;
      for ($i=$inizio;$i<$count;$i++){

      $homepage .="\n";
      $homepage .="Comune: ".$csv[$i][0]."\n";
      $homepage .="Indirizzo: ".$csv[$i][1]."\n";
      $homepage .="CAP: ".$csv[$i][2]."\n";
      if($csv[$i][3] !=NULL)	$homepage .="Segretario/Referente: ".$csv[$i][3]."\n";
      if($csv[$i][4] !=NULL) $homepage .="Tel: ".$csv[$i][4]."\n";
      if($csv[$i][5] !=NULL)$homepage .="Email: ".$csv[$i][5]."\n";
      if($csv[$i][6] !=NULL){
      $homepage .= "http://www.openstreetmap.org/?mlat=".$csv[$i][6]."&mlon=".$csv[$i][7]."#map=19/".$csv[$i][6]."/".$csv[$i][7]."/".$_POST['qrname'];
      }
      $homepage .="\n____________\n";

      }

      //}

      //	echo $alert;

      $chunks = str_split($homepage, self::MAX_LENGTH);
      foreach($chunks as $chunk) {
      $content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);
      $telegram->sendMessage($content);

      }
      $this->create_keyboard_temp($telegram,$chat_id);

      }
     */
}
?>

