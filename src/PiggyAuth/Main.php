<?php
namespace PiggyAuth;

use PiggyAuth\Commands\ChangePasswordCommand;
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
            $this->db->exec("CREATE TABLE players (name TEXT PRIMARY KEY, password TEXT, uuid INT);");
        } else {
            $this->db = new \SQLite3($this->getDataFolder() . "players.db", SQLITE3_OPEN_READWRITE);
        }
        $this->saveDefaultConfig();
        $this->getServer()->getCommandMap()->register('changepassword', new ChangePasswordCommand('changepassword', $this));
        $this->getServer()->getCommandMap()->register('login', new LoginCommand('login', $this));
        $this->getServer()->getCommandMap()->register('register', new RegisterCommand('register', $this));
        $this->getServer()->getCommandMap()->register('resetpassword', new ResetPasswordCommand('resetpassword', $this));
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new MessageTick($this), $this->getConfig()->get("seconds-til-next-message") * 20);
        if($this->getConfig()->get("popup") || $this->getConfig()->get("tip")) {
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTipTick($this), 20);
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("§aEnabled.");
    }

    public function getPlayer($player) {
        $statement = $this->db->prepare("SELECT * FROM players WHERE name = :name");
        $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
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

    public function isCorrectPassword(Player $player, $password) {
        $data = $this->getPlayer($player->getName());
        if(!is_null($data)) {
            if(password_verify($password, $data["password"])) {
                return true;
            }
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
            $player->sendMessage($this->getConfig()->get("register-success"));
        }
        return true;
    }

    public function register(Player $player, $password) {
        if($this->isRegistered($player->getName())) {
            $player->sendMessage($this->getConfig()->get("already-registered"));
            return false;
        }
        $this->force($player, false);
        $statement = $this->db->prepare("INSERT INTO players (name, password, uuid) VALUES (:name, :password, :uuid)");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->bindValue(":uuid", $player->getUniqueId()->toString(), SQLITE3_INTEGER);
        $statement->execute();
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
        $statement = $this->db->prepare("UPDATE players SET password = :password WHERE name = :name");
        $statement->bindValue(":name", strtolower($player->getName()), SQLITE3_TEXT);
        $statement->bindValue(":password", password_hash($password, PASSWORD_BCRYPT), SQLITE3_TEXT);
        $statement->execute();
    }

    public function resetpassword($player) {
        if($this->isRegistered($player->getName())) {
            $statement = $this->db->prepare("DELETE FROM players WHERE name = :name");
            $statement->bindValue(":name", strtolower($player), SQLITE3_TEXT);
            $statement->execute();
            if(isset($this->authenticated[strtolower($player)])) {
                unset($this->authenticated[strtolower($player)]);
            }
            return true;
        }
        return false;
    }

}
