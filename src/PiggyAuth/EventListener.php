<?php
namespace PiggyAuth;

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
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;

class EventListener implements Listener {
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }

    public function onPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if(!$this->plugin->isAuthenticated($player)) {
            if($this->plugin->getConfig("chat-login")) {
                if($this->plugin->isRegistered($player->getName())) {
                    $this->plugin->login($player, $message);
                } else {
                    if(strlen($message) < $this->plugin->getConfig()->get("minimum-password-length")) {
                        $player->sendMessage($this->plugin->getConfig()->get("password-too-short"));
                    } else {
                        if(!isset($this->plugin->confirmPassword[strtolower($player->getName())])) {
                            $this->plugin->confirmPassword[strtolower($player->getName())] = $message;
                            $player->sendMessage($this->plugin->getConfig("confirm-password"));
                        } else {
                            if($this->plugin->confirmPassword[strtolower($player->getName())] == $message) {
                                $this->plugin->register($player, $message);
                                unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                            } else {
                                $player->sendMessage($this->plugin->getConfig("password-not-same"));
                                unset($this->plugin->confirmPassword[strtolower($player->getName())]);
                            }
                        }
                    }
                }
            }
            $event->setCancelled();
        }
    }

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $message = strtolower($event->getMessage());
        if(!$this->plugin->isAuthenticated($player)) {
            if($message !== "/login" && $message !== "register"){
               $event->setCancelled(); 
            }
        }
    }
    
    public function onDrop(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }
    
    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event) {
        $player = $event->getPlayer();
        if(!$this->plugin->isAuthenticated($player)) {
            $event->setCancelled();
        }
    }
    
    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        if($this->plugin->getConfig("auto-authentication")) {
            $data = $this->plugin->getPlayer($player->getName());
            if(!is_null($data)) {
                if($player->getUniqueId() == $data["uuid"]){
                    $this->plugin->forcelogin($player);
                }
            }
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

    public function onQuit(PlayerQuitEvent $event) {
        if($this->plugin->isAuthenticated($player)) {
            unset($this->plugin->authenticated[strtolower($player->getName())]);
        }
    }

}
