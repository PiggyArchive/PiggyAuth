<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class SendPinCommand
 * @package PiggyAuth\Commands
 */
class SendPinCommand extends PluginCommand
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
    public function execute(CommandSender $sender, $currentAlias, array $args)
    {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->languagemanager->getMessage($sender, "use-in-game"));
            return false;
        }
        $result = $this->getPlugin()->emailmanager->sendEmail($this->getPlugin()->sessionmanager->getSession($sender)->getEmail(), $this->getPlugin()->languagemanager->getMessage($sender, "email-subject-sendpin"), str_replace("{pin}", $this->getPlugin()->sessionmanager->getSession($sender)->getPin(), $this->getPlugin()->languagemanager->getMessage($sender, "email-sendpin")), $sender);
        return true;
    }

}
