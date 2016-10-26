<?php
namespace PiggyAuth;

use PiggyAuth\Tasks\TimeoutTask;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\Player;

class EventListener implements Listener {
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("disable-block-break")) {
                $event->setCancelled();
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("disable-block-place")) {
                $event->setCancelled();
            }
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if($entity instanceof Player && !$this->plugin->isAuthenticated($entity)) {
            if(!$this->plugin->getConfig()->get("disable-damage")) {
                $event->setCancelled();
            }
        }
        if($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if($damager instanceof Player && !$this->plugin->isAuthenticated($damager)) {
                if(!$this->plugin->getConfig()->get("disable-damage-others")) {
                    $event->setCancelled();
                }
            }
        }
    }

    public function onPickupArrow(InventoryPickupArrowEvent $event) {
        $player = $event->getInventory()->getHolder();
        if($player instanceof Player && !$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("disable-arrow-pickup")) {
                $event->setCancelled();
            }
        }
    }

    public function onPickupItem(InventoryPickupItemEvent $event) {
        $player = $event->getInventory()->getHolder();
        if($player instanceof Player && !$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("disable-item-pickup")) {
                $event->setCancelled();
            }
        }
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $recipients = array();
        if(!$this->plugin->isAuthenticated($player)) {
            if($this->plugin->getConfig("chat-login")) {
                if($this->plugin->isRegistered($player->getName())) {
                    $this->plugin->login($player, $message);
                } else {
                    if(!isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
                        $this->plugin->confirmPassword[strtolower($player->getName())] = $message;
                        $player->sendMessage($this->plugin->getMessage("confirm-password"));
                    } else {
                        if($this->plugin->confirmPassword[strtolower($player->getName())] == $message) {
                            unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                            $this->plugin->register($player, $message, $message, "enter");
                        } else {
                            $player->sendMessage($this->plugin->getMessage("password-not-match"));
                            unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                        }
                    }
                }
            }
            $event->setCancelled();
        } else {
            if($this->plugin->isCorrectPassword($player, $message)) {
                $player->sendMessage($this->plugin->getMessage("dont-say-password"));
                $event->setCancelled();
            }
            if(isset($this->plugin->giveEmail[strtolower($player->getName())])) {
                if(strtolower($message) !== "none" && !filter_var($message, FILTER_VALIDATE_EMAIL)) {
                    $player->sendMessage($this->plugin->getMessage("invalid-email"));
                } else {
                    unset($this->plugin->giveEmail[strtolower($player->getName())]);
                    $this->plugin->database->updatePlayer($player->getName(), $this->plugin->database->getPassword($player->getName()), $message, $this->plugin->database->getPin($player->getName()), $player->getUniqueId()->toString(), $this->plugin->database->getUUID($player->getName()));
                    $player->sendMessage($this->plugin->getMessage("email-set"));
                }
                $event->setCancelled();
            }
        }
        if(!$this->plugin->getConfig()->get("see-messages")) {
            foreach($event->getRecipients() as $recipient) {
                if(!$recipient instanceof Player || $this->plugin->isAuthenticated($recipient)) {
                    array_push($recipients, $recipient);
                }
            }
            $event->setRecipients($recipients);
        }
    }

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) {
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
        if(!$this->plugin->isAuthenticated($player)) {
            if($message[0] == "/") {
                if(!in_array($args[0], $forgotpasswordaliases) && $args[0] !== "/login" && $args[0] !== "/register" && $args[0] !== "/sendpin") {
                    if(!$this->plugin->getConfig()->get("allow-commands")) {
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("allow-item-drop")) {
                $event->setCancelled();
            }
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("disable-hunger")) {
                $event->setCancelled();
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("allow-block-interact")) {
                $event->setCancelled();
            }
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("allow-eating")) {
                $event->setCancelled();
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $this->plugin->startSession($player);
        $data = $this->plugin->database->getPlayer($player->getName());
        if($this->plugin->getConfig()->get("auto-authentication") && !is_null($data) && $player->getUniqueId()->toString() == $data["uuid"]) {
            $this->plugin->force($player);
            return true;
        }
        if($this->plugin->getConfig()->get("xbox-bypass") && $this->plugin->getServer()->getName() == "ClearSky" && $player->isAuthenticated()) {
            if(!$this->plugin->isRegistered($player->getName())) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                $randompassword = [];
                $characteramount = strlen($characters) - 1;
                for($i = 0; $i < $this->plugin->getConfig()->get("minimum-password-length"); $i++) {
                    $character = mt_rand(0, $characteramount);
                    array_push($randompassword, $characters[$character]);
                }
                $randompassword = implode("", $randompassword);
                $this->plugin->register($player, $randompassword, $randompassword, "none", "true");
                $player->sendMessage(str_replace("{password}", $randompassword, $this->plugin->getMessage("auto-registered")));
            } else {
                if(!is_null($data) && $data["xbox"] == "true") {
                    $this->plugin->force($player);
                }
            }
            return true;
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            if(!$this->plugin->getConfig()->get("allow-movement")) {
                $event->setCancelled();
            }
        }
    }

    public function onPrelogin(PlayerPreLoginEvent $event) {
        $player = $event->getPlayer();
        if($this->plugin->getConfig()->get("single-session")) {
            if(!is_null($p = $this->plugin->getServer()->getPlayerExact($player->getName()))) {
                if($this->plugin->isAuthenticated($p) && $player->getUniqueId()->toString() !== $p->getUniqueId()->toString()) {
                    $player->close("", "Already logged in!");
                    $event->setCancelled();
                } else {
                    $p->close("", "Someone else is connecting to this account.");
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $this->plugin->logout($player);
    }

    public function onReceive(DataPacketReceiveEvent $event) {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if($packet instanceof ContainerSetSlotPacket) {
            if(!$this->plugin->isAuthenticated($player) && $this->plugin->getConfig()->get("hide-items")) {
                if($player->isSurvival()) {
                    if($packet->item !== Item::get(Item::AIR)) {
                        $pk = new ContainerSetSlotPacket();
                        $pk->windowid = $packet->windowid;
                        $pk->slot = $packet->slot;
                        $pk->hotbarSlot = $packet->hotbarSlot;
                        $pk->item = Item::get(Item::AIR);
                        $player->dataPacket($pk);
                        $event->setCancelled();
                    }
                }
            }
        }
    }

}
