<?php
namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ChangePasswordCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Change your password", "/changepassword <old password> <new password>", ["changepw", "changepwd", "cpw", "cpwd"]);
        $this->setPermission("piggyauth.command.changepassword");
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
            $sender->sendMessage("/changepassword <old password> <new password>");
            return false;
        }
        $this->plugin->changepassword($sender, $args[0], $args[1]);
        return true;
    }

}
