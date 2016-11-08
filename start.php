#!/usr/bin/php
<?php
//previsto da chiamare solo php start.php con 1 eventuale parametro che può essere
//sethook per settare il link di webhook
//sethook + cert per settare il link di webhook con il certificato self signed
//removehook per rimuovere il link di webhook
//getupdates per eseguzione a polling (con cron o manualmente)
//e non si imposta il primo paramentro da shell si assume di avere impostato il webhook e di utilizzare quello
include 'functions.php';
include('getUpdates.php');


//istanzia oggetto Telegram
$bot_id = TELEGRAM_BOT;
$bot = new Telegram($bot_id);

function usage() {
    printf("");
    printf("Usage: \n");
    printf("start.php [sethook] [cert] [removehook] [getupdates] \n");
    printf("\tsethook: to configure the webhook\n");
    printf("\tcert: to use a self-signed certificate\n");
    printf("\tremovehook: To remove the webhook\n");
    printf("\tgetupdates: To call the script with a crontab insted to use the webhook\n");
    printf("\n");
}

//valuta se l'interfaccia è di tipo CLI per vedere il parametro e settare o rimuovere il webhook e poi esce (se lanciato da riga di comando) 
if (php_sapi_name() == 'cli') {
    if (count($argv) == 1) {
        usage();
    } else if ($argv[1] == 'sethook' && $argv[2] == 'cert') {
        print "Sto configurando il WEBHOOK con questi parametri:\n"
                . "WEBHOOK: " . BOT_WEBHOOK . "\n"
                . "CERTIFICATE: " . CERTIFICATE . "\n";
        //set the webhook with self signed certificate
        $bot->setWebhook(BOT_WEBHOOK, CERTIFICATE);
    } else if ($argv[1] == 'sethook') {
        print "Sto configurando il WEBHOOK con questi parametri:\n"
                . "WEBHOOK: " . BOT_WEBHOOK . "\n";
        //setta il webhook
        $bot->setWebhook(BOT_WEBHOOK);
    } else if ($argv[1] == 'removehook') {
        print "Sto rimuovendo il WEBHOOK\n";
        //rimuove il webhook
        $bot->removeWebhook();
    } else if ($argv[1] == 'getupdates') {
        //esegue il getupdates manuale
        getUpdates($bot);
    }
    exit;
}

//legge
$bot->init();
$update = $bot->getData();

$update_manager = new mainloop();
$update_manager->start($bot, $update);
