<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

/**
 * Class PlayerRegisterEvent
 * @package PiggyAuth\Events
 */
class PlayerRegisterEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $password; //don't worry, it's encrypted ;)
    protected $email;
    protected $pin;
    protected $mode;

    /**
     * PlayerRegisterEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param Player $player
     * @param $password
     * @param $email
     * @param $pin
     * @param $mode
     */
    public function __construct($plugin, Player $player, $password, $email, $pin, $mode)
    {
        $this->player = $player;
        $this->password = $password;
        $this->email = $email;
        $this->pin = $pin;
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
    private function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    private function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    private function getPin()
    {
        return $this->pin;
    }

    /**
     * @return mixed
     */
    public function getMode()
    {
        return $this->mode;
    }
}
