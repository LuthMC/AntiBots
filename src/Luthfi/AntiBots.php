<?php

namespace Luthfi;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use Luthfi\events\PlayerJoinListener;

class AntiBots extends PluginBase implements Listener {

    /** @var Config */
    private $config;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener($this), $this);
        $this->getLogger()->info("AntiBots Enabled");
    }

    public function getConfigValue(string $key, $default = null) {
        return $this->config->get($key, $default);
    }
}
