<?php

namespace PiggyAuth\Events;

use pocketmine\event\plugin\PluginEvent;

/**
 * Class PlayerEvent
 * @package PiggyAuth\Events
 */
abstract class PlayerEvent extends PluginEvent
{
    /**
     * PlayerEvent constructor.
     * @param \pocketmine\plugin\Plugin $plugin
     */
    public function __construct($plugin)
    {
        parent::__construct($plugin);
    }
}
