<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;

class SQLite3 implements Database
{
    private $plugin;
    public $db;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        if (!file_exists($this->plugin->getDataFolder() . "players.db")) {
            $this->db = new \SQLite3($this->plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->db->exec("CREATE TABLE players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(100), email VARCHAR(100), pin INT, ip VARCHAR(32), uuid VARCHAR(100), attempts INT, xbox BIT(1));");
        } else {
            $this->db = new \SQLite3($this->plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE);
            //Updater
            $result = $this->db->query("SELECT * FROM players;");
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if ($data instanceof \SQLite3Result) {
                if (!isset($data["pin"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN pin INT");
                }
                if (!isset($data["attempts"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN attempts INT");
                }
                if (!isset($data["email"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN email VARCHAR(100)");
                }
                if (!isset($data["ip"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN ip VARCHAR(32)");
                }
                if (!isset($data["language"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN language VARCHAR(3)");
                }
            }
        }
    }


    public function getRegisteredCount()
    {
        return $this->db->querySingle("SELECT COUNT(*) as count FROM players");
    }

    public function getPlayer($player, $callback, $args)
    {
        $player = strtolower($player);
        $statement = $this->db->prepare("SELECT * FROM players WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($result instanceof \SQLite3Result) {
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if (isset($data["name"])) {
                unset($data["name"]);
                $statement->close();
            }
        } else {
            $data = null;
        }
        if ($callback !== null) {
            $callback($data, $args, $this->plugin);
        }
    }

    public function getOfflinePlayer($player)
    {
        $player = strtolower($player);
        $statement = $this->db->prepare("SELECT * FROM players WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $result = $statement->execute();
        $data = $result->fetchArray(SQLITE3_ASSOC);
        if ($data !== false) {
            $result->finalize();
            if (isset($data["name"])) {
                unset($data["name"]);
                $statement->close();
            }
            return $data;
        }
        return null;
    }

    public function updatePlayer($player, $column, $arg, $type = 0, $callback = null, $args = null)
    {
        $statement = $this->db->prepare("UPDATE players SET " . $column . " = :" . $column . " WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":" . $column, $arg, $type == 0 ? SQLITE3_TEXT : SQLITE3_INTEGER);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    public function insertData(Player $player, $password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $statement = $this->db->prepare("INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language) VALUES (:name, :password, :email, :pin, :uuid, :attempts, :xbox, :language)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_TEXT);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->bindValue(":xbox", $xbox, SQLITE3_TEXT);
        $statement->bindValue(":language", $this->plugin->languagemanager->getDefaultLanguage(), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $callback = null, $args = null)
    {
        $statement = $this->db->prepare("INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language) VALUES (:name, :password, :email, :pin, :uuid, :attempts, :xbox, :language)");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", "uuid", SQLITE3_TEXT);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->bindValue(":xbox", false, SQLITE3_TEXT);
        $statement->bindValue(":language", $this->plugin->languagemanager->getDefaultLanguage(), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    public function clearPassword($player, $callback = null, $args = null)
    {
        $statement = $this->db->prepare("DELETE FROM players WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

}
