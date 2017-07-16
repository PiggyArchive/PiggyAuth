<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class LoginCommand
 * @package PiggyAuth\Commands
 */
class LoginCommand extends PiggyAuthCommand
{
    /**
     * LoginCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Login to your account");
        $this->setUsage("/login <password>");
        $this->setPermission("piggyauth.command.login");
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
        if (!isset($args[0])) {
            $sender->sendMessage("/login <password>");
            return false;
        }
        $this->getPlugin()->getConfig()->get('async') ? $this->getPlugin()->asyncLogin($sender, $args[0], 0) : $this->getPlugin()->login($sender, $args[0], 0);
        return true;
    }
}
