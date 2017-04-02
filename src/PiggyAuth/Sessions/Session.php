<?php

namespace PiggyAuth\Sessions;

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

    public function updatePlayer($column, $arg, $callback, $args);

    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null);

}
