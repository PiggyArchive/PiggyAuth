<?php

namespace PiggyAuth\Converter;

interface Converter
{

    public function convertFromSQLite3($file, $table, $otherinfo);

    public function convertFromMySQL($host, $user, $password, $name, $port, $table, $otherinfo);

    public function convertFromYML($directory, $otherinfo);
}