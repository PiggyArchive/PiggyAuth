<?php

namespace PiggyAuth\Sessions;

use PiggyAuth\Databases\MySQL;
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
        if (isset($this->sessions[strtolower($player->getName())])) {
            return $this->sessions[strtolower($player->getName())];
        }
        return null;
    }

    /**
     * @param Player $player
     * @param bool $authenticated
     */
    public function loadSession(Player $player, $authenticated = false, $joinmessage = null)
    {
        $callback = function ($result, $args, $plugin) {
            $player = $plugin->getServer()->getPlayerExact($args[0]);
            if ($player instanceof Player) {
                $plugin->sessionmanager->createSession($player, $result);
                if (!$args[1]) {
                    $plugin->sessionmanager->getSession($player)->startSession($args[2]);
                }
                $plugin->sessionmanager->getSession($player)->setAuthenticated($args[1]);
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
        $this->sessions[strtolower($player->getName())] = new PiggyAuthSession($player, $this->plugin, $data);
    }

    /**
     * @param Player $player
     */
    public function unloadSession(Player $player)
    {
        if ($this->getSession($player) !== null) {
            unset($this->sessions[strtolower($player->getName())]);
        }
    }

}
