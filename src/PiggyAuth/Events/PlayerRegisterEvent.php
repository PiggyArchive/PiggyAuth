<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerRegisterEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $password; //don't worry, it's encrypted ;)
    protected $email;
    protected $pin;
    protected $mode;

    public function __construct($plugin, Player $player, $password, $email, $pin, $mode)
    {
        $this->player = $player;
        $this->password = $password;
        $this->email = $email;
        $this->pin = $pin;
        $this->mode = $mode;
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

    private function getEmail()
    {
        return $this->email;
    }

    private function getPin()
    {
        return $this->pin;
    }

    public function getMode()
    {
        return $this->mode;
    }
}
