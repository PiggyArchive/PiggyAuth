<?php

namespace PiggyAuth\Commands;

use PiggyAuth\Main;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * Class ConvertCommand
 * @package PiggyAuth\Commands
 */
class ConvertCommand extends VanillaCommand
{
    /**
     * ConvertCommand constructor.
     * @param string $name
     * @param Main $plugin
     */
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Convert SimpleAuth to PiggyAuth", "/convert <auth> <db> </info>");
        $this->setPermission("piggyauth.command.convert");
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
            $sender->sendMessage("§cThis should only be run on console.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("/convert <auth>");
            return false;
        } else {
            switch (strtolower($args[0])) {
                case "simpleauth":
                    if (!isset($args[1])) {
                        $sender->sendMessage("/convert " . $args[0] . " <db> <info>");
                        return false;
                    }
                    switch (strtolower($args[1])) {
                        case "yml":
                        case "yaml":
                            if (!isset($args[2])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " <directory>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromYML($args[2]);
                            break;
                        case "sqlite3":
                            if (!isset($args[2])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " <file> <table>");
                                return false;
                            }
                            if (!isset($args[3])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " " . $args[2] . " <table>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromSQLite3($args[2], $args[3]);
                            break;
                        case "mysql":
                            if (!isset($args[2]) || !isset($args[3]) || !isset($args[4]) || !isset($args[5]) || !isset($args[6]) || !isset($args[7])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " <db> <host> <user> <password> <name> <port> <table>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromMySQL($args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
                            break;
                        default:
                            $sender->sendMessage(str_replace("{db}", "YML, SQLite3, MySQL", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-database")));
                            break;
                    }
                    break;
                case "serverauth":
                    if (!isset($args[1])) {
                        $sender->sendMessage("/convert " . $args[0] . " <db> <info>");
                        return false;
                    }
                    switch (strtolower($args[1])) {
                        case "yml":
                        case "yaml":
                            if (!isset($args[2])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " <directory> <algorithm>");
                                return false;
                            }
                            if (!isset($args[3])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " " . $args[2] . "<algorithm>");
                                return false;
                            }
                            $this->plugin->serverauthconverter->convertFromYML($args[2], $args[3]);
                            break;
                        case "mysql":
                            if (!isset($args[2]) || !isset($args[3]) || !isset($args[4]) || !isset($args[5]) || !isset($args[6]) || !isset($args[7]) || !isset($args[8])) {
                                $sender->sendMessage("/convert " . $args[0] . " " . $args[1] . " <db> <host> <user> <password> <name> <port> <table> <algorithm>");
                                return false;
                            }
                            $this->plugin->serverauthconverter->convertFromMySQL($args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8]);
                            break;
                        default:
                            $sender->sendMessage(str_replace("{db}", "YML, MySQL", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-database")));
                            break;
                    }
                    break;
                default:
                    $sender->sendMessage(str_replace("{auth}", "SimpleAuth, ServerAuth", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-auth")));
                    break;
            }
            return true;
        }
    }
}