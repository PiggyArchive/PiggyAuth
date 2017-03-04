<?php

namespace PiggyAuth\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class ForceTask extends PluginTask {
    public function __construct($plugin, Player $player) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick) {
        $this->plugin->force($this->player, false);
    }

}
