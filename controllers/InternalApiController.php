<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Camera.php';
require_once __DIR__ . '/../config/security.php';

class InternalApiController {
    private function verifyInternalToken(): void {
        $token = $_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? '';
        if (!hash_equals(INTERNAL_SERVICE_TOKEN, $token)) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized service call'], 401);
        }
    }

    public function getStreamTarget(int $id): void {
        $this->verifyInternalToken();
        $target = Camera::getDecryptedTarget($id);
        if (!$target) {
            jsonResponse(['success' => false, 'message' => 'Camera not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $target]);
    }

    public function updateCameraStatus(int $id): void {
        $this->verifyInternalToken();
        $input = json_decode(file_get_contents('php://input'), true);
        $status = in_array($input['status'] ?? '', ['online', 'offline', 'error'], true) ? $input['status'] : 'offline';
        Camera::updateStatus($id, $status);
        jsonResponse(['success' => true]);
    }
}

