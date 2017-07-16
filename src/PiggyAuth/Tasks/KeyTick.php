<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

/**
 * Class KeyTick
 * @package PiggyAuth\Tasks
 */
class KeyTick extends PluginTask
{
    private $plugin;

    /**
     * KeyTick constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     */
    public function __construct($plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        $this->plugin->keytime += 1;
        if ($this->plugin->keytime >= 300) { //5 Mins
            $this->plugin->keytime = 0;
            $this->plugin->changeKey();
        }
    }

}
