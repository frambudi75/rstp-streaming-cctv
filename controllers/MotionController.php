<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/MotionEvent.php';
require_once __DIR__ . '/../models/Camera.php';
require_once __DIR__ . '/../config/security.php';

class MotionController {
    public function index(): void {
        $currentUser = requireAuth();
        $cameras = Camera::all();
        require __DIR__ . '/../views/dashboard/motion_logs.php';
    }

    public function apiList(): void {
        requireAuth();
        $filters = [
            'camera_id'  => $_GET['camera_id'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date'   => $_GET['end_date'] ?? null,
            'is_viewed'  => $_GET['is_viewed'] ?? null,
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(5, (int)($_GET['limit'] ?? 18)));

        $result = MotionEvent::all($filters, $page, $limit);
        jsonResponse(['success' => true, 'data' => $result]);
    }

    public function apiMarkViewed(int $id): void {
        requireAuth();
        MotionEvent::markViewed($id);
        jsonResponse(['success' => true]);
    }

    public function apiDelete(int $id): void {
        requireAuth();
        MotionEvent::delete($id);
        jsonResponse(['success' => true, 'message' => 'Event capture berhasil dihapus']);
    }

    public function apiCountUnread(): void {
        requireAuth();
        $count = MotionEvent::countUnread();
        jsonResponse(['success' => true, 'count' => $count]);
    }

    public function webhookStore(): void {
        $token = $_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? '';
        if (!hash_equals(INTERNAL_SERVICE_TOKEN, $token)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $cameraId = (int)($input['camera_id'] ?? 0);
        $snapshotPath = trim($input['snapshot_path'] ?? '');
        $confidence = (float)($input['confidence_score'] ?? 0.0);

        if (!$cameraId || $snapshotPath === '') {
            jsonResponse(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $eventId = MotionEvent::create($cameraId, $snapshotPath, $confidence);
        jsonResponse(['success' => true, 'event_id' => $eventId], 201);
    }
}

