<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;


/**
 * Class ResetPasswordCommand
 * @package PiggyAuth\Commands
 */
class ResetPasswordCommand extends PluginCommand
{
    /**
     * ResetPasswordCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
       $this->setDescription("Reset a player's password");
       $this->setUsage("/resetpassword <player>");
       $this->setAliases(["resetpw", "resetpwd", "rpw", "rpwd"]);
        $this->setPermission("piggyauth.command.resetpassword");
    }

    /**
     * @param CommandSender $sender
     * @param string $currentAlias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, $currentAlias, array $args)
    {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/resetpassword <player>");
            return false;
        }
        $this->getPlugin()->resetpassword($args[0], $sender);
        return true;
    }

}
