<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tai_khoan.php';
require_once __DIR__ . '/../models/admin.php';

/** Điểm vào khu vực quản trị, kiểm tra quyền trước khi xuất giao diện. */
class AdminController
{
    private function input(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';
    }

    public function login(): void
    {
        header('Cache-Control: no-store');
        if (isset($_SESSION['tai_khoan'])) {
            header('Location: index.php', true, 303);
            return;
        }
        $errors = [];
        $values = ['email' => ''];
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['email'] = strtolower(trim($this->input('email')));
            $password = $this->input('mat_khau');
            if (!hash_equals($_SESSION['csrf_token'], $this->input('csrf_token'))) {
                http_response_code(403);
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.';
            } else {
                if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Vui lòng nhập email đúng định dạng.';
                }
                if ($password === '') $errors['mat_khau'] = 'Vui lòng nhập mật khẩu.';
                if (!$errors) {
                    try {
                        $model = new TaiKhoanModel(getDatabaseConnection());
                        $account = $model->findByEmail($values['email']);
                        $hash = $account['mat_khau'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                        $verified = strlen($password) <= 72 && strpos($password, "\0") === false && password_verify($password, $hash);
                        if ($account && $verified && (int) $account['dang_hoat_dong'] === 1
                            && in_array($account['vai_tro'], ['admin', 'nhan_vien'], true)) {
                            session_regenerate_id(true);
                            $_SESSION['tai_khoan'] = [
                                'id_tai_khoan' => (int) $account['id_tai_khoan'],
                                'ho_ten' => $account['ho_ten'],
                                'email' => $account['email'],
                                'vai_tro' => $account['vai_tro'],
                            ];
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            header('Location: index.php', true, 303);
                            return;
                        }
                        $message = 'Thông tin đăng nhập không đúng hoặc tài khoản không có quyền quản trị.';
                    } catch (PDOException $exception) {
                        error_log('OnTrip admin login error: ' . $exception->getMessage());
                        http_response_code(503);
                        $message = 'Chưa thể kết nối dịch vụ tài khoản. Vui lòng thử lại sau.';
                    }
                } else {
                    $message = 'Vui lòng kiểm tra các thông tin bên dưới.';
                }
            }
        }
        $isRegister = false;
        $isAdminLogin = true;
        $authAssetPrefix = '../public/';
        $authHomeUrl = '../public/index.php';
        $authFormAction = 'index.php?action=login';
        $csrfToken = $_SESSION['csrf_token'];
        require __DIR__ . '/../views/auth/layout.php';
    }

    public function index(string $section = 'dashboard'): void
    {
        header('Cache-Control: no-store');
        // Hai form tài khoản hiện chỉ xem giao diện, chưa xử lý cập nhật.
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            return;
        }
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        // Đọc lại để quyền bị đổi hoặc tài khoản bị khóa có hiệu lực ngay.
        $model = new TaiKhoanModel(getDatabaseConnection());
        $account = $model->findByEmail($_SESSION['tai_khoan']['email']);
        $allowed = $account
            && (int) $account['id_tai_khoan'] === (int) $_SESSION['tai_khoan']['id_tai_khoan']
            && (int) $account['dang_hoat_dong'] === 1
            && in_array($account['vai_tro'], ['admin', 'nhan_vien'], true);
        if (!$allowed) {
            unset($_SESSION['tai_khoan']);
            http_response_code(403);
            require __DIR__ . '/../views/admin/forbidden.php';
            return;
        }
        $roleName = $account['vai_tro'] === 'admin' ? 'Quản trị viên' : 'Nhân viên';
        $csrfToken = $_SESSION['csrf_token'];
        $adminSection = in_array($section, ['profile', 'password'], true) ? $section : 'dashboard';
        if ($adminSection === 'profile' || $adminSection === 'password') {
            require __DIR__ . '/../views/admin/' . $adminSection . '.php';
            return;
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $dashboard = (new AdminModel(getDatabaseConnection()))->getOverview($now);
        $todayLabel = $now->format('d/m/Y');
        require __DIR__ . '/../views/admin/index.php';
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }
        if (!hash_equals($_SESSION['csrf_token'], $this->input('csrf_token'))) {
            http_response_code(403);
            require __DIR__ . '/../views/admin/forbidden.php';
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        session_destroy();
        header('Location: index.php?action=login', true, 303);
    }
}
