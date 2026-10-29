<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CameraController.php';
require_once __DIR__ . '/controllers/MotionController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/InternalApiController.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

if ($scriptDir !== '/' && $scriptDir !== '.' && $scriptDir !== '' && str_starts_with($requestUri, $scriptDir)) {
    $path = substr($requestUri, strlen($scriptDir));
} else {
    $path = $requestUri;
}

if (str_starts_with($path, '/index.php')) {
    $path = substr($path, strlen('/index.php'));
}

$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/' || $path === '') {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}

if ($path === '/login') {
    $controller = new AuthController();
    $method === 'POST' ? $controller->login() : $controller->showLogin();
    exit;
}

if ($path === '/logout') {
    $controller = new AuthController();
    $controller->logout();
    exit;
}

if ($path === '/dashboard') {
    $currentUser = requireAuth();
    $cameras = Camera::all();
    $unreadMotions = MotionEvent::countUnread();
    require __DIR__ . '/views/dashboard/index.php';
    exit;
}

if ($path === '/motion-events') {
    $controller = new MotionController();
    $controller->index();
    exit;
}

if ($path === '/admin/cameras') {
    $controller = new CameraController();
    $controller->index();
    exit;
}

if ($path === '/admin/cameras/create' && $method === 'POST') {
    $controller = new CameraController();
    $controller->create();
    exit;
}

if (preg_match('#^/admin/cameras/(\d+)/update$#', $path, $matches) && $method === 'POST') {
    $controller = new CameraController();
    $controller->update((int)$matches[1]);
    exit;
}

if (preg_match('#^/admin/cameras/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
    $controller = new CameraController();
    $controller->delete((int)$matches[1]);
    exit;
}

if ($path === '/admin/users') {
    $controller = new UserController();
    $controller->index();
    exit;
}

if ($path === '/admin/users/create' && $method === 'POST') {
    $controller = new UserController();
    $controller->create();
    exit;
}

if (preg_match('#^/admin/users/(\d+)/update$#', $path, $matches) && $method === 'POST') {
    $controller = new UserController();
    $controller->update((int)$matches[1]);
    exit;
}

if (preg_match('#^/admin/users/(\d+)/delete$#', $path, $matches) && $method === 'POST') {
    $controller = new UserController();
    $controller->delete((int)$matches[1]);
    exit;
}

// REST & Internal API Endpoints
if ($path === '/api/cameras') {
    $controller = new CameraController();
    $controller->apiList();
    exit;
}

if ($path === '/api/motion-events') {
    $controller = new MotionController();
    $controller->apiList();
    exit;
}

if ($path === '/api/motion-events/unread-count') {
    $controller = new MotionController();
    $controller->apiCountUnread();
    exit;
}

if (preg_match('#^/api/motion-events/(\d+)/viewed$#', $path, $matches) && ($method === 'POST' || $method === 'PATCH')) {
    $controller = new MotionController();
    $controller->apiMarkViewed((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/motion-events/(\d+)/delete$#', $path, $matches) && ($method === 'POST' || $method === 'DELETE')) {
    $controller = new MotionController();
    $controller->apiDelete((int)$matches[1]);
    exit;
}

if (preg_match('#^/internal/cameras/(\d+)/stream-target$#', $path, $matches)) {
    $controller = new InternalApiController();
    $controller->getStreamTarget((int)$matches[1]);
    exit;
}

if (preg_match('#^/internal/cameras/(\d+)/status$#', $path, $matches) && $method === 'POST') {
    $controller = new InternalApiController();
    $controller->updateCameraStatus((int)$matches[1]);
    exit;
}

if ($path === '/internal/motion-events' && $method === 'POST') {
    $controller = new MotionController();
    $controller->webhookStore();
    exit;
}

http_response_code(404);
echo "404 Not Found: " . e($path);
exit;

