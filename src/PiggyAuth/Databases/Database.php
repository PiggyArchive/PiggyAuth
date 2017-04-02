<?php

namespace PiggyAuth\Databases;

use pocketmine\Player;

interface Database
{
    public function getPlayer($player, $callback, $args);

    public function getOfflinePlayer($player);

    public function updatePlayer($player, $column, $arg, $type = SQLITE3_TEXT, $callback = null, $args = null);

    public function insertData(Player $player, $password, $email, $pin, $xbox, $callback = null, $args = null);

    public function insertDataWithoutPlayerObject($player, $password, $email, $pin, $callback = null, $args = null);

    public function clearPassword($player, $callback = null, $args = null);

}
