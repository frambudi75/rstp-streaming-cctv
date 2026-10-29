<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class User {
    public static function findByUsername(string $username): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, username, email, role, is_active, last_login_at, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT id, username, email, role, is_active, last_login_at, created_at FROM users ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    public static function create(string $username, string $email, string $password, string $role): int {
        $db = Database::getConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email, $hash, $role]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $email, ?string $password, string $role, int $isActive): bool {
        $db = Database::getConnection();
        if ($password !== null && $password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET email = ?, password_hash = ?, role = ?, is_active = ? WHERE id = ?');
            return $stmt->execute([$email, $hash, $role, $isActive, $id]);
        }
        $stmt = $db->prepare('UPDATE users SET email = ?, role = ?, is_active = ? WHERE id = ?');
        return $stmt->execute([$email, $role, $isActive, $id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function updateLastLogin(int $id): void {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}

