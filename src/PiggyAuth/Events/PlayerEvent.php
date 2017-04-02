<?php

namespace PiggyAuth\Events;

use pocketmine\event\plugin\PluginEvent;

abstract class PlayerEvent extends PluginEvent
{
    public function __construct($plugin)
    {
        parent::__construct($plugin);
    }
}
