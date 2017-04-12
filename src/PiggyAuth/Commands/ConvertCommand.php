<?php

namespace PiggyAuth\Commands;

use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ConvertCommand extends VanillaCommand
{
    public function __construct($name, $plugin)
    {
        parent::__construct($name, "Convert SimpleAuth to PiggyAuth", "/convert <auth> <db> </info>");
        $this->setPermission("piggyauth.command.convert");
        $this->plugin = $plugin;
    }

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
                                $sender->sendMessage("/convert " . $args[0] . " " .  $args[1] . " <directory>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromYML($args[2]);
                            break;
                        case "sqlite3":
                            if (!isset($args[2])) {
                                $sender->sendMessage("/convert " . $args[0] . " " .  $args[1] . " <file>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromSQLite3($args[2]);
                            break;
                        case "mysql":
                            if (!isset($args[2]) || !isset($args[3]) || !isset($args[4]) || !isset($args[5]) || !isset($args[6])) {
                                $sender->sendMessage("/convert " . $args[0] . " " .  $args[1] . " <db> <host> <user> <password> <name> <port>");
                                return false;
                            }
                            $this->plugin->simpleauthconverter->convertFromMySQL($args[2], $args[3], $args[4], $args[5], $args[6]);
                            break;
                        default:
                            $sender->sendMessage(str_replace("{db}", "YML, SQLite3, MySQL", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-database")));
                            break;
                    }
                    break;
                default:
                    $sender->sendMessage(str_replace("{auth}", "SimpleAuth", $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "invalid-auth")));
                    break;
            }
            return true;
        }
    }
}