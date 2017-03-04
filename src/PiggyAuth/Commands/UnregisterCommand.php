<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class UnregisterCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Unregister", "/unregister <password>");
        $this->setPermission("piggyauth.command.unregister");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args) {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/unregister <password>");
            return false;
        }
        $this->plugin->unregister($sender, $args[0]);
        return true;
    }

}
