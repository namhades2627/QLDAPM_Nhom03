<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/yeu_cau_tu_van.php';
require_once __DIR__ . '/../models/thong_bao.php';

class TuVanController
{
    private static function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /** Chỉ gọi sau khi kiểm tra chủ hội thoại hoặc phiên quản trị. */
    public static function handleThread(YeuCauTuVanModel $model, int $id, array $account, string $csrfToken, bool $staff): bool
    {
        $workflow = static function () use ($model, $id, $staff): ?array {
            if (!$staff) return null;
            $row = $model->find($id);
            return $row ? array_intersect_key($row, array_flip(['trang_thai', 'id_nguoi_phu_trach', 'ghi_chu', 'phien_ban'])) : null;
        };
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['messages'] ?? '') === '1') {
            $after = filter_var($_GET['after'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
            $before = filter_var($_GET['before'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) ?: 0;
            $messages = $model->messages($id, $after, $before);
            self::json(['success' => true, 'items' => $messages['items'], 'has_older' => $messages['has_older'],
                'csrf_token' => $csrfToken, 'workflow' => $workflow(), 'unread_count' => $staff ? null : (new ThongBaoModel(getDatabaseConnection()))->unreadCount((int) $account['id_tai_khoan'])]);
            return true;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return false;
        $json = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
        $input = static fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        $error = '';
        if (!hash_equals($csrfToken, $input('csrf_token'))) { http_response_code(403); $error = 'Phiên gửi đã thay đổi. Vui lòng tải lại cuộc trò chuyện.'; }
        elseif ($input('operation') !== 'reply') { http_response_code(400); $error = 'Thao tác không hợp lệ.'; }
        else {
            try {
                $result = $model->sendMessage($id, (int) $account['id_tai_khoan'], $input('noi_dung'), $input('message_token'), $staff, $staff ? ($_SESSION['admin_credential_stamp'] ?? '') : '');
                if ($json) {
                    $messages = $model->messages($id, $result['id'] - 1);
                    self::json(['success' => true, 'items' => $messages['items'], 'message_token' => bin2hex(random_bytes(32)), 'workflow' => $workflow()]);
                } else header('Location: index.php?' . ($staff ? 'action=yeu_cau_tu_van' : 'page=tu_van') . '&view=' . $id . '#conversation', true, 303);
                return true;
            } catch (OverflowException $exception) {
                http_response_code(429); header('Retry-After: 60'); $error = $exception->getMessage();
            } catch (DomainException $exception) {
                http_response_code(409); $error = $exception->getMessage();
            } catch (PDOException $exception) {
                error_log('OnTrip chat error: ' . $exception->getMessage());
                http_response_code(503); $error = 'Chưa nhận được xác nhận gửi tin. Bạn có thể thử lại, nội dung sẽ không bị gửi trùng.';
            }
        }
        if ($json) self::json(['success' => false, 'message' => $error]);
        else {
            // Giữ nội dung qua PRG, không gửi lại POST khi tải trang.
            $_SESSION['chat_error_' . ($staff ? 'staff' : 'customer') . '_' . $id] = ['message' => $error, 'content' => $input('noi_dung'), 'token' => $input('message_token')];
            header('Location: index.php?' . ($staff ? 'action=yeu_cau_tu_van' : 'page=tu_van') . '&view=' . $id . '#conversation', true, 303);
        }
        return true;
    }

    public function index(): void
    {
        header('Cache-Control: no-store');
        $model = new YeuCauTuVanModel(getDatabaseConnection());
        $id = filter_var($_GET['view'] ?? $_POST['id_yeu_cau'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
        $json = ($_GET['messages'] ?? '') === '1' || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
        $accountId = (int) ($_SESSION['tai_khoan']['id_tai_khoan'] ?? 0);
        if (!$accountId) {
            if ($json) { http_response_code(401); self::json(['success' => false, 'message' => 'Vui lòng đăng nhập lại để trò chuyện.']); }
            else header('Location: index.php?page=login&redirect=' . rawurlencode('index.php?page=tu_van' . ($id ? '&view=' . $id : '')), true, 303);
            return;
        }
        $account = $model->customerAccount($accountId);
        if (!$account) { http_response_code(403); if ($json) self::json(['success' => false, 'message' => 'Tài khoản không có quyền truy cập.']); else echo 'Tài khoản không có quyền truy cập.'; return; }
        $chatThread = $id ? $model->customerRequest($id, $accountId) : null;
        if (($id && !$chatThread) || ($_SERVER['REQUEST_METHOD'] === 'POST' && !$id)) {
            http_response_code(404); if ($json) self::json(['success' => false, 'message' => 'Không tìm thấy cuộc trò chuyện.']); else echo 'Không tìm thấy cuộc trò chuyện.'; return;
        }
        $csrfToken = $_SESSION['csrf_token'];
        if ($chatThread && self::handleThread($model, $id, $account, $csrfToken, false)) return;
        $number = filter_var($_GET['p'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $threads = $model->customerList($accountId, $number);
        $chatMessages = $chatThread ? $model->messages($id) : ['items' => [], 'has_older' => false];
        $chatRole = 'customer';
        $chatEmbedded = ($_GET['embed'] ?? '') === '1';
        $chatUrl = 'index.php?page=tu_van&view=' . $id . ($chatEmbedded ? '&embed=1' : '');
        $page = 'tu_van';
        require __DIR__ . '/../views/tu_van.php';
    }
}
