<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Tasks\ValidateEmailTask;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ChangeEmailCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Change your email", "/changeemail <email>");
        $this->setPermission("piggyauth.command.changeemail");
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
            $sender->sendMessage("/changeemail <email>");
            return false;
        } else {
            $function = function ($result, $args, $plugin) {
                $sender = $plugin->getServer()->getPlayerExact($args[0]);
                if ($sender instanceof Player) { //Check to make sure player didn't log off
                    if ($result) {
                        $plugin->sessionmanager->getSession($sender)->updatePlayer("email", $args[1]);
                        $sender->sendMessage($plugin->getMessage("email-change-success"));
                    } else {
                        $sender->sendMessage($plugin->getMessage("invalid-email"));
                    }
                }
            };
            $arguements = array($sender->getName(), $args[0]);
            $task = new ValidateEmailTask($this->plugin->getConfig()->getNested("emails.mailgun.public-api"), $args[0], $function, $arguements, $this->plugin);
            $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
            return true;
        }
    }

}
