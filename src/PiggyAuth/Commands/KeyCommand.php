<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class KeyCommand
 * @package PiggyAuth\Commands
 */
class KeyCommand extends PluginCommand
{
    /**
     * KeyCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Get the key.");
        $this->setUsage("/key <password>");
        $this->setPermission("piggyauth.command.key");
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
        if ($sender instanceof Player) {
            $sender->sendMessage($this->getPlugin()->languagemanager->getMessage($sender, "use-on-console"));
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/key <password>");
            return false;
        }
        if (!$this->getPlugin()->getConfig()->getNested("key.enabled")) {
            $sender->sendMessage($this->getPlugin()->languagemanager->getMessage($sender, "key-disabled"));
            return false;
        }
        $sender->sendMessage($this->getPlugin()->getKey($args[0]) == false ? $this->getPlugin()->languagemanager->getMessage($sender, "incorrect-password-other") : $this->getPlugin()->getKey($args[0]));
        return true;
    }
}
