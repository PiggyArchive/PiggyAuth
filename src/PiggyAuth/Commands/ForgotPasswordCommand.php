<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class ForgotPasswordCommand
 * @package PiggyAuth\Commands
 */
class ForgotPasswordCommand extends PiggyAuthCommand
{
    /**
     * ForgotPasswordCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Change your password if you forgot it");
        $this->setUsage("/forgotpassword <pin> <new password>");
        $this->setAliases(["forgetpassword", "forgotpw", "forgetpw", "forgotpwd", "forgetpwd", "fpw", "fpwd"]);
        $this->setPermission("piggyauth.command.forgotpassword");
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
        if (!isset($args[1])) {
            $sender->sendMessage("/forgotpassword <pin> <new password>");
            return false;
        }
        $this->getPlugin()->forgotpassword($sender, $args[0], $args[1]);
        return true;
    }
}
