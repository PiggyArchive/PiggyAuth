<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerTimeoutEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $player;

    public function __construct($plugin, Player $player) {
        $this->player = $player;
        parent::__construct($plugin);
    }

    public function getPlayer() {
        return $this->player;
    }

}
