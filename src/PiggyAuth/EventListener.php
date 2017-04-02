<?php

namespace PiggyAuth;

use PiggyAuth\Databases\MySQL;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Events\PlayerFailEvent;
use PiggyAuth\Events\PlayerLoginEvent;


use PiggyAuth\Tasks\ValidateEmailTask;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\Player;

class EventListener implements Listener
{
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-break")) {
                $event->setCancelled();
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-place")) {
                $event->setCancelled();
            }
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player && !$this->plugin->sessionmanager->getSession($entity)->isAuthenticated($entity)) {
            if (!$this->plugin->getConfig()->getNested("events.allow-damage")) {
                $event->setCancelled();
            }
        }
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof Player && !$this->plugin->sessionmanager->getSession($damager)->isAuthenticated($damager)) {
                if (!$this->plugin->getConfig()->getNested("events.allow-damage-others")) {
                    $event->setCancelled();
                }
            }
        }
    }

    public function onHeal(EntityRegainHealthEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player && !$this->plugin->sessionmanager->getSession($entity)->isAuthenticated($entity)) {
            if (!$this->plugin->getConfig()->getNested("events.allow-heal")) {
                $event->setCancelled();
            }
        }
    }

    public function onPickupArrow(InventoryPickupArrowEvent $event)
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-arrow-pickup")) {
                $event->setCancelled();
            }
        }
    }

    public function onPickupItem(InventoryPickupItemEvent $event)
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-item-pickup")) {
                $event->setCancelled();
            }
        }
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $recipients = array();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if ($this->plugin->getConfig()->getNested("login.chat-login")) {
                if ($this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                    $this->plugin->login($player, $message, 0);
                } else {
                    if (!isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
                        if (!isset($this->plugin->confirmedPassword[strtolower($player->getName())])) {
                            echo "1";
                            $this->plugin->confirmPassword[strtolower($player->getName())] = $message;
                            $player->sendMessage($this->plugin->getMessage("confirm-password"));
                        }
                    } else {
                        if ($this->plugin->confirmPassword[strtolower($player->getName())] == $message) {
                            echo "2";
                            unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                            $this->plugin->confirmedPassword[strtolower($player->getName())] = $message;
                            $this->plugin->giveEmail[strtolower($player->getName())] = true;
                            $player->sendMessage($this->plugin->getMessage("email"));
                            $event->setCancelled();
                            return true; //Stop the Invalid email message
                        } else {
                            echo "2B";
                            $player->sendMessage($this->plugin->getMessage("password-not-match"));
                            unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                            $this->plugin->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this->plugin, $player, Main::LOGIN, Main::PASSWORDS_NOT_MATCHED));
                        }
                    }
                    if (isset($this->plugin->giveEmail[strtolower($player->getName())])) {
                        echo "3";
                        $function = function ($result, $args, $plugin) {
                            $player = $plugin->getServer()->getPlayerExact($args[0]);
                            $message = $args[1];
                            echo "4";
                            if ($player instanceof Player) {
                                echo "5";
                                if ($result !== true) {
                                    echo "6";
                                    $player->sendMessage($plugin->getMessage("invalid-email"));
                                    $plugin->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($plugin, $player, Main::LOGIN, Main::INVALID_EMAIL));
                                } else {
                                    echo "6B";
                                    $plugin->register($player, $plugin->confirmedPassword[strtolower($player->getName())], $plugin->confirmedPassword[strtolower($player->getName())], $message);
                                    unset($plugin->confirmedPassword[strtolower($player->getName())]);
                                }
                            }
                            unset($plugin->giveEmail[strtolower($args[0])]);
                        };
                        $arguements = array($player->getName(), $message);
                        $task = new ValidateEmailTask($this->plugin->getConfig()->getNested("emails.mailgun.public-api"), $message, $function, $arguements, $this->plugin);
                        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask($task);
                        $event->setCancelled();
                    }
                }
            }
            $event->setCancelled();
        } else {
            /*if ($this->plugin->isCorrectPassword($player, $message)) {
            $player->sendMessage($this->plugin->getMessage("dont-say-password"));
            $event->setCancelled();
            }*/
        }
        if (!$this->plugin->getConfig()->getNested("message.see-message")) {
            foreach ($event->getRecipients() as $recipient) {
                if (!$recipient instanceof Player || ($this->plugin->sessionmanager->getSession($recipient) !== null && $this->plugin->sessionmanager->getSession($recipient)->isAuthenticated($recipient))) {
                    array_push($recipients, $recipient);
                }
            }
            $event->setRecipients($recipients);
        }
    }

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $message = strtolower($event->getMessage());
        $args = explode(" ", $message);
        $forgotpasswordaliases = array(
            "/forgotpassword",
            "/forgetpassword",
            "/forgotpw",
            "/forgetpw",
            "/forgotpwd",
            "/forgetpwd",
            "/fpw",
            "/fpwd");
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if ($message[0] == "/" || ($message[0] == "." && $message[1] == "/")) {
                if (!in_array($args[0], $forgotpasswordaliases) && $args[0] !== "/login" && $args[0] !== "/register" && $args[0] !== "/sendpin") {
                    if (!$this->plugin->getConfig()->getNested("events.allow-commands")) {
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-item-drop")) {
                $event->setCancelled();
            }
        }
    }

    public function onExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-hunger")) {
                $event->setCancelled();
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-interact")) {
                $event->setCancelled();
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-eating")) {
                $event->setCancelled();
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        if ($this->plugin->database instanceof SQLite3) {
            $this->plugin->sessionmanager->loadSession($player);
        }
        $data = $this->plugin->sessionmanager->getSession($player)->getData();
        if (!$this->plugin->sessionmanager->getSession($player)->isRegistered() && $this->plugin->getConfig()->getNested("message.join-message-for-new-players")) {
            $event->setJoinMessage(str_replace("{player}", $player->getName(), $this->plugin->getMessage("new-player")));
        }
        if ($this->plugin->getConfig()->getNested("message.hold-join-message")) {
            $this->plugin->joinMessage[strtolower($player->getName())] = $event->getJoinMessage();
            $event->setJoinMessage(null);
        }
        if ($this->plugin->getConfig()->getNested("login.auto-authentication") && !is_null($data) && $player->getUniqueId()->toString() == $data["uuid"]) {
            $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this->plugin, $player, Main::UUID));
            if (!$event->isCancelled()) {
                $this->plugin->force($player, true, 1);
            }
            return true;
        }
        if ($this->plugin->getConfig()->getNested("login.xbox-bypass") && $this->plugin->getServer()->getName() == "ClearSky" && $player->isAuthenticated()) {
            if (!$this->plugin->sessionmanager->getSession($player)->isRegistered()) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                $randompassword = [];
                $characteramount = strlen($characters) - 1;
                for ($i = 0; $i < $this->plugin->getConfig()->getNested("register.minimum-password-length"); $i++) {
                    $character = mt_rand(0, $characteramount);
                    array_push($randompassword, $characters[$character]);
                }
                $randompassword = implode("", $randompassword);
                $this->plugin->register($player, $randompassword, $randompassword, "none", true);
                $player->sendMessage(str_replace("{pin}", $this->plugin->database->getPin($player->getName()), str_replace("{password}", $randompassword, $this->plugin->getMessage("register-success-xbox"))));
            } else {
                if (!is_null($data) && $data["xbox"] == true) {
                    $this->plugin->getServer()->getPluginManager()->callEvent($event = new PlayerLoginEvent($this->plugin, $player, Main::XBOX));
                    if (!$event->isCancelled()) {
                        $this->plugin->force($player, true, 2);
                    }
                }
            }
            return true;
        }
        $this->plugin->startSession($player);
    }

    public function onKick(PlayerKickEvent $event)
    {
        $player = $event->getPlayer();
        $reason = $event->getReason();
        $plugin = $this->plugin->getServer()->getPluginManager()->getPlugin("PurePerms");
        if ($reason == "disconnectionScreen.serverFull") {
            if (in_array($player->getName(), $this->plugin->getConfig()->getNested("vipslots.players")) || ($plugin !== null && in_array($plugin->getUserDataMgr()->getGroup($player)->getName(), $this->plugin->getConfig()->getNested("vipslots.ranks")))) {
                $event->setCancelled();
            }
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-movement") && (!$this->plugin->getConfig()->getNested("events.allow-head-movement") || floor($event->getFrom()->x) !== floor($player->x) || floor($event->getFrom()->z) !== floor($player->z))) {
                $event->setCancelled();
            }
        }
    }

    public function onPrelogin(PlayerPreLoginEvent $event)
    {
        $player = $event->getPlayer();
        if ($this->plugin->getConfig()->getNested("login.single-session")) {
            if (!is_null($p = $this->plugin->getServer()->getPlayerExact($player->getName()))) {
                if ($this->plugin->isAuthenticated($p) && $player->getUniqueId()->toString() !== $p->getUniqueId()->toString()) {
                    $player->close("", "Already logged in!");
                    $event->setCancelled();
                } else {
                    $p->close("", "Someone else is connecting to this account.");
                }
            }
        }
        if ($this->plugin->database instanceof MySQL) {
            $this->plugin->sessionmanager->loadSession($player);
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated() && $this->plugin->getConfig()->getNested("message.hold-join-message")) {
            $event->setQuitMessage(null);
        }
        $this->plugin->logout($player);
    }

    public function onReceive(DataPacketReceiveEvent $event)
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof ContainerSetSlotPacket) {
            if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated() && $this->plugin->getConfig()->getNested("effects.hide-items")) {
                if ($player->isSurvival()) {
                    if ($packet->item !== Item::get(Item::AIR)) {
                        $pk = new ContainerSetSlotPacket();
                        $pk->windowid = $packet->windowid;
                        $pk->slot = $packet->slot;
                        $pk->hotbarSlot = $packet->hotbarSlot;
                        $pk->item = Item::get(Item::AIR);
                        $pk->selectSlot = $packet->selectSlot;
                        $player->dataPacket($pk);
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onSend(DataPacketSendEvent $event)
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if ($packet instanceof MobEffectPacket && $this->plugin->sessionmanager->getSession($player) !== null) {
            if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated() && $this->plugin->getConfig()->getNested("effects.hide-effects")) {
                if ($packet->eventId !== MobEffectPacket::EVENT_ADD) {
                    return false;
                }
                if ($this->plugin->getConfig()->getNested("effects.blindness") && ($packet->effectId == 15 || $packet->effectId == 16)) {
                    return false;
                }
                $event->setCancelled();
            }
        }
    }

}
