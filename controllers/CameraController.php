<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Camera.php';
require_once __DIR__ . '/../models/SystemLog.php';
require_once __DIR__ . '/../config/security.php';

class CameraController {
    public function index(): void {
        $currentUser = requireAdmin();
        $cameras = Camera::all();
        require __DIR__ . '/../views/admin/cameras.php';
    }

    public function apiList(): void {
        requireAuth();
        $cameras = Camera::all();
        jsonResponse(['success' => true, 'data' => $cameras]);
    }

    public function create(): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/cameras');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $ip = trim($_POST['ip_address'] ?? '');
        $port = (int)($_POST['port'] ?? 554);
        $rtspPath = trim($_POST['rtsp_path'] ?? '/live/ch0');

        if ($name === '' || $ip === '') {
            $_SESSION['flash_error'] = 'Nama kamera dan IP address wajib diisi.';
            header('Location: ' . APP_URL . '/admin/cameras');
            exit;
        }

        $camId = Camera::create([
            'name'               => $name,
            'location'           => trim($_POST['location'] ?? ''),
            'group_name'         => trim($_POST['group_name'] ?? 'Gedung A'),
            'floor_subgroup'     => trim($_POST['floor_subgroup'] ?? 'Lantai 1'),
            'ip_address'         => $ip,
            'port'               => $port,
            'rtsp_path'          => $rtspPath,
            'username'           => trim($_POST['username'] ?? ''),
            'password'           => $_POST['password'] ?? '',
            'motion_enabled'     => isset($_POST['motion_enabled']) ? 1 : 0,
            'motion_sensitivity' => $_POST['motion_sensitivity'] ?? 'medium',
        ]);

        SystemLog::record($currentUser['id'], 'ADD_CCTV', 'cameras', $camId, json_encode(['name' => $name, 'ip' => $ip]));
        $_SESSION['flash_success'] = 'Kamera CCTV berhasil ditambahkan.';
        header('Location: ' . APP_URL . '/admin/cameras');
        exit;
    }

    public function update(int $id): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/cameras');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $ip = trim($_POST['ip_address'] ?? '');

        if ($name === '' || $ip === '') {
            $_SESSION['flash_error'] = 'Nama kamera dan IP address wajib diisi.';
            header('Location: ' . APP_URL . '/admin/cameras');
            exit;
        }

        Camera::update($id, [
            'name'               => $name,
            'location'           => trim($_POST['location'] ?? ''),
            'group_name'         => trim($_POST['group_name'] ?? 'Gedung A'),
            'floor_subgroup'     => trim($_POST['floor_subgroup'] ?? 'Lantai 1'),
            'ip_address'         => $ip,
            'port'               => (int)($_POST['port'] ?? 554),
            'rtsp_path'          => trim($_POST['rtsp_path'] ?? '/live/ch0'),
            'username'           => trim($_POST['username'] ?? ''),
            'password'           => $_POST['password'] ?? '',
            'motion_enabled'     => isset($_POST['motion_enabled']) ? 1 : 0,
            'motion_sensitivity' => $_POST['motion_sensitivity'] ?? 'medium',
        ]);

        SystemLog::record($currentUser['id'], 'EDIT_CCTV', 'cameras', $id, json_encode(['name' => $name, 'ip' => $ip]));
        $_SESSION['flash_success'] = 'Konfigurasi kamera berhasil diperbarui.';
        header('Location: ' . APP_URL . '/admin/cameras');
        exit;
    }

    public function delete(int $id): void {
        $currentUser = requireAdmin();
        $csrf = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf)) {
            $_SESSION['flash_error'] = 'Token sesi tidak valid.';
            header('Location: ' . APP_URL . '/admin/cameras');
            exit;
        }

        Camera::delete($id);
        SystemLog::record($currentUser['id'], 'DELETE_CCTV', 'cameras', $id);
        $_SESSION['flash_success'] = 'Kamera CCTV berhasil dihapus.';
        header('Location: ' . APP_URL . '/admin/cameras');
        exit;
    }
}

