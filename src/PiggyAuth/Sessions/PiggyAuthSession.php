<?php

namespace PiggyAuth\Sessions;

use PiggyAuth\Packet\BossEventPacket;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\MobEffectPacket;
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

    /**
     * PiggyAuthSession constructor.
     * @param Player $player
     * @param $plugin
     * @param $data
     */
    public function __construct(Player $player, $plugin, $data)
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
        if ($this->plugin->languagemanager->isLanguage($this->data["language"])) {
            return $this->data["language"];
        }
        return $this->plugin->languagemanager->getDefaultLanguage();
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
    public function isComfirmingPassword()
    {
        return $this->isConfirmingPassword;
    }

    /**
     * @param null $arg
     */
    public function setComfirmingPassword($arg = null)
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

    public function getJoinMessage()
    {
        return $this->joinmessage;
    }

    public function setJoinMessage($message){
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
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
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
        $this->plugin->sessionmanager->loadSession($this->player, true); //Reload
    }

    /**
     * @return bool
     * @internal param Player $player
     */
    public function startSession()
    {
        if (in_array(strtolower($this->player->getName()), $this->plugin->getConfig()->getNested("login.accounts-bypassed"))) {
            $this->authenticated = true;
            return true;
        }
        $this->player->sendMessage($this->plugin->languagemanager->getMessage($this->player, "join-message"));
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
            $this->player->sendMessage($this->plugin->languagemanager->getMessage($this->player, "login-message"));
        } else {
            $this->player->sendMessage($this->plugin->languagemanager->getMessage($this->player, "register-message"));
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
                if (!$this->plugin->sessionmanager->getSession($p)->isAuthenticated($p)) {
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
                $pk->eid = $this->player->getId();
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
            $wither = Entity::createEntity("Wither", $this->player->getLevel(), new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $this->player->x + 0.5), new DoubleTag("", $this->player->y - 25), new DoubleTag("", $this->player->z + 0.5)]), "Motion" => new ListTag("Motion", [new DoubleTag("", 0), new DoubleTag("", 0), new DoubleTag("", 0)]), "Rotation" => new ListTag("Rotation", [new FloatTag("", 0), new FloatTag("", 0)])]));
            $wither->spawnTo($this->player);
            $wither->setNameTag($this->isRegistered() == false ? $this->plugin->languagemanager->getMessage($this->player, "register-boss-bar") : $this->plugin->languagemanager->getMessage($this->player, "login-boss-bar"));
            $this->setWither($wither);
            $wither->setMaxHealth($this->plugin->getConfig()->getNested("timeout.timeout-time"));
            $wither->setHealth($this->plugin->getConfig()->getNested("timeout.timeout-time"));
            $pk = new BossEventPacket();
            $pk->eid = $wither->getId();
            $pk->state = 0;
            $this->player->dataPacket($pk);
        }
    }

}

