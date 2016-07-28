<?php
namespace PiggyAuth;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;

class Main extends PluginBase {
    public function onEnable() {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("§aEnabled.");
    }
    
    public function isCorrectPassword(Player $player, $password){
        
    }
    
    public function isAuthenticated(Player $player){
        
    }
   
   public function isRegistered(Player $player){
        
    }
     
    public function login(Player $player, $password){
        
    }
    
    public function register(Player $player, $password){
        
    }
    
    public function changepassword(Player $player, $oldpassword, $newpassword){
        
    }
    
}
