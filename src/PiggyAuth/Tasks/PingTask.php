<?php
namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class PingTask extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        $this->plugin->database->db->ping();
    }

}
