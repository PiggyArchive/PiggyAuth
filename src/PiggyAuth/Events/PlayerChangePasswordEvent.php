<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class PlayerChangePasswordEvent
 * @package PiggyAuth\Events
 */
class PlayerChangePasswordEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $oldpassword; //don't worry, it's encrypted ;)
    protected $password; //don't worry, it's encrypted ;)
    protected $pin;
    protected $mode;
    private $oldpin;

    /**
     * PlayerChangePasswordEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param Player $player
     * @param $oldpassword
     * @param $password
     * @param $oldpin
     * @param $pin
     */
    public function __construct($plugin, Player $player, $oldpassword, $password, $oldpin, $pin)
    {
        $this->player = $player;
        $this->oldpassword = $oldpassword;
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
    private function getOldPassword()
    {
        return $this->oldpassword;
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
