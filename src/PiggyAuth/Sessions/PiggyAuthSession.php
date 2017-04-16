<?php

namespace PiggyAuth\Sessions;

use pocketmine\Player;

/**
 * Class PiggyAuthSession
 * @package PiggyAuth\Sessions
 */
class PiggyAuthSession implements Session
{
    private $player;
    private $plugin;
    private $data;
    private $authenticated = false;
    private $registered = false;

    /**
     * PiggyAuthSession constructor.
     * @param Player $player
     * @param $plugin
     * @param $data
     */
    public function __construct(Player $player, $plugin, $data)
    {
        $this->player = $player;
        $this->plugin = $plugin;
        $this->data = $data;
        if (is_null($this->data) !== true && $this->data !== false) {
            $this->registered = true;
        }
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->player->getName();
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * @param $args
     */
    public function setRegistered($args)
    {
        $this->registered = $args;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * @param bool $arg
     */
    public function setAuthenticated($arg = true)
    {
        $this->authenticated = $arg;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->data["password"];
    }

    /**
     * @return mixed
     */
    public function getPin()
    {
        return $this->data["pin"];
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->data["email"];
    }

    /**
     * @return mixed
     */
    public function getIP()
    {
        return $this->data["email"];
    }

    /**
     * @return mixed
     */
    public function getUUID()
    {
        return $this->data["uuid"];
    }

    /**
     * @return mixed
     */
    public function getAttempts()
    {
        return $this->data["attempts"];
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        if ($this->plugin->languagemanager->isLanguage($this->data["language"])) {
            return $this->data["language"];
        }
        return $this->plugin->languagemanager->getDefaultLanguage();
    }

    /**
     * @return mixed
     */
    public function getOriginAuth()
    {
        return $this->data["auth"];
    }

    /**
     * @param null $callback
     * @param null $args
     */
    public function clearPassword($callback = null, $args = null)
    {
        $this->authenticated = false;
        $this->registered = false;
        $this->data = null;
        $this->plugin->database->clearPassword($this->getName(), $callback, $args);
    }


    /**
     * @param $column
     * @param $arg
     * @param null $callback
     * @param null $args
     */
    public function updatePlayer($column, $arg, $callback = null, $args = null)
    {
        $this->plugin->database->updatePlayer($this->getName(), $column, $arg, $callback, $args);
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
    }

    /**
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     */
    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $this->plugin->database->insertData($this->player, $password, $email, $pin, $xbox, $callback, $args);
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
    }

}
