<?php
/**
 * Telegram Bot LibreItalia
 * @author Francesco Piero Paolicelli @piersoft
 */

include("Telegram.php");
#include("settings_t.php");

class mainloop
{
    const MAX_LENGTH = 4096;
    function start($telegram, $update)
    {
        
        date_default_timezone_set('Europe/Rome');
        $today        = date("Y-m-d H:i:s");
        $text         = $update["message"]["text"];
        $chat_id      = $update["message"]["chat"]["id"];
        $user_id      = $update["message"]["from"]["id"];
        $location     = $update["message"]["location"];
        $reply_to_msg = $update["message"]["reply_to_message"];
        
        $this->shell($telegram, $text, $chat_id, $user_id, $location, $reply_to_msg);
	$log = "TEXT: $text, CHATID: $chat_id, USERID: $user_id,LOCATION: $location,REPLY: $reply_to_msg";
        mylog($log);
	$db = NULL;
        
    }
    
    //gestisce l'interfaccia utente
    function shell($telegram, $text, $chat_id, $user_id, $location, $reply_to_msg)
    {
        date_default_timezone_set('Europe/Rome');
        $today = date("Y-m-d H:i:s");
        $log = "TEXT: $text, CHATID: $chat_id, USERID: $user_id, LOCATION: $location, REPLY: $reply_to_msg";
        mylog($log); 

        if ($text == "/start" || $text == "Informazioni") {
            $img      = curl_file_create('logo.png', 'image/png');
            $contentp = array(
                'chat_id' => $chat_id,
                'photo' => $img
            );
            $telegram->sendPhoto($contentp);
            $reply   = "Benvenuto. Questo è un servizio automatico (bot da Robot) di " . NAME . ". Puoi ricercare gli argomenti per parola chiave anteponendo il carattere ?, oppure cliccare su FAQ per avere l'elenco delle FAQ predefinite e quindi fare una ricerca per numero domanda corrispondente. In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\nQuesto bot è stato realizzato da @piersoft insieme a " . NAME . ". Il progetto e il codice sorgente sono liberamente riutilizzabili con licenza MIT.";
            $content = array(
                'chat_id' => $chat_id,
                'text' => $reply,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
            $log = $today . ";new chat started;" . $chat_id . "\n";
            $this->create_keyboard_temp($telegram, $chat_id);
            
            exit;
        }
        /*
        elseif ($text == "Città") {
        $reply = "Digita direttamente il nome del Comune oppure clicca sulla graffetta (📎) e poi 'posizione' per avere eventualmente la sede in loco";
        $content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
        $telegram->sendMessage($content);
        $log=$today. ";new chat started;" .$chat_id. "\n";
        //		$this->create_keyboard_temp($telegram,$chat_id);
        exit;
        }*/
        elseif ($text == "Ricerca") {
            $reply   = "Scrivi la parola da cercare anteponendo il carattere ?, ad esempio: ?LibreItalia";
            $content = array(
                'chat_id' => $chat_id,
                'text' => $reply,
                'disable_web_page_preview' => true
            );
            $telegram->sendMessage($content);
            $log = $today . ";new chat started;" . $chat_id . "\n";
            //			$this->create_keyboard_temp($telegram,$chat_id);
            exit;
            
        } elseif ($location != null) {
            
            //	$this->location_manager($telegram,$user_id,$chat_id,$location);
            //	exit;
            
        }
         elseif (strpos($text, '/contact') !== false) {
		 $text     = str_replace("?", "", $text);
                $location = "Sto cercando argomenti con parola chiave: " . $text;
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                $text  = str_replace(" ", "%20", $text);
                $text  = strtoupper($text);
                #$urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(B)%20contains%20%27";
                $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=";
		$urlgd .= rawurlencode("SELECT * WHERE upper(C) contains '");
                $urlgd .= $text;
		$urlgd .= rawurlencode("'");
                $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
                sleep(1);
                $inizio   = 1;
                $homepage = "";
                //$comune="Lecce";

                //echo $urlgd;
                $csv   = array_map('str_getcsv', file($urlgd));
                //var_dump($csv[1][0]);
                $count = 0;
                foreach ($csv as $data => $csv1) {
                    $count = $count + 1;
                }
                if ($count == 0) {
                    $location = "Nessun risultato trovato";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }
     		
		if ($count > 40) {
                    $location = "Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                    exit;
                }else{
                    $location = "Trovati $count elementi";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);

                }

                function decode_entities($text)
                {

                    $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
                    $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
                    $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text); #hex notation
                    $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
                    return $text;
                }

                function format_elements($csv, $row){
                        $result = "\n";
                        $result .= "N°: " . $csv[$row][0] . "\n" ;
                        for($i = 1; $i < 11; $i++){
                                $result .= $csv[0][$i] . ": " . $csv[$row][$i]  . "\n";
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
                        'chat_id' => $chat_id,
                        'text' => $chunk,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }

	}
		elseif (strpos($text, '/pos') !== false ){
                $location = "Sto elaborando la posizione per il N^: " .  substr($text, 4);
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                //$text=str_replace(" ","%20",$text);
                //$text=strtoupper($text);
                $urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=";//SELECT%20%2A%20WHERE%20A%20%3D%20";
                $urlgd .= rawurlencode("SELECT * WHERE A = ");
                $urlgd .= substr($text, 4);
                $urlgd .= rawurlencode(" ");
                $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
                $inizio   = 1;
                $homepage = "";

                $csv   = array_map('str_getcsv', file($urlgd));

                                $count = 0;
                foreach ($csv as $data => $csv1) {
                    $count = $count + 1;
                }

                if ($count == 0 || $count == 1) {
                    $location = "Nessun risultato trovato";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }

                function decode_entities($text)
                {

                    $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
                    $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
                    $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text); #hex notation
                    $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
                    return $text;
                }

                for ($i = $inizio; $i < $count; $i++) {
                        $homepage .= "\n";
                        $homepage .= $csv[$i][1] . "\n";
                        $homepage .= "____________\n";
                }

                $chunks = str_split($homepage, self::MAX_LENGTH);
                foreach ($chunks as $chunk) {
                   $venue = array(
                        'chat_id' => $chat_id,
                        'latitude' => $csv[1][11],
                        'longitude' => $csv[1][12],
                        'title' => "Address",
                        'address' => $csv[1][8]
                    );
                        mylog("latitude => ". $csv[1][11] .",
                                longitude => ".$csv[1][12] .",
                                title => Address,
                                address => ".$csv[1][8]
                        );
                        $telegram->sendVenue($venue);
                }

            }

	        //elseif($text !=null)
            elseif (strpos($text, '/') === false) {
            
            if (strpos($text, '?') !== false) {
                $text     = str_replace("?", "", $text);
                $location = "Sto cercando argomenti con parola chiave: " . $text;
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                $text  = str_replace(" ", "%20", $text);
                $text  = strtoupper($text);
                #$urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(B)%20contains%20%27";
                $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=";//SELECT%20%2A%20WHERE%20upper(C)%20contains%20%27";
		$urlgd .= rawurlencode("SELECT * WHERE upper(C) contains '");
                $urlgd .= $text;
                $urlgd .= rawurlencode("'");

                $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
                sleep(1);
                $inizio   = 1;
                $homepage = "";
                //$comune="Lecce";
                
                //echo $urlgd;
                $csv   = array_map('str_getcsv', file($urlgd));
                //var_dump($csv[1][0]);
                $count = 0;
                foreach ($csv as $data => $csv1) {
                    $count = $count + 1;
                }
                if ($count == 0) {
                    $location = "Nessun risultato trovato";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }
                if ($count > 40) {
                    $location = "Troppe risposte per il criterio scelto. Ti preghiamo di fare una ricerca più circoscritta";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                    exit;
                }else{
	            $location = "Trovati $count elementi";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);

		}

                function decode_entities($text)
                {
                    
                    $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
                    $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
                    $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text); #hex notation
                    $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
                    return $text;
                }

		function format_elements($csv, $row){
			$result = "\n";
			$result .= "N°: " . $csv[$row][0] . "\n" ;
			for($i = 1; $i < 11; $i++){
				$result .= $csv[0][$i] . ": " . $csv[$row][$i]  . "\n";
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
                        'chat_id' => $chat_id,
                        'text' => $chunk,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }

            } else if (strpos($text, 'FAQ') !== false) {
                //	$text=str_replace("?","",$text);
                $location = "Puoi digitare direttamente il N° della FAQ che ti interessa";
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                $location = "Ecco le FAQ disponibili:\n";
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                //	$text=str_replace(" ","%20",$text);
                $urlgd = "https://spreadsheets.google.com/tq?tqx=out:csv&tq=";//SELECT%20%2A%20WHERE%20A%20IS%20NOT%20NULL";
		$urlgd .= rawurlencode("SELECT * WHERE A IS NOT NULL ");

                //$urlgd .=$text;
                $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
                sleep(1);
                $inizio   = 1;
                $homepage = "";
                //$comune="Lecce";
                
                //echo $urlgd;
                $csv   = array_map('str_getcsv', file($urlgd));
                //var_dump($csv[1][0]);
                $count = 0;
                foreach ($csv as $data => $csv1) {
                    $count = $count + 1;
                }
                if ($count == 0) {
                    $location = "Nessun risultato trovato";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }
                function decode_entities($text)
                {
                    
                    $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
                    $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
                    $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text); #hex notation
                    $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
                    return $text;
                }
                for ($i = $inizio; $i < $count; $i++) {
                    
                    
                    $homepage .= "\n";
                    $homepage .= "N°: " . $csv[$i][0] . " - ";
                    $homepage .= $csv[$i][1] . "\n";
                    $homepage .= "____________\n";
                    
                    
                }
                $chunks = str_split($homepage, self::MAX_LENGTH);
                foreach ($chunks as $chunk) {
                    $content = array(
                        'chat_id' => $chat_id,
                        'text' => $chunk,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }
                
            } elseif (strpos($text, '1') !== false || strpos($text, '2') !== false || strpos($text, '3') !== false || strpos($text, '4') !== false || strpos($text, '5') !== false || strpos($text, '6') !== false || strpos($text, '7') !== false || strpos($text, '8') !== false || strpos($text, '9') !== false || strpos($text, '0') !== false) {
                $location = "Sto elaborando la risposta alla FAQ N°: " . $text;
                $content  = array(
                    'chat_id' => $chat_id,
                    'text' => $location,
                    'disable_web_page_preview' => true
                );
                $telegram->sendMessage($content);
                //$text=str_replace(" ","%20",$text);
                //$text=strtoupper($text);
                $urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=";//SELECT%20%2A%20WHERE%20A%20%3D%20";
		$urlgd .= rawurlencode("SELECT * WHERE A = ");
                $urlgd .= $text;
                $urlgd .= rawurlencode(" ");
                $urlgd .= "&key=" . GDRIVEKEY . "&gid=" . GDRIVEGID1;
                $inizio   = 1;
                $homepage = "";
                //$comune="Lecce";
                
                //echo $urlgd;
                $csv   = array_map('str_getcsv', file($urlgd));
                //var_dump($csv[1][0]);
                $count = 0;
                foreach ($csv as $data => $csv1) {
                    $count = $count + 1;
                }
                if ($count == 0 || $count == 1) {
                    $location = "Nessun risultato trovato";
                    $content  = array(
                        'chat_id' => $chat_id,
                        'text' => $location,
                        'disable_web_page_preview' => true
                    );
                    $telegram->sendMessage($content);
                }
                function decode_entities($text)
                {
                    
                    $text = htmlentities($text, ENT_COMPAT, 'ISO-8859-1', true);
                    $text = preg_replace('/&#(\d+);/me', "chr(\\1)", $text); #decimal notation
                    $text = preg_replace('/&#x([a-f0-9]+);/mei', "chr(0x\\1)", $text); #hex notation
                    $text = html_entity_decode($text, ENT_COMPAT, "UTF-8"); #NOTE: UTF-8 does not work!
                    return $text;
                }
                for ($i = $inizio; $i < $count; $i++) {
                    
                    
                    $homepage .= "\n";
                    $homepage .= $csv[$i][1] . "\n";
                    $homepage .= "____________\n";
                }
                $chunks = str_split($homepage, self::MAX_LENGTH);
                foreach ($chunks as $chunk) {
               	   $contact = array(
                        'chat_id' => $chat_id,
                        'phone_number' => $csv[1][6],
			'first_name' => $csv[1][3],
			'last_name' => $csv[1][2]
                    );
		mylog("phone_number: ".$csv[1][6] .", first_name: " . $csv[1][3] . ", last_name: " . $csv[1][2]);
                    $telegram->sendContact($contact);
    
	     	   # $content = array(
                   #     'chat_id' => $chat_id,
                   #     'text' => $chunk,
                   #     'disable_web_page_preview' => true
                   # );
                   # $telegram->sendMessage($content);
                }
                
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
    
    function create_keyboard_temp($telegram, $chat_id)
    {
        $option = array(["FAQ","Ricerca"],["Informazioni"],["pippo","paperino"]);
        $keyb    = $telegram->buildKeyBoard($option, $onetime = false);
        $content = array(
            'chat_id' => $chat_id,
            'reply_markup' => $keyb,
            'text' => "[Digita o fai una Scelta]"
        );
        $telegram->sendMessage($content);
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

