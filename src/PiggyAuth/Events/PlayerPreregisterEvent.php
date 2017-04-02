<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;


class PlayerPreregisterEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $sender;
    protected $password; //don't worry, it's encrypted ;)
    protected $email;
    protected $pin;

    public function __construct($plugin, $sender, $player, $password, $email, $pin)
    {
        $this->player = $player;
        $this->sender = $sender;
        $this->password = $password;
        $this->email = $email;
        $this->pin = $pin;
        parent::__construct($plugin);
    }

    public function getSender()
    {
        return $this->sender;
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

}
