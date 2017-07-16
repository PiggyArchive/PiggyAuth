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
    private $db;
    private $plugin;

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
    public function onRun(int $currentTick)
    {
        $ping = $this->db->db->ping();
        if(!$ping){
            foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                $player->close("", $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getSessionManager()->getSession($player)->getLanguage(), "connection-lost"));
            }
            $this->plugin->getServer()->setConfigBool("white-list", true);
            $this->plugin->getLogger()->error($this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "connection-lost"));
        }
    }

}
