<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;


use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class RegisterCommand
 * @package PiggyAuth\Commands
 */
class RegisterCommand extends VanillaCommand
{
    /**
     * RegisterCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Register an account", "/register <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.register");
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
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        if (!isset($args[0]) || !isset($args[1])) {
            $sender->sendMessage("/register <password> <confirm password> [email]");
            return false;
        }
        if (!isset($args[2])) {
            $args[2] = "none";
        } else {
            $function = function ($result, $args, $plugin) {
                $sender = $plugin->getServer()->getPlayerExact($args[0]);
                if ($sender instanceof Player) { //Check to make sure player didn't log off
                    if ($result) {
                        $plugin->register($sender, $args[1], $args[2], $args[3]);
                    } else {
                        $sender->sendMessage($plugin->languagemanager->getMessage($sender, "invalid-email"));
                    }
                }
                return true;
            };
            $arguements = array(
                $sender->getName(),
                $args[0],
                $args[1],
                $args[2]);
            $this->plugin->emailmanager->validateEmail($args[2], $function, $arguements);
            return true;
        }
        return true;
    }

}
