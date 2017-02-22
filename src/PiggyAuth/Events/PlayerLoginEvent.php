<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerLoginEvent extends PlayerEvent implements Cancellable {
    public static $handlerList = null;
    protected $player;
    protected $mode;

    public function __construct($plugin, Player $player, $mode) {
        $this->player = $player;
        $this->mode = $mode;
        parent::__construct($plugin);
    }

    public function getPlayer() {
        return $this->player;
    }

    public function getMode() {
        return $this->mode;
    }
}
