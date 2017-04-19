<?php

namespace PiggyAuth\Databases;

use PiggyAuth\Main;
use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Class YAML
 * @package PiggyAuth\Databases
 */
class YAML implements Database
{
    private $plugin;

    /**
     * YAML constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        @mkdir($this->plugin->getDataFolder() . "players");
    }


    /**
     * @return int
     */
    public function getRegisteredCount()
    {
        $files = scandir($this->plugin->getDataFolder() . "players/");
        $count = 0;
        foreach($files as $file){
            if(strpos($file, ".yml") !== false){
                $count++;
            }
        }
        return $count;
    }

    /**
     * @param $player
     * @param $callback
     * @param $args
     */
    public function getPlayer($player, $callback, $args)
    {
        if(file_exists($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml")){
            $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml");
            $data = $file->getAll();
        }else{
            $data = null;
        }
        if ($callback !== null) {
            $callback($data, $args, $this->plugin);
        }
    }

    /**
     * @param $player
     */
    public function getOfflinePlayer($player)
    {
        if(file_exists($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml")){
            $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml");
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
     */
    public function updatePlayer($player, $column, $arg, $type = 0, $callback = null, $args = null)
    {
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml");
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
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player->getName()) . ".yml", Config::YAML, [
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
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", $callback = null, $args = null)
    {
        $file = new Config($this->plugin->getDataFolder() . "players/" . strtolower($player) . ".yml", Config::YAML, [
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
     */
    public function clearPassword($player, $callback = null, $args = null)
    {
        $result = @unlink($this->plugin->getDataFolder() . "players/" . $player . ".yml");
        if ($callback !== null) {
            $callback($result, $args, $this->plugin);
        }
    }

}
