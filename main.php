<?php

/**
 * Telegram Bot LibreItalia
 * @author Francesco Piero Paolicelli @piersoft
 */
include("Telegram.php");

class mainloop {

    const MAX_LENGTH = 4096;

    var $text;
    var $chat_id;
    var $user_id;
    var $username;
    var $firstName;
    var $lastName;
    var $location;

    function start($telegram, $update) {
        mylog(print_r($update, true));

        date_default_timezone_set('Europe/Rome');
        $today = date("Y-m-d H:i:s");
        $this->text = $update["message"]["text"];
        $this->chat_id = $update["message"]["chat"]["id"];
        $this->user_id = $update["message"]["from"]["id"];
        $this->username = $update["message"]["from"]["username"];
        $this->firstName = $update["message"]["from"]["first_name"];
        $this->lastName = $update["message"]["from"]["last_name"];
        //$this->location = $update["message"]["location"];
        // $reply_to_msg = $update["message"]["reply_to_message"];

        $request = "The user $this->username ($this->firstName $this->lastName) id: $this->user_id has sent this msg : '$this->text' ";
        mylog($request);
        $this->shell($telegram);

        //  $db = NULL;
    }

    //gestisce l'interfaccia utente
    function shell($telegram) {
        date_default_timezone_set('Europe/Rome');
        $today = date("Y-m-d H:i:s");
        $log = "TEXT: $this->text, CHATID: $this->chat_id, USERID: $this->user_id, LOCATION: $this->location";
        mylog($log);

        if ($this->text == "/start" || $this->text == "Informazioni") {
            $this->sendInformazioni($telegram);
        } elseif ($this->text == "Ricerca") {
            $this->sendRicerca($telegram);
        } elseif ($this->location != null) {
            //	$this->location_manager($telegram,$user_id,$chat_id,$location);
            //	exit;
        } elseif (strpos($this->text, '/p') !== false) {
            $this->sendPosition($telegram);
        } elseif (strpos($this->text, '/') === false) {
            if (strpos($this->text, '?') !== false) {
                $this->sendListResult($telegram);
            } else if (strpos($this->text, 'PAROLE CHIAVE') !== false) {
                $this->sendListKey($telegram);
            } elseif (is_numeric($this->text)) {
                $this->sendContactInfo($telegram);
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
              exit;
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
            $this->create_keyboard_temp($telegram, $chat_id);
            exit;
        }
    }

    function create_keyboard_temp($telegram) {
        $option = array(["PAROLE CHIAVE", "Ricerca"], ["Informazioni"]);
        $keyb = $telegram->buildKeyBoard($option, $onetime = false);
        $content = array(
            'chat_id' => $this->chat_id,
            'reply_markup' => $keyb,
            'text' => "[Digita o fai una Scelta]"
        );
        $telegram->sendMessage($content);
    }

    function sendContactInfo($telegram) {
        $location = "Sto raccogliendo l'informazione N°: " . $this->text;
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq="; //SELECT%20%2A%20WHERE%20A%20%3D%20";
        $urlgd .= rawurlencode("SELECT * WHERE A = ");
        $urlgd .= $this->text;
        $urlgd .= rawurlencode(" ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        $inizio = 1;
        $res = "";

        $csv = array_map('str_getcsv', file($urlgd));

        $count = 0;
        foreach ($csv as $data => $csv1) {
            $count = $count + 1;
        }
        if ($count == 0 || $count == 1) {
            $location = "Nessun risultato trovato";
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }



        for ($i = $inizio; $i < $count; $i++) {
            $res .= "\n";
            $res .= $csv[$i][1] . "\n";
            $res .= "____________\n";
        }
        $chunks = str_split($res, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $contact = array(
                'chat_id' => $this->chat_id,
                'phone_number' => $csv[1][6],
                'first_name' => $csv[1][3],
                'last_name' => $csv[1][2]
            );
            mylog("phone_number: " . $csv[1][6] . ", first_name: " . $csv[1][3] . ", last_name: " . $csv[1][2]);
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
        $reply = "Benvenuto. Questo è un servizio automatico (bot da Robot) di " . NAME . ". "
                . "Puoi ricercare gli argomenti per parola chiave anteponendo il carattere ?, "
                . "oppure cliccare su FAQ per avere l'elenco delle FAQ predefinite "
                . "e quindi fare una ricerca per numero domanda corrispondente. "
                . "In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\n"
                . "Questo bot è stato realizzato da @pagaia per Italiani a Bruxelles. "
                . "Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.";
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
        $log = $today . ";new chat started;" . $this->chat_id . "\n";
        $this->create_keyboard_temp($telegram);

        exit;
    }

    function sendRicerca($telegram) {
        $reply = "Scrivi la parola da cercare anteponendo il carattere ?, ad esempio: ?idraulico";
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $reply,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);

        exit;
    }

    function sendListKey($telegram) {
        $location = "Puoi digitare direttamente il N° dell'informazione che ti interessa";
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
        $location = "Ecco la lista delle PAROLE CHIAVE disponibili:\n";
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq="; //SELECT%20%2A%20WHERE%20A%20IS%20NOT%20NULL";
        $urlgd .= rawurlencode("SELECT C, count(A) WHERE A IS NOT NULL group by C ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        sleep(1);
         
        $inizio = 1;
        $res = "";
        //$comune="Lecce";
        //echo $urlgd;
        $csv = array_map('str_getcsv', file($urlgd));
        //var_dump($csv[1][0]);
        $count = 0;
        foreach ($csv as $data => $csv1) {
            $count = $count + 1;
        }
        if ($count == 0) {
            $location = "Nessun risultato trovato";
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }

        for ($row = $inizio; $row < $count; $row++) {
            $res .= "\n";
            $res .= "N°: " . $csv[$row][1] ." - ". $csv[$row][0] . "\n";
        }
        $chunks = str_split($res, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $chunk,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }
    }

    function sendListResult($telegram) {
        $text = str_replace("?", "", $this->text);
        $location = "Sto cercando argomenti con parola chiave: " . $text;
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);
        $text = str_replace(" ", "%20", $text);
        $text = strtoupper($text);
        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq="; //SELECT%20%2A%20WHERE%20upper(C)%20contains%20%27";
        $urlgd .= rawurlencode("SELECT * WHERE upper(C) contains '");
        $urlgd .= $text;
        $urlgd .= rawurlencode("'");

        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
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
        if ($count == 0) {
            $location = "Nessun risultato trovato";
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }
        if ($count > 40) {
            $location = "Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
            exit;
        } else {
            $location = (1 == $count ? "Trovato $count elemento" : "Trovati $count elementi");
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }

        function format_elements($csv, $row) {
            $result = "\n";
            $result .= "N°: " . $csv[$row][0] . "\n";
            for ($i = 1; $i < 11; $i++) {
                $result .= $csv[0][$i] . ": " . $csv[$row][$i] . "\n";
            }
            $result .= "\n";
            mylog($result);
            return $result;
        }

        for ($i = $inizio; $i < $count; $i++) {
            $homepage .= format_elements($csv, $i);
        }

        $chunks = str_split($homepage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $chunk,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }
    }

    function sendPosition($telegram) {
        $text = substr($this->text, 3);
        $location = "Sto elaborando la posizione per il N^: " . $text;
        $content = array(
            'chat_id' => $this->chat_id,
            'text' => $location,
            'disable_web_page_preview' => true
        );
        $telegram->sendMessage($content);

        $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq="; //SELECT%20%2A%20WHERE%20A%20%3D%20";
        $urlgd .= rawurlencode("SELECT * WHERE A = ");
        $urlgd .= $text;
        $urlgd .= rawurlencode(" ");
        $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
        $inizio = 1;
        $homepage = "";

        $csv = array_map('str_getcsv', file($urlgd));

        $count = 0;
        foreach ($csv as $data => $csv1) {
            $count = $count + 1;
        }

        if ($count == 0 || $count == 1) {
            $location = "Nessun risultato trovato";
            $content = array(
                'chat_id' => $this->chat_id,
                'text' => $location,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
        }

        for ($i = $inizio; $i < $count; $i++) {
            $homepage .= "\n";
            $homepage .= $csv[$i][1] . "\n";
            $homepage .= "____________\n";
        }

        $chunks = str_split($homepage, self::MAX_LENGTH);
        foreach ($chunks as $chunk) {
            $venue = array(
                'chat_id' => $this->chat_id,
                'latitude' => $csv[1][11],
                'longitude' => $csv[1][12],
                'title' => "Address",
                'address' => $csv[1][8]
            );
            mylog("latitude => " . $csv[1][11] . ",
                                longitude => " . $csv[1][12] . ",
                                title => Address,
                                address => " . $csv[1][8]
            );
            $telegram->sendVenue($venue);
        }
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

