<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SendPinCommand
 * @package PiggyAuth\Commands
 */
class SendPinCommand extends PiggyAuthCommand
{
    /**
     * SendPinCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Send your pin to your email");
        $this->setUsage("/sendpin");
        $this->setPermission("piggyauth.command.sendpin");
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
        $this->getPlugin()->getEmailManager()->sendEmail($this->getPlugin()->getSessionManager()->getSession($sender)->getEmail(), $this->getPlugin()->getLanguageManager()->getMessage($sender, "email-subject-sendpin"), str_replace("{pin}", $this->getPlugin()->getSessionManager()->getSession($sender)->getPin(), $this->getPlugin()->getLanguageManager()->getMessage($sender, "email-sendpin")), $sender);
        return true;
    }

}
