<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Packet\SetTitlePacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\PluginTask;

use PiggyAuth\FakeAttribute;

/**
 * Class OtherMessageTypeTick
 * @package PiggyAuth\Tasks
 */
class OtherMessageTypeTick extends PluginTask
{
    /**
     * OtherMessageTypeTick constructor.
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
                if ($this->plugin->getConfig()->getNested("message.popup")) {
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendPopup($this->plugin->languagemanager->getMessage($player, "login-popup"));
                    } else {
                        $player->sendPopup($this->plugin->languagemanager->getMessage($player, "register-popup"));
                    }
                }
                if ($this->plugin->getConfig()->getNested("message.tip")) {
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendTip($this->plugin->languagemanager->getMessage($player, "login-tip"));
                    } else {
                        $player->sendTip($this->plugin->languagemanager->getMessage($player, "register-tip"));
                    }
                }
                if ($this->plugin->getConfig()->getNested("message.title")) {
                    $message = "";
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $message = $this->plugin->languagemanager->getMessage($player, "login-title");
                    } else {
                        $message = $this->plugin->languagemanager->getMessage($player, "register-title");
                    }
                    $pk = new SetTitlePacket();
                    $pk->type = SetTitlePacket::TYPE_SET_TITLE;
                    $pk->text = $message;
                    $player->dataPacket($pk);
                }
                if ($this->plugin->getConfig()->getNested("message.subtitle")) {
                    $message = "";
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $message = $this->plugin->languagemanager->getMessage($player, "login-subtitle");
                    } else {
                        $message = $this->plugin->languagemanager->getMessage($player, "register-subtitle");
                    }
                    $pk = new SetTitlePacket();
                    $pk->type = SetTitlePacket::TYPE_SET_SUBTITLE;
                    $pk->text = $message;
                    $player->dataPacket($pk);
                }
                if ($this->plugin->getConfig()->getNested("message.actionbar")) {
                    $message = "";
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $message = $this->plugin->languagemanager->getMessage($player, "login-actionbar");
                    } else {
                        $message = $this->plugin->languagemanager->getMessage($player, "register-actionbar");
                    }
                    $pk = new SetTitlePacket();
                    $pk->type = SetTitlePacket::TYPE_SET_ACTIONBAR_MESSAGE;
                    $pk->text = $message;
                    $player->dataPacket($pk);
                }
                if ($this->plugin->getConfig()->getNested("message.boss-bar")) {
                    if ($this->plugin->sessionmanager->getSession($player)->getWither() !== null) {
                        $pk = new UpdateAttributesPacket();
                        $pk->entries[] = new FakeAttribute(0.00, $this->plugin->getConfig()->getNested("timeout.timeout-time"), ($this->plugin->getConfig()->getNested("timeout.timeout-time") - $this->plugin->sessionmanager->getSession($player)->getTimeoutTick() - 1), "minecraft:health");
                        $pk->entityId = $this->plugin->sessionmanager->getSession($player)->getWither()->getId();
                        $player->dataPacket($pk);
                    }
                }
                $pk = new SetTitlePacket();
                $pk->type = SetTitlePacket::TYPE_SET_ANIMATION_TIMES;
                $pk->fadeInTime = 0;
                $pk->stayTime = 20;
                $pk->fadeOutTime = 0;
                $player->dataPacket($pk);
            }
        }
    }

}
