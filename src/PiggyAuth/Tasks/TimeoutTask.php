<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Events\PlayerTimeoutEvent;
use PiggyAuth\Main;
use PiggyAuth\Sessions\PiggyAuthSession;
use pocketmine\scheduler\PluginTask;

/**
 * Class TimeoutTask
 * @package PiggyAuth\Tasks
 */
class TimeoutTask extends PluginTask
{
    private $plugin;

    /**
     * TimeoutTask constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    /**
     * @param $currentTick
     */
    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $session = $this->plugin->getSessionManager()->getSession($player);
            if ($session instanceof PiggyAuthSession && !$session->isAuthenticated()) {
                $session->addTimeoutTick();
                if ($session->getTimeoutTick() == $this->plugin->getConfig()->getNested("timeout.timeout-time")) {
                    $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerTimeoutEvent($this->plugin, $player));
                    if (!$event->isCancelled()) {
                        $player->kick($this->plugin->getLanguageManager()->getMessage($player, "timeout-message"));
                    }
                }
            }
        }
    }

}
