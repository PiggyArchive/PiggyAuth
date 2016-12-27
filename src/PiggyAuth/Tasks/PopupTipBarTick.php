<?php
namespace PiggyAuth\Tasks;

use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\PluginTask;

use PiggyAuth\FakeAttribute;

class PopupTipBarTick extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if(!$this->plugin->isAuthenticated($player) && !isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
              if (!$this->plugin->getConfig()->get("steve-bypass")) {
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
                if($this->plugin->getConfig()->get("boss-bar")) {
                  if(isset($this->plugin->wither[strtolower($player->getName())])) {
                    $pk = new UpdateAttributesPacket();
                    $pk->entries[] = new FakeAttribute(0.00, $this->plugin->getConfig()->get("timeout-time"), ($this->plugin->getConfig()->get("timeout-time") - $this->plugin->timeouttick[strtolower($player->getName())]) - 1, "minecraft:health");
                    $pk->entityId = $this->plugin->wither[strtolower($player->getName())]->getId();
                    $player->dataPacket($pk);
                    //$this->plugin->wither[strtolower($player->getName())]->setHealth($this->plugin->getConfig()->get("timeout-time") - $this->plugin->timeouttick[strtolower($player->getName())]);
                  }
                }
              }
            }
        }
    }
}
