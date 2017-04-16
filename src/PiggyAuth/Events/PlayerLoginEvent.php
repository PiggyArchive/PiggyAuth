<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class PlayerLoginEvent
 * @package PiggyAuth\Events
 */
class PlayerLoginEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $mode;

    /**
     * PlayerLoginEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param Player $player
     * @param $mode
     */
    public function __construct($plugin, Player $player, $mode)
    {
        $this->player = $player;
        $this->mode = $mode;
        parent::__construct($plugin);
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }
}
