<?php
namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class PreregisterCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Register an account", "/preregister <player> <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.preregister");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args) {
        if(!$this->testPermission($sender)) {
            return true;
        }
        if(!isset($args[0]) || !isset($args[1]) || !isset($args[2])) {
            $sender->sendMessage("/preregister <player> <password> <confirm password> [email]");
            return false;
        }
        if(!isset($args[3])) {
            $args[3] = "none";
        } else {
            if(!filter_var($args[2], FILTER_VALIDATE_EMAIL)) {
                $sender->sendMessage($this->plugin->getMessage("invalid-email"));
                return false;
            }
        }
        $this->plugin->preregister($sender, $args[0], $args[1], $args[2], $args[3]);
        return true;
    }

}
