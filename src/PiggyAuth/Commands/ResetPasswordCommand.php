<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;

/**
 * Class ResetPasswordCommand
 * @package PiggyAuth\Commands
 */
class ResetPasswordCommand extends VanillaCommand
{
    /**
     * ResetPasswordCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Reset a player's password", "/resetpassword <player>", ["resetpw", "resetpwd", "rpw", "rpwd"]);
        $this->setPermission("piggyauth.command.resetpassword");
        $this->plugin = $plugin;
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
        $this->plugin->resetpassword($args[0], $sender);
        return true;
    }

}
