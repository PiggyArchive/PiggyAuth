<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class MessageTick extends PluginTask
{
    public function __construct($plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($this->plugin->sessionmanager->getSession($player) !== null && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated() && !isset($this->plugin->confirmPassword[strtolower($player->getName())]) && isset($this->plugin->messagetick[strtolower($player->getName())])) {
                if ($this->plugin->messagetick[strtolower($player->getName())] == $this->plugin->getConfig()->getNested("message.seconds-til-next-message")) {
                    $this->plugin->messagetick[strtolower($player->getName())] = 0;
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendMessage($this->plugin->languagemanager->getMessage($player, "login-message"));
                    } else {
                        $player->sendMessage($this->plugin->languagemanager->getMessage($player, "register-message"));
                    }
                } else {
                    $this->plugin->messagetick[strtolower($player->getName())] += 1;
                }
            }
        }
    }

}
