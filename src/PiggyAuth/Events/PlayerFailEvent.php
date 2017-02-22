<?php

namespace PiggyAuth\Events;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerFailEvent extends PlayerEvent{
    public static $handlerList = null;
    public $action;
    public $error;
    
    public function __construct(Player $player, $action, $error) {
        $this->player = $player;
        $this->action = $action;
        $this->error = $error;
    }
    
    public function getAction(){
        return $this->action;
    }
    
    public function getError(){
        return $this->error;
    }
}
