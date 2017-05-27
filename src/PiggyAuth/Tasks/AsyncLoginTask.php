<?php

namespace PiggyAuth\Tasks;

use PiggyAuth\Main;
use PiggyAuth\Events\PlayerFailEvent;
use PiggyAuth\Events\PlayerLoginEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class AsyncLoginTask extends AsyncTask
{

    public function __construct(Player $player, $passwordHash, $potentialPassword, $originAuth, $mode, $cost)
    {
        $this->playerName = $player->getName();
        $this->playerAddress = $player->getAddress();
        $this->playerUniqueId = $player->getUniqueId();
        $this->passwordHash = $passwordHash;
        $this->potentialPassword = $potentialPassword;
        $this->originAuth = $originAuth;
        $this->mode = $mode;
        $this->cost = $cost;
    }

    public function onRun()
    {
        if (strpos($this->originAuth, "ServerAuth") !== false) {
            $auth = explode("_", $this->originAuth);
            if (isset($auth[0]) && isset($auth[1])) {
                if (hash($auth[1], $this->potentialPassword) == $this->passwordHash) {
                    $this->setResult(['authenticated' => true, 'updatePlayer' => true, 'rehashedPassword' => $this->needsRehashPassword()]);
                    return;
                }
                $this->setResult(['authenticated' => false, 'updatePlayer' => false]);
                return;
            }
        }

        switch ($this->originAuth) {
            case "SimpleAuth":
                if (hash_equals($this->passwordHash, $this->hashSimpleAuth(strtolower($this->playerName), $this->potentialPassword))) {
                    $this->setResult(['authenticated' => true, 'updatePlayer' => true, 'rehashedPassword' => $this->needsRehashPassword()]);
                    return;
                }
                $this->setResult(['authenticated' => false, 'updatePlayer' => false]);
                return;
            case "PiggyAuth":
            default:
                if (password_verify($this->potentialPassword, $this->passwordHash)) {
                    $this->setResult(['authenticated' => true, 'updatePlayer' => false, 'rehashedPassword' => $this->needsRehashPassword()]);
                    return;
                }
                $this->setResult(['authenticated' => false, 'updatePlayer' => false]);
                return;
        }
    }

    public function hashSimpleAuth($salt, $password)
    {
        return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
    }

    public function needsRehashPassword()
    {
        $options = ['cost' => $this->cost];
        if (password_needs_rehash($this->passwordHash, PASSWORD_BCRYPT, $options)) {
            return password_hash($this->potentialPassword, PASSWORD_BCRYPT, $options);
        }
        return null;
    }

    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin('PiggyAuth');

        if($plugin->isDisabled() || !(($player = $server->getPlayerExact($this->playerName)) instanceof Player)){
            return;
        }

        if($this->getResult()['updatePlayer']){
            $plugin->sessionmanager->getSession($player)->updatePlayer("auth", "PiggyAuth");
        }

        if (!$this->getResult()['authenticated']) { // Failed
            if ($plugin->getConfig()->getNested("key.enabled")) {
                if ($this->potentialPassword === $plugin->getKey($this->potentialPassword)) { // Please look at this @MCPEPIG
                    $plugin->changeKey();
                    $plugin->keytime = 0;
                    $plugin->force($player);
                    return;
                }
                if (in_array($this->potentialPassword, $plugin->expiredkeys)) { // Please look at this @MCPEPIG
                    $player->sendMessage($plugin->languagemanager->getMessage($player, "key-expired"));
                    $server->getPluginManager()->callEvent(new PlayerFailEvent($plugin, $player, Main::LOGIN, Main::KEY_EXPIRED));
                    return;
                }
            }

            $plugin->sessionmanager->getSession($player)->addTry();
            if ($plugin->sessionmanager->getSession($player)->getTries() >= $plugin->getConfig()->getNested("login.tries")) {
                $plugin->sessionmanager->getSession($player)->updatePlayer("attempts", $plugin->sessionmanager->getSession($player)->getAttempts() + 1, 1);
                if ($plugin->getConfig()->getNested("emails.send-email-on-attemptedlogin")) {
                    $plugin->emailmanager->sendEmail($plugin->sessionmanager->getSession($player)->getEmail(), $plugin->languagemanager->getMessage($player, "email-subject-attemptedlogin"), $plugin->languagemanager->getMessage($player, "email-attemptedlogin"));
                }
                $player->kick($plugin->languagemanager->getMessage($player, "too-many-tries"));
                return;
            }
            $tries = $plugin->getConfig()->getNested("login.tries") - $plugin->sessionmanager->getSession($player)->getTries();
            $player->sendMessage(str_replace("{tries}", $tries, $plugin->languagemanager->getMessage($player, "incorrect-password")));
            $server->getPluginManager()->callEvent(new PlayerFailEvent($plugin, $player, Main::LOGIN, Main::WRONG_PASSWORD));
            return;
        }

        //Succeded

        $server->getPluginManager()->callEvent($event = new PlayerLoginEvent($plugin, $player, Main::NORMAL));
        if (!$event->isCancelled()) {
            if ($player->getAddress() !== $plugin->sessionmanager->getSession($player)->getIP()) {
                if ($plugin->getConfig()->getNested("emails.send-email-on-login-from-new-ip")) {
                    $plugin->emailmanager->sendEmail($plugin->sessionmanager->getSession($player)->getEmail(), $plugin->languagemanager->getMessage($player, "email-subject-login-from-new-ip"), str_replace("{ip}", $player->getAddress(), $plugin->languagemanager->getMessage($player, "email-login-from-new-ip")));
                }
            }
            $rehashedpassword = $this->getResult()['rehashedPassword'];
            $plugin->force($player, true, $this->mode, $rehashedpassword);
        }
    }
}
