<?php

//Telegram
define('API', ''); // api google shortner eventuale per shot link
define('TELEGRAM_BOT', ''); // token Telegram Bot *obbligatorio*
define('BOT_WEBHOOK', ''); // url assoluto https per start.php
define('CERTIFICATE', '');   // path assoluto per il certificato autofirmato
define('GDRIVEKEY', ''); // key dello sheet di google drive
define('GDRIVEGID1', ''); //gid del foglio di calcolo . di solito il primo gid=0 per esempio FAQ
define('GDRIVEGID2', ''); // gid dell'eventuale altro foglio di calcolo per esempio foglio Risposte
define('GDRIVEGID3', ''); // gid dell'eventuale altro foglio di calcolo per esempio sedi azienda/sindacato
define('NAME', ''); // nome del Bot che appare nelle Informazioni /start
define('FOLDERLOG', ''); // define the folder log for all logs
define('GKEY', ''); // this Key is used to query the google API for geo search

// LOG LEVEL definition
define("LOGNULL", 0);
define("LOGERROR", 1);
define("LOGWARN", 2);
define("LOGINFO", 3);
define("LOGDEBUG", 4);
define("LOGLEVEL", LOGINFO);
define("WELCOME", "Benvenuto. Questo è un servizio automatico (bot da Robot) di " . NAME . ". "
        . "Puoi ricercare gli argomenti per parola chiave anteponendo il carattere ?, "
        . "oppure cliccare su KEYWORDS per avere la lista delle parole chiave. "
        . "In qualsiasi momento scrivendo /start ti ripeterò questo messaggio di benvenuto.\n"
        . "Questo bot è stato realizzato da @pagaia per il gruppo Facebook Italiani a Bruxelles. "
        . "I dati presenti nel Database sono raccolti dai file del gruppo facebook Italiani a Bruxelles. "
        . "L'autore non è responsabile dei contenuti. Il progetto, in fase sperimentale, ed il codice sorgente sono liberamente riutilizzabili con licenza MIT.");
define("LOGO", "img/logo.png");

// Your Openstreetmap Query settings
define('AROUND', 10000);      //Number of meters to calculate radius to search
define('MAX', 20);      //max number of points to search
//define('TAG','"emergency"="defibrillator"');			//tag to search accoring to Overpass_API Query Language
define('TAG', '"name"="Kit soccorso bici"');   //tag to search accoring to Overpass_API Query Language


// Define the database fields for gdrive
define('ID', 'A');
define('update', 'B');
define('Key', 'C');
define('Name', 'D');
define('Email', 'E');
define('Phone', 'F');
define('Mobile1', 'G');
define('Mobile2', 'H');
define('Address', 'I');
define('Description', 'J');
define('web', 'K');
define('lat', 'L');
define('lng', 'M');
define('profession', 'N');
?>
