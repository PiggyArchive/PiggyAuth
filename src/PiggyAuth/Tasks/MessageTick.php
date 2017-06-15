<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\PluginTask;

/**
 * Class MessageTick
 * @package PiggyAuth\Tasks
 */
class MessageTick extends PluginTask
{
    private $plugin;

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
            if ($this->plugin->getSessionManager()->getSession($player) !== null && !$this->plugin->getSessionManager()->getSession($player)->isAuthenticated()) {
                if ($this->plugin->getSessionManager()->getSession($player)->getMessageTick() == $this->plugin->getConfig()->getNested("message.seconds-til-next-message")) {
                    $this->plugin->getSessionManager()->getSession($player)->setMessageTick(0);
                    if ($this->plugin->getSessionManager()->getSession($player)->isRegistered()) {
                        $player->sendMessage($this->plugin->getLanguageManager()->getMessage($player, "login-message"));
                    } else {
                        $player->sendMessage($this->plugin->getLanguageManager()->getMessage($player, "register-message"));
                    }
                } else {
                    $this->plugin->getSessionManager()->getSession($player)->addMessageTick();
                }
            }
        }
    }

}
