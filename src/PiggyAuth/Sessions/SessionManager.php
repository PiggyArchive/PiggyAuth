<?php

namespace PiggyAuth\Sessions;

use pocketmine\Player;

class SessionManager
{
    private $plugin;
    private $sessions;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function getSession(Player $player)
    {
        if (isset($this->sessions[strtolower($player->getName())])) {
            return $this->sessions[strtolower($player->getName())];
        }
        return null;
    }

    public function loadSession(Player $player, $authenticated = false)
    {
        $callback = function ($result, $args, $plugin) {
            $player = $plugin->getServer()->getPlayerExact($args[0]);
            if ($player instanceof Player) {
                $plugin->sessionmanager->createSession($player, $result);
                $plugin->sessionmanager->getSession($player)->setAuthenticated($args[1]);
            }
        };
        $args = array($player->getName(), $authenticated);
        $this->plugin->database->getPlayer($player->getName(), $callback, $args);
    }

    public function createSession(Player $player, $data)
    {
        $this->sessions[strtolower($player->getName())] = new PiggyAuthSession($player, $this->plugin, $data);
    }

    public function unloadSession(Player $player)
    {
        if ($this->getSession($player) !== null) {
            unset($this->sessions[strtolower($player->getName())]);
        }
    }

}
