<?php
// Make a connection, select the 'test' collection in the 'demo' database,
// and clean out the collection.

require 'lib/vendor/autoload.php';
include 'User.php';

//$client = new MongoDB\Client("mongodb://localhost:27017");
//$collection = $client->IaBxL->users;

/*
$collection = (new MongoDB\Client)->IaBxL->users;

$result = $collection->insertOne(new User('idididdid','username'));

$person = $collection->findOne(['_id' => $result->getInsertedId()]);

var_dump($person);
 * 
 */
?>
