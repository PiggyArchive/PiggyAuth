<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;

/**
 * Class SetLanguageCommand
 * @package PiggyAuth\Commands
 */
class SetLanguageCommand extends PluginCommand
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
            $sender->sendMessage("/setlanguage <language>");
        }
        if (!$this->getPlugin()->languagemanager->isLanguage($args[0])) {
            $languages = implode(", ", $this->getPlugin()->languagemanager->getLanguages());
            $sender->sendMessage(str_replace("{languages}", $languages, $this->getPlugin()->languagemanager->getMessage($sender, "invalid-language")));
            return true;
        }
        $this->getPlugin()->sessionmanager->getSession($sender)->updatePlayer("language", $args[0]);
        $sender->sendMessage($this->getPlugin()->languagemanager->getMessageFromLanguage($args[0], "language-changed"));
    }
}