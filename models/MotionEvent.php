<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class MotionEvent {
    public static function all(array $filters = [], int $page = 1, int $limit = 20): array {
        $db = Database::getConnection();
        $offset = max(0, ($page - 1) * $limit);

        $conditions = [];
        $params = [];

        if (!empty($filters['camera_id'])) {
            $conditions[] = 'm.camera_id = ?';
            $params[] = (int)$filters['camera_id'];
        }
        if (!empty($filters['start_date'])) {
            $conditions[] = 'm.detected_at >= ?';
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        if (!empty($filters['end_date'])) {
            $conditions[] = 'm.detected_at <= ?';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        if (isset($filters['is_viewed']) && $filters['is_viewed'] !== '') {
            $conditions[] = 'm.is_viewed = ?';
            $params[] = (int)$filters['is_viewed'];
        }

        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $countStmt = $db->prepare("SELECT COUNT(*) FROM motion_events m {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT m.id, m.camera_id, c.name AS camera_name, c.location AS camera_location,
                   m.snapshot_path, m.confidence_score, m.is_viewed, m.detected_at, m.created_at
            FROM motion_events m
            JOIN cameras c ON m.camera_id = c.id
            {$whereClause}
            ORDER BY m.detected_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        return [
            'data'         => $records,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $limit,
            'total_pages'  => (int)ceil($total / max(1, $limit)),
        ];
    }

    public static function countUnread(): int {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT COUNT(*) FROM motion_events WHERE is_viewed = 0');
        return (int)$stmt->fetchColumn();
    }

    public static function create(int $cameraId, string $snapshotPath, float $confidenceScore): int {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO motion_events (camera_id, snapshot_path, confidence_score, detected_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$cameraId, $snapshotPath, $confidenceScore]);
        return (int)$db->lastInsertId();
    }

    public static function markViewed(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE motion_events SET is_viewed = 1 WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT snapshot_path FROM motion_events WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['snapshot_path'])) {
            $filePath = dirname(__DIR__) . '/' . ltrim($row['snapshot_path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $del = $db->prepare('DELETE FROM motion_events WHERE id = ?');
        return $del->execute([$id]);
    }
}

