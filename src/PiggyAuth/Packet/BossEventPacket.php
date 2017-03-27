<?php

namespace PiggyAuth\Packet;

use pocketmine\network\mcpe\protocol\DataPacket;

class BossEventPacket extends DataPacket {
    const NETWORK_ID = 0x4b;

    public $eid;
    public $state;

    public function decode() {
        $this->eid = $this->getEntityId();
        $this->state = $this->getUnsignedVarInt();
    }

    public function encode() {
        $this->reset();
        $this->putEntityId($this->eid);
        $this->putUnsignedVarInt($this->state);
    }
}
