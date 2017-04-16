<?php

namespace PiggyAuth;

use PiggyAuth\Databases\MySQL;
use PiggyAuth\Databases\SQLite3;
use PiggyAuth\Events\PlayerFailEvent;
use PiggyAuth\Events\PlayerLoginEvent;


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

/**
 * Class EventListener
 * @package PiggyAuth
 */
class EventListener implements Listener
{
    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-break")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     */
    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-place")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     */
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

    /**
     * @param EntityRegainHealthEvent $event
     */
    public function onHeal(EntityRegainHealthEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player && !$this->plugin->sessionmanager->getSession($entity)->isAuthenticated($entity)) {
            if (!$this->plugin->getConfig()->getNested("events.allow-heal")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param InventoryPickupArrowEvent $event
     */
    public function onPickupArrow(InventoryPickupArrowEvent $event)
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-arrow-pickup")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param InventoryPickupItemEvent $event
     */
    public function onPickupItem(InventoryPickupItemEvent $event)
    {
        $player = $event->getInventory()->getHolder();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-item-pickup")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     */
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
                    if (!$this->plugin->sessionmanager->getSession($player)->isConfirmingPassword()) {
                        if ($this->plugin->sessionmanager->getSession($player)->getSecondPassword() == null) {
                            $this->plugin->sessionmanager->getSession($player)->setConfirmingPassword();
                            $this->plugin->sessionmanager->getSession($player)->setFirstPassword($message);
                            $player->sendMessage($this->plugin->languagemanager->getMessage($player, "confirm-password"));
                        }
                    } else {
                        if ($this->plugin->sessionmanager->getSession($player)->getFirstPassword() == $message) {
                            $this->plugin->sessionmanager->getSession($player)->setConfirmingPassword(false);
                            $this->plugin->sessionmanager->getSession($player)->setFirstPassword(null);
                            $this->plugin->sessionmanager->getSession($player)->setSecondPassword($message);
                            $this->plugin->sessionmanager->getSession($player)->setGivingEmail();
                            $player->sendMessage($this->plugin->languagemanager->getMessage($player, "email"));
                            $event->setCancelled();
                            return true; //Stop the Invalid email message
                        } else {
                            $player->sendMessage($this->plugin->languagemanager->getMessage($player, "password-not-match"));
                            $this->plugin->sessionmanager->getSession($player)->setConfirmingPassword(false);
                            $this->plugin->sessionmanager->getSession($player)->setFirstPassword(null);
                            $this->plugin->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($this->plugin, $player, Main::LOGIN, Main::PASSWORDS_NOT_MATCHED));
                        }
                    }
                    if ($this->plugin->sessionmanager->getSession($player)->isGivingEmail()) {
                        $function = function ($result, $args, $plugin) {
                            $player = $plugin->getServer()->getPlayerExact($args[0]);
                            $message = $args[1];
                            if ($player instanceof Player) {
                                if ($result !== true) {
                                    $player->sendMessage($plugin->languagemanager->getMessage($player, "invalid-email"));
                                    $plugin->getServer()->getPluginManager()->callEvent(new PlayerFailEvent($plugin, $player, Main::LOGIN, Main::INVALID_EMAIL));
                                } else {
                                    $plugin->register($player, $plugin->sessionmanager->getSession($player)->getSecondPassword(), $plugin->sessionmanager->getSession($player)->getSecondPassword(), $message);
                                    $this->plugin->sessionmanager->getSession($player)->setSecondPassword(null);
                                    $this->plugin->sessionmanager->getSession($player)->setGivingEmail(false);
                                }
                            }
                        };
                        $arguements = array($player->getName(), $message);
                        $this->plugin->emailmanager->validateEmail($message, $function, $arguements);
                        $event->setCancelled();
                    }
                }
            }
            $event->setCancelled();
        } else {
            /*if ($this->plugin->isCorrectPassword($player, $message)) {
            $player->sendMessage($this->plugin->languagemanager->getMessage($player, "dont-say-password"));
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

    /**
     * @param PlayerCommandPreprocessEvent $event
     */
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

    /**
     * @param PlayerDropItemEvent $event
     */
    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-item-drop")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerExhaustEvent $event
     */
    public function onExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof Player && !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-hunger")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-block-interact")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     */
    public function onConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-eating")) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     * @return bool
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        if ($this->plugin->database instanceof SQLite3) {
            $this->plugin->sessionmanager->loadSession($player);
        }
        $data = $this->plugin->sessionmanager->getSession($player)->getData();
        if (!$this->plugin->sessionmanager->getSession($player)->isRegistered() && $this->plugin->getConfig()->getNested("message.join-message-for-new-players")) {
            $event->setJoinMessage(str_replace("{player}", $player->getName(), $this->plugin->languagemanager->getMessageFromLanguage($this->plugin->languagemanager->getDefaultLanguage(), "new-player")));
        }
        if ($this->plugin->getConfig()->getNested("message.hold-join-message")) {
            $this->plugin->sessionmanager->getSession($player)->setJoinMessage($event->getJoinMessage());
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
                $player->sendMessage(str_replace("{pin}", $this->plugin->database->getPin($player->getName()), str_replace("{password}", $randompassword, $this->plugin->languagemanager->getMessage($player, "register-success-xbox"))));
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
        $this->plugin->sessionmanager->getSession($player)->startSession();
    }

    /**
     * @param PlayerKickEvent $event
     */
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

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        if (!$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) {
            if (!$this->plugin->getConfig()->getNested("events.allow-movement") && (!$this->plugin->getConfig()->getNested("events.allow-head-movement") || floor($event->getFrom()->x) !== floor($player->x) || floor($event->getFrom()->z) !== floor($player->z))) {
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     */
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

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        if (($this->plugin->sessionmanager->getSession($player) == null || !$this->plugin->sessionmanager->getSession($player)->isAuthenticated()) && $this->plugin->getConfig()->getNested("message.hold-join-message")) {
            $event->setQuitMessage(null);
        }
        $this->plugin->logout($player);
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
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
                        if (isset($pk->selectSlot)) {
                            $pk->selectSlot = $packet->selectSlot;
                        }
                        $player->dataPacket($pk);
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    /**
     * @param DataPacketSendEvent $event
     * @return bool
     */
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
