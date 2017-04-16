<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;


use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

/**
 * Class PreregisterCommand
 * @package PiggyAuth\Commands
 */
class PreregisterCommand extends VanillaCommand
{
    /**
     * PreregisterCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Register an account", "/preregister <player> <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.preregister");
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $currentAlias
     * @param array $args
     * @return bool
     */
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
                        $sender->sendMessage($plugin->languagemanager->getMessage($sender, "invalid-email"));
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
            $this->plugin->emailmanager->validateEmail($args[3], $function, $arguements);
            return true;
        }
        return true;
    }

}
