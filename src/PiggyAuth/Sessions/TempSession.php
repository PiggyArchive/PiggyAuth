<?php

namespace PiggyAuth\Sessions;

use PiggyAuth\Main;
use pocketmine\Player;

// Used to prevent crashes while waiting for callback
class TempSession extends PiggyAuthSession {

    public function __construct(Player $player, Main $plugin)
    {
        parent::__construct($player, $plugin, null);
    }

    public function setAuthenticated($arg = true){
        return false;
    }

    public function setRegistered($args){
        return false;
    }

    public function setRegistering(bool $arg = true){
        return false;
    }

    public function setVerifying(bool $arg = true){
        return false;
    }
}
