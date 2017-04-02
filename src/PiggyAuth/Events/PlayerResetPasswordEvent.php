<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;


class PlayerResetPasswordEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $sender;

    public function __construct($plugin, $sender, $player)
    {
        $this->sender = $sender;
        $this->player = $player;
        parent::__construct($plugin);
    }

    public function getPlayer()
    {
        return $this->player;
    }

    public function getSender()
    {
        return $this->sender;
    }
}
