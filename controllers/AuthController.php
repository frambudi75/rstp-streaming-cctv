<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/SystemLog.php';
require_once __DIR__ . '/../config/security.php';

class AuthController {
    public function showLogin(): void {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $token = $_POST['csrf_token'] ?? '';

        if (!validateCsrfToken($token)) {
            $_SESSION['login_error'] = 'Token sesi tidak valid. Silakan muat ulang halaman.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Username dan password wajib diisi.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            SystemLog::record(null, 'LOGIN_FAILED', 'users', null, json_encode(['username' => $username]));
            $_SESSION['login_error'] = 'Username atau password tidak sesuai.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        if (!(int)$user['is_active']) {
            $_SESSION['login_error'] = 'Akun Anda dinonaktifkan. Hubungi administrator.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        User::updateLastLogin((int)$user['id']);
        SystemLog::record((int)$user['id'], 'LOGIN_SUCCESS', 'users', (int)$user['id']);

        header('Location: ' . APP_URL . '/dashboard');
        exit;
    }

    public function logout(): void {
        if (!empty($_SESSION['user_id'])) {
            SystemLog::record((int)$_SESSION['user_id'], 'LOGOUT', 'users', (int)$_SESSION['user_id']);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}

