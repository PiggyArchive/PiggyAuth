<?php
namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ResetPasswordCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Reset a player's password", "/resetpassword <player>", ["resetpw", "resetpwd", "rpw", "rpwd"]);
        $this->setPermission("piggyauth.command.resetpassword");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args) {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0])) {
            $sender->sendMessage("/resetpassword <player>");
            return false;
        }
        $this->plugin->resetpassword($args[0], $sender);
        return true;
    }

}
