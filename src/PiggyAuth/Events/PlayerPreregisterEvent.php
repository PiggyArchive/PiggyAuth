<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;


/**
 * Class PlayerPreregisterEvent
 * @package PiggyAuth\Events
 */
class PlayerPreregisterEvent extends PlayerEvent implements Cancellable
{
    public static $handlerList = null;
    protected $player;
    protected $sender;
    protected $password; //don't worry, it's encrypted ;)
    protected $email;
    protected $pin;

    /**
     * PlayerPreregisterEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param $sender
     * @param $player
     * @param $password
     * @param $email
     * @param $pin
     */
    public function __construct($plugin, $sender, $player, $password, $email, $pin)
    {
        $this->player = $player;
        $this->sender = $sender;
        $this->password = $password;
        $this->email = $email;
        $this->pin = $pin;
        parent::__construct($plugin);
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
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

}
