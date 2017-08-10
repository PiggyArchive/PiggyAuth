<?php

namespace PiggyAuth\Sessions;


use pocketmine\Player;

/**
 * Class SessionManager
 * @package PiggyAuth\Sessions
 */
class SessionManager
{
    private $plugin;
    private $sessions;

    /**
     * SessionManager constructor.
     * @param $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param Player $player
     * @return null
     */
    public function getSession(Player $player)
    {
        if (isset($this->sessions[$player->getLowerCaseName()])) {
            return $this->sessions[$player->getLowerCaseName()];
        }
        return null;
    }

    /**
     * @param Player $player
     * @param bool $authenticated
     * @param null $joinmessage
     */
    public function loadSession(Player $player, $authenticated = false, $joinmessage = null)
    {
        $callback = function ($result, $args, $plugin) {
            $player = $plugin->getServer()->getPlayerExact($args[0]);
            if ($player instanceof Player) {
                $plugin->getSessionManager()->createSession($player, $result);
                $plugin->getSessionManager()->getSession($player)->setAuthenticated($args[1]);
                if (!$args[1]) {
                    $plugin->getSessionManager()->getSession($player)->startSession($args[2]);
                }
            }
        };
        $args = array($player->getName(), $authenticated, $joinmessage);
        $this->plugin->database->getPlayer($player->getName(), $callback, $args);
    }

    /**
     * @param Player $player
     * @param $data
     */
    public function createSession(Player $player, $data)
    {
        $this->sessions[$player->getLowerCaseName()] = new PiggyAuthSession($player, $this->plugin, $data);
    }
    }

    /**
     * @param Player $player
     */
    public function unloadSession(Player $player)
    {
        if ($this->getSession($player) !== null) {
            unset($this->sessions[$player->getLowerCaseName()]);
        }
    }

}
