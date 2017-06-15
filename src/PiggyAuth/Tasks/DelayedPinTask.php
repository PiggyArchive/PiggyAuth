<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

/**
 * Class DelayedPinTask
 * @package PiggyAuth\Tasks
 */
class DelayedPinTask extends PluginTask
{
    private $player;
    private $plugin;

    /**
     * DelayedPinTask constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     * @param $player
     */
    public function __construct($plugin, $player)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    /**
     * @param $currentTick
     */
    public function onRun($currentTick)
    {
        $this->player->sendMessage(str_replace("{pin}", $this->plugin->getSessionManager()->getSession($this->player)->getPin(), $this->plugin->getLanguageManager()->getMessage($this->player, "register-success")));
    }
}