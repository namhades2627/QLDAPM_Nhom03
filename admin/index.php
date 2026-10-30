<?php
// Lối vào riêng /admin; phiên quản trị độc lập với website khách hàng.
session_name('ONTRIP_ADMIN');
session_start([
    'use_strict_mode' => true,
    'cookie_path' => str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) . '/',
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Lax',
]);
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
require_once __DIR__ . '/../app/controllers/AdminController.php';
$controller = new AdminController();
$action = $_GET['action'] ?? '';
if ($action === 'logout') {
    $controller->logout();
} elseif ($action === 'login' || !isset($_SESSION['tai_khoan'])) {
    $controller->login();
} else {
    try {
        $section = in_array($action, ['profile', 'password'], true) ? $action : 'dashboard';
        $controller->index($section);
    } catch (PDOException $exception) {
        error_log('OnTrip admin database error: ' . $exception->getMessage());
        http_response_code(503);
        $errorTitle = 'Chưa thể tải bảng điều khiển';
        $errorMessage = 'Vui lòng thử lại sau ít phút.';
        require __DIR__ . '/../app/views/admin/forbidden.php';
    }
}
