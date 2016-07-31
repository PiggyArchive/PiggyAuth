<?php
namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
use PiggyAuth\Commands\PinCommand;
use PiggyAuth\Commands\ForgotPasswordCommand;
use PiggyAuth\Commands\LoginCommand;
use PiggyAuth\Commands\RegisterCommand;
use PiggyAuth\Commands\ResetPasswordCommand;
use PiggyAuth\Tasks\MessageTick;
use PiggyAuth\Tasks\PopupTipTick;

use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class Main extends PluginBase {
    public $authenticated;
    public $confirmPassword;

    public function onEnable() {
        if(!file_exists($this->getDataFolder() . "players.db")) {
            $this->db = new \SQLite3($this->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $this->db->exec("CREATE TABLE players (name TEXT PRIMARY KEY, password TEXT, pin INT, uuid INT);");
        } else {
            $this->db = new \SQLite3($this->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE);
        }
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('forgotpassword', new ForgotPasswordCommand('forgotpassword', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('pin', new PinCommand('pin', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), $this->getConfig()->get("seconds-til-next-message") * 20);
        if($this->getConfig()->get("popup") || $this->getConfig()->get("tip")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTipTick($this), 20);
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("§aEnabled.");
    }

    public function getPlayer($player) {
        $player = strtolower($player);
        $statement = $this->db->prepare("SELECT * FROM players WHERE name = :name");
        $statement->bindValue(":name", $player, SQLITE3_TEXT);
        $result = $statement->execute();
        if($result instanceof \SQLite3Result) {
            $data = $result->fetchArray(SQLITE3_ASSOC);
            $result->finalize();
            if(isset($data["name"])) {
                unset($data["name"]);
                $statement->close();
                return $data;
            }
        }
        $statement->close();
        return null;
    }

    public function updatePlayer(Player $player) {
        $statement = $this->db->prepare("UPDATE players SET uuid = :uuid WHERE name = :name");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_INTEGER);
        $statement->execute();
    }

    public function getPin(Player $player) {
        $data = $this->getPlayer($player->getName());
        if(!is_null($data)) {
            return $data["pin"];
        }
        return null;
    }

    public function generatePin(Player $player) {
        $newpin = mt_rand(1000, 9999);
        if($this->isCorrectPin($player, $newpin)) { //Player cant have same pin
            return $this->generatePin($player);
        }
        return $newpin;
    }

    public function getPassword(Player $player) { //ENCRYPTED!
        $data = $this->getPlayer($player->getName());
        if(!is_null($data)) {
            return $data["password"];
        }
        return null;
    }

    public function isCorrectPassword(Player $player, $password) {
        if(password_verify($password, $this->getPassword($player))) {
            return true;
        }
        return false;
    }

    public function isCorrectPin(Player $player, $pin) {
        if($pin == $this->getPin($player)) {
            return true;
        }
        return false;
    }

    public function isAuthenticated(Player $player) {
        if(isset($this->authenticated[strtolower($player->getName())])) return true;
        return false;
    }

    public function isRegistered($player) {
        return $this->getPlayer(strtolower($player)) !== null;
    }

    public function login(Player $player, $password) {
        if($this->isAuthenticated($player)) {
            $player->sendMessage($this->getConfig()->get("already-authenticated"));
            return false;
        }
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getConfig()->get("not-registered"));
            return false;
        }
        if(!$this->isCorrectPassword($player, $password)) {
            $player->sendMessage($this->getConfig()->get("incorrect-password"));
            return false;
        }
        $this->force($player);
        return true;
    }

    public function force(Player $player, $login = true) {
        $this->authenticated[strtolower($player->getName())] = true;
        $this->updatePlayer($player);
        if($this->getConfig()->get("invisible")) {
            $player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
            $player->setDataProperty(Entity::DATA_SHOW_NAMETAG, Entity::DATA_TYPE_BYTE, 1);
        }
        if($this->getConfig()->get("blindness")) {
            $player->removeEffect(15);
            $player->removeEffect(16);
        }
        if($login) {
            $player->sendMessage($this->getConfig()->get("authentication-success"));
        } else {
            $player->sendMessage(str_replace("{pin}", $this->getPin($player), $this->getConfig()->get("register-success")));
        }
        return true;
    }

    public function register(Player $player, $password) {
        if($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getConfig()->get("already-registered"));
            return false;
        }
        $statement = $this->db->prepare("INSERT INTO players (name, password, pin, uuid) VALUES (:name, :password, :pin, :uuid)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->bindValue(":pin", $this->generatePin($player), SQLITE3_INTEGER);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_INTEGER);
        $statement->execute();
        $this->force($player, false);
        return true;
    }

    public function changepassword(Player $player, $oldpassword, $newpassword) {
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getConfig()->get("not-registered"));
            return false;
        }
        if(!$this->isCorrectPassword($player, $oldpassword)) {
            $player->sendMessage($this->getConfig()->get("incorrect-password"));
            return false;
        }
        $pin = $this->generatePin($player);
        $statement = $this->db->prepare("UPDATE players SET password = :password WHERE name = :name");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($newpassword, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->execute();
        $player->sendMessage($this->getConfig()->get("password-change-success"));
        return true;
    }

    public function forgotpassword(Player $player, $pin, $newpassword) {
        if(!$this->isRegistered($player->getName())) {
            $player->sendMessage($this->getConfig()->get("not-registered"));
            return false;
        }
        if($this->isAuthenticated($player)) {
            $player->sendMessage($this->getConfig()->get("already-authenticated"));
            return false;
        }
        if(!$this->isCorrectPin($player, $pin)) {
            $player->sendMessage($this->getConfig()->get("incorrect-pin"));
            return false;
        }
        $newpin = $this->generatePin($player);
        $statement = $this->db->prepare("UPDATE players SET password = :password, pin = :pin WHERE name = :name");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($newpassword, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->bindValue(":pin", $newpin, SQLITE3_INTEGER);
        $statement->execute();
        $player->sendMessage(str_replace("{pin}", $newpin, $this->getConfig()->get("forgot-password-success")));
    }

    public function resetpassword($player, $sender) {
        $player = strtolower($player);
        if($this->isRegistered($player)) {
            $statement = $this->db->prepare("DELETE FROM players WHERE name = :name");
            $statement->bindValue(":name", $player, SQLITE3_TEXT);
            $statement->execute();
            if(isset($this->authenticated[$player])) {
                unset($this->authenticated[$player]);
            }
            $sender->sendMessage($this->getConfig()->get("password-reset-success"));
            return true;
        }
        $sender->sendMessage($this->getConfig()->get("not-registered-two"));
        return false;
    }

}
