<?php

namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
use PiggyAuth\Commands\ChangeEmailCommand;
use PiggyAuth\Commands\ForgotPasswordCommand;
use PiggyAuth\Commands\LoginCommand;
use PiggyAuth\Commands\LogoutCommand;
use PiggyAuth\Commands\KeyCommand;
use PiggyAuth\Commands\PinCommand;
use PiggyAuth\Commands\PreregisterCommand;
use PiggyAuth\Commands\RegisterCommand;
use PiggyAuth\Commands\ResetPasswordCommand;
use PiggyAuth\Commands\SendPinCommand;
use PiggyAuth\Commands\UnregisterCommand;
use PiggyAuth\Events\PlayerChangePasswordEvent;
use PiggyAuth\Events\PlayerFailEvent;
use PiggyAuth\Events\PlayerForgetPasswordEvent;
use PiggyAuth\Events\PlayerLoginEvent;
use PiggyAuth\Events\PlayerLogoutEvent;
use PiggyAuth\Events\PlayerPreregisterEvent;
use PiggyAuth\Events\PlayerRegisterEvent;
use PiggyAuth\Events\PlayerResetPasswordEvent;
use PiggyAuth\Events\PlayerTimeoutEvent;
use PiggyAuth\Events\PlayerUnregisterEvent;
use PiggyAuth\Databases\MySQL;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Entities\Wither;
use PiggyAuth\Packet\BossEventPacket;
use PiggyAuth\Tasks\AttributeTick;
use PiggyAuth\Tasks\ForceTask; //Delayed for async
use PiggyAuth\Tasks\KeyTick;
use PiggyAuth\Tasks\MessageTick;
use PiggyAuth\Tasks\PingTask;
use PiggyAuth\Tasks\PopupTipBarTick;
use PiggyAuth\Tasks\SendEmailTask; //Async
use PiggyAuth\Tasks\StartSessionTask; //Delayed for async
use PiggyAuth\Tasks\TimeoutTask;

use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class Main extends PluginBase {
    //ACTIONS
    const LOGIN = 0;
    const REGISTER = 1;
    const PREREGISTER = 2;
    const UNREGISTER = 3;
    const CHANGE_PASSWORD = 4;
    const FORGET_PASSWORD = 5;
    const RESET_PASSWORD = 6;
    const LOGOUT = 7;

    //MODES
    const NORMAL = 8;
    const UUID = 9;
    const XBOX = 10;

    //ERROR
    const WRONG_PASSWORD = 11;
    const WRONG_PIN = 12;
    const ACCOUNT_BLOCKED = 13;
    const ALREADY_AUTHENTICATED = 14;
    const NOT_REGISTERED = 15;
    const KEY_EXPIRED = 16;
    const TOO_MANY_ATTEMPTS = 17;
    const ALREADY_REGISTERED = 18;
    const PASSWORDS_NOT_MATCHED = 19;
    const PASSWORD_BLOCKED = 20;
    const PASSWORD_USERNAME = 21;
    const PASSWORD_TOO_SHORT = 22;
    const INVALID_EMAIL = 23;
    const TOO_MANY_ON_IP = 24;
    const CANT_USE_PIN = 25;
    const OTHER = 100;

    public $authenticated;
    public $confirmPassword;
    public $confirmedPassword;
    public $giveEmail;
    public $keepCape;
    public $joinMessage;
    public $gamemode;
    public $messagetick;
    public $timeouttick;
    public $tries;
    public $database;
    public $wither;
    private $key = "PiggyAuthKey";
    public $keytime = 299; //300 = Reset
    public $pubapi;
    public $api;
    public $domain;
    public $from;
    public $expiredkeys = [];

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('changeemail', new ChangeEmailCommand('changeemail', $this));
        $this->getServer()->getCommandMap()->register('forgotpassword', new ForgotPasswordCommand('forgotpassword', $this));
        $this->getServer()->getCommandMap()->register('key', new KeyCommand('key', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('logout', new LogoutCommand('logout', $this));
        $this->getServer()->getCommandMap()->register('pin', new PinCommand('pin', $this));
        $this->getServer()->getCommandMap()->register('preregister', new PreregisterCommand('preregister', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getCommandMap()->register('sendpin', new SendPinCommand('sendpin', $this));
        $this->getServer()->getCommandMap()->register('unregister', new UnregisterCommand('unregister', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AttributeTick($this), 20);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), 20);
        if ($this->getConfig()->getNested("key.enabled")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new KeyTick($this), 20);
        }
        if ($this->getConfig()->getNested("message.popup") || $this->getConfig()->getNested("message.tip") || $this->getConfig()->getNested("message.boss-bar")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTipBarTick($this), 20);
        }
        if ($this->getConfig()->getNested("timeout.enabled")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeoutTask($this), 20);
        }
        if ($this->getConfig()->getNested("message.boss-bar")) {
            Entity::registerEntity(Wither::class);
            $this->getServer()->getNetwork()->registerPacket(BossEventPacket::NETWORK_ID, BossEventPacket::class);
        }
        $this->pubapi = $this->getConfig()->getNested("emails.mailgun.public-api");
        $this->api = $this->getConfig()->getNested("emails.mailgun.api");
        $this->domain = $this->getConfig()->getNested("emails.mailgun.domain");
        $this->from = $this->getConfig()->getNested("emails.mailgun.from");
        switch ($this->getConfig()->getNested("database")) {
            case "mysql":
                $this->database = new MySQL($this);
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new PingTask($this, $this->database), 300);
                break;
            case "sqlite3":
                $this->database = new SQLite3($this);
                break;
            default:
                $this->database = new SQLite3($this);
                $this->getLogger()->error("§cDatabase not found, using default.");
                break;
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        foreach ($this->getServer()->getOnlinePlayers() as $player) { //Reload, players still here but plugin restarts!
            $this->startSession($player);
        }
        $this->getLogger()->info("§aEnabled.");
    }

    public function getDatabase() {
        return $this->database;
    }

    public function generatePin(Player $player) {
        $newpin = mt_rand(1000, 9999);
        if ($this->isCorrectPin($player, $newpin) || $newpin == 1234) { //Player cant have same pin or have 1234 as pin
            return $this->generatePin($player);
        }
        return $newpin;
    }

    public function isCorrectPassword(Player $player, $password) {
        if (password_verify($password, $this->database->getPassword($player->getName()))) {
            return true;
        }
        return false;
    }

    public function isCorrectPin(Player $player, $pin) {
        if ($pin == $this->database->getPin($player->getName())) {
            return true;
        }
        return false;
    }

    public function isAuthenticated(Player $player) {
        if (isset($this->authenticated[strtolower($player->getName())]))
            return true;
        return false;
    }

    public function isRegistered($player) {
        return $this->database->getPlayer($player) !== null;
    }

    public function isBlocked($player) {
        return in_array(strtolower($player), $this->getConfig()->getNested("register.blocked-accounts"));
    }

    public function isPasswordBlocked($password) {
        return in_array(strtolower($password), $this->getConfig()->getNested("register.blocked-passwords"));
    }

    public function login(Player $player, $password, $mode = 0) {
        if ($this->isBlocked($player->getName())) {
            $player->sendMessage($this->getMessage("account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->isAuthenticated($player)) {
            $player->sendMessage($this->getMessage("already-authenticated"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::ALREADY_AUTHENTICATED));
            return false;
        }
        if (!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::NOT_REGISTERED));
            return false;
        }
        if (!$this->isCorrectPassword($player, $password)) {
            if ($this->getConfig()->getNested("key.enabled")) {
                if ($password == $this->key) {
                    $this->changeKey();
                    $this->keytime = 0;
                    $this->force($player);
                    return true;
                }
                if (in_array($password, $this->expiredkeys)) {
                    $player->sendMessage($this->getMessage("key-expired"));
                    $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::KEY_EXPIRED));
                    return true;
                }
            }
            if (isset($this->tries[strtolower($player->getName())])) {
                $this->tries[strtolower($player->getName())]++;
                if ($this->tries[strtolower($player->getName())] >= $this->getConfig()->getNested("login.tries")) {
                    $this->database->updatePlayer($player->getName(), $this->database->getPassword($player->getName()), $this->database->getEmail($player->getName()), $this->database->getPin($player->getName()), $this->database->getIP($player->getName()), $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()) + 1);
                    $player->kick($this->getMessage("too-many-tries"));
                    if ($this->database->getEmail($player->getName()) !== "none" && $this->getConfig()->getNested("emails.send-email-on-attemptedlogin")) {
                        $this->emailUser($this->api, $this->domain, $this->database->getEmail($player->getName()), $this->from, $this->getMessage("email-subject-attemptedlogin"), $this->getMessage("email-attemptedlogin"));
                    }
                    return false;
                }
            } else {
                $this->tries[strtolower($player->getName())] = 1;
            }
            $tries = $this->getConfig()->getNested("login.tries") - $this->tries[strtolower($player->getName())];
            $player->sendMessage(str_replace("{tries}", $tries, $this->getMessage("incorrect-password")));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::WRONG_PASSWORD));
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this, $player, self::NORMAL));
        if (!$event->isCancelled()) {
            if ($player->getAddress !== $this->database->getIP()) {
                if ($this->database->getEmail($player->getName()) !== "none" && $this->getConfig()->getNested("emails.send-email-on-login-from-new-ip")) {
                    $this->emailUser($this->api, $this->domain, $this->database->getEmail($player->getName()), $this->from, $this->getMessage("email-subject-login-from-new-ip"), str_replace("{ip", $player->getAddress(), $this->getMessage("email-login-from-new-ip")));
                }
            }
            $rehashedpassword = $this->needsRehashPassword($this->database->getPassword($player->getName()), $password);
            $this->force($player, true, $mode, $rehashedpassword);
        }
        return true;
    }

    public function force(Player $player, $login = true, $mode = 0, $rehashedpassword = null) {
        if ($login) {
            if ($this->isTooManyIPOnline($player)) {
                $player->sendMessage($this->getMessage("too-many-on-ip"));
                $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::TOO_MANY_ON_IP));
                return false;
            }
            switch ($mode) {
                case 1:
                    $player->sendMessage($this->getMessage("authentication-success-uuid"));
                    break;
                case 2:
                    $player->sendMessage($this->getMessage("authentication-success-xbox"));
                    break;
                case 0:
                default:
                    $player->sendMessage($this->getMessage("authentication-success"));
                    break;
            }
            if (!$this->database->getAttempts($player->getName()) == 0) {
                $player->sendMessage(str_replace("{attempts}", $this->database->getAttempts($player->getName()), $this->getMessage("attempted-logins")));
            }
        } else {
            if (!$mode == 3) {
                $player->sendMessage(str_replace("{pin}", $this->database->getPin($player->getName()), $this->getMessage("register-success")));
            }
        }
        if (isset($this->messagetick[strtolower($player->getName())])) {
            unset($this->messagetick[strtolower($player->getName())]);
        }
        if (isset($this->timeouttick[strtolower($player->getName())])) {
            unset($this->timeouttick[strtolower($player->getName())]);
        }
        if (isset($this->tries[strtolower($player->getName())])) {
            unset($this->tries[strtolower($player->getName())]);
        }
        if (isset($this->joinMessage[strtolower($player->getName())]) && $this->getConfig()->getNested("message.hold-join-message")) {
            $this->getServer()->broadcastMessage($this->joinMessage[strtolower($player->getName())]);
            unset($this->joinMessage[strtolower($player->getName())]);
        }
        $this->authenticated[strtolower($player->getName())] = true;
        if ($this->getConfig()->getNested("effects.invisible")) {
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            $player->setNameTagVisible(true);
        }
        if ($this->getConfig()->getNested("effects.blindness")) {
            $player->removeEffect(15);
            $player->removeEffect(16);
        }
        if ($this->getConfig()->getNested("effects.hide-players")) {
            foreach ($this->getServer()->getOnlinePlayers() as $p) {
                $player->showPlayer($p);
            }
        }
        if ($this->getConfig()->getNested("effects.hide-health")) {
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $player->getId();
            $pk->entries = [$player->getAttributeMap()->getAttribute(Attribute::HEALTH)];
            $player->dataPacket($pk);
        }
        if ($this->getConfig()->getNested("effects.hide-hunger")) {
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $player->getId();
            $pk->entries = [$player->getAttributeMap()->getAttribute(Attribute::HUNGER)];
            $player->dataPacket($pk);
        }
        if ($this->getConfig()->getNested("effects.hide-xp")) {
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $player->getId();
            $pk->entries = [$player->getAttributeMap()->getAttribute(Attribute::EXPERIENCE)];
            $player->dataPacket($pk);
        }
        if ($this->getConfig()->getNested("effects.hide-effects")) {
            $player->sendPotionEffects($player);
        }
        if ($this->getConfig()->getNested("login.return-to-spawn")) {
            $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
        }
        if ($this->getConfig()->getNested("register.cape-for-registration")) {
            $cape = "Minecon_MineconSteveCape2016";
            if (isset($this->keepCape[strtolower($player->getName())])) {
                $cape = $this->keepCape[strtolower($player->getName())];
                unset($this->keepCape[strtolower($player->getName())]);
            } else {
                $capes = array(
                    "Minecon_MineconSteveCape2016",
                    "Minecon_MineconSteveCape2015",
                    "Minecon_MineconSteveCape2013",
                    "Minecon_MineconSteveCape2012",
                    "Minecon_MineconSteveCape2011");
                $cape = array_rand($capes);
                $cape = $capes[$cape];
            }
            $player->setSkin($player->getSkinData(), $cape);
        }
        if ($this->getConfig()->getNested("effects.hide-items")) {
            $player->getInventory()->sendContents($player);
        }
        if ($this->getConfig()->getNested("login.adventure-mode")) {
            if (isset($this->gamemode[strtolower($player->getName())])) {
                $player->setGamemode($this->gamemode[strtolower($player->getName())]);
                unset($this->gamemode[strtolower($player->getName())]);
            }
        }
        if ($this->getConfig()->getNested("message.boss-bar")) {
            if (isset($this->wither[strtolower($player->getName())])) {
                $this->wither[strtolower($player->getName())]->kill();
                unset($this->wither[strtolower($player->getName())]);
            }
        }
        $password = $rehashedpassword !== null ? $rehashedpassword : $this->database->getPassword($player->getName());
        $this->database->updatePlayer($player->getName(), $password, $this->database->getEmail($player->getName()), $this->database->getPin($player->getName()), $player->getAddress(), $player->getUniqueId()->toString(), 0);
        return true;
    }

    public function register(Player $player, $password, $confirmpassword, $email = "none", $xbox = false) {
        if (isset($this->confirmPassword[strtolower($player->getName())])) {
            unset($this->confirmPassword[strtolower($player->getName())]);
        }
        if ($this->isBlocked($player->getName())) {
            $player->sendMessage($this->getMessage("account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("already-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::ALREADY_REGISTERED));
            return false;
        }
        if ($password !== $confirmpassword) {
            $player->sendMessage($this->getMessage("password-not-match"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORDS_NOT_MATCHED));
            return false;
        }
        if ($this->isPasswordBlocked($password)) {
            $player->sendMessage($this->getMessage("password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($password) == strtolower($player->getName()) || strpos($password, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->getMessage("password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_USERNAME));
            return false;
        }
        if (strlen($password) < $this->getConfig()->getNested("register.minimum-password-length")) {
            $player->sendMessage($this->getMessage("password-too-short"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_TOO_SHORT));
            return false;
        }
        $password = $this->hashPassword($password);
        $pin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerRegisterEvent($this, $player, $password, $email, $pin, $xbox == "false" ? self::NORMAL : self::XBOX));
        if (!$event->isCancelled()) {
            $this->database->insertData($player, $password, $email, $pin, $xbox);
            $this->getServer()->getScheduler()->scheduleDelayedTask(new ForceTask($this, $player, $xbox), 10);
            if ($this->getConfig()->getNested("progress-reports.enabled")) {
                if ($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number") >= 0 && floor($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) == $this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) {
                    $this->emailUser($this->api, $this->domain, $this->getConfig()->getNested("progress-reports.progress-report-email"), $this->from, "Server Progress Report", str_replace("{port}", $this->getServer()->getPort(), str_replace("{ip}", $this->getServer()->getIP(), str_replace("{players}", $this->database->getRegisteredCount(), str_replace("{player}", $player->getName(), $this->getMessage("progress-reports.progress-report"))))));
                }
            }
        }
        return true;
    }

    public function preregister($sender, $player, $password, $confirmpassword, $email = "none") {
        if (isset($this->confirmPassword[strtolower($player)])) {
            unset($this->confirmPassword[strtolower($player)]);
        }
        if ($this->isBlocked($player)) {
            $sender->sendMessage($this->getMessage("account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->isRegistered($player)) {
            $sender->sendMessage($this->getMessage("already-registered-two"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::ALREADY_REGISTERED));
            return false;
        }
        if ($password !== $confirmpassword) {
            $sender->sendMessage($this->getMessage("password-not-match"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORDS_NOT_MATCHED));
            return false;
        }
        if ($this->isPasswordBlocked($password)) {
            $sender->sendMessage($this->getMessage("password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($password) == strtolower($player) || strpos($password, strtolower($player)) !== false) {
            $sender->sendMessage($this->getMessage("password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_USERNAME));
            return false;
        }
        if (strlen($password) < $this->getConfig()->getNested("register.minimum-password-length")) {
            $sender->sendMessage($this->getMessage("password-too-short"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_TOO_SHORT));
            return false;
        }
        $password = $this->hashPassword($password);
        $pin = mt_rand(1000, 9999);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerPreregisterEvent($this, $sender, $player, $password, $email, $pin));
        if (!$event->isCancelled()) {
            $this->database->insertDataWithoutPlayerObject($player, $password, $email, $pin);
            $p = $this->getServer()->getPlayerExact($player);
            if ($p instanceof Player) {
                $this->getServer()->getScheduler()->scheduleDelayedTask(new ForceTask($this, $p), 10);
            }
            if ($this->getConfig()->getNested("progress-reports.enabled")) {
                if ($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number") >= 0 && floor($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) == $this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) {
                    $this->emailUser($this->api, $this->domain, $this->getConfig()->getNested("progress-reports.progress-report-email"), $this->from, "Server Progress Report", str_replace("{port}", $this->getServer()->getPort(), str_replace("{ip}", $this->getServer()->getIP(), str_replace("{players}", $this->database->getRegisteredCount(), str_replace("{player}", $player, $this->getMessage("progress-reports.progress-report"))))));
                }
            }
            $sender->sendMessage($this->getMessage("preregister-success"));
        }
        return true;
    }

    public function unregister(Player $player, $password) {
        if (!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::UNREGISTER, self::NOT_REGISTERED));
            return false;
        }
        if (!$this->isCorrectPassword($player, $password)) {
            $player->sendMessage($this->getMessage("incorrect-password-other"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::UNREGISTER, self::WRONG_PASSWORD));
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerUnregisterEvent($this, $player));
        if (!$event->isCancelled()) {
            if (isset($this->authenticated[strtolower($player->getName())])) {
                unset($this->authenticated[strtolower($player->getName())]);
            }
            $this->database->clearPassword($player->getName());
            $this->getServer()->getScheduler()->scheduleDelayedTask(new StartSessionTask($this, $player), 10);
            $player->sendMessage($this->getMessage("unregister-success"));
        }
        return true;
    }

    public function changepassword(Player $player, $oldpassword, $newpassword) {
        if (!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::NOT_REGISTERED));
            return false;
        }
        if (!$this->isCorrectPassword($player, $oldpassword)) {
            $player->sendMessage($this->getMessage("incorrect-password-other"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::WRONG_PASSWORD));
            return false;
        }
        if ($this->isPasswordBlocked($newpassword)) {
            $player->sendMessage($this->getMessage("password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($newpassword) == strtolower($player->getName()) || strpos($newpassword, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->getMessage("password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::PASSWORD_USERNAME));
            return false;
        }
        $newpassword = $this->hashPassword($newpassword);
        $oldpassword = $this->hashPassword($oldpassword);
        $oldpin = $this->database->getPin($player->getName());
        $pin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerChangePasswordEvent($this, $player, $oldpassword, $newpassword, $oldpin, $pin));
        if (!$event->isCancelled()) {
            $this->database->updatePlayer($player->getName(), $newpassword, $this->database->getEmail($player->getName()), $pin, $player->getAddress(), $player->getUniqueId()->toString(), 0);
            $player->sendMessage(str_replace("{pin}", $pin, $this->getMessage("change-password-success")));
            if ($this->getConfig()->getNested("emails.send-email-on-changepassword") && $this->database->getEmail($player) !== "none") {
                $this->emailUser($this->api, $this->domain, $this->database->getEmail($player->getName()), $this->from, $this->getMessage("email-subject-changedpassword"), $this->getMessage("email-changedpassword"));
            }
        }
        return true;
    }

    public function forgotpassword(Player $player, $pin, $newpassword) {
        if (!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::NOT_REGISTERED));
            return false;
        }
        if ($this->isAuthenticated($player)) {
            $player->sendMessage($this->getMessage("already-authenticated"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::ALREADY_AUTHENTICATED));
            return false;
        }
        if (!$this->isCorrectPin($player, $pin)) {
            $player->sendMessage($this->getMessage("incorrect-pin"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::WRONG_PIN));
            return false;
        }
        if (in_array($player->getName(), $this->getConfig()->getNested("pin.cant-use-pin"))) {
            $player->sendMessage($this->getMessage("cant-use-pin"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::CANT_USE_PIN));
            return false;
        }
        if ($this->isPasswordBlocked($newpassword)) {
            $player->sendMessage($this->getMessage("password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($newpassword) == strtolower($player->getName()) || strpos($newpassword, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->getMessage("password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::PASSWORD_USERNAME));
            return false;
        }
        $newpassword = $this->hashPassword($newpassword);
        ;
        $newpin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerForgetPasswordEvent($this, $player, $newpassword, $pin, $newpin));
        if (!$event->isCancelled()) {
            $this->database->updatePlayer($player->getName(), $newpassword, $this->database->getEmail($player->getName()), $newpin, $this->database->getIP($player->getName()), $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()));
            $player->sendMessage(str_replace("{pin}", $newpin, $this->getMessage("forgot-password-success")));
            if ($this->getConfig()->getNested("emails.send-email-on-changepassword") && $this->database->getEmail($player) !== "none") {
                $this->emailUser($this->api, $this->domain, $this->database->getEmail($player->getName()), $this->from, $this->getMessage("email-subject-changedpassword"), $this->getMessage("email-changedpassword"));
            }
        }
    }

    public function resetpassword($player, $sender) {
        $player = strtolower($player);
        if ($this->isRegistered($player)) {
            $this->getServer()->getPluginManager()->callEvent($event = new PlayerResetPasswordEvent($this, $sender, $player));
            if (!$event->isCancelled()) {
                if ($this->getConfig()->getNested("emails.send-email-on-resetpassword") && $this->database->getEmail($player) !== "none") {
                    $this->emailUser($this->api, $this->domain, $this->database->getEmail($player), $this->from, $this->getMessage("email-subject-passwordreset"), $this->getMessage("email-passwordreset"));
                }
                $this->database->clearPassword($player);
                if (isset($this->authenticated[$player])) {
                    unset($this->authenticated[$player]);
                }
                $playerobject = $this->getServer()->getPlayerExact($player);
                if ($playerobject instanceof Player) {
                    $this->getServer()->getScheduler()->scheduleDelayedTask(new StartSessionTask($this, $playerobject), 10);
                }
                $sender->sendMessage($this->getMessage("password-reset-success"));
                return true;
            }
        }
        $sender->sendMessage($this->getMessage("not-registered-two"));
        $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::RESET_PASSWORD, self::NOT_REGISTERED));
        return false;
    }

    public function logout(Player $player, $quit = true) {
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerLogoutEvent($this, $player));
        if (!$event->isCancelled()) {
            if ($this->isAuthenticated($player)) {
                unset($this->authenticated[strtolower($player->getName())]);
                if (!$quit) {
                    $this->startSession($player);
                }
            } else {
                if ($this->getConfig()->getNested("login.adventure-mode")) {
                    if (isset($this->gamemode[strtolower($player->getName())])) {
                        $player->setGamemode($this->gamemode[strtolower($player->getName())]);
                        unset($this->gamemode[strtolower($player->getName())]);
                    }
                }
                if (isset($this->confirmPassword[strtolower($player->getName())])) {
                    unset($this->confirmPassword[strtolower($player->getName())]);
                }
                if (isset($this->messagetick[strtolower($player->getName())])) {
                    unset($this->messagetick[strtolower($player->getName())]);
                }
                if (isset($this->timeouttick[strtolower($player->getName())])) {
                    unset($this->timeouttick[strtolower($player->getName())]);
                }
                if (isset($this->tries[strtolower($player->getName())])) {
                    unset($this->tries[strtolower($player->getName())]);
                }
                if ($this->getConfig()->getNested("message.boss-bar")) {
                    if (isset($this->wither[strtolower($player->getName())])) {
                        $this->wither[strtolower($player->getName())]->kill();
                        unset($this->wither[strtolower($player->getName())]);
                    }
                }
            }
        }
    }

    public function getMessage($message) {
        return str_replace("&", "§", $this->getConfig()->getNested($message));
    }

    public function emailUser($api, $domain, $to, $from, $subject, $body, $player = null) {
        $task = new SendEmailTask($api, $domain, $to, $from, $subject, $body, $player);
        $this->getServer()->getScheduler()->scheduleAsyncTask($task);
    }

    public function startSession(Player $player) {
        if (in_array(strtolower($player->getName()), $this->getConfig()->getNested("login.accounts-bypassed"))) {
            $this->authenticated[strtolower($player->getName())] = true;
            return true;
        }
        $player->sendMessage($this->getMessage("join-message"));
        $this->messagetick[strtolower($player->getName())] = 0;
        if ($this->getConfig()->getNested("register.cape-for-registration")) {
            $stevecapes = array(
                "Minecon_MineconSteveCape2016",
                "Minecon_MineconSteveCape2015",
                "Minecon_MineconSteveCape2013",
                "Minecon_MineconSteveCape2012",
                "Minecon_MineconSteveCape2011");
            if (in_array($player->getSkinId(), $stevecapes)) {
                $this->keepCape[strtolower($player->getName())] = $player->getSkinId();
                $player->setSkin($player->getSkinData(), "Standard_Custom");
            } else {
                $alexcapes = array(
                    "Minecon_MineconAlexCape2016",
                    "Minecon_MineconAlexCape2015",
                    "Minecon_MineconAlexCape2013",
                    "Minecon_MineconAlexCape2012",
                    "Minecon_MineconAlexCape2011");
                if (in_array($player->getSkinId(), $alexcapes)) {
                    $this->keepCape[strtolower($player->getName())] = $player->getSkinId();
                    $player->setSkin($player->getSkinData(), "Standard_CustomSlim");
                }
            }
        }
        if ($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("login-message"));
        } else {
            $player->sendMessage($this->getMessage("register-message"));
        }
        if ($this->getConfig()->getNested("effects.invisible")) {
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
            $player->setNameTagVisible(false);
        }
        if ($this->getConfig()->getNested("effects.blindness")) {
            $effect = Effect::getEffect(15);
            $effect->setAmplifier(99);
            $effect->setDuration(999999);
            $effect->setVisible(false);
            $player->addEffect($effect);
            $effect = Effect::getEffect(16);
            $effect->setAmplifier(99);
            $effect->setDuration(999999);
            $effect->setVisible(false);
            $player->addEffect($effect);
        }
        if ($this->getConfig()->getNested("effects.hide-items")) {
            $player->getInventory()->sendContents($player);
        }
        if ($this->getConfig()->getNested("effects.hide-players")) {
            foreach ($this->getServer()->getOnlinePlayers() as $p) {
                $player->hidePlayer($p);
                if (!$this->isAuthenticated($p)) {
                    $p->hidePlayer($player);
                }
            }
        }
        if ($this->getConfig()->getNested("effects.hide-effects")) {
            foreach ($player->getEffects() as $effect) {
                if ($this->getConfig()->getNested("blindness") && ($effect->getId() == 15 || $effect->getId() == 16)) {
                    continue;
                }
                $pk = new MobEffectPacket();
                $pk->eid = $player->getId();
                $pk->eventId = MobEffectPacket::EVENT_REMOVE;
                $pk->effectId = $effect->getId();
                $player->dataPacket($pk);
            }
        }
        if ($this->getConfig()->getNested("login.adventure-mode")) {
            $this->gamemode[strtolower($player->getName())] = $player->getGamemode();
            $player->setGamemode(2);
        }
        if ($this->getConfig()->getNested("timeout.enabled")) {
            $this->timeouttick[strtolower($player->getName())] = 0;
        }
        if ($this->getConfig()->getNested("message.boss-bar")) {
            $wither = Entity::createEntity("Wither", $player->getLevel(), new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x + 0.5), new DoubleTag("", $player->y + 25), new DoubleTag("", $player->z + 0.5)]), "Motion" => new ListTag("Motion", [new DoubleTag("", 0), new DoubleTag("", 0), new DoubleTag("", 0)]), "Rotation" => new ListTag("Rotation", [new FloatTag("", 0), new FloatTag("", 0)])]));
            $wither->spawnTo($player);
            $wither->setNameTag($this->isRegistered($player->getName()) == false ? $this->getMessage("register-boss-bar") : $this->getMessage("login-boss-bar"));
            $this->wither[strtolower($player->getName())] = $wither;
            $wither->setMaxHealth($this->getConfig()->getNested("timeout.timeout-time"));
            $wither->setHealth($this->getConfig()->getNested("timeout.timeout-time"));
            $pk = new BossEventPacket();
            $pk->eid = $wither->getId();
            $pk->state = 0;
            $player->dataPacket($pk);
        }
    }

    public function isTooManyIPOnline(Player $player) {
        $players = 0;
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            if ($p !== $player) {
                if ($p->getAddress() == $player->getAddress()) {
                    if ($this->isAuthenticated($p)) {
                        $players++;
                    }
                }
            }
        }
        return $players > ($this->getConfig()->getNested("login.ip-limit") - 1);
    }

    public function hashPassword($password) {
        $options = ['cost' => $this->getConfig()->getNested("hash.cost")];
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    public function needsRehashPassword($password, $plainpassword) {
        $options = ['cost' => $this->getConfig()->getNested("hash.cost")];
        if (password_needs_rehash($password, PASSWORD_BCRYPT, $options)) {
            return password_hash($plainpassword, PASSWORD_BCRYPT, $options);
        }
        return null;
    }

    public function getKey($password) {
        if (password_verify($password, $this->database->getPassword($this->getConfig()->getNested("key.owner")))) {
            return $this->key;
        }
        return false;
    }

    public function changeKey() {
        array_push($this->expiredkeys, $this->key);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $key = [];
        $characteramount = strlen($characters) - 1;
        for ($i = 0; $i < $this->getConfig()->getNested("register.minimum-password-length"); $i++) {
            $character = mt_rand(0, $characteramount);
            array_push($key, $characters[$character]);
        }
        $key = implode("", $key);
        if ($this->key == $key) {
            $this->changeKey();
            return false;
        }
        $this->key = $key;
        return true;
    }

}
