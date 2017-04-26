<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class PinCommand
 * @package PiggyAuth\Commands
 */
class PinCommand extends PluginCommand
{
    /**
     * PinCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Get your pin");
        $this->setUsage("/pin");
        $this->setPermission("piggyauth.command.pin");
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
        $sender->sendMessage(str_replace("{pin}", $this->getPlugin()->sessionmanager->getSession($sender)->getPin(), $this->getPlugin()->languagemanager->getMessage($sender, "your-pin")));
        return true;
    }
}
