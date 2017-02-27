<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Tasks\MySQLTask;
use PiggyAuth\Main;

use pocketmine\Player;

class MySQL implements Database {
    public $plugin;
    public $db;

    public function __construct(Main $plugin, $outdated) {
        $this->plugin = $plugin;
        $credentials = $this->plugin->getConfig()->get("mysql");
        $this->db = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
        $task = new MySQLTask($credentials, "CREATE TABLE IF NOT EXISTS players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(100), email VARCHAR(100), pin INT, uuid VARCHAR(100), attempts INT, xbox BIT(1));");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function getRegisteredCount() {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "SELECT count(1) FROM players");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
        return $task->getResult()["count(1)"];
    }

    public function getPlayer($player) {
        $player = strtolower($player);
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "SELECT * FROM players WHERE name = '" . $this->db->escape_string($player) . "'");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
        $data = unserialize($task->getResult());
        var_dump($data);
        if (isset($data["name"])) {
            unset($data["name"]);
            return $data;
        }
        return null;
    }

    public function updatePlayer($player, $password, $email, $pin, $uuid, $attempts) {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "UPDATE players SET password = '" . $this->db->escape_string($password) . "', email = '" . $this->db->escape_string($email) . "', pin = '" . intval($pin) . "', uuid = '" . $this->db->escape_string($uuid) . "', attempts = '" . intval($attempts) . "' WHERE name = '" . $this->db->escape_string($player) . "'");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function insertData(Player $player, $password, $email, $pin, $xbox) {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "INSERT INTO players (name, password, email, pin, uuid, attempts, xbox) VALUES ('" . $this->db->escape_string(strtolower($player->getName())) . "', '" . $this->db->escape_string($password) . "', '" . $this->db->escape_string($email) . "', '" . intval($pin) . "', '" . $player->getUniqueId()->toString() . "', '0', '" . $xbox . "')");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function insertDataWithoutPlayerObject($player, $password, $email, $pin) {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "INSERT INTO players (name, password, email, pin, uuid, attempts, xbox) VALUES ('" . $this->db->escape_string(strtolower($player)) . "', '" . $this->db->escape_string($password) . "', '" . $this->db->escape_string($email) . "', '" . intval($pin) . "', 'uuid', '0', 'false')");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function getPin($player) {
        $data = $this->getPlayer($player);
        if (!is_null($data)) {
            if (!isset($data["pin"])) {
                $pin = mt_rand(1000, 9999); //If you use $this->generatePin(), there will be issues!
                $this->updatePlayer($player, $this->getPassword($player), $this->getEmail($player), $pin, $this->getUUID($player), $this->getAttempts($player));
                return $pin;
            }
            return $data["pin"];
        }
        return null;
    }

    public function getPassword($player) { //ENCRYPTED!
        $data = $this->getPlayer($player);
        if (!is_null($data)) {
            return $data["password"];
        }
        return null;
    }

    public function clearPassword($player) {
        $task = new MySQLTask($this->plugin->getConfig()->get("mysql"), "DELETE FROM players WHERE name = '" . $this->db->escape_string($player) . "'");
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function getEmail($player) {
        $data = $this->getPlayer($player);
        if (!is_null($data)) {
            if (!isset($data["email"])) {
                return "none";
            }
            return $data["email"];
        }
        return "none";
    }

    public function getUUID($player) {
        $data = $this->getPlayer($player);
        if (!is_null($data)) {
            return $data["uuid"];
        }
        return null;
    }

    public function getAttempts($player) {
        $data = $this->getPlayer($player);
        if (!is_null($data)) {
            if (!isset($data["attempts"])) {
                $this->updatePlayer($player, $this->getPassword($player), $this->getEmail($player), $this->getPin($player), $this->getUUID($player), 0);
                return 0;
            }
            return $data["attempts"];
        }
        return null;
    }
}
