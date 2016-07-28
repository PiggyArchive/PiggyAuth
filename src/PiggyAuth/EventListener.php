<?php
namespace PiggyAuth;

use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;

class EventListener implements Listener {
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        if($this->plugin->isAuthenticated($player)){
            if(!$this->plugin->getConfig()->get("allow-block-interaction")){
                $event->setCancelled();
            }
        }
    }

    public function onMove(PlayerMoveEvent $event) {
        $player = $event->getPlayer();
        if($this->plugin->isAuthenticated($player)){
            if(!$this->plugin->getConfig()->get("allow-movement")){
                $event->setCancelled();
            }
        }
    }

    public function onJoin(PlayerJoinEvent){

    }

    public function onQuit(PlayerQuitEvent){

    }

}
