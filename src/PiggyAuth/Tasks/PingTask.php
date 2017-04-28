<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Main;
use pocketmine\scheduler\PluginTask;

/**
 * Class PingTask
 * @package PiggyAuth\Tasks
 */
class PingTask extends PluginTask
{
    /**
     * PingTask constructor.
     * @param Main $plugin
     * @param $db
     */
    public function __construct($plugin, $db)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->db = $db;
    }

    /**
     * @param $currentTick
     */
    public function onRun($currentTick)
    {
        $ping = $this->db->db->ping();
        if(!$ping){
            foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                $player->close("", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->sessionmanager->getSession($player)->getLanguage(), "connection-lost"));
            }
            $this->plugin->getServer()->setConfigBool("white-list", true);
            $this->plugin->getLogger()->error($this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "connection-lost"));
        }
    }

}
