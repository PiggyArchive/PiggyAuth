<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class ChangeEmailCommand
 * @package PiggyAuth\Commands
 */
class ChangeEmailCommand extends VanillaCommand
{
    /**
     * ChangeEmailCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Change your email", "/changeemail <email>");
        $this->setPermission("piggyauth.command.changeemail");
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
        if (!isset($args[0])) {
            $sender->sendMessage("/changeemail <email>");
            return false;
        } else {
            $function = function ($result, $args, $plugin) {
                $sender = $plugin->getServer()->getPlayerExact($args[0]);
                if ($sender instanceof Player) { //Check to make sure player didn't log off
                    if ($result) {
                        $plugin->sessionmanager->getSession($sender)->updatePlayer("email", $args[1]);
                        $sender->sendMessage($plugin->languagemanager->getMessage($sender, "email-change-success"));
                    } else {
                        $sender->sendMessage($plugin->languagemanager->getMessage($sender, "invalid-email"));
                    }
                }
            };
            $arguements = array($sender->getName(), $args[0]);
            $this->plugin->emailmanager->validateEmail($args[0], $function, $arguements);
            return true;
        }
    }

}
