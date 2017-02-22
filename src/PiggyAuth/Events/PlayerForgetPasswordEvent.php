<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerForgetPasswordEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;

    public function __construct(Player $player, $password, $oldpin, $pin) {
        $this->player = $player;
        $this->password = $password;
        $this->oldpin = $oldpin;
        $this->pin = $pin;
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
