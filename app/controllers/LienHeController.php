<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/lien_he.php';
require_once __DIR__ . '/../models/yeu_cau_tu_van.php';

class LienHeController
{
    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    public function index(): void
    {
        header('Cache-Control: no-store');
        $wantsJson = $_SERVER['REQUEST_METHOD'] === 'POST'
            && str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
        $connection = getDatabaseConnection();
        $chatModel = new YeuCauTuVanModel($connection);
        $account = $chatModel->customerAccount((int) ($_SESSION['tai_khoan']['id_tai_khoan'] ?? 0)) ?? [];
        $canChat = $account !== [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$canChat) {
            $loginUrl = 'index.php?page=login&redirect=' . rawurlencode('index.php?page=lien_he#form-section-title');
            if ($wantsJson) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập tài khoản khách hàng để trò chuyện.', 'login_url' => $loginUrl], JSON_UNESCAPED_UNICODE);
            } else header('Location: ' . $loginUrl, true, 303);
            return;
        }
        $values = ['ho_ten' => $account['ho_ten'] ?? '', 'email' => $account['email'] ?? '',
            'so_dien_thoai' => $account['so_dien_thoai'] ?? '', 'chu_de' => 'tu_van_tour', 'noi_dung' => ''];
        $errors = [];
        $successMessage = (string) ($_SESSION['lien_he_thanh_cong'] ?? '');
        unset($_SESSION['lien_he_thanh_cong']);

        // Giữ tối đa 5 biểu mẫu trong một giờ, hỗ trợ nhiều tab và gửi lại sau lỗi mạng.
        $tokens = $_SESSION['tu_van_tokens'] ?? [];
        $tokens = array_filter($tokens, static fn($issued) => is_int($issued) && $issued >= time() - 3600);
        $formToken = $this->input('ma_gui');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['chu_de', 'noi_dung'] as $key) $values[$key] = $this->input($key);
            $values['email'] = mb_strtolower($values['email'], 'UTF-8');
            $values['so_dien_thoai'] = preg_replace('/[\s().-]/', '', $values['so_dien_thoai']);
            if (!hash_equals($_SESSION['csrf_token'], $this->input('csrf_token'))) {
                http_response_code(403);
                $errors['form'] = 'Phiên gửi không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } elseif (!isset($tokens[$formToken])) {
                http_response_code(409);
                $errors['form'] = 'Biểu mẫu đã hết hạn. Vui lòng tải lại trang trước khi gửi.';
            } elseif ($this->input('website') !== '' || time() - $tokens[$formToken] < 2) {
                http_response_code(429);
                $errors['form'] = 'Vui lòng chờ một chút trước khi gửi yêu cầu.';
            } else {
                if (!isset(YeuCauTuVanModel::TOPICS[$values['chu_de']])) $errors['form'] = 'Vui lòng chọn một chủ đề tư vấn hợp lệ.';
                $length = mb_strlen($values['noi_dung'], 'UTF-8');
                if ($length < 1 || $length > 2000) $errors['noi_dung'] = 'Tin nhắn cần từ 1 đến 2.000 ký tự.';
                if (!$errors) {
                    try {
                        $result = (new YeuCauTuVanModel($connection))->create($values, $formToken,
                            (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), isset($account['id_tai_khoan']) ? (int) $account['id_tai_khoan'] : null);
                        $message = $result['duplicate'] ? 'Tin nhắn đã được ghi nhận. Mở cuộc trò chuyện để tiếp tục trao đổi với OnTrip.' : 'Đã gửi tin nhắn. OnTrip sẽ phản hồi trong cuộc trò chuyện của bạn.';
                        $conversationUrl = 'index.php?page=tu_van&view=' . $result['id'];
                        if ($wantsJson) {
                            $nextToken = bin2hex(random_bytes(32));
                            $tokens[$nextToken] = time();
                            $_SESSION['tu_van_tokens'] = array_slice($tokens, -5, null, true);
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => true, 'message' => $message, 'conversation_url' => $conversationUrl,
                                'form_token' => $nextToken, 'csrf_token' => $_SESSION['csrf_token']], JSON_UNESCAPED_UNICODE);
                            return;
                        }
                        $_SESSION['lien_he_thanh_cong'] = $message;
                        $_SESSION['tu_van_tokens'] = $tokens;
                        header('Location: ' . $conversationUrl, true, 303);
                        return;
                    } catch (OverflowException $exception) {
                        http_response_code(429);
                        header('Retry-After: 900');
                        $errors['form'] = $exception->getMessage();
                    } catch (DomainException $exception) {
                        http_response_code(409);
                        $errors['form'] = $exception->getMessage();
                    } catch (PDOException $exception) {
                        error_log('OnTrip consultation save error: ' . $exception->getMessage());
                        http_response_code(503);
                        $errors['form'] = 'Chưa thể lưu yêu cầu lúc này. Vui lòng thử lại hoặc liên hệ hotline; yêu cầu chưa được xác nhận thành công.';
                    }
                } else http_response_code(422);
            }
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($tokens[$formToken])) {
            $formToken = bin2hex(random_bytes(32));
            $tokens[$formToken] = time();
        }
        $_SESSION['tu_van_tokens'] = array_slice($tokens, -5, null, true);
        if ($wantsJson) {
            if (http_response_code() < 400) http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $errors['form'] ?? 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.',
                'errors' => $errors, 'form_token' => $formToken, 'csrf_token' => $_SESSION['csrf_token']], JSON_UNESCAPED_UNICODE);
            return;
        }
        $guides = (new LienHeModel($connection))->getFeaturedGuides();
        $page = 'lien_he';
        require __DIR__ . '/../views/lien_he.php';
    }
}
