<?php
namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;

class SQLite3 implements Database {
    public $plugin;
    public $db;

    public function __construct(Main $plugin, $outdated) {
        $this->plugin = $plugin;
        if(!file_exists($this->plugin->getDataFolder() . "players.db")) {
            $this->db = new \SQLite3($this->plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->db->exec("CREATE TABLE players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(100), email VARCHAR(100), pin INT, uuid VARCHAR(100), attempts INT);");
        } else {
            $this->db = new \SQLite3($this->plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE);
            //Updater
        }
        if($outdated) {
            $this->db->exec("ALTER TABLE players ADD COLUMN email INT");
            $this->db->exec("ALTER TABLE players ADD COLUMN pins INT");
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

    public function updatePlayer($player, $password, $email, $pin, $uuid, $attempts) {
        $statement = $this->db->prepare("UPDATE players SET pin = :pin, password = :password, email = :email, uuid = :uuid, attempts = :attempts WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $statement->bindValue(":attempts", $attempts, SQLITE3_INTEGER);
        $statement->execute();
    }

    public function insertData(Player $player, $password, $email) {
        $statement = $this->db->prepare("INSERT INTO players (name, password, email, pin, uuid, attempts) VALUES (:name, :password, :email, :pin, :uuid, :attempts)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $this->plugin->generatePin($player), SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_TEXT);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->execute();
    }

    public function getPin($player) {
        $data = $this->getPlayer($player);
        if(!is_null($data)) {
            if(!isset($data["pin"])) {
                $pin = mt_rand(1000, 9999); //If you use $this->generatePin(), there will be issues!
                $this->updatePlayer($player, $this->getPassword($player), $pin, $this->getUUID($player), $this->getAttempts($player));
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
        $statement = $this->db->prepare("DELETE FROM players WHERE name = :name");
        $statement->bindValue(":name", $player, SQLITE3_TEXT);
        $statement->execute();
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
                $this->updatePlayer($player, $this->getPassword($player), $this->getPin($player), $this->getUUID($player), 0);
                return 0;
            }
            return $data["attempts"];
        }
        return null;
    }
}
