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
    public function getPlayer($player, callable $callback, $args);

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
     * @param callable|null $callback
     * @param null $args
     * @return mixed
     */
    public function updatePlayer($player, $column, $arg, $type = SQLITE3_TEXT, callable $callback = null, $args = null);

    /**
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param callable|null $callback
     * @param null $args
     * @return mixed
     */
    public function insertData(Player $player, $password, $email, $pin, $xbox, callable $callback = null, $args = null);

    /**
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     * @param string $auth
     * @param callable|null $callback
     * @param null $args
     * @return mixed
     */
    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $auth = "PiggyAuth", callable $callback = null, $args = null);

    /**
     * @param $player
     * @param callable|null $callback
     * @param null $args
     * @return mixed
     */
    public function clearPassword($player, callable $callback = null, $args = null);

}
