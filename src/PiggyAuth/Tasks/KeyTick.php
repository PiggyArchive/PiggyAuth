<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class KeyTick extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        $this->plugin->keytime += 1;
        if ($this->plugin->keytime >= 300) { //5 Mins
            $this->plugin->keytime = 0;
            $this->plugin->changeKey();
        }
    }

}
