<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireAuth(): array {
    if (empty($_SESSION['user_id'])) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        header('Location: ' . APP_URL . '/login');
        exit;
    }
    return [
        'id'       => (int)$_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
    ];
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            jsonResponse(['success' => false, 'message' => 'Forbidden: Admin access required'], 403);
        }
        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }
    return $user;
}

function encryptCameraPassword(string $password): array {
    $key = hash('sha256', APP_ENCRYPTION_KEY, true);
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($password, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return [
        'ciphertext' => base64_encode($ciphertext),
        'iv'         => base64_encode($iv)
    ];
}

function decryptCameraPassword(string $ciphertextBase64, string $ivBase64): string {
    $key = hash('sha256', APP_ENCRYPTION_KEY, true);
    $ciphertext = base64_decode($ciphertextBase64);
    $iv = base64_decode($ivBase64);
    $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : '';
}

