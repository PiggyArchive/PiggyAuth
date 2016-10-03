<?php
namespace PiggyAuth\Databases;

use PiggyAuth\Main;

class SQLite3 implements Database {
    public $plugin;
    public $db;

    public function __construct(Main $plugin, $outdated) {
        if(!file_exists($plugin->getDataFolder() . "players.db")) {
            $this->db = new \SQLite3($plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->db->exec("CREATE TABLE players (name TEXT PRIMARY KEY, password TEXT, pin INT, uuid INT, attempts INT);");
        } else {
            $this->db = new \SQLite3($plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE);
            //Updater
        }
        if($outdated) {
            $this->db->exec("ALTER TABLE players ADD COLUMN pins INT"); //Just in case :P
            $this->db->exec("ALTER TABLE players ADD COLUMN attempts INT");
        }
    }

    public function getPlayer($player) {
        $player = strtolower($player);
        $statement = $this->db->prepare("SELECT * FROM players WHERE name = :name");
        $statement->bindValue(":name", $player, SQLITE3_TEXT);
        $result = $statement->execute();
        if($result instanceof \SQLite3Result) {
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if(isset($data["name"])) {
                unset($data["name"]);
                $statement->close();
                return $data;
            }
        }
        $statement->close();
        return null;
    }

    public function updatePlayer($player, $password, $pin, $uuid, $attempts) {
        $statement = $this->db->prepare("UPDATE players SET pin = :pin, password = :password, uuid = :uuid, attempts = :attempts WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $uuid, SQLITE3_INTEGER);
        $statement->bindValue(":attempts", $attempts, SQLITE3_INTEGER);
        $statement->execute();
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
