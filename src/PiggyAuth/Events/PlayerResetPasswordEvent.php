<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerResetPasswordEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $sender;

    public function __construct($sender, $player) {
        $this->sender = $sender;
        $this->player = $player;
    }

    public function getSender() {
        return $this->sender;
    }
}
