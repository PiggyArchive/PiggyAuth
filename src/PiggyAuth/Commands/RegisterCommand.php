<?php
namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class RegisterCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Register an account", "/register <password> <confirm password>");
        $this->setPermission("piggyauth.command.register");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args) {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        if(!isset($args[1])) {
            $sender->sendMessage("/register <password> <confirm password>");
            return false;
        }
        $this->plugin->register($sender, $args[0], $args[1]);
        return true;
    }

}
