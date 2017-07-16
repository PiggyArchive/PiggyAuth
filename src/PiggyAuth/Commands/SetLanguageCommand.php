<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SetLanguageCommand
 * @package PiggyAuth\Commands
 */
class SetLanguageCommand extends PiggyAuthCommand
{
    /**
     * SetLanguageCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, $plugin);
        $this->setDescription("Set language");
        $this->setUsage("/setlanguage <language>");
        $this->setAliases(["setlang", "lang"]);
        $this->setPermission("piggyauth.command.setlanguage");
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
            $sender->sendMessage("/setlanguage <language>");
            return false;
        }
        if (!$this->getPlugin()->getLanguageManager()->isLanguage($args[0])) {
            $languages = implode(", ", $this->getPlugin()->getLanguageManager()->getLanguages());
            $sender->sendMessage(str_replace("{languages}", $languages, $this->getPlugin()->getLanguageManager()->getMessage($sender, "invalid-language")));
            return false;
        }
        $this->getPlugin()->getSessionManager()->getSession($sender)->updatePlayer("language", $args[0]);
        $sender->sendMessage($this->getPlugin()->getLanguageManager()->getMessageFromLanguage($args[0], "language-changed"));
        return true;
    }
}
