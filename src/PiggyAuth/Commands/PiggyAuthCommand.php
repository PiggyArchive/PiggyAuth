<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\PluginCommand;
use pocketmine\Server;
use pocketmine\plugin\Plugin;

/**
 * Class PiggyAuthCommand
 * @package PiggyAuth\Commands
 */
class PiggyAuthCommand extends PluginCommand
{
    /**
     * @return Main
     */
    public function getPlugin(): Plugin
    {
        return Server::getInstance()->getPluginManager()->getPlugin("PiggyAuth");
    }
}
