<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class LoginCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Login to your account", "/login <password>");
        $this->setPermission("piggyauth.command.login");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args)
    {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/login <password>");
            return false;
        }
        $this->plugin->login($sender, $args[0], 0);
        return true;
    }

}
