<?php

namespace PiggyAuth\Sessions;

use PiggyAuth\Main;
use pocketmine\Player;

// Used to prevent crashes while waiting for callback

/**
 * Class TempSession
 * @package PiggyAuth\Sessions
 */
class TempSession extends PiggyAuthSession {

    /**
     * TempSession constructor.
     * @param Player $player
     * @param Main $plugin
     */
    public function __construct(Player $player, Main $plugin)
    {
        parent::__construct($player, $plugin, null);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return false;
    }

    /**
     * @param bool $arg
     * @return bool
     */
    public function setAuthenticated($arg = true){
        return false;
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return false;
    }

    /**
     * @param $args
     * @return bool
     */
    public function setRegistered($args){
        return false;
    }

    /**
     * @return bool
     */
    public function isRegistering(): bool
    {
        return false;
    }

    /**
     * @param bool $arg
     * @return bool
     */
    public function setRegistering(bool $arg = true){
        return false;
    }

    /**
     * @return bool
     */
    public function isVerifying(): bool
    {
        return false;
    }

    /**
     * @param bool $arg
     * @return bool
     */
    public function setVerifying(bool $arg = true){
        return false;
    }

    /**
     * @return bool
     */
    public function isConfirmingPassword()
    {
        return false;
    }

    /**
     * @param bool $arg
     * @return bool
     */
    public function setConfirmingPassword($arg = true)
    {
        return false;
    }
}
