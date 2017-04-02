<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Tasks\ValidateEmailTask;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

class PreregisterCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Register an account", "/preregister <player> <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.preregister");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args)
    {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!isset($args[0]) || !isset($args[1]) || !isset($args[2])) {
            $sender->sendMessage("/preregister <player> <password> <confirm password> [email]");
            return false;
        }
        if (!isset($args[3])) {
            $args[3] = "none";
        } else {
            $function = function ($result, $args, $plugin) {
                $sender = $args[0] instanceof ConsoleCommandSender ? $args[0] : $plugin->getServer()->getPlayerExact($args[0]);
                if ($sender instanceof Player || $sender instanceof ConsoleCommandSender) { //Check to make sure player didn't log off
                    if ($result) {
                        $plugin->preregister($sender, $args[1], $args[2], $args[3], $args[4]);
                    } else {
                        $sender->sendMessage($plugin->getMessage("invalid-email"));
                    }
                }
                return true;
            };
            $arguements = array(
                $sender instanceof ConsoleCommandSender ? $sender : $sender->getName(),
                $args[0],
                $args[1],
                $args[2],
                $args[3]);
            $task = new ValidateEmailTask($this->plugin->getConfig()->getNested("emails.mailgun.public-api"), $args[3], $function, $arguements, $this->plugin);
            $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
            return true;
        }
        $this->plugin->preregister($sender, $args[0], $args[1], $args[2], $args[3]);
        return true;
    }

}
