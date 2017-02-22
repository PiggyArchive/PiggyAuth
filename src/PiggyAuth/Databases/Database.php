<?php

namespace PiggyAuth\Databases;

use pocketmine\Player;

interface Database {
    public function getPlayer($player);

    public function updatePlayer($player, $password, $email, $pin, $uuid, $attempts);

    public function insertData(Player $player, $password, $email, $pin, $xbox);

    public function getPin($player);

    public function getPassword($player);

    public function clearPassword($player);

    public function getUUID($player);

    public function getAttempts($player);
}
