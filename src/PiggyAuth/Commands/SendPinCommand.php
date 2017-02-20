<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SendPinCommand extends VanillaCommand {
    public function __construct($name, $plugin) {
        parent::__construct($name, "Send your pin to your email", "/sendpin");
        $this->setPermission("piggyauth.command.sendpin");
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
        if ($this->plugin->database->getEmail($sender->getName()) !== "none") {
            $result = $this->plugin->emailUser($this->plugin->database->getEmail($sender->getName()), $this->plugin->getMessage("email-subject-sendpin"), str_replace("{pin}", $this->plugin->database->getPin($sender->getName()), $this->plugin->getMessage("email-sendpin")));
            if ($result == "success") {
                $sender->sendMessage($this->plugin->getMessage("email-success"));
                return true;
            }
            $sender->sendMessage($this->plugin->getMessage("email-fail"));
            return false;
        }
        $sender->sendMessage($this->plugin->getMessage("no-email"));
        return true;
    }

}
