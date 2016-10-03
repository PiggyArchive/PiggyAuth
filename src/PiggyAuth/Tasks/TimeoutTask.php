<?php
namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class TimeoutTask extends PluginTask {
    public function __construct($plugin, $player) {
        parent::__construct($plugin, $player);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick) {
        if(!$this->plugin->isAuthenticated($this->player)) {
            $this->player->kick($this->plugin->getMessage("timeout-message"));
        }
    }

}
