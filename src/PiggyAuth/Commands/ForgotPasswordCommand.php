<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class ForgotPasswordCommand
 * @package PiggyAuth\Commands
 */
class ForgotPasswordCommand extends VanillaCommand
{
    /**
     * ForgotPasswordCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Change your password if you forgot it", "/forgotpassword <pin> <new password>", ["forgetpassword", "forgotpw", "forgetpw", "forgotpwd", "forgetpwd", "fpw", "fpwd"]);
        $this->setPermission("piggyauth.command.forgotpassword");
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
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->plugin->languagemanager->getMessage($sender, "use-in-game"));
            return false;
        }
        if (!isset($args[1])) {
            $sender->sendMessage("/forgotpassword <pin> <new password>");
            return false;
        }
        $this->plugin->forgotpassword($sender, $args[0], $args[1]);
        return true;
    }

}
