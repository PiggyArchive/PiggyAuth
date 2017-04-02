<?php

namespace PiggyAuth\Tasks;

use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\PluginTask;

use PiggyAuth\FakeAttribute;

class PopupTipBarTick extends PluginTask
{
    public function __construct($plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($this->plugin->sessionmanager->getSession($player) !== null && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated() && !isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
                if ($this->plugin->getConfig()->getNested("message.popup")) {
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendPopup($this->plugin->getMessage("login-popup"));
                    } else {
                        $player->sendPopup($this->plugin->getMessage("register-popup"));
                    }
                }
                if ($this->plugin->getConfig()->getNested("message.tip")) {
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendTip($this->plugin->getMessage("login-tip"));
                    } else {
                        $player->sendTip($this->plugin->getMessage("register-tip"));
                    }
                }
                if ($this->plugin->getConfig()->getNested("message.boss-bar")) {
                    if (isset($this->plugin->wither[strtolower($player->getName())])) {
                        $pk = new UpdateAttributesPacket();
                        $pk->entries[] = new FakeAttribute(0.00, $this->plugin->getConfig()->getNested("timeout.timeout-time"), ($this->plugin->getConfig()->getNested("timeout.timeout-time") - $this->plugin->timeouttick[strtolower($player->getName())]) - 1, "minecraft:health");
                        $pk->entityId = $this->plugin->wither[strtolower($player->getName())]->getId();
                        $player->dataPacket($pk);
                    }
                }
            }
        }
    }

}
