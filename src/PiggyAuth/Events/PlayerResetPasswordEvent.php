<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;


/**
 * Class PlayerResetPasswordEvent
 * @package PiggyAuth\Events
 */
class PlayerResetPasswordEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $sender;

    /**
     * PlayerResetPasswordEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param $sender
     * @param $player
     */
    public function __construct($plugin, $sender, $player)
    {
        $this->sender = $sender;
        $this->player = $player;
        parent::__construct($plugin);
    }

    /**
     * @return mixed
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }
}
