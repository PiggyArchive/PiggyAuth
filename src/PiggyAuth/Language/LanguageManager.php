<?php

namespace PiggyAuth\Language;

use pocketmine\Player;
use pocketmine\utils\Config;

/**
 * Class LanguageManager
 * @package PiggyAuth\Language
 */
class LanguageManager
{
    private $languages;
    private $languagefiles;
    private $defaultlanguage;

    /**
     * LanguageManager constructor.
     * @param $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->loadLanguages();
    }

    public function getDefaultLanguage()
    {
        return $this->defaultlanguage;
    }

    /**
     * @param $language
     * @return bool
     */
    public function setDefaultLanguage($language)
    {
        if ($this->isLanguage($language)) {
            $this->defaultlanguage = $language;
            return true;
        }
        return false;
    }

    /**
     * @param $player
     * @param $message
     * @return mixed
     */
    public function getMessage($player, $message)
    {
        if ($player instanceof Player) {
            $language = $this->plugin->sessionmanager->getSession($player)->getLanguage();
        } else {
            $language = $this->getDefaultLanguage();
        }
        return str_replace("&", "§", $this->languagefiles[$language]->getNested($message));
    }

    /**
     * @param $language
     * @param $message
     * @return mixed
     */
    public function getMessageFromLanguage($language, $message)
    {
        return str_replace("&", "§", $this->languagefiles[$language]->getNested($message));
    }

    /**
     * @param $language
     * @return mixed
     */
    public function getLanguage($language)
    {
        return $this->languagefiles[$language];
    }

    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param $language
     * @return bool
     */
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