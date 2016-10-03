<?php
namespace PiggyAuth\Databases;

interface Database {
    public function getPlayer($player);

    public function updatePlayer($player, $password, $pin, $uuid, $attempts);

    public function getPin($player);

    public function getPassword($player);

    public function getUUID($player);

    public function getAttempts($player);
}
