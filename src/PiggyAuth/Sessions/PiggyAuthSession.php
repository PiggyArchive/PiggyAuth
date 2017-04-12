<?php

namespace PiggyAuth\Sessions;

use pocketmine\Player;

class PiggyAuthSession implements Session
{
    private $player;
    private $plugin;
    private $data;
    private $authenticated = false;
    private $registered = false;

    public function __construct(Player $player, $plugin, $data)
    {
        $this->player = $player;
        $this->plugin = $plugin;
        $this->data = $data;
        if (is_null($this->data) !== true && $this->data !== false) {
            $this->registered = true;
        }
    }

    public function getPlayer()
    {
        return $this->player;
    }

    public function getName()
    {
        return $this->player->getName();
    }

    public function isRegistered()
    {
        return $this->registered;
    }

    public function setRegistered($args)
    {
        $this->registered = $args;
    }

    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    public function setAuthenticated($arg = true)
    {
        $this->authenticated = $arg;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getPassword()
    {
        return $this->data["password"];
    }

    public function getPin()
    {
        return $this->data["pin"];
    }

    public function getEmail()
    {
        return $this->data["email"];
    }

    public function getIP()
    {
        return $this->data["email"];
    }

    public function getUUID()
    {
        return $this->data["uuid"];
    }

    public function getAttempts()
    {
        return $this->data["attempts"];
    }

    public function getLanguage()
    {
        if ($this->plugin->languagemanager->isLanguage($this->data["language"])) {
            return $this->data["language"];
        }
        return $this->plugin->languagemanager->getDefaultLanguage();
    }

    public function getOriginAuth()
    {
        return $this->data["auth"];
    }

    public function clearPassword($callback = null, $args = null)
    {
        $this->authenticated = false;
        $this->registered = false;
        $this->data = null;
        $this->plugin->database->clearPassword($this->getName(), $callback, $args);
    }


    public function updatePlayer($column, $arg, $callback = null, $args = null)
    {
        $this->plugin->database->updatePlayer($this->getName(), $column, $arg, $callback, $args);
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
    }

    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $this->plugin->database->insertData($this->player, $password, $email, $pin, $xbox, $callback, $args);
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
    }

}
