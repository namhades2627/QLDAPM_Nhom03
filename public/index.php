<?php
// Điều hướng trang chủ và các trang tour qua Controller tương ứng.
$requestedPage = $_GET['page'] ?? '';
$page = in_array($requestedPage, ['tours', 'tour-detail'], true) ? $requestedPage : 'home';
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
