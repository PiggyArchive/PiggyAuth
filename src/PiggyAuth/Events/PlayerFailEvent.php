<?php

namespace PiggyAuth\Events;

use pocketmine\Player;

/**
 * Class PlayerFailEvent
 * @package PiggyAuth\Events
 */
class PlayerFailEvent extends PlayerEvent
{
    public static $handlerList = null;
    protected $player;
    protected $action;
    protected $error;

    /**
     * PlayerFailEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param $player
     * @param $action
     * @param $error
     */
    public function __construct($plugin, $player, $action, $error)
    {
        $this->player = $player;
        $this->action = $action;
        $this->error = $error;
        parent::__construct($plugin);
    }

    /**
     * @return null|Player
     */
    public function getPlayer()
    {
        return $this->player instanceof Player ? $this->player : null;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }
}
