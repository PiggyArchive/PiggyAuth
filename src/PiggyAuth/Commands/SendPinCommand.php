<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SendPinCommand
 * @package PiggyAuth\Commands
 */
class SendPinCommand extends VanillaCommand
{
    /**
     * SendPinCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Send your pin to your email", "/sendpin");
        $this->setPermission("piggyauth.command.sendpin");
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
            $sender->sendMessage($this->plugin->languagemanager->getMessage($sender, "use-in-game"));
            return false;
        }
        $result = $this->plugin->emailmanager->sendEmail($this->plugin->sessionmanager->getSender($sender)->getEmail(), $this->plugin->languagemanager->getMessage($sender, "email-subject-sendpin"), str_replace("{pin}", $this->plugin->sessionmanager->getSender($sender)->getPin(), $this->plugin->languagemanager->getMessage($sender, "email-sendpin")), $sender);
        return true;
    }

}
