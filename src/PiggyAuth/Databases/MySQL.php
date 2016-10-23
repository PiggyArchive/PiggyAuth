<?php
namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;

class MySQL implements Database {
    public $plugin;
    public $db;

    public function __construct(Main $plugin, $outdated) {
        $this->plugin = $plugin;
        $mysql = $this->plugin->getConfig()->get("mysql");
        $this->db = new \mysqli($mysql["host"], $mysql["user"], $mysql["password"], $mysql["name"], $mysql["port"]);
        if($this->db->connect_error) {
            $this->plugin->getLogger()->error($this->db->connect_error);
        } else {
            $this->db->query("CREATE TABLE IF NOT EXISTS players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(100), email VARCHAR(100), pin INT, uuid VARCHAR(100), attempts INT, xbox VARCHAR(5));");
        }
        if($outdated) {
            $this->db->query("ALTER TABLE players ADD email VARCHAR(100) after password");
            $this->db->query("ALTER TABLE players ADD xbox VARCHAR(5) after attempts");
        }
    }

    public function getRegisteredCount() {
        $result = $this->db->query("SELECT count(1) FROM players");
        $data = $result->fetch_assoc();
        $result->free();
        return $data["count(1)"];
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

    public function updatePlayer($player, $password, $email, $pin, $uuid, $attempts) {
        $this->db->query("UPDATE players SET password = '" . $this->db->escape_string($password) . "', email = '" . $this->db->escape_string($email) . "', pin = '" . intval($pin) . "', uuid = '" . $this->db->escape_string($uuid) . "', attempts = '" . intval($attempts) . "' WHERE name = '" . $this->db->escape_string($player) . "'");
    }

    public function insertData(Player $player, $password, $email, $xbox) {
        $this->db->query("INSERT INTO players (name, password, email, pin, uuid, attempts, xbox) VALUES ('" . $this->db->escape_string(strtolower($player->getName())) . "', '" . $this->db->escape_string(password_hash($password, PASSWORD_BCRYPT)) . "', '" . $this->db->escape_string($email) . "', '" . $this->plugin->generatePin($player) . "', '" . $player->getUniqueId()->toString() . "', '0', '" . $xbox . "')");
    }

    public function getPin($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            if(!isset($data["pin"])) {
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
        if(!is_null($data)) {
            return $data["password"];
        }
        return null;
    }

    public function clearPassword($player) {
        $this->db->query("DELETE FROM players WHERE name = '" . $this->db->escape_string($player) . "'");
    }

    public function getEmail($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            if(!isset($data["email"])) {
                return "none";
            }
            return $data["email"];
        }
        return "none";
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
                $this->updatePlayer($player, $this->getPassword($player), $this->getEmail($player), $this->getPin($player), $this->getUUID($player), 0);
                return 0;
            }
            return $data["attempts"];
        }
        return null;
    }
}
