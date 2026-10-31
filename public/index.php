<?php
// Khởi tạo trước mọi HTML để có thể đổi session ID và chuyển hướng an toàn.
session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Lax',
]);
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// Điều hướng trang chủ và các trang tour qua Controller tương ứng.
$requestedPage = $_GET['page'] ?? '';
$page = in_array($requestedPage, ['tours', 'tour-detail', 'booking', 'orders', 'login', 'register', 'logout', 'admin'], true) ? $requestedPage : 'home';
if ($page === 'admin') {
    header('Location: ../admin/', true, 303);
    return;
}
// Các yêu cầu tài khoản được xử lý qua TaiKhoanController.
if (in_array($page, ['login', 'register', 'logout'], true)) {
    require_once __DIR__ . '/../app/controllers/TaiKhoanController.php';
    $authController = new TaiKhoanController();
    if ($page === 'logout') $authController->logout();
    else $authController->form($page);
    return;
}
// Các trang đặt tour và đơn đặt được xử lý qua DonDatController.
if (in_array($page, ['booking', 'orders'], true)) {
    require_once __DIR__ . '/../app/controllers/DonDatController.php';
    $bookingController = new DonDatController();
    try {
        if ($page === 'booking') $bookingController->form();
        else $bookingController->orders();
    } catch (PDOException $exception) {
        error_log('OnTrip database error: ' . $exception->getMessage());
        http_response_code(503);
        require __DIR__ . '/../app/views/errors/database.php';
    }
    return;
}
require_once __DIR__ . '/../app/controllers/HomeController.php';
require_once __DIR__ . '/../app/controllers/TourController.php';
try {
    if ($page === 'home') {
        (new HomeController())->index();
    } else {
        $controller = new TourController();
        if ($page === 'tour-detail') {
            $controller->detail();
        } else {
            $controller->index();
        }
    }
} catch (PDOException $exception) {
    error_log('OnTrip database error: ' . $exception->getMessage());
    http_response_code(503);
    require __DIR__ . '/../app/views/errors/database.php';
}
