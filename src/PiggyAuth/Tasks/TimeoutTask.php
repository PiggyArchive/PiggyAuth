<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class TimeoutTask extends PluginTask
{
    public function __construct($plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($this->plugin->sessionmanager->getSession($player) !== null && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
                if (isset($this->plugin->timeouttick[strtolower($player->getName())])) {
                    $this->plugin->timeouttick[strtolower($player->getName())]++;
                    if ($this->plugin->timeouttick[strtolower($player->getName())] == $this->plugin->getConfig()->getNested("timeout.timeout-time")) {
                        $player->kick($this->plugin->getMessage("timeout-message"));
                    }
                }
            }
        }
    }

}
