<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class PlayerUnregisterEvent
 * @package PiggyAuth\Events
 */
class PlayerUnregisterEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;

    /**
     * PlayerUnregisterEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param Player $player
     */
    public function __construct($plugin, Player $player)
    {
        $this->player = $player;
        parent::__construct($plugin);
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

}
