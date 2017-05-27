<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Main;
use PiggyAuth\Databases\MySQL;
use PiggyAuth\Events\PlayerRegisterEvent;
use PiggyAuth\Events\DelayedPinTask;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class AsyncRegisterTask extends AsyncTask
{

    public function __construct($playerName, $password, $email, $pin, $xbox){
        $this->playerName = $playerName;
        $this->password = $password;
        $this->pin = $pin;
        $this->xbox = $xbox;
    }

    public function onRun(){
        $this->setResult(Main::hashPassword($this->password));
    }

    public function onCompletion(Server $server){
        $plugin = $server->getPluginManager()->getPlugin('PiggyAuth');

        if($plugin->isDisabled() || (($player = $server->getPlayerExact($this->playerName)) instanceof Player)){
            return;
        }
        $plugin->sessionmanager->getSession($player)->setRegistering(false);

        $password = $this->getResult();

        $server->getPluginManager()->callEvent($event = new PlayerRegisterEvent($plugin, $player, $password, $this->email, $this->pin, $this->xbox == "false" ? Main::NORMAL : Main::XBOX));
        if (!$event->isCancelled()) {
            $callback = function ($result, $args, $plugin) {
                $player = $plugin->getServer()->getPlayerExact($args[0]);
                if ($player instanceof Player) {
                    $plugin->force($player, false, $args[1] == false ? 0 : 3);
                    if ($args[1] == false) {
                        if ($plugin->database instanceof MySQL) {
                            $plugin->getServer()->getScheduler()->scheduleDelayedTask(new DelayedPinTask($plugin, $player), 5);
                        } else {
                            $player->sendMessage(str_replace("{pin}", $plugin->sessionmanager->getSession($player)->getPin(), $plugin->languagemanager->getMessage($player, "register-success")));
                        }
                    }
                }
            };
            $args = array($player->getName(), $xbox);
            $plugin->sessionmanager->getSession($player)->insertData($password, $this->email, $this->pin, $this->xbox, $callback, $args);
            if ($plugin->getConfig()->getNested("progress-reports.enabled")) {
                $plugin->progressReport($player->getName());
            }
        }
    }
}
