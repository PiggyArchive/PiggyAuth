<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;
use pocketmine\utils\Config;


/**
 * Class IndividualFiles
 * @package PiggyAuth\Databases
 */
class IndividualFiles implements Database
{
    private $plugin;
    private $extension;


    /**
     * IndividualFiles constructor.
     * @param Main $plugin
     * @param $extension
     */
    public function __construct(Main $plugin, $extension)
    {
        $this->plugin = $plugin;
        $this->extension = $extension;
        @mkdir($this->plugin->getDataFolder() . "players");
    }


    /**
     * @param null $callback
     * @param null $args
     * @return int
     */
    public function getRegisteredCount($callback = null, $args = null)
    {
        $files = scandir($this->plugin->getDataFolder() . "players/");
        $count = 0;
        foreach ($files as $file) {
            if (strpos($file, "." . $this->extension) !== false) {
                $count++;
            }
        }
        $callback($count, $args, $this->plugin);
    }

    /**
     * @param $player
     * @param $callback
     * @param $args
     * @return mixed|void
     */
    public function getPlayer($player, $callback = null, $args = null)
    {
        if (file_exists($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension)) {
            $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension);
            $data = $file->getAll();
        } else {
            $data = null;
        }
        if ($callback !== null) {
            $callback($data, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     * @return array|mixed|null
     */
    public function getOfflinePlayer($player)
    {
        if (file_exists($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension)) {
            $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension);
            return $file->getAll();
        }
        return null;
    }

    /**
     * @param $player
     * @param $column
     * @param $arg
     * @param int $type
     * @param null $callback
     * @param null $args
     * @return mixed|void
     */
    public function updatePlayer($player, $column, $arg, $type = 0, $callback = null, $args = null)
    {
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension);
        $file->set($column, $arg);
        $file->save();
        if ($callback !== null) {
            $callback($file, $args, $this->plugin);
        }
    }

    /**
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $type = Config::YAML;
        switch ($this->extension) {
            case "yml":
                $type = Config::YAML;
                break;
            case "json":
                $type = Config::JSON;
                break;
        }
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player->getName()) . "." . $this->extension, $type, [
            "password" => $password,
            "email" => $email,
            "pin" => $pin,
            "uuid" => $player->getUniqueId()->toString(),
            "attempts" => 0,
            "xbox" => $xbox,
            "language" => $this->plugin->languagemanager->getDefaultLanguage(),
            "auth" => "PiggyAuth"
        ]);
        if ($callback !== null) {
            $callback($file, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     * @param string $auth
     * @param null $callback
     * @param null $args
     * @return mixed|void
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", $callback = null, $args = null)
    {
        $type = Config::YAML;
        switch ($this->extension) {
            case "yml":
                $type = Config::YAML;
                break;
            case "json":
                $type = Config::JSON;
                break;
        }
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . "." . $this->extension, $type, [
            "password" => $password,
            "email" => $email,
            "pin" => $pin,
            "uuid" => "uuid",
            "attempts" => 0,
            "xbox" => false,
            "language" => $this->plugin->languagemanager->getDefaultLanguage(),
            "auth" => "PiggyAuth"
        ]);
        if ($callback !== null) {
            $callback($file, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     * @param null $callback
     * @param null $args
     * @return mixed|void
     */
    public function clearPassword($player, $callback = null, $args = null)
    {
        $result = @unlink($this->plugin->getDataFolder() . "players/" . $player . "." . $this->extension);
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

}
