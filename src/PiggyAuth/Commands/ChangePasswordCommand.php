<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class ChangePasswordCommand
 * @package PiggyAuth\Commands
 */
class ChangePasswordCommand extends PluginCommand
{
    /**
     * ChangePasswordCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Change your password");
        $this->setUsage("/changepassword <old password> <new password>");
        $this->setAliases(["changepw", "changepwd", "cpw", "cpwd"]);
        $this->setPermission("piggyauth.command.changepassword");
    }
}
