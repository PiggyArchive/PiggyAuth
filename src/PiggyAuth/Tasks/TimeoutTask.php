<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Events\PlayerTimeoutEvent;
use pocketmine\scheduler\PluginTask;

/**
 * Class TimeoutTask
 * @package PiggyAuth\Tasks
 */
class TimeoutTask extends PluginTask
{
    /**
     * TimeoutTask constructor.
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
    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($this->plugin->sessionmanager->getSession($player) !== null && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
                $this->plugin->sessionmanager->getSession($player)->addTimeoutTick();
                if ($this->plugin->sessionmanager->getSession($player)->getTimeoutTick() == $this->plugin->getConfig()->getNested("timeout.timeout-time")) {
                    $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerTimeoutEvent($this->plugin, $player));
                    if (!$event->isCancelled()) {
                        $player->kick($this->plugin->languagemanager->getMessage($player, "timeout-message"));
                    }
                }
            }
        }
    }

}
