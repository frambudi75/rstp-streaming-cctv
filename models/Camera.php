<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/redis.php';

class Camera {
    public static function all(): array {
        $cacheKey = 'cctv:cameras:all';
        $cached = RedisCache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $db = Database::getConnection();
        $stmt = $db->query('
            SELECT id, name, location, group_name, floor_subgroup, ip_address, port, rtsp_path, username, status, motion_enabled, motion_sensitivity, last_seen_at 
            FROM cameras 
            ORDER BY group_name ASC, floor_subgroup ASC, id ASC
        ');
        $cameras = $stmt->fetchAll();
        foreach ($cameras as &$cam) {
            $cam['stream_url'] = STREAMER_WS_URL . '/stream?id=' . $cam['id'];
        }

        RedisCache::set($cacheKey, $cameras, 120);
        return $cameras;
    }

    public static function findById(int $id): ?array {
        $cacheKey = 'cctv:camera:' . $id;
        $cached = RedisCache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT id, name, location, group_name, floor_subgroup, ip_address, port, rtsp_path, username, status, motion_enabled, motion_sensitivity, last_seen_at 
            FROM cameras 
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $result = $row ?: null;

        if ($result !== null) {
            RedisCache::set($cacheKey, $result, 120);
        }
        return $result;
    }

    public static function getGroupsAndFloors(): array {
        $cacheKey = 'cctv:groups_floors';
        $cached = RedisCache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        $db = Database::getConnection();
        $groups = $db->query("SELECT DISTINCT group_name FROM cameras WHERE group_name IS NOT NULL AND group_name != '' ORDER BY group_name ASC")->fetchAll(PDO::FETCH_COLUMN);
        $floors = $db->query("SELECT DISTINCT floor_subgroup FROM cameras WHERE floor_subgroup IS NOT NULL AND floor_subgroup != '' ORDER BY floor_subgroup ASC")->fetchAll(PDO::FETCH_COLUMN);
        $result = [
            'groups' => $groups ?: [],
            'floors' => $floors ?: []
        ];

        RedisCache::set($cacheKey, $result, 300);
        return $result;
    }

    public static function getDecryptedTarget(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, name, ip_address, port, rtsp_path, username, encrypted_password, iv_vector, motion_enabled, motion_sensitivity FROM cameras WHERE id = ?');
        $stmt->execute([$id]);
        $cam = $stmt->fetch();
        if (!$cam) {
            return null;
        }

        $password = '';
        if (!empty($cam['encrypted_password']) && !empty($cam['iv_vector'])) {
            $password = decryptCameraPassword($cam['encrypted_password'], $cam['iv_vector']);
        }

        $authPart = '';
        if (!empty($cam['username'])) {
            $user = rawurlencode($cam['username']);
            $pass = rawurlencode($password);
            $authPart = $password !== '' ? "{$user}:{$pass}@" : "{$user}@";
        }

        if (str_starts_with($cam['ip_address'], 'rtsp://') || str_starts_with($cam['ip_address'], 'http://') || str_starts_with($cam['ip_address'], 'https://')) {
            $rtspUrl = $cam['ip_address'];
        } else {
            $path = '/' . ltrim($cam['rtsp_path'], '/');
            $rtspUrl = sprintf('rtsp://%s%s:%d%s', $authPart, $cam['ip_address'], (int)$cam['port'], $path);
        }

        return [
            'id'                 => (int)$cam['id'],
            'name'               => $cam['name'],
            'rtsp_url'           => $rtspUrl,
            'motion_enabled'     => (bool)$cam['motion_enabled'],
            'motion_sensitivity' => $cam['motion_sensitivity'],
        ];
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $enc = ['ciphertext' => null, 'iv' => null];
        if (!empty($data['password'])) {
            $enc = encryptCameraPassword($data['password']);
        }

        $stmt = $db->prepare('
            INSERT INTO cameras (name, location, group_name, floor_subgroup, ip_address, port, rtsp_path, username, encrypted_password, iv_vector, motion_enabled, motion_sensitivity, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "offline")
        ');

        $stmt->execute([
            $data['name'],
            $data['location'] ?? null,
            !empty($data['group_name']) ? trim($data['group_name']) : 'Gedung A',
            !empty($data['floor_subgroup']) ? trim($data['floor_subgroup']) : 'Lantai 1',
            $data['ip_address'],
            (int)($data['port'] ?? 554),
            $data['rtsp_path'] ?? '/live/ch0',
            $data['username'] ?? null,
            $enc['ciphertext'],
            $enc['iv'],
            isset($data['motion_enabled']) ? (int)$data['motion_enabled'] : 1,
            $data['motion_sensitivity'] ?? 'medium'
        ]);

        self::clearCache();
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $params = [
            $data['name'],
            $data['location'] ?? null,
            !empty($data['group_name']) ? trim($data['group_name']) : 'Gedung A',
            !empty($data['floor_subgroup']) ? trim($data['floor_subgroup']) : 'Lantai 1',
            $data['ip_address'],
            (int)($data['port'] ?? 554),
            $data['rtsp_path'] ?? '/live/ch0',
            $data['username'] ?? null,
            isset($data['motion_enabled']) ? (int)$data['motion_enabled'] : 1,
            $data['motion_sensitivity'] ?? 'medium',
        ];

        if (!empty($data['password'])) {
            $enc = encryptCameraPassword($data['password']);
            $sql = '
                UPDATE cameras 
                SET name = ?, location = ?, group_name = ?, floor_subgroup = ?, ip_address = ?, port = ?, rtsp_path = ?, username = ?, motion_enabled = ?, motion_sensitivity = ?, encrypted_password = ?, iv_vector = ? 
                WHERE id = ?
            ';
            $params[] = $enc['ciphertext'];
            $params[] = $enc['iv'];
            $params[] = $id;
        } else {
            $sql = '
                UPDATE cameras 
                SET name = ?, location = ?, group_name = ?, floor_subgroup = ?, ip_address = ?, port = ?, rtsp_path = ?, username = ?, motion_enabled = ?, motion_sensitivity = ? 
                WHERE id = ?
            ';
            $params[] = $id;
        }

        $stmt = $db->prepare($sql);
        $res = $stmt->execute($params);
        self::clearCache($id);
        return $res;
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM cameras WHERE id = ?');
        $res = $stmt->execute([$id]);
        self::clearCache($id);
        return $res;
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE cameras SET status = ?, last_seen_at = IF(? = "online", NOW(), last_seen_at) WHERE id = ?');
        $res = $stmt->execute([$status, $status, $id]);
        self::clearCache($id);
        return $res;
    }

    private static function clearCache(?int $id = null): void {
        RedisCache::del('cctv:cameras:all');
        RedisCache::del('cctv:groups_floors');
        if ($id !== null) {
            RedisCache::del('cctv:camera:' . $id);
        }
    }
}
