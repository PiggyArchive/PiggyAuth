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

    /**
     * @param $column
     * @param $arg
     * @param $callback
     * @param $args
     * @return mixed
     */
    public function updatePlayer($column, $arg, $callback, $args);

    /**
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     * @return mixed
     */
    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null);

}
