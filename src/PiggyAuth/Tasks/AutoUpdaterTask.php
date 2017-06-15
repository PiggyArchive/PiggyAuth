<?php

namespace PiggyAuth\Tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class AutoUpdaterTask
 * @package PiggyAuth\Tasks
 */
class AutoUpdaterTask extends AsyncTask
{
    public $autoinstall;
    public $result;

    /**
     * AutoUpdaterTask constructor.
     * @param mixed|null $autoinstall
     */
    public function __construct($autoinstall)
    {
        $this->autoinstall = $autoinstall;
    }

    public function onRun()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/MCPEPIG/PiggyAuth/releases");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Awesome-Octocat-App');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $this->result = serialize(json_decode(curl_exec($ch)));

    }

    /**
     * @param Server $server
     * @return bool
     */
    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin("PiggyAuth");
        $pluginversion = $plugin->getDescription()->getVersion();
        $pluginversionwithoutbuildnumber = explode(".", $pluginversion);
        unset($pluginversionwithoutbuildnumber[3]);
        $pluginbuildnumber = explode(".", $pluginversion)[3];
        if (is_array(unserialize($this->result))) { //Over api rate-limit protection
            $release = str_replace("v", "", unserialize($this->result)[0]->tag_name);
            $releasewithoutbuildnumber = explode(".", $release);
            unset($releasewithoutbuildnumber[3]);
            $releasebuildnumber = explode(".", $release)[3];
            $features = unserialize($this->result)[0]->body;
            if ($pluginversion < $release || ($pluginversionwithoutbuildnumber == $releasewithoutbuildnumber && ($pluginbuildnumber < $releasebuildnumber || $pluginbuildnumber !== "00" && $releasebuildnumber == "00"))) {
                if ($this->autoinstall) {
                    $file = fopen("https://github.com/MCPEPIG/PiggyAuth/releases/download/v" . $release . "/PiggyAuth.phar", "r");
                    file_put_contents("plugins/PiggyAuth.phar", $file);
                    fclose($file);
                    $plugin->getLogger()->info(str_replace("{features}", $features, str_replace("{version}", $release, $plugin->getLanguageManager()->getMessageFromLanguage($plugin->getLanguageManager()->getDefaultLanguage(), "plugin-auto-updated"))));
                    $server->getPluginManager()->disablePlugin($plugin);
                    $server->getPluginManager()->enablePlugin($server->getPluginManager()->loadPlugin($server->getDataPath() . "/plugins/PiggyAuth.phar"));
                    return true;
                }
                $plugin->getLogger()->info(str_replace("{features}", $features, str_replace("{version}", $release, $plugin->getLanguageManager()->getMessageFromLanguage($plugin->getLanguageManager()->getDefaultLanguage(), "plugin-outdated"))));
                return true;
            }
            $plugin->getLogger()->info($plugin->getLanguageManager()->getMessageFromLanguage($plugin->getLanguageManager()->getDefaultLanguage(), "plugin-up-to-date"));
            return true;
        }
        $plugin->getLogger()->info($plugin->getLanguageManager()->getMessageFromLanguage($plugin->getLanguageManager()->getDefaultLanguage(), "over-api-rate-limit"));
        return false;
    }
}