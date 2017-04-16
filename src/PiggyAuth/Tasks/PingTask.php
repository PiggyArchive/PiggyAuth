<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

/**
 * Class PingTask
 * @package PiggyAuth\Tasks
 */
class PingTask extends PluginTask
{
    /**
     * PingTask constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param $db
     */
    public function __construct($plugin, $db)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->db = $db;
    }

    /**
     * @param $currentTick
     */
    public function onRun($currentTick)
    {
        $this->db->db->ping();
    }

}
