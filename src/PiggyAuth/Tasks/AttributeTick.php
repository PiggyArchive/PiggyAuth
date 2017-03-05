<?php

namespace PiggyAuth\Tasks;

use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\scheduler\PluginTask;

use PiggyAuth\FakeAttribute;

class AttributeTick extends PluginTask {
    public function __construct($plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if (!$this->plugin->isAuthenticated($player)) {
                if ($this->plugin->getConfig()->getNested("effects.hide-health")) {
                    $pk = new UpdateAttributesPacket();
                    $pk->entityId = $player->getId();
                    $pk->entries[] = new FakeAttribute(0.00, 20.00, 20.00, "minecraft:health");
                    $player->dataPacket($pk);
                }
                if ($this->plugin->getConfig()->getNested("effects.hide-hunger")) {
                    $pk = new UpdateAttributesPacket();
                    $pk->entityId = $player->getId();
                    $pk->entries[] = new FakeAttribute(0.00, 20.00, 20.00, "minecraft:player.hunger");
                    $player->dataPacket($pk);
                }
                if ($this->plugin->getConfig()->getNested("effects.hide-xp")) {
                    $pk = new UpdateAttributesPacket();
                    $pk->entityId = $player->getId();
                    $pk->entries[] = new FakeAttribute(0.00, 24791.00, 0.00, "minecraft:player.level");
                    $player->dataPacket($pk);

                    $pk = new UpdateAttributesPacket();
                    $pk->entityId = $player->getId();
                    $pk->entries[] = new FakeAttribute(0.00, 1.00, 0.00, "minecraft:player.experience");
                    $player->dataPacket($pk);
                }
                if (!$this->plugin->getConfig()->getNested("events.allow-effect-tick")) {
                    foreach ($player->getEffects() as $effect) {
                        $effect->setDuration($effect->getDuration() + 20);
                        $player->sendPotionEffects($player);
                    }
                }
            }
        }
    }
}
