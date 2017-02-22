<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerChangePasswordEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $player;
    protected $oldpassword; //don't worry, it's encrypted ;)
    protected $password; //don't worry, it's encrypted ;)
    protected $pin;
    protected $mode;

    public function __construct($plugin, Player $player, $oldpassword, $password, $oldpin, $pin) {
        $this->player = $player;
        $this->oldpassword = $oldpassword;
        $this->password = $password;
        $this->oldpin = $oldpin;
        $this->pin = $pin;
        parent::__construct($plugin);
    }

    public function getPlayer() {
        return $this->player;
    }

    private function getOldPassword() {
        return $this->oldpassword;
    }
    private function getPassword() {
        return $this->password;
    }

    private function getOldPin() {
        return $this->oldpin;
    }

    private function getPin() {
        return $this->pin;
    }
}
