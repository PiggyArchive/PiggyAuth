<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerLoginEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $mode;

    public function __construct(Player $player, $mode) {
        $this->player = $player;
        $this->mode = $mode;
    }

    public function getMode() {
        return $this->mode;
    }
}
