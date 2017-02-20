<?php
namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class PingTask extends PluginTask {
    public function __construct($plugin, $db) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->db = $db;
    }

    public function onRun($currentTick) {
        $this->db->db->ping();
    }

}
