<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/SystemLog.php';
require_once __DIR__ . '/../config/security.php';

class UserController {
    public function index(): void {
        $currentUser = requireAdmin();
        $users = User::all();
        require __DIR__ . '/../views/admin/users.php';
    }

    public function create(): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = in_array($_POST['role'] ?? '', ['admin', 'observer'], true) ? $_POST['role'] : 'observer';

        if ($username === '' || $email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Semua kolom wajib diisi.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        if (User::findByUsername($username)) {
            $_SESSION['flash_error'] = 'Username sudah digunakan.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        $userId = User::create($username, $email, $password, $role);
        SystemLog::record($currentUser['id'], 'ADD_USER', 'users', $userId, json_encode(['username' => $username, 'role' => $role]));
        $_SESSION['flash_success'] = 'Pengguna berhasil ditambahkan.';
        header('Location: ' . APP_URL . '/admin/users');
        exit;
    }

    public function update(int $id): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = !empty($_POST['password']) ? $_POST['password'] : null;
        $role = in_array($_POST['role'] ?? '', ['admin', 'observer'], true) ? $_POST['role'] : 'observer';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        User::update($id, $email, $password, $role, $isActive);
        SystemLog::record($currentUser['id'], 'EDIT_USER', 'users', $id);
        $_SESSION['flash_success'] = 'Data pengguna berhasil diperbarui.';
        header('Location: ' . APP_URL . '/admin/users');
        exit;
    }

    public function delete(int $id): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        if ($id === $currentUser['id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri.';
            header('Location: ' . APP_URL . '/admin/users');
            exit;
        }

        User::delete($id);
        SystemLog::record($currentUser['id'], 'DELETE_USER', 'users', $id);
        $_SESSION['flash_success'] = 'Pengguna berhasil dihapus.';
        header('Location: ' . APP_URL . '/admin/users');
        exit;
    }
}

