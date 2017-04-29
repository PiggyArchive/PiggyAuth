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
            $sender->sendMessage($this->plugin->languagemanager->getMessage($sender, "use-in-game"));
            return false;
        }
        if (!isset($args[1])) {
            $sender->sendMessage("/changepassword <old password> <new password>");
            return false;
        }
        $this->plugin->changepassword($sender, $args[0], $args[1]);
        return true;
    }
}
