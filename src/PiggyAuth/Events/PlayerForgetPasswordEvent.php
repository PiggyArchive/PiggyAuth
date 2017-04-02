<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerForgetPasswordEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;

    public function __construct($plugin, Player $player, $password, $oldpin, $pin)
    {
        $this->player = $player;
        $this->password = $password;
        $this->oldpin = $oldpin;
        $this->pin = $pin;
        parent::__construct($plugin);
    }

    public function getPlayer()
    {
        return $this->player;
    }

    private function getPassword()
    {
        return $this->password;
    }

    private function getOldPin()
    {
        return $this->oldpin;
    }

    private function getPin()
    {
        return $this->pin;
    }

}
