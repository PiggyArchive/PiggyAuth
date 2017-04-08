<?php

namespace PiggyAuth\Language;

use pocketmine\Player;
use pocketmine\utils\Config;

class LanguageManager
{
    private $languages;
    private $languagefiles;
    private $defaultlanguage;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->loadLanguages();
    }

    public function getDefaultLanguage()
    {
        return $this->defaultlanguage;
    }

    public function setDefaultLanguage($language)
    {
        if ($this->isLanguage($language)) {
            $this->defaultlanguage = $language;
            return true;
        }
        return false;
    }

    public function getMessage($player, $message)
    {
        if ($player instanceof Player) {
            $language = $this->plugin->sessionmanager->getSession($player)->getLanguage();
        } else {
            $language = $this->getDefaultLanguage();
        }
        return str_replace("&", "§", $this->languagefiles[$language]->getNested($message));
    }

    public function getMessageFromLanguage($language, $message)
    {
        return str_replace("&", "§", $this->languagefiles[$language]->getNested($message));
    }

    public function getLanguage($language)
    {
        return $this->languagefiles[$language];
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    public function isLanguage($language)
    {
        return in_array($language, $this->languages);
    }

    public function loadLanguages()
    {
        $files = scandir($this->plugin->getFile() . "resources/languages/");
        foreach ($files as $file) { //Load all language files
            if (strpos($file, "lang_") !== false && strpos($file, ".yml") !== false) {
                $this->plugin->saveResource("languages/" . $file);
            }
        }
        $files = scandir($this->plugin->getDataFolder() . "languages/");
        $languages = array();
        $languagefiles = array();
        foreach ($files as $file) { //Read all files (not in first foreach so we can load language files by other people)
            if (strpos($file, "lang_") !== false && strpos($file, ".yml") !== false) {
                $language = str_replace("lang_", "", str_replace(".yml", "", $file));
                array_push($languages, $language);
                $this->languagefiles[$language] = new Config($this->plugin->getDataFolder() . "languages/" . $file);
            }
        }
        $this->languages = $languages;
        $this->defaultlanguage = $this->isLanguage($this->plugin->getConfig()->getNested("message.lang")) ? $this->plugin->getConfig()->getNested("message.lang") : "eng";
    }
}