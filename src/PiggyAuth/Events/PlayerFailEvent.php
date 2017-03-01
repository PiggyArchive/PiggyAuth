<?php

namespace PiggyAuth\Events;

use pocketmine\Player;

class PlayerFailEvent extends PlayerEvent {
    public static $handlerList = null;
    protected $player;
    protected $action;
    protected $error;

    public function __construct($plugin, $player, $action, $error) {
        $this->player = $player;
        $this->action = $action;
        $this->error = $error;
        parent::__construct($plugin);
    }

    public function getPlayer() {
        return $this->player instanceof Player ? $this->player : null;
    }

    public function getAction() {
        return $this->action;
    }

    public function getError() {
        return $this->error;
    }
}
