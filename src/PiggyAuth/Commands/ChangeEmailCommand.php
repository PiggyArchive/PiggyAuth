<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class ChangeEmailCommand
 * @package PiggyAuth\Commands
 */
class ChangeEmailCommand extends PiggyAuthCommand
{
    /**
     * ChangeEmailCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Change your email");
        $this->setUsage("/changeemail <email>");
        $this->setPermission("piggyauth.command.changeemail");
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
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->getLanguageManager()->getMessage($sender, "use-in-game"));
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
                        $plugin->getSessionManager()->getSession($sender)->updatePlayer("email", $args[1]);
                        $sender->sendMessage($plugin->getLanguageManager()->getMessage($sender, "email-change-success"));
                    } else {
                        $sender->sendMessage($plugin->getLanguageManager()->getMessage($sender, "invalid-email"));
                    }
                }
            };
            $arguements = array($sender->getName(), $args[0]);
            $this->getPlugin()->getEmailManager()->validateEmail($args[0], $function, $arguements);
            return true;
        }
    }
}
