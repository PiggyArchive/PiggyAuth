<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

/**
 * Class MessageTick
 * @package PiggyAuth\Tasks
 */
class MessageTick extends PluginTask
{
    /**
     * MessageTick constructor.
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
                if ($this->plugin->sessionmanager->getSession($player)->getMessageTick() == $this->plugin->getConfig()->getNested("message.seconds-til-next-message")) {
                    $this->plugin->sessionmanager->getSession($player)->setMessageTick(0);
                    if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                        $player->sendMessage($this->plugin->languagemanager->getMessage($player, "login-message"));
                    } else {
                        $player->sendMessage($this->plugin->languagemanager->getMessage($player, "register-message"));
                    }
                } else {
                    $this->plugin->sessionmanager->getSession($player)->addMessageTick();
                }
            }
        }
    }

}
