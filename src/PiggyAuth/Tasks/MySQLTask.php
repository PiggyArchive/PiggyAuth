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
        $result = $db->query(unserialize($this->query));
        if ($result instanceof \mysqli_result) {
            var_dump($result->fetch_assoc());            
            $this->setResult(serialize($result->fetch_assoc()));
        }
    }
    
    public function onCompletion(Server $server){
		if($this->hasCallback){
			$cb = $this->fetchLocal($server);
			$cb($result);
        }
    }

}
