<?php

namespace PiggyAuth\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class StartSessionTask extends PluginTask {
    public function __construct($plugin, Player $player) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick) {
        $this->plugin->startSession($this->player);
    }

}
