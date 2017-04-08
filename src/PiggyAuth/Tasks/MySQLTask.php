<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class MySQLTask extends AsyncTask
{
    private $credentials;
    private $query;
    private $callback;
    private $args;
    public $result;

    public function __construct($credentials, $query, $callback = null, $args = null)
    {
        $this->credentials = serialize($credentials);
        $this->query = serialize($query);
        $this->callback = $callback;
        $this->args = $args;
    }

    public function onRun()
    {
        $credentials = unserialize($this->credentials);
        $db = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
        $result = $db->query(unserialize($this->query));
        if ($result instanceof \mysqli_result) {
            $data = $result->fetch_assoc();
            $result->free();
            if (isset($data["name"])) {
                unset($data["name"]);
            }
        } else {
            $data = null;
        }
        $this->result = serialize($data);
    }

    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin("PiggyAuth");
        if ($this->callback !== null && $this->args !== null) {
            if ($plugin->isEnabled()) {
                $callback = $this->callback;
                $result = unserialize($this->result);
                $callback($result, $this->args, $plugin);
            }
        }
    }

}
