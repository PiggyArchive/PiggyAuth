<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SendPinCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Send your pin to your email", "/sendpin");
        $this->setPermission("piggyauth.command.sendpin");
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
        $result = $this->plugin->emailmanager->sendEmail($this->plugin->sessionmanager->getSender($sender)->getEmail(), $this->plugin->languagemanager->getMessage($sender, "email-subject-sendpin"), str_replace("{pin}", $this->plugin->sessionmanager->getSender($sender)->getPin(), $this->plugin->languagemanager->getMessage($sender, "email-sendpin")), $sender);
        return true;
    }

}
