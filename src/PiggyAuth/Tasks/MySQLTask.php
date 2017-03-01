<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;

class MySQLTask extends AsyncTask {
    private $credentials;
    private $query;

    public function __construct($credentials, $query) {
        $this->credentials = serialize($credentials);
        $this->query = serialize($query);
    }

    public function onRun() {
        $credentials = unserialize($this->credentials);
        $db = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
        /*if ($result instanceof \mysqli_result) {
            var_dump($result);
            $this->setResult(serialize($result));
        }*/
    }

}
