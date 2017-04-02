<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ForgotPasswordCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Change your password if you forgot it", "/forgotpassword <pin> <new password>", ["forgetpassword", "forgotpw", "forgetpw", "forgotpwd", "forgetpwd", "fpw", "fpwd"]);
        $this->setPermission("piggyauth.command.forgotpassword");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $currentAlias, array $args)
    {
        if (!$this->testPermission($sender)) {
            return true;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cYou must use the command in-game.");
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
