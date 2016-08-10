<?php
namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class MessageTick extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if(!$this->plugin->isAuthenticated($player) && !isset($this->plugin->confirmPassword[strtolower($player->getName())]) && isset($this->plugin->messagetick[strtolower($player->getName())])) {
                if($this->plugin->messagetick[strtolower($player->getName())] == $this->plugin->getConfig()->get("seconds-til-next-message")) {
                    $this->plugin->messagetick[strtolower($player->getName())] = 0;
                    if($this->plugin->isRegistered($player->getName())) {
                        $player->sendMessage($this->plugin->getConfig()->get("login"));
                    } else {
                        $player->sendMessage($this->plugin->getConfig()->get("register"));
                    }
                } else {
                    $this->plugin->messagetick[strtolower($player->getName())] += 1;
                }
            }
        }
    }

}
