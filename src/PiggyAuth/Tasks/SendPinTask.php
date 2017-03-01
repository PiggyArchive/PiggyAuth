<?php

namespace PiggyAuth\Tasks;

use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class SendPinTask extends PluginTask {
    public function __construct($plugin, Player $player) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick) {
        $this->player->sendMessage(str_replace("{pin}", $this->plugin->database->getPin($this->player->getName()), $this->plugin->getMessage("register-success")));
    }

}
