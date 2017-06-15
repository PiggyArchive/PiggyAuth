<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class KeyCommand
 * @package PiggyAuth\Commands
 */
class KeyCommand extends PiggyAuthCommand
{
    private $plugin;

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
            $sender->sendMessage($this->getPlugin()->getLanguageManager()->getMessage($sender, "use-on-console"));
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/key <password>");
            return false;
        }
        if (!$this->getPlugin()->getConfig()->getNested("key.enabled")) {
            $sender->sendMessage($this->getPlugin()->getLanguageManager()->getMessage($sender, "key-disabled"));
            return false;
        }
        $sender->sendMessage(password_verify($args[0], $this->getPlugin()->database->getOfflinePlayer($this->plugin->getConfig()->getNested("key.owner"))["password"]) == false ? $this->getPlugin()->getLanguageManager()->getMessage($sender, "incorrect-password-other") : $this->getPlugin()->getKey());
        return true;
    }
}
