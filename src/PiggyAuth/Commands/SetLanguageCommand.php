<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class SetLanguageCommand
 * @package PiggyAuth\Commands
 */
class SetLanguageCommand extends VanillaCommand
{
    /**
     * SetLanguageCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Set language", "/setlanguage <language>", ["setlang", "lang"]);
        $this->setPermission("piggyauth.command.setlanguage");
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
            $sender->sendMessage("§cYou must use the command in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/setlanguage <language>");
        }
        if (!$this->plugin->languagemanager->isLanguage($args[0])) {
            $languages = implode(", ", $this->plugin->languagemanager->getLanguages());
            $sender->sendMessage(str_replace("{languages}", $languages, $this->plugin->languagemanager->getMessage($sender, "invalid-language")));
            return true;
        }
        $this->plugin->sessionmanager->getSession($sender)->updatePlayer("language", $args[0]);
        $sender->sendMessage($this->plugin->languagemanager->getMessageFromLanguage($args[0], "language-changed"));
    }
}