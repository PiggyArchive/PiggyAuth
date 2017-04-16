<?php

namespace PiggyAuth\Converter;

use pocketmine\utils\Config;

/**
 * Class ServerAuthConverter
 * @package PiggyAuth\Converter
 */
class ServerAuthConverter implements Converter
{

    /**
     * ServerAuthConverter constructor.
     * @param $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param $file
     * @param $algorithm
     * @param $table
     * @return bool
     */
    public function convertFromSQLite3($file, $algorithm, $table)
    {
        return false;
    }

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $name
     * @param $port
     * @param $table
     * @param $algorithm
     * @return bool
     */
    public function convertFromMySQL($host, $user, $password, $name, $port, $table, $algorithm)
    {
        $credentials = $this->plugin->getConfig()->get("mysql");
        $database = null;
        $algorithms = hash_algos();
        if (in_array($algorithm, $algorithms)) {
            if ($credentials["host"] == $host && $credentials["user"] == $user && $credentials["password"] == $password && $credentials["name"] == $name && $credentials["port"] == $port) {
                $database = $this->plugin->database->db;
            } else {
                $database = new \mysqli($credentials["host"], $credentials["user"], $credentials["password"], $credentials["name"], $credentials["port"]);
            }
            $result = $database->query("SELECT * FROM " . $table);
            if ($result instanceof \mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    if ($this->plugin->database->getOfflinePlayer($row["user"])) {
                        $this->plugin->getLogger()->info(str_replace("{player}", $row["user"], $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "player-account-already-exists")));
                        continue;
                    }
                    $this->plugin->database->insertDataWithoutPlayerObject($row["user"], $row["password"], "none", mt_rand(1000, 9999), "ServerAuth_" . $algorithm);
                }
                $result->free();
                return true;
            }
        } else {
            $this->plugin->getLogger()->info(str_replace("{algorithms}", implode(", ", $algorithms), str_replace("{algorithm}", $algorithm, $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-hash-algorithm"))));
            return false;
        }
    }

    /**
     * @param $directoryname
     * @param $algorithm
     * @return bool
     */
    public function convertFromYML($directoryname, $algorithm)
    {
        $algorithms = hash_algos();
        if (in_array($algorithm, $algorithms)) {
            if (is_dir($this->plugin->getDataFolder() . "convert/" . $directoryname . "/")) {
                $files = scandir($this->plugin->getDataFolder() . "convert/" . $directoryname . "/");
                foreach ($files as $file) {
                    if ($file !== "." && $file !== "..") {
                        if (strpos($file, ".yml") !== false) {
                            $yml = new Config($this->plugin->getDataFolder() . "convert/" . $directoryname . "/" . $file);
                            $data = $yml->getAll();
                            $name = str_replace(".yml", "", $file);
                            if ($this->plugin->database->getOfflinePlayer($name)) {
                                $this->plugin->getLogger()->info(str_replace("{player}", $name, $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "player-account-already-exists")));
                                continue;
                            }
                            $this->plugin->database->insertDataWithoutPlayerObject($name, $data["password"], "none", mt_rand(1000, 9999), "ServerAuth_" . $algorithm);
                        } else {
                            $this->plugin->getLogger()->info(str_replace("{file}", $file, $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "file-not-yml")));
                        }
                    }
                }
            } else {
                $this->plugin->getLogger()->info(str_replace("{file}", $directoryname, $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-directory")));
                return false;
            }
        } else {
            $this->plugin->getLogger()->info(str_replace("{algorithms}", implode(", ", $algorithms), str_replace("{algorithm}", $algorithm, $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-hash-algorithm"))));
            return false;
        }
        return true;
    }
}