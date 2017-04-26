<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class MySQLTask
 * @package PiggyAuth\Tasks
 */
class MySQLTask extends AsyncTask
{
    private $credentials;
    private $query;
    private $callback = false;
    private $args;
    public $result;

    /**
     * MySQLTask constructor.
     * @param mixed|null $credentials
     * @param $query
     * @param null $callback
     * @param null $args
     */
    public function __construct($credentials, $query, $callback = null, $args = null)
    {
        if ($callback !== null) {
            parent::__construct($callback);
        }
        $this->credentials = serialize($credentials);
        $this->query = serialize($query);
        $this->callback = $callback !== null;
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

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin("PiggyAuth");
        if ($this->callback && $this->args !== null) {
            if ($plugin->isEnabled()) {
                $callback = $this->fetchLocal($server);
                $result = unserialize($this->result);
                $callback($result, $this->args, $plugin);
            }
        }
    }

}
