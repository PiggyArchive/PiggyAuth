<?php

namespace PiggyAuth\Entities;

use pocketmine\entity\Entity;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class Wither extends Entity
{
    const NETWORK_ID = 52;

    public function getName()
    {
        return "Wither";
    }

    public function initEntity()
    {
        $this->setMaxHealth(300);
        parent::initEntity();
    }

    public function spawnTo(Player $player)
    {
        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = Wither::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);
        parent::spawnTo($player);
    }

}
