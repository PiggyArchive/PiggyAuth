<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\PluginCommand;
use pocketmine\Server;

/**
 * Class PiggyAuthCommand
 * @package PiggyAuth\Commands
 */
class PiggyAuthCommand extends PluginCommand
{
    /**
     * @return Main
     */
    public function getPlugin()
    {
        return Server::getInstance()->getPluginManager()->getPlugin("PiggyAuth");
    }
}