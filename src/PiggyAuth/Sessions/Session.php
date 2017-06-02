<?php

namespace PiggyAuth\Sessions;

/**
 * Interface Session
 * @package PiggyAuth\Sessions
 */
interface Session
{
    public function getPlayer();

    public function getName();

    public function getData();

    public function getPassword();

    public function getPin();

    public function getEmail();

    public function getIP();

    public function getUUID();

    public function clearPassword();

    public function isConfirmingPassword();

    /**
     * @param bool $arg
     */
    public function setConfirmingPassword($arg = true);

    public function getFirstPassword();

    /**
     * @param $password
     */
    public function setFirstPassword($password);

    public function getSecondPassword();

    /**
     * @param $password
     */
    public function setSecondPassword($password);

    public function isGivingEmail();

    /**
     * @param bool $arg
     */
    public function setGivingEmail($arg = true);

    /**
     * @return bool
     */
    public function isVerifying() : bool;

    /**
     * @param bool $arg
     * @return mixed
     */
    public function setVerifying(bool $arg = true);

    /**
     * @return bool
     */
    public function isRegistering() : bool;

    /**
     * @param bool $arg
     * @return mixed
     */
    public function setRegistering(bool $arg = true);

    public function getMessageTick();

    /**
     * @param $arg
     */
    public function setMessageTick($arg);

    public function addMessageTick();

    public function getCape();

    /**
     * @param $cape
     */
    public function setCape($cape);

    public function getGamemode();

    /**
     * @param $gamemode
     */
    public function setGamemode($gamemode);

    public function getTimeoutTick();

    /**
     * @param $arg
     */
    public function setTimeoutTick($arg);

    public function addTimeoutTick();

    public function getWither();

    /**
     * @param $wither
     */
    public function setWither($wither);

    public function getTries();

    /**
     * @param $tries
     */
    public function setTries($tries);

    public function addTry();

    public function getJoinMessage();

    /**
     * @param $message
     */
    public function setJoinMessage($message);

    /**
     * @param $column
     * @param $arg
     * @param $callback
     * @param $args
     */
    public function updatePlayer($column, $arg, $callback, $args);

    /**
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     */
    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null);

}
