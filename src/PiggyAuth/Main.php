<?php
namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
use PiggyAuth\Commands\ChangeEmailCommand;
use PiggyAuth\Commands\PinCommand;
use PiggyAuth\Commands\ForgotPasswordCommand;
use PiggyAuth\Commands\LoginCommand;
use PiggyAuth\Commands\LogoutCommand;
use PiggyAuth\Commands\RegisterCommand;
use PiggyAuth\Commands\ResetPasswordCommand;
use PiggyAuth\Commands\SendPinCommand;
use PiggyAuth\Databases\MySQL;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Tasks\MessageTick;
use PiggyAuth\Tasks\PingTask;
use PiggyAuth\Tasks\PopupTipTick;
use PiggyAuth\Tasks\TimeoutTask;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class Main extends PluginBase {
    public $authenticated;
    public $confirmPassword;
    public $giveEmail;
    public $messagetick;
    public $tries;
    public $database;

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('changeemail', new ChangeEmailCommand('changeemail', $this));
        $this->getServer()->getCommandMap()->register('forgotpassword', new ForgotPasswordCommand('forgotpassword', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('logout', new LogoutCommand('logout', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('pin', new PinCommand('pin', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getCommandMap()->register('sendpin', new SendPinCommand('sendpin', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), 20);
        if($this->getConfig()->get("popup") || $this->getConfig()->get("tip")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTipTick($this), 20);
        }
        $outdated = false;
        if($this->getConfig()->get("config->update")) {
            if(!$this->getConfig()->exists("version")) {
                rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
                $this->saveDefaultConfig();
                $outdated = true;
            } elseif($this->getConfig()->get("version") !== $this->getDescription()->getVersion()) {
                switch($this->getConfig()->get("version")) {
                    case "1.0.9":
                    case "1.0.8":
                        rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
                        $this->saveDefaultConfig();
                        $outdated = true; //DB Updater
                        break;
                }
                $this->getConfig()->set("version", $this->getDescription()->getVersion());
                $this->getConfig()->save();
            }
        }
        switch($this->getConfig()->get("database")) {
            case "mysql":
                $this->database = new MySQL($this, $outdated);
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new PingTask($this), 300);
                break;
            case "sqlite3":
                $this->database = new SQLite3($this, $outdated);
                break;
            default:
                $this->database = new SQLite3($this, $outdated);
                $this->getLogger()->error("§cDatabase not found, using default.");
                break;
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("§aEnabled.");
    }

    public function getDatabase() {
        return $this->database;
    }

    public function generatePin(Player $player) {
        $newpin = mt_rand(1000, 9999);
        if($this->isCorrectPin($player, $newpin)) { //Player cant have same pin
            return $this->generatePin($player);
        }
        return $newpin;
    }

    public function isCorrectPassword(Player $player, $password) {
        if(password_verify($password, $this->database->getPassword($player->getName()))) {
            return true;
        }
        return false;
    }

    public function isCorrectPin(Player $player, $pin) {
        if($pin == $this->database->getPin($player->getName())) {
            return true;
        }
        return false;
    }

    public function isAuthenticated(Player $player) {
        if(isset($this->authenticated[strtolower($player->getName())])) return true;
        return false;
    }

    public function isRegistered($player) {
        return $this->database->getPlayer($player) !== null;
    }

    public function login(Player $player, $password) {
        if($this->isAuthenticated($player)) {
            $player->sendMessage($this->getMessage("already-authenticated"));
            return false;
        }
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            return false;
        }
        if(!$this->isCorrectPassword($player, $password)) {
            if(isset($this->tries[strtolower($player->getName())])) {
                $this->tries[strtolower($player->getName())]++;
                if($this->tries[strtolower($player->getName())] >= $this->getConfig()->get("tries")) {
                    $this->database->updatePlayer($player->getName(), $this->database->getPassword($player->getName()), $this->database->getEmail($player->getName()), $this->database->getPin($player->getName()), $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()) + 1);
                    $player->kick($this->getMessage("too-many-tries"));
                    if($this->database->getEmail($player->getName()) !== "none") {
                        $this->emailUser($this->database->getEmail($player->getName()), $this->getMessage("email-subject-attemptedlogin"), $this->getMessage("email-attemptedlogin"));
                    }
                    return false;
                }
            } else {
                $this->tries[strtolower($player->getName())] = 1;
            }
            $tries = $this->getConfig()->get("tries") - $this->tries[strtolower($player->getName())];
            $player->sendMessage(str_replace("{tries}", $tries, $this->getMessage("incorrect-password")));
            return false;
        }
        $this->force($player);
        return true;
    }

    public function force(Player $player, $login = true) {
        if(isset($this->messagetick[strtolower($player->getName())])) {
            unset($this->messagetick[strtolower($player->getName())]);
        }
        if(isset($this->tries[strtolower($player->getName())])) {
            unset($this->tries[strtolower($player->getName())]);
        }
        $this->authenticated[strtolower($player->getName())] = true;
        if($this->getConfig()->get("invisible")) {
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            $player->setDataProperty(Entity::DATA_SHOW_NAMETAG, Entity::DATA_TYPE_BYTE, 1);
        }
        if($this->getConfig()->get("blindness")) {
            $player->removeEffect(15);
            $player->removeEffect(16);
        }
        if($login) {
            $player->sendMessage(str_replace("{attempts}", $this->database->getAttempts($player->getName()), $this->getMessage("authentication-success")));
        } else {
            $player->sendMessage(str_replace("{pin}", $this->database->getPin($player->getName()), $this->getMessage("register-success")));
        }
        if($this->getConfig()->get("cape-for-registration")) {
            $capes = array(
                "Minecon_MineconSteveCape2016",
                "Minecon_MineconSteveCape2015",
                "Minecon_MineconSteveCape2013",
                "Minecon_MineconSteveCape2012",
                "Minecon_MineconSteveCape2011");
            $cape = array_rand($capes);
            $cape = $capes[$cape];
            $player->setSkin($player->getSkinData(), $cape);
        }
        if($this->plugin->getConfig()->get("hide-items")) {
            $player->getInventory()->sendContents($player);
        }
        $this->database->updatePlayer($player->getName(), $this->database->getPassword($player->getName()), $this->database->getEmail($player->getName()), $this->database->getPin($player->getName()), $player->getUniqueId()->toString(), 0);
        return true;
    }

    public function register(Player $player, $password, $confirmpassword, $email = "none", $xbox = "false") {
        if($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("already-registered"));
            return false;
        }
        if(strlen($password) < $this->getConfig()->get("minimum-password-length")) {
            $player->sendMessage($this->getMessage("password-too-short"));
            return false;
        }
        if(in_array(strtolower($password), $this->getConfig()->get("blocked-passwords")) || in_array(strtolower($confirmpassword), $this->getConfig()->get("blocked-passwords"))) {
            $player->sendMessage($this->getMessage("password-blocked"));
            return false;
        }
        if($password !== $confirmpassword) {
            $player->sendMessage($this->getMessage("password-not-match"));
            return false;
        }
        if($email == "enter") {
            $this->giveEmail[strtolower($player->getName())] = true;
            $player->sendMessage($this->getMessage("email"));
        }
        $this->database->insertData($player, $password, $email, $xbox);
        $this->force($player, false);
        return true;
    }

    public function changepassword(Player $player, $oldpassword, $newpassword) {
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            return false;
        }
        if(!$this->isCorrectPassword($player, $oldpassword)) {
            $player->sendMessage($this->getMessage("incorrect-password"));
            return false;
        }
        $pin = $this->generatePin($player);
        $this->database->updatePlayer($player->getName(), password_hash($newpassword, PASSWORD_BCRYPT), $this->database->getEmail($player->getName()), $pin, $player->getUniqueId()->toString(), 0);
        $player->sendMessage(str_replace("{pin}", $pin, $this->getMessage("change-password-success")));
        if($this->getConfig()->get("send-email-on-changepassword") && $this->database->getEmail($player) !== "none") {
            $this->emailUser($this->database->getEmail($player->getName()), $this->getMessage("email-subject-changedpassword"), $this->getMessage("email-changedpassword"));
        }
        return true;
    }

    public function forgotpassword(Player $player, $pin, $newpassword) {
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("not-registered"));
            return false;
        }
        if($this->isAuthenticated($player)) {
            $player->sendMessage($this->getMessage("already-authenticated"));
            return false;
        }
        if(!$this->isCorrectPin($player, $pin)) {
            $player->sendMessage($this->getMessage("incorrect-pin"));
            return false;
        }
        $newpin = $this->generatePin($player);
        $this->database->updatePlayer($player->getName(), password_hash($newpassword, PASSWORD_BCRYPT), $this->database->getEmail($player->getName()), $newpin, $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()));
        $player->sendMessage(str_replace("{pin}", $newpin, $this->getMessage("forgot-password-success")));
        if($this->getConfig()->get("send-email-on-changepassword") && $this->database->getEmail($player) !== "none") {
            $this->emailUser($this->database->getEmail($player->getName()), $this->getMessage("email-subject-changedpassword"), $this->getMessage("email-changedpassword"));
        }
    }

    public function resetpassword($player, $sender) {
        $player = strtolower($player);
        if($this->isRegistered($player)) {
            if($this->getConfig()->get("send-email-on-resetpassword") && $this->database->getEmail($player) !== "none") {
                $this->emailUser($this->database->getEmail($player), $this->getMessage("email-subject-passwordreset"), $this->getMessage("email-passwordreset"));
            }
            $this->database->clearPassword($player);
            if(isset($this->authenticated[$player])) {
                unset($this->authenticated[$player]);
            }
            $playerobject = $this->getServer()->getPlayerExact($player);
            if($playerobject instanceof Player) {
                $playerobject->sendMessage($this->getMessage("join-message"));
                $this->messagetick[$player] = 5;
                if($this->getConfig()->get("timeout")) {
                    $this->getServer()->getScheduler()->scheduleDelayedTask(new TimeoutTask($this, $playerobject), $this->getConfig()->get("timeout-time") * 20);
                }
            }
            $sender->sendMessage($this->getMessage("password-reset-success"));
            return true;
        }
        $sender->sendMessage($this->getMessage("not-registered-two"));
        return false;
    }

    public function logout(Player $player, $quit = true) {
        if($this->isAuthenticated($player)) {
            unset($this->authenticated[strtolower($player->getName())]);
            if(!$quit) {
                $player->sendMessage($this->getMessage("join-message"));
                $this->messagetick[strtolower($player->getName())] = 5;
                if($this->getConfig()->get("timeout")) {
                    $this->getServer()->getScheduler()->scheduleDelayedTask(new TimeoutTask($this, $player), $this->getConfig()->get("timeout-time") * 20);
                }
            }
        } else {
            if(isset($this->confirmPassword[strtolower($player->getName())])) {
                unset($this->confirmPassword[strtolower($player->getName())]);
            }
            if(isset($this->messagetick[strtolower($player->getName())])) {
                unset($this->messagetick[strtolower($player->getName())]);
            }
            if(isset($this->tries[strtolower($player->getName())])) {
                unset($this->tries[strtolower($player->getName())]);
            }
        }
    }

    public function getMessage($message) {
        return str_replace("&", "§", $this->getConfig()->get($message));
    }

    //Code by @xBeastMode
    public function emailUser($to, $title, $body) {
        $ch = curl_init();
        $title = str_replace(" ", "+", $title);
        $body = str_replace(" ", "+", $body);
        $url = 'https://mcpefun.com/mailserver/?to=' . $to . '&subject=' . $title . '&body=' . $body;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
