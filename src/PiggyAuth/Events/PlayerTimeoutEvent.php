<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerTimeoutEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    
    public function __construct(Player $player) {
        $this->player = $player;
    }
}
