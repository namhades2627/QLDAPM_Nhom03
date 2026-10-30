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
$page = in_array(
    $requestedPage,
    ['tours', 'tour-detail', 'login', 'register', 'logout', 'admin', 'booking', 'booking-success', 'my-bookings', 'cancel-booking'],
    true
) ? $requestedPage : 'home';
if ($page === 'admin') {
    header('Location: ../admin/', true, 303);
    return;
}
// Các yêu cầu tài khoản được xử lý qua AuthController.
if (in_array($page, ['login', 'register', 'logout'], true)) {
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    $authController = new AuthController();
    if ($page === 'logout') $authController->logout();
    else $authController->form($page);
    return;
}
// Đặt tour, xem đơn và hủy đơn đi qua BookingController.
if (in_array($page, ['booking', 'booking-success', 'my-bookings', 'cancel-booking'], true)) {
    require_once __DIR__ . '/../app/controllers/BookingController.php';
    try {
        $bookingController = new BookingController();
        if ($page === 'booking') $bookingController->form();
        elseif ($page === 'booking-success') $bookingController->success();
        elseif ($page === 'cancel-booking') $bookingController->cancel();
        else $bookingController->myBookings();
    } catch (PDOException $exception) {
        error_log('OnTrip booking database error: ' . $exception->getMessage());
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
