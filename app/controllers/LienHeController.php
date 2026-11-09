<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/lien_he.php';

/**
 * Điều phối hiển thị trang liên hệ và xử lý gửi tin nhắn tư vấn từ khách hàng.
 */
class LienHeController
{
    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    public function index(): void
    {
        $connection = getDatabaseConnection();
        $model = new LienHeModel($connection);
        $guides = $model->getFeaturedGuides();

        $sessionAccount = $_SESSION['tai_khoan'] ?? null;
        $isLoggedIn = is_array($sessionAccount);

        $values = [
            'ho_ten' => $isLoggedIn ? ($sessionAccount['ho_ten'] ?? '') : '',
            'email' => $isLoggedIn ? ($sessionAccount['email'] ?? '') : '',
            'so_dien_thoai' => '',
            'chu_de' => 'tu_van_tour',
            'noi_dung' => '',
        ];
        $errors = [];
        $successMessage = '';

        // Đọc thông báo gửi thành công từ flash session
        if (!empty($_SESSION['lien_he_thanh_cong'])) {
            $successMessage = (string) $_SESSION['lien_he_thanh_cong'];
            unset($_SESSION['lien_he_thanh_cong']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $this->input('csrf_token');
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
                http_response_code(403);
                $errors['csrf'] = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
            } else {
                $values['ho_ten'] = mb_substr($this->input('ho_ten'), 0, 100, 'UTF-8');
                $values['email'] = mb_substr($this->input('email'), 0, 150, 'UTF-8');
                $values['so_dien_thoai'] = mb_substr($this->input('so_dien_thoai'), 0, 20, 'UTF-8');
                $values['chu_de'] = $this->input('chu_de');
                $values['noi_dung'] = mb_substr($this->input('noi_dung'), 0, 2000, 'UTF-8');

                $allowedTopics = [
                    'tu_van_tour' => 'Tư vấn chọn tour & lịch trình',
                    'dat_tour_doan' => 'Thiết kế tour đoàn / gia đình theo yêu cầu',
                    'thanh_toan_ve' => 'Hỗ trợ đặt chỗ & thanh toán vé',
                    'gop_y_dich_vu' => 'Đóng góp ý kiến & hợp tác dịch vụ',
                ];

                if ($values['ho_ten'] === '' || mb_strlen($values['ho_ten'], 'UTF-8') < 2) {
                    $errors['ho_ten'] = 'Vui lòng nhập họ và tên (tối thiểu 2 ký tự).';
                }

                if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Vui lòng nhập địa chỉ email hợp lệ.';
                }

                if ($values['so_dien_thoai'] === '' || !preg_match('~^(0[2-9]\d{8,9})$~', $values['so_dien_thoai'])) {
                    $errors['so_dien_thoai'] = 'Vui lòng nhập số điện thoại Việt Nam hợp lệ (10 số, ví dụ 0901234567).';
                }

                if (!array_key_exists($values['chu_de'], $allowedTopics)) {
                    $values['chu_de'] = 'tu_van_tour';
                }

                if ($values['noi_dung'] === '' || mb_strlen($values['noi_dung'], 'UTF-8') < 10) {
                    $errors['noi_dung'] = 'Vui lòng nhập nội dung cần tư vấn hoặc nhắn nhủ (tối thiểu 10 ký tự).';
                }

                if (empty($errors)) {
                    // Ghi nhận thành công vào flash session và chuyển hướng PRG (Post-Redirect-Get)
                    $_SESSION['lien_he_thanh_cong'] = 'Cảm ơn ' . htmlspecialchars($values['ho_ten'], ENT_QUOTES, 'UTF-8') . '! OnTrip đã ghi nhận yêu cầu. Đội ngũ hướng dẫn viên/chuyên viên tư vấn sẽ liên hệ lại với bạn qua số ' . htmlspecialchars($values['so_dien_thoai'], ENT_QUOTES, 'UTF-8') . ' hoặc email trong vòng 15 phút.';
                    header('Location: index.php?page=lien_he', true, 303);
                    return;
                }
            }
        }

        $page = 'lien_he';
        require __DIR__ . '/../views/lien_he.php';
    }
}
