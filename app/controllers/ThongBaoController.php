<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/thong_bao.php';

class ThongBaoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $accountId = (int) ($_SESSION['tai_khoan']['id_tai_khoan'] ?? 0);
        if (!$accountId) {
            header('Location: index.php?page=login', true, 303);
            return;
        }
        $model = new ThongBaoModel(getDatabaseConnection());
        if (!$model->isCustomer($accountId)) {
            http_response_code(403);
            echo 'Tài khoản không có quyền xem thông báo khách hàng.';
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(403);
                echo 'Phiên xác nhận không hợp lệ. Vui lòng tải lại trang.';
                return;
            }
            $operation = $_POST['operation'] ?? '';
            if ($operation === 'read_all') {
                $model->readAll($accountId);
                header('Location: index.php?page=thong_bao', true, 303);
                return;
            }
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($operation !== 'read' || !$id) {
                http_response_code(400);
                echo 'Thao tác không hợp lệ.';
                return;
            }
            $code = $model->readOne($accountId, $id);
            if ($code === null) {
                http_response_code(404);
                echo 'Không tìm thấy thông báo.';
                return;
            }
            header('Location: index.php?page=orders&id=' . rawurlencode($code), true, 303);
            return;
        }
        $currentPage = filter_var($_GET['trang'] ?? 1, FILTER_VALIDATE_INT) ?: 1;
        $notifications = $model->listForAccount($accountId, $currentPage);
        $csrfToken = $_SESSION['csrf_token'];
        require __DIR__ . '/../views/thong_bao.php';
    }
}
