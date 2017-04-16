<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class LogoutCommand
 * @package PiggyAuth\Commands
 */
class LogoutCommand extends VanillaCommand
{
    /**
     * LogoutCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Logout your account", "/logout");
        $this->setPermission("piggyauth.command.logout");
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
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        $this->plugin->logout($sender, false);
        return true;
    }

}
