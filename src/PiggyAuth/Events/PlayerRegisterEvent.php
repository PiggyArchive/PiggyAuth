<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerRegisterEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $password; //don't worry, it's encrypted ;)
    protected $email;
    protected $pin;
    protected $mode;

    public function __construct(Player $player, $password, $email, $pin, $mode) {
        $this->player = $player;
        $this->password = $password;
        $this->email = $email;
        $this->pin = $pin;
        $this->mode = $mode;
    }

    private function getPassword() {
        return $this->password;
    }

    private function getEmail() {
        return $this->email;
    }

    private function getPin() {
        return $this->pin;
    }

    public function getMode() {
        return $this->mode;
    }
}
