<?php

namespace PiggyAuth\Sessions;

use PiggyAuth\Events\PlayerLoginEvent;
use PiggyAuth\Main;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\Player;

/**
 * Class PiggyAuthSession
 * @package PiggyAuth\Sessions
 */
class PiggyAuthSession implements Session
{
    private $player;
    private $plugin;
    private $data;
    private $authenticated = false;
    private $registered = false;
    private $isConfirmingPassword = false;
    private $firstPassword = null;
    private $secondPassword = null;
    private $isGivingEmail = false;
    private $messagetick = 0;
    private $cape = null;
    private $gamemode = null;
    private $timeouttick = 0;
    private $wither = null;
    private $tries = 0;
    private $joinmessage = null;
    private $isVerifying = false;
    private $isRegistering = false;

    /**
     * PiggyAuthSession constructor.
     * @param Player $player
     * @param Main $plugin
     * @param $data
     */
    public function __construct(Player $player, Main $plugin, $data)
    {
        $this->player = $player;
        $this->plugin = $plugin;
        $this->data = $data;
        if (is_null($this->data) !== true && $this->data !== false) {
            $this->registered = true;
        }
    }

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->player->getName();
    }

    /**
     * @return bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * @param $args
     */
    public function setRegistered($args)
    {
        $this->registered = $args;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->authenticated;
    }

    /**
     * @param bool $arg
     */
    public function setAuthenticated($arg = true)
    {
        $this->authenticated = $arg;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->data["password"];
    }

    /**
     * @return mixed
     */
    public function getPin()
    {
        return $this->data["pin"];
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->data["email"];
    }

    /**
     * @return mixed
     */
    public function getIP()
    {
        return $this->data["email"];
    }

    /**
     * @return mixed
     */
    public function getUUID()
    {
        return $this->data["uuid"];
    }

    /**
     * @return mixed
     */
    public function getAttempts()
    {
        return $this->data["attempts"];
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        if ($this->plugin->getLanguageManager()->isLanguage($this->data["language"])) {
            return $this->data["language"];
        }
        return $this->plugin->getLanguageManager()->getDefaultLanguage();
    }

    /**
     * @return mixed
     */
    public function getOriginAuth()
    {
        return $this->data["auth"];
    }

    /**
     * @param null $callback
     * @param null $args
     */
    public function clearPassword($callback = null, $args = null)
    {
        $this->authenticated = false;
        $this->registered = false;
        $this->data = null;
        $this->plugin->database->clearPassword($this->getName(), $callback, $args);
    }

    /**
     * @return bool
     */
    public function isConfirmingPassword()
    {
        return $this->isConfirmingPassword;
    }

    /**
     * @param bool|null $arg
     */
    public function setConfirmingPassword($arg = true)
    {
        $this->isConfirmingPassword = $arg;
    }

    /**
     * @return null
     */
    public function getFirstPassword()
    {
        return $this->firstPassword;
    }

    /**
     * @param $password
     */
    public function setFirstPassword($password)
    {
        $this->firstPassword = $password;
    }

    /**
     * @return null
     */
    public function getSecondPassword()
    {
        return $this->secondPassword;
    }

    /**
     * @param $password
     */
    public function setSecondPassword($password)
    {
        $this->secondPassword = $password;
    }

    /**
     * @return bool
     */
    public function isGivingEmail()
    {
        return $this->isGivingEmail;
    }

    /**
     * @param bool $arg
     */
    public function setGivingEmail($arg = true)
    {
        $this->isGivingEmail = $arg;
    }

    /**
     * @return bool
     */
    public function isVerifying() : bool
    {
        return $this->isVerifying;
    }

    /**
     * @param bool $arg
     * @return void
     */
    public function setVerifying(bool $arg = true)
    {
        $this->isVerifying = $arg;
    }

    /**
     * @return bool
     */
    public function isRegistering() : bool
    {
        return $this->isRegistering;
    }

    /**
     * @param bool $arg
     * @return void
     */
    public function setRegistering(bool $arg = true)
    {
        $this->isRegistering = $arg;
    }

    /**
     * @return int
     */
    public function getMessageTick()
    {
        return $this->messagetick;
    }

    /**
     * @param $arg
     */
    public function setMessageTick($arg)
    {
        $this->messagetick = $arg;
    }

    public function addMessageTick()
    {
        $this->messagetick++;
    }

    /**
     * @return mixed
     */
    public function getCape()
    {
        return $this->cape;
    }

    /**
     * @param $cape
     */
    public function setCape($cape)
    {
        $this->cape = $cape;
    }

    /**
     * @return mixed
     */
    public function getGamemode()
    {
        return $this->gamemode;
    }

    /**
     * @param $gamemode
     */
    public function setGamemode($gamemode)
    {
        $this->gamemode = $gamemode;
    }

    /**
     * @return mixed
     */
    public function getTimeoutTick()
    {
        return $this->timeouttick;
    }

    /**
     * @param $arg
     */
    public function setTimeoutTick($arg)
    {
        $this->timeouttick = $arg;
    }

    public function addTimeoutTick()
    {
        $this->timeouttick++;
    }

    /**
     * @return mixed
     */
    public function getWither()
    {
        return $this->wither;
    }

    /**
     * @param $wither
     */
    public function setWither($wither)
    {
        $this->wither = $wither;
    }

    /**
     * @return mixed
     */
    public function getTries()
    {
        return $this->tries;
    }

    /**
     * @param $tries
     */
    public function setTries($tries)
    {
        $this->tries = $tries;
    }

    public function addTry()
    {
        $this->tries++;
    }

    /**
     * @return null
     */
    public function getJoinMessage()
    {
        return $this->joinmessage;
    }

    /**
     * @param $message
     */
    public function setJoinMessage($message)
    {
        $this->joinmessage = $message;
    }

    /**
     * @param $column
     * @param $arg
     * @param null $callback
     * @param null $args
     */
    public function updatePlayer($column, $arg, $callback = null, $args = null)
    {
        $this->plugin->database->updatePlayer($this->getName(), $column, $arg, $callback, $args);
        $this->plugin->getSessionManager()->loadSession($this->player, true); //Reload
    }

    /**
     * @param $password
     * @param $email
     * @param $pin
     * @param $xbox
     * @param null $callback
     * @param null $args
     */
    public function insertData($password, $email, $pin, $xbox, $callback = null, $args = null)
    {
        $this->plugin->database->insertData($this->player, $password, $email, $pin, $xbox, $callback, $args);
        $this->plugin->getSessionManager()->loadSession($this->player, true); //Reload
    }

    /**
     * @param null $joinmessage
     * @return bool
     * @internal param Player $player
     */
    public function startSession($joinmessage = null)
    {
        if (in_array(strtolower($this->player->getName()), $this->plugin->getConfig()->getNested("login.accounts-bypassed"))) {
            $this->authenticated = true;
            return true;
        }
        if (!$this->isRegistered() && $this->plugin->getConfig()->getNested("message.join-message-for-new-players")) {
            $this->setJoinMessage(str_replace("{player}", $this->player->getName(), $this->plugin->getLanguageManager()->getMessageFromLanguage($this->plugin->getLanguageManager()->getDefaultLanguage(), "new-player")));
        }
        if ($this->plugin->getConfig()->getNested("message.hold-join-message")) {
            $this->setJoinMessage($joinmessage);
        }
        if ($this->plugin->getConfig()->getNested("login.auto-authentication") && !is_null($this->getData()) && $this->player->getUniqueId()->toString() == $this->getUUID()) {
            $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this->plugin, $this->player, Main::UUID));
            if (!$event->isCancelled()) {
                $this->plugin->force($this->player, true, 1);
            }
            return true;
        }
        /*if ($this->plugin->getConfig()->getNested("login.xbox-bypass") && $this->plugin->getServer()->getName() == "ClearSky" && $player->isAuthenticated()) {
            if (!$this->plugin->getSessionManager()->getSession($player)->isRegistered()) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                $randompassword = [];
                $characteramount = strlen($characters) - 1;
                for ($i = 0; $i < $this->plugin->getConfig()->getNested("register.minimum-password-length"); $i++) {
                    $character = mt_rand(0, $characteramount);
                    array_push($randompassword, $characters[$character]);
                }
                $randompassword = implode("", $randompassword);
                $this->plugin->register($player, $randompassword, $randompassword, "none", true);
                $player->sendMessage(str_replace("{pin}", $this->plugin->database->getPin($player->getName()), str_replace("{password}", $randompassword, $this->plugin->getLanguageManager()->getMessage($player, "register-success-xbox"))));
            } else {
                if (!is_null($data) && $data["xbox"] == true) {
                    $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this->plugin, $player, Main::XBOX));
                    if (!$event->isCancelled()) {
                        $this->plugin->force($player, true, 2);
                    }
                }
            }
            return true;
        }*/
        $this->player->sendMessage($this->plugin->getLanguageManager()->getMessage($this->player, "join-message"));
        if ($this->plugin->getConfig()->getNested("register.cape-for-registration")) {
            $stevecapes = array(
                "Minecon_MineconSteveCape2016",
                "Minecon_MineconSteveCape2015",
                "Minecon_MineconSteveCape2013",
                "Minecon_MineconSteveCape2012",
                "Minecon_MineconSteveCape2011");
            if (in_array($this->player->getSkinId(), $stevecapes)) {
                $this->setCape($this->player->getSkinId());
                $this->player->setSkin($this->player->getSkinData(), "Standard_Custom");
            } else {
                $alexcapes = array(
                    "Minecon_MineconAlexCape2016",
                    "Minecon_MineconAlexCape2015",
                    "Minecon_MineconAlexCape2013",
                    "Minecon_MineconAlexCape2012",
                    "Minecon_MineconAlexCape2011");
                if (in_array($this->player->getSkinId(), $alexcapes)) {
                    $this->setCape($this->player->getSkinId());
                    $this->player->setSkin($this->player->getSkinData(), "Standard_CustomSlim");
                }
            }
        }
        if ($this->isRegistered()) {
            $this->player->sendMessage($this->plugin->getLanguageManager()->getMessage($this->player, "login-message"));
        } else {
            $this->player->sendMessage($this->plugin->getLanguageManager()->getMessage($this->player, "register-message"));
        }
        if ($this->plugin->getConfig()->getNested("effects.invisible")) {
            $this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
            $this->player->setNameTagVisible(false);
        }
        if ($this->plugin->getConfig()->getNested("effects.blindness")) {
            $effect = Effect::getEffect(15);
            $effect->setAmplifier(99);
            $effect->setDuration(999999);
            $effect->setVisible(false);
            $this->player->addEffect($effect);
            $effect = Effect::getEffect(16);
            $effect->setAmplifier(99);
            $effect->setDuration(999999);
            $effect->setVisible(false);
            $this->player->addEffect($effect);
        }
        if ($this->plugin->getConfig()->getNested("effects.hide-players")) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                $this->player->hidePlayer($p);
                if (!$this->plugin->getSessionManager()->getSession($p)->isAuthenticated($p)) {
                    $p->hidePlayer($this->player);
                }
            }
        }
        if ($this->plugin->getConfig()->getNested("effects.hide-effects")) {
            foreach ($this->player->getEffects() as $effect) {
                if ($this->plugin->getConfig()->getNested("blindness") && ($effect->getId() == 15 || $effect->getId() == 16)) {
                    continue;
                }
                $pk = new MobEffectPacket();
                $pk->entityRuntimeId = $this->player->getId();
                $pk->eventId = MobEffectPacket::EVENT_REMOVE;
                $pk->effectId = $effect->getId();
                $this->player->dataPacket($pk);
            }
        }
        if ($this->plugin->getConfig()->getNested("login.adventure-mode")) {
            $this->setGamemode($this->player->getGamemode());
            $this->player->setGamemode(2);
        }
        if ($this->plugin->getConfig()->getNested("message.boss-bar")) {
            $message = $this->isRegistered() == false ? $this->plugin->getLanguageManager()->getMessage($this->player, "register-boss-bar") : $this->plugin->getLanguageManager()->getMessage($this->player, "login-boss-bar");
            $wither = Entity::createEntity("Wither", $this->player->getLevel(), new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $this->player->x + 0.5), new DoubleTag("", $this->player->y - 25), new DoubleTag("", $this->player->z + 0.5)]), "Motion" => new ListTag("Motion", [new DoubleTag("", 0), new DoubleTag("", 0), new DoubleTag("", 0)]), "Rotation" => new ListTag("Rotation", [new FloatTag("", 0), new FloatTag("", 0)])]));
            $wither->spawnTo($this->player);
            $wither->setNameTag($message);
            $this->setWither($wither);
            $wither->setMaxHealth($this->plugin->getConfig()->getNested("timeout.timeout-time"));
            $wither->setHealth($this->plugin->getConfig()->getNested("timeout.timeout-time"));
            $pk = new BossEventPacket();
            $pk->bossEid = $wither->getId();
            $pk->eventType = BossEventPacket::TYPE_SHOW;
            $pk->healthPercent = 1;
            $pk->title = $message;
            $this->player->dataPacket($pk);
        }
        return true;
    }

}
