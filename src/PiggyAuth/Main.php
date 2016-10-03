<?php
namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
use PiggyAuth\Commands\PinCommand;
use PiggyAuth\Commands\ForgotPasswordCommand;
use PiggyAuth\Commands\LoginCommand;
use PiggyAuth\Commands\LogoutCommand;
use PiggyAuth\Commands\RegisterCommand;
use PiggyAuth\Commands\ResetPasswordCommand;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Tasks\MessageTick;
use PiggyAuth\Tasks\PopupTipTick;
use PiggyAuth\Tasks\TimeoutTask;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class Main extends PluginBase {
    public $authenticated;
    public $confirmPassword;
    public $messagetick;
    public $tries;
    public $database;

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('forgotpassword', new ForgotPasswordCommand('forgotpassword', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('logout', new LogoutCommand('logout', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('pin', new PinCommand('pin', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), 20);
        if($this->getConfig()->get("popup") || $this->getConfig()->get("tip")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTipTick($this), 20);
        }
        $outdated = false;
        if(!$this->getConfig()->exists("version")) {
            $this->getConfig()->set("version", $this->getDescription()->getVersion());
            $this->getConfig()->save();
            $outdated = true;
        } elseif($this->getConfig()->get("version") < $this->getDescription()->getVersion()) {
            switch($this->getConfig()->get("version")) {
                default:
                    $this->getConfig()->set("version", $this->getDescription()->getVersion());
                    $this->getConfig()->save();
                    break;
            }
        }
        switch($this->getConfig()->get("database")) {
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
                    $this->database->updatePlayer($player->getName(), $this->database->getPassword($player->getName()), $this->database->getPin($player->getName()), $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()) + 1);
                    $player->kick($this->getMessage("too-many-tries"));
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
        $this->database->updatePlayer($player->getName(), $this->database->getPassword($player->getName()), $this->database->getPin($player->getName()), $player->getUniqueId()->toString(), 0);
        return true;
    }

    public function register(Player $player, $password, $confirmpassword) {
        if($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getMessage("already-registered"));
            return false;
        }
        if(strlen($password) < $this->getConfig()->get("minimum-password-length")) {
            $player->sendMessage($this->getMessage("password-too-short"));
            return false;
        }
        if($password !== $confirmpassword) {
            $player->sendMessage($this->getMessage("password-not-match"));
            return false;
        }
        $statement = $this->database->db->prepare("INSERT INTO players (name, password, pin, uuid, attempts) VALUES (:name, :password, :pin, :uuid, :attempts)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->bindValue(":pin", $this->generatePin($player), SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_INTEGER);
        $statement->bindValue(":attempts", 0, SQLITE3_INTEGER);
        $statement->execute();
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
        $this->database->updatePlayer($player->getName(), password_hash($newpassword, PASSWORD_BCRYPT), $pin, $player->getUniqueId()->toString(), 0);
        $player->sendMessage($this->getMessage("change-password-success"));
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
        $this->database->updatePlayer($player->getName(), password_hash($newpassword, PASSWORD_BCRYPT), $newpin, $this->database->getUUID($player->getName()), $this->database->getAttempts($player->getName()));
        $player->sendMessage(str_replace("{pin}", $newpin, $this->getMessage("forgot-password-success")));
    }

    public function resetpassword($player, $sender) {
        $player = strtolower($player);
        if($this->isRegistered($player)) {
            $statement = $this->database->db->prepare("DELETE FROM players WHERE name = :name");
            $statement->bindValue(":name", $player, SQLITE3_TEXT);
            $statement->execute();
            if(isset($this->authenticated[$player])) {
                unset($this->authenticated[$player]);
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
                $this->messagetick[strtolower($player->getName())] = 5;
                $this->getServer()->getScheduler()->scheduleDelayedTask(new TimeoutTask($this, $player), $this->getConfig()->get("timeout") * 20);
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

}
