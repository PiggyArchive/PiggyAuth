<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class PinCommand
 * @package PiggyAuth\Commands
 */
class PinCommand extends PiggyAuthCommand
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
            $sender->sendMessage($this->getPlugin()->getLanguageManager()->getMessage($sender, "use-in-game"));
            return false;
        }
        $sender->sendMessage(str_replace("{pin}", $this->getPlugin()->getSessionManager()->getSession($sender)->getPin(), $this->getPlugin()->getLanguageManager()->getMessage($sender, "your-pin")));
        return true;
    }
}
