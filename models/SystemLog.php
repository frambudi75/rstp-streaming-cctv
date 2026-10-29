<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class SystemLog {
    public static function record(?int $userId, string $action, ?string $targetEntity = null, ?int $targetId = null, ?string $details = null): void {
        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $db->prepare('INSERT INTO system_logs (user_id, action, target_entity, target_id, ip_address, details) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $action, $targetEntity, $targetId, $ip, $details]);
        } catch (Throwable) {
            // Suppress secondary logging failures
        }
    }

    public static function recent(int $limit = 50): array {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT l.*, u.username 
            FROM system_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC 
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

