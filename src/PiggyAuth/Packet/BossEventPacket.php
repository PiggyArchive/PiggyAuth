<?php

namespace PiggyAuth\Packet;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

/**
 * Class BossEventPacket
 * @package PiggyAuth\Packet
 */
class BossEventPacket extends DataPacket
{
    const NETWORK_ID = 0x4c;

    public $entityRuntimeId;
    public $state;

    public function decode()
    {
        $this->entityRuntimeId = $this->getEntityUniqueId();
        $this->state = $this->getUnsignedVarInt();
    }

    public function encode()
    {
        $this->reset();
        $this->putEntityUniqueId($this->entityRuntimeId);
        $this->putEntityRuntimeId($this->entityRuntimeId);
        $this->putUnsignedVarInt($this->state);
    }

    /**
     * @param NetworkSession $session
     * @return bool
     */
    public function handle(NetworkSession $session) : bool{
        return null;
    }
}
