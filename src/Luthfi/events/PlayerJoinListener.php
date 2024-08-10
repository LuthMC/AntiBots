<?php

namespace Luthfi\events;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Player;
use Luthfi\AntiBots;

class PlayerJoinListener implements Listener {

    private $plugin;

    private $connectionAttempts = [];

    public function __construct(AntiBots $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerPreLogin(PlayerPreLoginEvent $event): void {
        $player = $event->getPlayer();
        $ip = $player->getAddress();
        $username = $player->getName();

        // Check for blacklisted IPs
        if ($this->plugin->getConfigValue("ip_blacklisting.enabled", true)) {
            $blacklist = $this->plugin->getConfigValue("ip_blacklisting.blacklist", []);
            if (in_array($ip, $blacklist)) {
                $event->setCancelled(true);
                $this->plugin->getLogger()->warning("Blocked blacklisted IP: $ip");
                return;
            }
        }

        // Check for invalid usernames
        if ($this->plugin->getConfigValue("filtering.invalid_usernames", true)) {
            if (!$this->isValidUsername($username)) {
                $event->setCancelled(true);
                $this->plugin->getLogger()->warning("Blocked invalid username: $username");
                return;
            }
        }

        // Rate limiting
        if ($this->plugin->getConfigValue("rate_limiting.enabled", true)) {
            $this->checkRateLimiting($ip, $event);
        }
    }

    private function isValidUsername(string $username): bool {
        return preg_match("/^[a-zA-Z0-9_]{3,16}$/", $username);
    }

    private function checkRateLimiting(string $ip, PlayerPreLoginEvent $event): void {
        $timeLimit = $this->plugin->getConfigValue("rate_limiting.time_limit", 60);
        $maxConnections = $this->plugin->getConfigValue("rate_limiting.max_connections", 5);

        $currentTime = time();
        $this->connectionAttempts[$ip] = array_filter(
            $this->connectionAttempts[$ip] ?? [],
            fn($time) => $currentTime - $time <= $timeLimit
        );

        if (count($this->connectionAttempts[$ip]) >= $maxConnections) {
            $event->setCancelled(true);
            $this->plugin->getLogger()->warning("Rate limiting triggered for IP: $ip");
            if ($this->plugin->getConfigValue("notifications.notify_on_bot_attack", true)) {
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer) {
                    if ($onlinePlayer->hasPermission("antibots.notify")) {
                        $onlinePlayer->sendMessage("Bot attack detected from IP: $ip");
                    }
                }
            }
        } else {
            $this->connectionAttempts[$ip][] = $currentTime;
        }
    }
}
