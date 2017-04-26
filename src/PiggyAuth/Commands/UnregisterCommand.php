<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class UnregisterCommand
 * @package PiggyAuth\Commands
 */
class UnregisterCommand extends PluginCommand
{
    /**
     * UnregisterCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Unregister");
        $this->setUsage("/unregister <password>");
        $this->setPermission("piggyauth.command.unregister");
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
        if (!isset($args[0])) {
            $sender->sendMessage("/unregister <password>");
            return false;
        }
        $this->getPlugin()->unregister($sender, $args[0]);
        return true;
    }
}
