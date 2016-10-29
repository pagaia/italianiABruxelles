<?php

class User implements MongoDB\BSON\Persistable {

    public $id;
    public $user;
    public $tgUserId;
    public $location;
    public $createdAt;

    public function __construct($tgUserId, $user) {
        $this->id = new MongoDB\BSON\ObjectID;
        $this->tgUserId = $tgUserId;
        $this->user = $user;


        // Get current time in milliseconds since the epoch
        $msec = floor(microtime(true) * 1000);
        $this->createdAt = new MongoDB\BSON\UTCDateTime($msec);
    }

    function bsonSerialize() {
        return [
            '_id' => $this->id,
            'user' => $this->user,
            'tgUserId' => $this->tgUserId,
            'location' => $this->location,
            'createdAt' => $this->createdAt,
        ];
    }

    function bsonUnserialize(array $data) {
        $this->id = $data['_id'];
        $this->user = $data['user'];
        $this->tgUserId = $data['tgUserId'];
        $this->location = $data['location'];
        $this->createdAt = $data['createdAt'];
    }

    public function addLocation(array $location) {
        $this->location = $location;
    }

    public function getLocation() {
        return $this->location;
    }

}

?>
