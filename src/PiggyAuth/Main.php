<?php

namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
use PiggyAuth\Commands\ChangeEmailCommand;
use PiggyAuth\Commands\ConvertCommand;
use PiggyAuth\Commands\ForgotPasswordCommand;
use PiggyAuth\Commands\LoginCommand;
use PiggyAuth\Commands\LogoutCommand;
use PiggyAuth\Commands\KeyCommand;
use PiggyAuth\Commands\PinCommand;
use PiggyAuth\Commands\PreregisterCommand;
use PiggyAuth\Commands\RegisterCommand;
use PiggyAuth\Commands\ResetPasswordCommand;
use PiggyAuth\Commands\SendPinCommand;
use PiggyAuth\Commands\SetLanguageCommand;
use PiggyAuth\Commands\UnregisterCommand;
use PiggyAuth\Converter\ServerAuthConverter;
use PiggyAuth\Converter\SimpleAuthConverter;
use PiggyAuth\Databases\IndividualFiles;
use PiggyAuth\Emails\EmailManager;
use PiggyAuth\Events\PlayerChangePasswordEvent;
use PiggyAuth\Events\PlayerFailEvent;
use PiggyAuth\Events\PlayerForgetPasswordEvent;
use PiggyAuth\Events\PlayerLoginEvent;
use PiggyAuth\Events\PlayerLogoutEvent;
use PiggyAuth\Events\PlayerPreregisterEvent;
use PiggyAuth\Events\PlayerRegisterEvent;
use PiggyAuth\Events\PlayerResetPasswordEvent;

use PiggyAuth\Events\PlayerUnregisterEvent;
use PiggyAuth\Databases\MySQL;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Entities\Wither;
use PiggyAuth\Language\LanguageManager;
use PiggyAuth\Packet\BossEventPacket;
use PiggyAuth\Sessions\SessionManager;
use PiggyAuth\Tasks\AttributeTick;
use PiggyAuth\Tasks\AutoUpdaterTask;
use PiggyAuth\Tasks\DelayedPinTask;
use PiggyAuth\Tasks\KeyTick;
use PiggyAuth\Tasks\MessageTick;
use PiggyAuth\Tasks\PingTask;
use PiggyAuth\Tasks\OtherMessageTypeTick;
use PiggyAuth\Tasks\TimeoutTask;

use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;

use pocketmine\entity\Entity;


use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

/**
 * Class Main
 * @package PiggyAuth
 */
class Main extends PluginBase
{
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

    public $database;
    public $emailmanager;
    public $expiredkeys = [];
    private $key = "PiggyAuthKey";
    public $keytime = 299; //300 = Reset
    public $languagemanager;
    public $serverauthconverter;
    public $sessionmanager;
    public $simpleauthconverter;
    public $tries;

    public function onEnable()
    {
        @mkdir($this->getDataFolder() . "convert");
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('changeemail', new ChangeEmailCommand('changeemail', $this));
        $this->getServer()->getCommandMap()->register("convert", new ConvertCommand("convert", $this));
        $this->getServer()->getCommandMap()->register('forgotpassword', new ForgotPasswordCommand('forgotpassword', $this));
        $this->getServer()->getCommandMap()->register('key', new KeyCommand('key', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('logout', new LogoutCommand('logout', $this));
        $this->getServer()->getCommandMap()->register('pin', new PinCommand('pin', $this));
        $this->getServer()->getCommandMap()->register('preregister', new PreregisterCommand('preregister', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getCommandMap()->register('sendpin', new SendPinCommand('sendpin', $this));
        $this->getServer()->getCommandMap()->register("setlanguage", new SetLanguageCommand("setlanguage", $this));
        $this->getServer()->getCommandMap()->register('unregister', new UnregisterCommand('unregister', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new AttributeTick($this), 20);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), 20);
        if ($this->getConfig()->getNested("key.enabled")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new KeyTick($this), 20);
        }
        if ($this->getConfig()->getNested("message.popup") || $this->getConfig()->getNested("message.tip") || $this->getConfig()->getNested("message.boss-bar")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new OtherMessageTypeTick($this), 20);
        }
        if ($this->getConfig()->getNested("timeout.enabled")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new TimeoutTask($this), 20);
        }
        if ($this->getConfig()->getNested("message.boss-bar")) {
            Entity::registerEntity(Wither::class);
            $this->getServer()->getNetwork()->registerPacket(BossEventPacket::NETWORK_ID, BossEventPacket::class);
        }
        switch ($this->getConfig()->getNested("database")) {
            case "mysql":
                $this->database = new MySQL($this);
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new PingTask($this, $this->database), 300);
                break;
            case "sqlite3":
                $this->database = new SQLite3($this);
                break;
            case "yml":
            case "yaml":
                $this->database = new IndividualFiles($this, "yml");
                break;
            case "json":
                $this->database = new IndividualFiles($this, "json");
                break;
            default:
                $this->database = new SQLite3($this);
                $this->getLogger()->error("§cDatabase not found, using default.");
                break;
        }
        $this->sessionmanager = new SessionManager($this);
        $this->languagemanager = new LanguageManager($this);
        $this->emailmanager = new EmailManager($this, $this->getConfig()->getNested("emails.mailgun.domain"), $this->getConfig()->getNested("emails.mailgun.api"), $this->getConfig()->getNested("emails.mailgun.public-api"), $this->getConfig()->getNested("emails.mailgun.from"));
        $this->simpleauthconverter = new SimpleAuthConverter($this);
        $this->serverauthconverter = new ServerAuthConverter($this);
        if ($this->getConfig()->getNested("auto-updater.enabled")) { //Should do after LanguageManager is initiated...
            $this->getLogger()->error("Sorry, we have temporarily disabled Auto Updater due to requests from @SOF3.");
            //$this->getServer()->getScheduler()->scheduleAsyncTask(new AutoUpdaterTask($this->getConfig()->getNested("auto-updater.auto-install")));
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        foreach ($this->getServer()->getOnlinePlayers() as $player) { //Reload, players still here but plugin restarts!
            $this->sessionmanager->loadSession($player);
        }
        $this->getLogger()->info("§aEnabled.");
    }

    public function onDisable()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->logout($player);
        }
    }

    /**
     * @return MySQL|SQLite3|IndividualFiles
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return SessionManager
     */
    public function getSessionManager()
    {
        return $this->sessionmanager;

    }

    /**
     * @return LanguageManager
     */
    public function getLanguageManager()
    {
        return $this->languagemanager;
    }

    /**
     * @return EmailManager
     */
    public function getEmailManager()
    {
        return $this->emailmanager;
    }

    /**
     * @param Player $player
     * @return int
     */
    public function generatePin(Player $player)
    {
        $newpin = mt_rand(1000, 9999);
        if ($this->isCorrectPin($player, $newpin) || $newpin == 1234) { //Player cant have same pin or have 1234 as pin
            return $this->generatePin($player);
        }
        return $newpin;
    }

    /**
     * @param Player $player
     * @param $password
     * @return bool
     */
    public function isCorrectPassword(Player $player, $password)
    {
        if (strpos($this->sessionmanager->getSession($player)->getOriginAuth(), "ServerAuth") !== false) {
            $auth = explode("_", $this->sessionmanager->getSession($player)->getOriginAuth());
            if (isset($auth[0]) && isset($auth[1])) {
                if (hash($auth[1], $password) == $this->sessionmanager->getSession($player)->getPassword()) {
                    $this->sessionmanager->getSession($player)->updatePlayer("auth", "PiggyAuth");
                    return true;
                }
                return false;
            }
        }
        switch ($this->sessionmanager->getSession($player)->getOriginAuth()) {
            case "SimpleAuth":
                if (hash_equals($this->sessionmanager->getSession($player)->getPassword(), $this->hashSimpleAuth(strtolower($player->getName()), $password))) {
                    $this->sessionmanager->getSession($player)->updatePlayer("auth", "PiggyAuth");
                    return true;
                }
                return false;
            case "PiggyAuth":
            default:
                if (password_verify($password, $this->sessionmanager->getSession($player)->getPassword())) {
                    return true;
                }
                return false;
        }
    }

    /**
     * @param Player $player
     * @param $pin
     * @return bool
     */
    public function isCorrectPin(Player $player, $pin)
    {
        if ($pin == $this->sessionmanager->getSession($player)->getPin()) {
            return true;
        }
        return false;
    }

    /**
     * @param $player
     * @return bool
     */
    public function isBlocked($player)
    {
        return in_array(strtolower($player), $this->getConfig()->getNested("register.blocked-accounts"));
    }

    /**
     * @param $password
     * @return bool
     */
    public function isPasswordBlocked($password)
    {
        return in_array(strtolower($password), $this->getConfig()->getNested("register.blocked-passwords"));
    }

    /**
     * @param Player $player
     * @param $password
     * @param int $mode
     * @return bool
     */
    public function login(Player $player, $password, $mode = 0)
    {
        if ($this->isBlocked($player->getName())) {
            $player->sendMessage($this->languagemanager->getMessage($player, "account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->sessionmanager->getSession($player)->isAuthenticated()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "already-authenticated"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::ALREADY_AUTHENTICATED));
            return false;
        }
        if (!$this->sessionmanager->getSession($player)->isRegistered()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "not-registered"));
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
                    $player->sendMessage($this->languagemanager->getMessage($player, "key-expired"));
                    $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::KEY_EXPIRED));
                    return true;
                }
            }

            $this->sessionmanager->getSession($player)->addTry();
            if ($this->sessionmanager->getSession($player)->getTries() >= $this->getConfig()->getNested("login.tries")) {
                $this->sessionmanager->getSession($player)->updatePlayer("attempts", $this->sessionmanager->getSession($player)->getAttempts() + 1, 1);
                if ($this->getConfig()->getNested("emails.send-email-on-attemptedlogin")) {
                    $this->emailmanager->sendEmail($this->sessionmanager->getSession($player)->getEmail(), $this->languagemanager->getMessage($player, "email-subject-attemptedlogin"), $this->languagemanager->getMessage($player, "email-attemptedlogin"));
                }
                $player->kick($this->languagemanager->getMessage($player, "too-many-tries"));
                return false;
            }
            $tries = $this->getConfig()->getNested("login.tries") - $this->sessionmanager->getSession($player)->getTries();
            $player->sendMessage(str_replace("{tries}", $tries, $this->languagemanager->getMessage($player, "incorrect-password")));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::WRONG_PASSWORD));
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this, $player, self::NORMAL));
        if (!$event->isCancelled()) {
            if ($player->getAddress() !== $this->sessionmanager->getSession($player)->getIP()) {
                if ($this->getConfig()->getNested("emails.send-email-on-login-from-new-ip")) {
                    $this->emailmanager->sendEmail($this->sessionmanager->getSession($player)->getEmail(), $this->languagemanager->getMessage($player, "email-subject-login-from-new-ip"), str_replace("{ip}", $player->getAddress(), $this->languagemanager->getMessage($player, "email-login-from-new-ip")));
                }
            }
            $rehashedpassword = $this->needsRehashPassword($this->sessionmanager->getSession($player)->getPassword(), $password);
            $this->force($player, true, $mode, $rehashedpassword);
        }
        return true;
    }

    /**
     * @param Player $player
     * @param bool $login
     * @param int $mode
     * @param null $rehashedpassword
     * @return bool
     */
    public function force(Player $player, $login = true, $mode = 0, $rehashedpassword = null)
    {
        if ($login) {
            if ($this->isTooManyIPOnline($player)) {
                $player->sendMessage($this->languagemanager->getMessage($player, "too-many-on-ip"));
                $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::LOGIN, self::TOO_MANY_ON_IP));
                return false;
            }
            switch ($mode) {
                case 1:
                    $player->sendMessage($this->languagemanager->getMessage($player, "authentication-success-uuid"));
                    break;
                case 2:
                    $player->sendMessage($this->languagemanager->getMessage($player, "authentication-success-xbox"));
                    break;
                case 0:
                default:
                    $player->sendMessage($this->languagemanager->getMessage($player, "authentication-success"));
                    break;
            }
            if (!$this->sessionmanager->getSession($player)->getAttempts() == 0) {
                $player->sendMessage(str_replace("{attempts}", $this->sessionmanager->getSession($player)->getAttempts(), $this->languagemanager->getMessage($player, "attempted-logins")));
                $this->sessionmanager->getSession($player)->updatePlayer("attempts", 0);
            }
        }
        $this->sessionmanager->getSession($player)->setMessageTick(0);
        $this->sessionmanager->getSession($player)->setTimeoutTick(0);
        $this->sessionmanager->getSession($player)->setTries(0);
        if ($this->getConfig()->getNested("message.hold-join-message")) {
            if ($this->sessionmanager->getSession($player)->getJoinMessage() !== null) {
                $this->getServer()->broadcastMessage($this->sessionmanager->getSession($player)->getJoinMessage());
                $this->sessionmanager->getSession($player)->setJoinMessage(null);
            }
        }
        $this->sessionmanager->getSession($player)->setAuthenticated();
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
            if ($this->sessionmanager->getSession($player)->getCape() !== null) {
                $cape = $this->sessionmanager->getSession($player)->getCape();
                $this->sessionmanager->getSession($player)->setCape(null);
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
            if ($this->sessionmanager->getSession($player)->getGamemode() !== null) {
                $player->setGamemode($this->sessionmanager->getSession($player)->getGamemode());
                $this->sessionmanager->getSession($player)->setGamemode(null);
            }
        }
        if ($this->getConfig()->getNested("message.boss-bar")) {
            if ($this->sessionmanager->getSession($player)->getWither() !== null) {
                $this->sessionmanager->getSession($player)->getWither()->kill();
                $this->sessionmanager->getSession($player)->setWither(null);
            }
        }
        if ($rehashedpassword !== null) {
            $this->sessionmanager->getSession($player)->updatePlayer("password", $rehashedpassword);
        }
        $this->sessionmanager->getSession($player)->updatePlayer("ip", $player->getAddress());
        $this->sessionmanager->getSession($player)->updatePlayer("uuid", $player->getUniqueId()->toString());
        return true;
    }

    /**
     * @param Player $player
     * @param $password
     * @param $confirmpassword
     * @param string $email
     * @param bool $xbox
     * @return bool
     */
    public function register(Player $player, $password, $confirmpassword, $email = "none", $xbox = false)
    {
        $this->sessionmanager->getSession($player)->setSecondPassword(null);
        if ($this->isBlocked($player->getName())) {
            $player->sendMessage($this->languagemanager->getMessage($player, "account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->sessionmanager->getSession($player)->isRegistered()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "already-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::ALREADY_REGISTERED));
            return false;
        }
        if ($password !== $confirmpassword) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-not-match"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORDS_NOT_MATCHED));
            return false;
        }
        if ($this->isPasswordBlocked($password)) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($password) == strtolower($player->getName()) || strpos($password, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_USERNAME));
            return false;
        }
        if (strlen($password) < $this->getConfig()->getNested("register.minimum-password-length")) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-too-short"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::REGISTER, self::PASSWORD_TOO_SHORT));
            return false;
        }
        $password = $this->hashPassword($password);
        $pin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerRegisterEvent($this, $player, $password, $email, $pin, $xbox == "false" ? self::NORMAL : self::XBOX));
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
            $this->sessionmanager->getSession($player)->insertData($password, $email, $pin, $xbox, $callback, $args);
            if ($this->getConfig()->getNested("progress-reports.enabled")) {
                if ($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number") >= 0 && floor($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) == $this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) {
                    $this->emailmanager->sendEmail($this->getConfig()->getNested("progress-reports.progress-report-email"), "Server Progress Report", str_replace("{port}", $this->getServer()->getPort(), str_replace("{ip}", $this->getServer()->getIP(), str_replace("{players}", $this->database->getRegisteredCount(), str_replace("{player}", $player->getName(), $this->languagemanager->getMessageFromLanguage($this->languagemanager->getDefaultLanguage(), "progress-reports.progress-report"))))));
                }
            }
        }
        return true;
    }

    /**
     * @param CommandSender $sender
     * @param $player
     * @param $password
     * @param $confirmpassword
     * @param string $email
     * @return bool
     */
    public function preregister($sender, $player, $password, $confirmpassword, $email = "none")
    {
        if ($this->isBlocked($player)) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "account-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::ACCOUNT_BLOCKED));
            return false;
        }
        if ($this->database->getOfflinePlayer($player) !== null) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "already-registered-two"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::ALREADY_REGISTERED));
            return false;
        }
        if ($password !== $confirmpassword) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "password-not-match"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORDS_NOT_MATCHED));
            return false;
        }
        if ($this->isPasswordBlocked($password)) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($password) == strtolower($player) || strpos($password, strtolower($player)) !== false) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_USERNAME));
            return false;
        }
        if (strlen($password) < $this->getConfig()->getNested("register.minimum-password-length")) {
            $sender->sendMessage($this->languagemanager->getMessage($sender, "password-too-short"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::PREREGISTER, self::PASSWORD_TOO_SHORT));
            return false;
        }
        $password = $this->hashPassword($password);
        $pin = mt_rand(1000, 9999);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerPreregisterEvent($this, $sender, $player, $password, $email, $pin));
        if (!$event->isCancelled()) {
            $callback = function ($result, $args, $plugin) {
                $player = $this->getServer()->getPlayerExact($args[0]);
                if ($player instanceof Player) {
                    $plugin->force($player, false);
                    $player->sendMessage(str_replace("{pin}", $plugin->sessionmanager->getSession($player)->getPin(), $plugin->languagemanager->getMessage($player, "register-success")));
                }
            };
            $args = array($player);
            $this->database->insertDataWithoutPlayerObject($player, $password, $email, $pin, "PiggyAuth", $callback, $args);
            if ($this->getConfig()->getNested("progress-reports.enabled")) {
                if ($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number") >= 0 && floor($this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) == $this->database->getRegisteredCount() / $this->getConfig()->getNested("progress-reports.progress-report-number")) {
                    $this->emailmanager->sendEmail($this->getConfig()->getNested("progress-reports.progress-report-email"), "Server Progress Report", str_replace("{port}", $this->getServer()->getPort(), str_replace("{ip}", $this->getServer()->getIP(), str_replace("{players}", $this->database->getRegisteredCount(), str_replace("{player}", $player, $this->languagemanager->getMessageFromLanguage($this->languagemanager->getDefaultLanguage(), "progress-reports.progress-report"))))));
                }
            }
            $sender->sendMessage($this->languagemanager->getMessage($sender, "preregister-success"));
        }
        return true;
    }

    /**
     * @param Player $player
     * @param $password
     * @return bool
     */
    public function unregister(Player $player, $password)
    {
        if (!$this->sessionmanager->getSession($player)->isRegistered()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::UNREGISTER, self::NOT_REGISTERED));
            return false;
        }
        if (!$this->isCorrectPassword($player, $password)) {
            $player->sendMessage($this->languagemanager->getMessage($player, "incorrect-password-other"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::UNREGISTER, self::WRONG_PASSWORD));
            return false;
        }
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerUnregisterEvent($this, $player));
        if (!$event->isCancelled()) {
            $callback = function ($result, $args, $plugin) {
                $player = $plugin->getServer()->getPlayerExact($args[0]);
                if ($player instanceof Player) {
                    $plugin->logout($player, false);
                }
            };
            $args = array($player->getName());
            $this->sessionmanager->getSession($player)->clearPassword($callback, $args);
            $player->sendMessage($this->languagemanager->getMessage($player, "unregister-success"));
        }
        return true;
    }

    /**
     * @param Player $player
     * @param $oldpassword
     * @param $newpassword
     * @return bool
     */
    public function changepassword(Player $player, $oldpassword, $newpassword)
    {
        if (!$this->sessionmanager->getSession($player)->isRegistered()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::NOT_REGISTERED));
            return false;
        }
        if (!$this->isCorrectPassword($player, $oldpassword)) {
            $player->sendMessage($this->languagemanager->getMessage($player, "incorrect-password-other"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::WRONG_PASSWORD));
            return false;
        }
        if ($this->isPasswordBlocked($newpassword)) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($newpassword) == strtolower($player->getName()) || strpos($newpassword, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::CHANGE_PASSWORD, self::PASSWORD_USERNAME));
            return false;
        }
        $newpassword = $this->hashPassword($newpassword);
        $oldpassword = $this->hashPassword($oldpassword);
        $oldpin = $this->sessionmanager->getSession($player)->getPin();
        $pin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerChangePasswordEvent($this, $player, $oldpassword, $newpassword, $oldpin, $pin));
        if (!$event->isCancelled()) {
            $this->sessionmanager->getSession($player)->updatePlayer("password", $newpassword);
            $this->sessionmanager->getSession($player)->updatePlayer("pin", $pin, 1);
            $player->sendMessage(str_replace("{pin}", $pin, $this->languagemanager->getMessage($player, "change-password-success")));
            if ($this->getConfig()->getNested("emails.send-email-on-changepassword")) {
                $this->emailmanager->sendEmail($this->sessionmanager->getSession($player)->getEmail(), $this->languagemanager->getMessage($player, "email-subject-changedpassword"), $this->languagemanager->getMessage($player, "email-changedpassword"));
            }
        }
        return true;
    }

    /**
     * @param Player $player
     * @param $pin
     * @param $newpassword
     * @return bool
     */
    public function forgotpassword(Player $player, $pin, $newpassword)
    {
        if (!$this->sessionmanager->getSession($player)->isRegistered()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "not-registered"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::NOT_REGISTERED));
            return false;
        }
        if ($this->sessionmanager->getSession($player)->isAuthenticated()) {
            $player->sendMessage($this->languagemanager->getMessage($player, "already-authenticated"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::ALREADY_AUTHENTICATED));
            return false;
        }
        if (!$this->isCorrectPin($player, $pin)) {
            $this->sessionmanager->getSession($player)->addTry();
            if ($this->sessionmanager->getSession($player)->getTries() >= $this->getConfig()->getNested("login.tries")) {
                $this->sessionmanager->getSession($player)->updatePlayer("attempts", $this->sessionmanager->getSession($player)->getAttempts() + 1, 1);
                if ($this->getConfig()->getNested("emails.send-email-on-attemptedlogin")) {
                    $this->emailmanager->sendEmail($this->sessionmanager->getSession($player)->getEmail(), $this->languagemanager->getMessage($player, "email-subject-attemptedlogin"), $this->languagemanager->getMessage($player, "email-attemptedlogin"));
                }
                $player->kick($this->languagemanager->getMessage($player, "too-many-tries"));
                return false;
            }
            $tries = $this->getConfig()->getNested("login.tries") - $this->sessionmanager->getSession($player)->getTries();
            $player->sendMessage(str_replace("{tries}", $tries, $this->languagemanager->getMessage($player, "incorrect-pin")));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::WRONG_PIN));
            return false;
        }
        if (in_array($player->getName(), $this->getConfig()->getNested("pin.cant-use-pin"))) {
            $player->sendMessage($this->languagemanager->getMessage($player, "cant-use-pin"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::CANT_USE_PIN));
            return false;
        }
        if ($this->isPasswordBlocked($newpassword)) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-blocked"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::PASSWORD_BLOCKED));
            return false;
        }
        if (strtolower($newpassword) == strtolower($player->getName()) || strpos($newpassword, strtolower($player->getName())) !== false) {
            $player->sendMessage($this->languagemanager->getMessage($player, "password-username"));
            $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::FORGET_PASSWORD, self::PASSWORD_USERNAME));
            return false;
        }
        $newpassword = $this->hashPassword($newpassword);;
        $newpin = $this->generatePin($player);
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerForgetPasswordEvent($this, $player, $newpassword, $pin, $newpin));
        if (!$event->isCancelled()) {
            $callback = function ($result, $args, $plugin) {
                $player = $plugin->getServer()->getPlayerExact($args[0]);
                if ($player instanceof Player) {
                    $plugin->force($player, false);
                }
            };
            $args = array($player->getName());
            $this->sessionmanager->getSession($player)->updatePlayer("password", $newpassword, 0, $callback, $args);
            $this->sessionmanager->getSession($player)->updatePlayer("pin", $newpin, 1);
            $player->sendMessage(str_replace("{pin}", $newpin, $this->languagemanager->getMessage($player, "forgot-password-success")));
            if ($this->getConfig()->getNested("emails.send-email-on-changepassword")) {
                $this->emailmanager->sendEmail($this->sessionmanager->getSession($player)->getEmail(), $this->languagemanager->getMessage($player, "email-subject-changedpassword"), $this->languagemanager->getMessage($player, "email-changedpassword"));
            }
        }
    }

    /**
     * @param $player
     * @param $sender
     * @return bool
     */
    public function resetpassword($player, $sender)
    {
        $data = $this->database->getOfflinePlayer($player);
        if ($data !== null) {
            $this->getServer()->getPluginManager()->callEvent($event = new PlayerResetPasswordEvent($this, $sender, $player));
            if (!$event->isCancelled()) {
                if ($this->getConfig()->getNested("emails.send-email-on-resetpassword")) {
                    $this->emailmanager->sendEmail($this->database->getOfflinePlayer($player)["email"], $this->languagemanager->getMessageFromLanguage($this->languagemanager->getDefaultLanguage(), "email-subject-passwordreset"), $this->languagemanager->getMessageFromLanguage($this->languagemanager->getDefaultLanguage(), "email-passwordreset"));
                }
                $callback = function ($result, $args, $plugin) {
                    $player = $plugin->getServer()->getPlayerExact($args[0]);
                    if ($player instanceof Player) {
                        $plugin->logout($player, false);
                    }
                };
                $args = array($player);
                $this->database->clearPassword($player, $callback, $args);

                $sender->sendMessage($this->languagemanager->getMessage($sender, "password-reset-success"));
                return true;
            }

        }
        $sender->sendMessage($this->languagemanager->getMessage($sender, "not-registered-two"));
        $this->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this, $player, self::RESET_PASSWORD, self::NOT_REGISTERED));
        return false;
    }

    /**
     * @param Player $player
     * @param bool $quit
     */
    public function logout(Player $player, $quit = true)
    {
        $this->getServer()->getPluginManager()->callEvent($event = new PlayerLogoutEvent($this, $player));
        if (!$event->isCancelled()) {
            if ($this->sessionmanager->getSession($player) !== null && !$this->sessionmanager->getSession($player)->isAuthenticated()) {
                {
                    if ($this->getConfig()->getNested("login.adventure-mode")) {
                        if ($this->sessionmanager->getSession($player)->getGamemode() !== null) {
                            $player->setGamemode($this->sessionmanager->getSession($player)->getGamemode());
                            $this->sessionmanager->getSession($player)->setGamemode(null);
                        }
                    }
                    $this->sessionmanager->getSession($player)->setMessageTick(0);
                    $this->sessionmanager->getSession($player)->setTimeoutTick(0);
                    $this->sessionmanager->getSession($player)->setTries(0);
                    if ($this->getConfig()->getNested("message.boss-bar")) {
                        if ($this->sessionmanager->getSession($player)->getWither() !== null) {
                            $this->sessionmanager->getSession($player)->getWither()->kill();
                            $this->sessionmanager->getSession($player)->setWither(null);
                        }
                    }
                }
            }
            if ($quit !== true) {
                $this->sessionmanager->loadSession($player); //Reload
            } else {
                $this->sessionmanager->unloadSession($player);
            }
        }
    }

    /**
     * @param $player
     * @param $message
     * @return mixed
     */
    public function getMessage($player, $message)
    {
        return $this->languagemanager->getMessage($player, $message);
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isTooManyIPOnline(Player $player)
    {
        $players = 0;
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            if ($p !== $player) {
                if ($p->getAddress() == $player->getAddress()) {
                    if ($this->sessionmanager->getPlayer($p)->isAuthenticated()) {
                        $players++;
                    }
                }
            }
        }
        return $players > ($this->getConfig()->getNested("login.ip-limit") - 1);
    }

    /**
     * @param $password
     * @return bool|string
     */
    public function hashPassword($password)
    {
        $options = ['cost' => $this->getConfig()->getNested("hash.cost")];
        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * @param $password
     * @param $plainpassword
     * @return bool|null|string
     */
    public function needsRehashPassword($password, $plainpassword)
    {
        $options = ['cost' => $this->getConfig()->getNested("hash.cost")];
        if (password_needs_rehash($password, PASSWORD_BCRYPT, $options)) {
            return password_hash($plainpassword, PASSWORD_BCRYPT, $options);
        }
        return null;
    }

    /**
     * @param $password
     * @return bool|string
     */
    public function getKey($password)
    {
        if (password_verify($password, $this->database->getOfflinePlayer($this->getConfig()->getNested("key.owner"))["password"])) {
            return $this->key;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function changeKey()
    {
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

    /**
     * @return mixed
     */
    public function getFile()
    {
        return parent::getFile();
    }

    /**
     * @param $salt
     * @param $password
     * @return string
     */
    public function hashSimpleAuth($salt, $password)
    {
        return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
    }
}
