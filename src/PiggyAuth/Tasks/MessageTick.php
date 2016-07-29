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
            if(!$this->plugin->isAuthenticated($player)) {
                if($this->plugin->isRegistered($player->getName())) {
                    $player->sendMessage($this->plugin->getConfig()->get("login"));
                } else {
                    $player->sendMessage($this->plugin->getConfig()->get("register"));
                }
            }
        }
    }

}
