<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class KeyCommand
 * @package PiggyAuth\Commands
 */
class KeyCommand extends VanillaCommand
{
    /**
     * KeyCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Get the key", "/key <password>");
        $this->setPermission("piggyauth.command.key");
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
        if ($sender instanceof Player) {
            $sender->sendMessage($this->plugin->languagemanager->getMessage($sender, "use-on-console"));
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/key <password>");
            return false;
        }
        if(!$this->plugin->getConfig()->getNested("key.enabled")) {
            $sender->sendMessage($this->plugin->languagemanager->getMessage($sender, "key-disabled"));
            return false;
        }
        $sender->sendMessage($this->plugin->getKey($args[0]) == false ? $this->plugin->languagemanager->getMessage($sender, "incorrect-password-other") : $this->plugin->getKey($args[0]));
        return true;
    }

}
