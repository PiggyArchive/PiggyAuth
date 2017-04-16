<?php

namespace PiggyAuth\Databases;

use pocketmine\Player;

/**
 * Interface Database
 * @package PiggyAuth\Databases
 */
interface Database
{
    /**
     * @param $player
     * @param $callback
     * @param $args
     * @return mixed
     */
    public function getPlayer($player, $callback, $args);

    /**
     * @param $player
     * @return mixed
     */
    public function getOfflinePlayer($player);

    /**
     * @param $player
     * @param $column
     * @param $arg
     * @param int $type
     * @param null $callback
     * @param null $args
     * @return mixed
     */
    public function updatePlayer($player, $column, $arg, $type = SQLITE3_TEXT, $callback = null, $args = null);

    /**
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     * @return mixed
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, $callback = null, $args = null);

    /**
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     * @param string $auth
     * @param null $callback
     * @param null $args
     * @return mixed
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", $callback = null, $args = null);

    /**
     * @param $player
     * @param null $callback
     * @param null $args
     * @return mixed
     */
    public function clearPassword($player, $callback = null, $args = null);

}
