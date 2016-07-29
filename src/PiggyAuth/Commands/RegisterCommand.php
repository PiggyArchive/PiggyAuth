<?php
namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class RegisterCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Register", "/register <password>");
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
        if(!isset($args[0])) {
            $sender->sendMessage("/register <password>");
            return false;
        }
        if(strlen($args[0]) < $this->plugin->getConfig()->get("minimum-password-length")) {
            $sender->sendMessage($this->plugin->getConfig()->get("password-too-short"));
            return true;
        }
        $this->plugin->register($sender, $args[0]);
        return true;
    }

}
