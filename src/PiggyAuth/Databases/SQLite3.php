<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;

/**
 * Class SQLite3
 * @package PiggyAuth\Databases
 */
class SQLite3 implements Database
{
    private $plugin;
    public $db;

    /**
     * SQLite3 constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        if (!file_exists($this->plugin->getDataFolder() . "players.db")) {
            $this->db = new \SQLite3($this->plugin->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->db->exec("CREATE TABLE players (name VARCHAR(100) PRIMARY KEY, password VARCHAR(200), email VARCHAR(100), pin INT, ip VARCHAR(32), uuid VARCHAR(100), attempts INT, xbox BIT(1), language VARCHAR(3), auth VARCHAR(100));");
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
                if (!isset($data["auth"])) {
                    $this->db->exec("ALTER TABLE players ADD COLUMN auth VARCHAR(10)");
                }
            }
        }
    }


    /**
     * @param callable|null $callback
     * @param null $args
     */
    public function getRegisteredCount(callable $callback = null, $args = null)
    {
        $callback($this->db->querySingle("SELECT COUNT(*) as count FROM players"), $args, $this->plugin);
    }

    /**
     * @param $player
     * @param $callback
     * @param $args
     * @return mixed|void
     */
    public function getPlayer($player, callable $callback, $args)
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

    /**
     * @param $player
     * @return array|null
     */
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

    /**
     * @param $player
     * @param $column
     * @param $arg
     * @param int $type
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function updatePlayer($player, $column, $arg, $type = 0, callable $callback = null, $args = null)
    {
        $statement = $this->db->prepare("UPDATE players SET " . $column . " = :" . $column . " WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":" . $column, $arg, $type == 0 ? SQLITE3_TEXT : SQLITE3_INTEGER);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    /**
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, callable $callback = null, $args = null)
    {
        $statement = $this->db->prepare("INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language, auth) VALUES (:name, :password, :email, :pin, :uuid, :attempts, :xbox, :language, :auth)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_TEXT);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->bindValue(":xbox", $xbox, SQLITE3_TEXT);
        $statement->bindValue(":language", $this->plugin->getLanguageManager()->getDefaultLanguage(), SQLITE3_TEXT);
        $statement->bindValue(":auth", "PiggyAuth", SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     * @param string $auth
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", callable $callback = null, $args = null)
    {
        $statement = $this->db->prepare("INSERT INTO players (name, password, email, pin, uuid, attempts, xbox, language, auth) VALUES (:name, :password, :email, :pin, :uuid, :attempts, :xbox, :language, :auth)");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $statement->bindValue(":password", $password, SQLITE3_TEXT);
        $statement->bindValue(":email", $email, SQLITE3_TEXT);
        $statement->bindValue(":pin", $pin, SQLITE3_INTEGER);
        $statement->bindValue(":uuid", "uuid", SQLITE3_TEXT);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->bindValue(":xbox", false, SQLITE3_TEXT);
        $statement->bindValue("::auth", $auth, SQLITE3_TEXT);
        $statement->bindValue(":language", $this->plugin->getLanguageManager()->getDefaultLanguage(), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     * @param callable|null $callback
     * @param null $args
     * @return mixed|void
     */
    public function clearPassword($player, callable $callback = null, $args = null)
    {
        $statement = $this->db->prepare("DELETE FROM players WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
        $result = $statement->execute();
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

}
