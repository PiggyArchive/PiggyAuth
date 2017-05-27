<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;


use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class RegisterCommand
 * @package PiggyAuth\Commands
 */
class RegisterCommand extends PluginCommand
{
    /**
     * RegisterCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Register an account");
        $this->setUsage("/register <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.register");
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
            $sender->sendMessage($this->getPlugin()->languagemanager->getMessage($sender, "use-in-game"));
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
                        $plugin->getConfig()->get('async') ? $plugin->asyncRegister($sender, $args[1], $args[2], $args[3]) : $plugin->register($sender, $args[1], $args[2], $args[3]);
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
            $this->getPlugin()->emailmanager->validateEmail($args[2], $function, $arguements);
            return true;
        }
        return true;
    }

}
