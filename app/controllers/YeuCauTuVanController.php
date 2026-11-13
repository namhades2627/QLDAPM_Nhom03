<?php
require_once __DIR__ . '/../models/yeu_cau_tu_van.php';
require_once __DIR__ . '/TuVanController.php';

/** AdminController đã kiểm tra phiên và quyền trước khi gọi module. */
class YeuCauTuVanController
{
    private function query(string $key): string
    {
        return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
    }

    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    public function index(array $account, string $roleName, string $csrfToken): void
    {
        $model = new YeuCauTuVanModel(getDatabaseConnection());
        $adminSection = 'yeu_cau_tu_van';
        $avatarUrl = AdminController::avatarUrl($account['hinh_anh'] ?? null);
        $filters = ['q' => mb_substr($this->query('q'), 0, 120, 'UTF-8'),
            'status' => '', 'owner' => ''];
        $page = filter_var($this->query('p'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $listUrl = static fn(array $extra = []): string => 'index.php?' . http_build_query(array_filter(array_merge(['action' => 'yeu_cau_tu_van'], $filters, ['p' => $page], $extra), static fn($value) => $value !== '' && $value !== null));
        $id = filter_var($this->query('view'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        if (($_SERVER['REQUEST_METHOD'] === 'GET' && $this->query('messages') === '1') || ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->input('operation') === 'reply')) {
            $id = $id ?: (filter_var($this->input('id_yeu_cau'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0);
            if (!$id || !$model->find($id)) {
                http_response_code(404); header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            TuVanController::handleThread($model, $id, $account, $csrfToken, true);
            return;
        }
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(400);
            $message = 'Thao tác không hợp lệ. Vui lòng gửi tin nhắn trong khung chat.';
        }
        $requests = $model->listRequests($filters, $page);
        $detail = $id ? $model->find($id) : null;
        if ($id && !$detail) {
            http_response_code(404);
            $message = 'Không tìm thấy cuộc trò chuyện.';
        } elseif (!$id && $requests['items']) {
            $detail = $model->find((int) $requests['items'][0]['id_yeu_cau']);
        }
        $chatMessages = $detail && $detail['id_tai_khoan'] ? $model->messages((int) $detail['id_yeu_cau']) : ['items' => [], 'has_older' => false];
        require __DIR__ . '/../views/admin/yeu_cau_tu_van.php';
    }
}
