<?php

namespace PiggyAuth\Converter;

/**
 * Interface Converter
 * @package PiggyAuth\Converter
 */
interface Converter
{

    /**
     * @param $file
     * @param $table
     * @param $otherinfo
     * @return mixed
     */
    public function convertFromSQLite3($file, $table, $otherinfo);

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $name
     * @param $port
     * @param $table
     * @param $otherinfo
     * @return mixed
     */
    public function convertFromMySQL($host, $user, $password, $name, $port, $table, $otherinfo);

    /**
     * @param $directory
     * @param $otherinfo
     * @return mixed
     */
    public function convertFromYML($directory, $otherinfo);
}