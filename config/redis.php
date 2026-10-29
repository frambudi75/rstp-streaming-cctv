<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class RedisCache {
    private static ?Redis $client = null;
    private static bool $checked = false;

    private static function init(): ?Redis {
        if (self::$checked) {
            return self::$client;
        }
        self::$checked = true;

        if (!class_exists('Redis')) {
            return null;
        }

        try {
            $redis = new Redis();
            $host = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
            $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
            $connected = @$redis->connect($host, $port, 1.0); // 1s timeout
            if ($connected) {
                self::$client = $redis;
            }
        } catch (Throwable) {
            self::$client = null;
        }

        return self::$client;
    }

    public static function get(string $key): mixed {
        $client = self::init();
        if (!$client) return null;
        try {
            $data = $client->get($key);
            return $data !== false ? json_decode($data, true) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function set(string $key, mixed $value, int $ttl = 60): bool {
        $client = self::init();
        if (!$client) return false;
        try {
            return $client->setex($key, $ttl, json_encode($value));
        } catch (Throwable) {
            return false;
        }
    }

    public static function del(string $key): bool {
        $client = self::init();
        if (!$client) return false;
        try {
            return (bool)$client->del($key);
        } catch (Throwable) {
            return false;
        }
    }

    public static function invalidateGroup(string $pattern): void {
        $client = self::init();
        if (!$client) return;
        try {
            $keys = $client->keys($pattern);
            if (!empty($keys)) {
                $client->del($keys);
            }
        } catch (Throwable) {}
    }
}

