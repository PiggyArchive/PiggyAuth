<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ChangeEmailCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Change your email", "/changeemail <email>");
        $this->setPermission("piggyauth.command.changeemail");
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
            $sender->sendMessage("/changeemail <email>");
            return false;
        } else {
            if (!$this->plugin->isValidEmail($this->plugin->pubapi, $args[0])) {
                $sender->sendMessage($this->plugin->getMessage("invalid-email"));
                return false;
            }
        }
        $this->plugin->database->updatePlayer($sender->getName(), $this->plugin->database->getPassword($sender->getName()), $args[0], $this->plugin->database->getPin($sender->getName()), $this->plugin->database->getUUID($sender->getName()), $this->plugin->database->getAttempts($sender->getName()));
        $sender->sendMessage($this->plugin->getMessage("email-change-success"));
        return true;
    }

}
