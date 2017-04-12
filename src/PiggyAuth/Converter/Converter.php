<?php

namespace PiggyAuth\Converter;

interface Converter
{

    public function convertFromSQLite3($file);

    public function convertFromMySQL($host, $user, $password, $name, $port);

    public function convertFromYML($directory);
}