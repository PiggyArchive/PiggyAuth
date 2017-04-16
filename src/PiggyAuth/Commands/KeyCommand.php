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
            $sender->sendMessage("§cThis is for the console only.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/key <password>");
            return false;
        }
        $sender->sendMessage($this->plugin->getKey($args[0]) == false ? "Incorrect password." : $this->plugin->getKey($args[0]));
        return true;
    }

}
