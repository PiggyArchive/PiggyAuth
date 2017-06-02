<?php

namespace PiggyAuth\Packet;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

/**
 * Class SetTitlePacket
 * @package PiggyAuth\Packet
 */
class SetTitlePacket extends DataPacket
{
    const NETWORK_ID = 0x58;

    const TYPE_CLEAR_TITLE = 0;
    const TYPE_RESET_TITLE = 1;
    const TYPE_SET_TITLE = 2;
    const TYPE_SET_SUBTITLE = 3;
    const TYPE_SET_ACTIONBAR_MESSAGE = 4;
    const TYPE_SET_ANIMATION_TIMES = 5;

    public $type;
    public $text;
    public $fadeInTime;
    public $stayTime;
    public $fadeOutTime;

    public function decode()
    {
        $this->type = $this->getVarInt();
        $this->text = $this->getString();
        $this->fadeInTime = $this->getVarInt();
        $this->stayTime = $this->getVarInt();
        $this->fadeOutTime = $this->getVarInt();
    }

    public function encode()
    {
        $this->reset();
        $this->putVarInt($this->type);
        $this->putString($this->text);
        $this->putVarInt($this->fadeInTime);
        $this->putVarInt($this->stayTime);
        $this->putVarInt($this->fadeOutTime);
    }

    /**
     * @param NetworkSession $session
     * @return bool
     */
    public function handle(NetworkSession $session) : bool{
        return $session->handleSetTitle($this);
    }
}