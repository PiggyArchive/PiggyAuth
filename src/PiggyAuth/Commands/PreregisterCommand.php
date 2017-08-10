<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;


use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;

/**
 * Class PreregisterCommand
 * @package PiggyAuth\Commands
 */
class PreregisterCommand extends PiggyAuthCommand
{
    /**
     * PreregisterCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Register an account");
        $this->setUsage("/preregister <player> <password> <confirm password> [email]");
        $this->setPermission("piggyauth.command.preregister");
    }

    /**
     * @param CommandSender $sender
     * @param string $currentAlias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $currentAlias, array $args)
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
        }
        $function = function ($result, $args, $plugin) {
            $sender = $args[0] instanceof ConsoleCommandSender ? $args[0] : $plugin->getServer()->getPlayerExact($args[0]);
            if ($sender instanceof Player || $sender instanceof ConsoleCommandSender) { //Check to make sure player didn't log off
                if ($result) {
                    $plugin->preregister($sender, $args[1], $args[2], $args[3], $args[4]);
                } else {
                    $sender->sendMessage($plugin->getLanguageManager()->getMessage($sender, "invalid-email"));
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
        $this->getPlugin()->getEmailManager()->validateEmail($args[3], $function, $arguements);
        return true;
    }

}
