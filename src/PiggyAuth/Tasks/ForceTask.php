<?php

namespace PiggyAuth\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class ForceTask extends PluginTask {
    public function __construct($plugin, Player $player, $xbox = false) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
        $this->xbox = $xbox;
    }

    public function onRun($currentTick) {
        if($this->plugin->getServer()->getPlayerExact($this->player->getName()) instanceof Player){ //Check to make sure the player is still online
            $this->plugin->force($this->player, false, $this->xbox == false ? 0 : 3);
        }
    }

}
