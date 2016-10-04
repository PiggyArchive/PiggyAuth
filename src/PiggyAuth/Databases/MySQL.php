<?php
namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;

class MySQL implements Database {
    public $plugin;
    public $db;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $mysql = $this->plugin->getConfig()->get("mysql");
        $this->db = new \mysqli($mysql["host"], $mysql["user"], $mysql["password"], $mysql["name"], $mysql["port"]);
        if($this->db->connect_error) {
            $this->plugin->getLogger()->error($this->db->connect_error);
        } else {
            $this->db->query("CREATE TABLE IF NOT EXISTS players (name VARCHAR PRIMARY KEY, password CHAR, pin INT, uuid VARCHAR, attempts INT)");
        }
    }

    public function getPlayer($player) {
        $player = strtolower($player);
        $result = $this->db->query("SELECT * FROM players WHERE name = '" . $this->db->escape_string($player) . "'");
        if($result instanceof \mysqli_result) {
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"])) {
                unset($data["name"]);
                return $data;
            }
        }
        return null;
    }

    public function updatePlayer($player, $password, $pin, $uuid, $attempts) {
        $this->db->query("UPDATE players SET password = '" . $this->db->escape_string($password) . "', pin = '" . intval($pin) . "', uuid = '" . $this->db->escape_string($uuid) . "', attempts = '" . intval($attempts) . "' WHERE name = '" . $this->db->escape_string($player) . "'");
    }

    public function insertData(Player $player, $password) {
        $this->db->query("INSERT INTO players (name, password, pin, uuid, attempts) VALUES ('" . $this->db->escape_string(strtolower($player->getName())) . "', '" . $this->db->escape_string(password_hash($password, PASSWORD_BCRYPT)) . "', '" . $this->plugin->generatePin($player) . "', '" . $player->getUniqueId()->toString() . "', '0')");
    }

    public function getPin($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            if(!isset($data["pin"])) {
                $pin = mt_rand(1000, 9999); //If you use $this->generatePin(), there will be issues!
                $this->updatePlayer($player, $pin, $this->getPassword($player), $this->getUUID($player), $this->getAttempts($player));
                return $pin;
            }
            return $data["pin"];
        }
        return null;
    }

    public function getPassword($player) { //ENCRYPTED!
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            return $data["password"];
        }
        return null;
    }
    public function clearPassword($player) {
        $this->db->query("DELETE FROM players WHERE name = '" . $this->db->escape_string($player) . "'");
    }

    public function getUUID($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            return $data["uuid"];
        }
        return null;
    }

    public function getAttempts($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            if(!isset($data["attempts"])) {
                $this->updatePlayer($player, $this->getPin($player), $this->getPassword($player), $this->getUUID($player), 0);
                return 0;
            }
            return $data["attempts"];
        }
        return null;
    }
}
