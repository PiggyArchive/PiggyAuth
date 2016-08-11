<?php
namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

class PopupTipTick extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if(!$this->plugin->isAuthenticated($player) && !isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
                if($this->plugin->getConfig()->get("popup")) {
                    if($this->plugin->isRegistered($player->getName())) {
                        $player->sendPopup($this->plugin->getMessage("login-popup"));
                    } else {
                        $player->sendPopup($this->plugin->getMessage("register-popup"));
                    }
                }
                if($this->plugin->getConfig()->get("tip")) {
                    if($this->plugin->isRegistered($player->getName())) {
                        $player->sendTip($this->plugin->getMessage("login-tip"));
                    } else {
                        $player->sendTip($this->plugin->getMessage("register-tip"));
                    }
                }
            }
        }
    }

}
