<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class PlayerForgetPasswordEvent
 * @package PiggyAuth\Events
 */
class PlayerForgetPasswordEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;

    /**
     * PlayerForgetPasswordEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param Player $player
     * @param $password
     * @param $oldpin
     * @param $pin
     */
    public function __construct($plugin, Player $player, $password, $oldpin, $pin)
    {
        $this->player = $player;
        $this->password = $password;
        $this->oldpin = $oldpin;
        $this->pin = $pin;
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
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    private function getOldPin()
    {
        return $this->oldpin;
    }

    /**
     * @return mixed
     */
    private function getPin()
    {
        return $this->pin;
    }

}
