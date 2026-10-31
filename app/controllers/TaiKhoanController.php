<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tai_khoan.php';

/** Kiểm tra form, xác thực và quản lý phiên tài khoản. */
class TaiKhoanController
{
    private function input(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';
    }

    public function form(string $page): void
    {
        header('Cache-Control: no-store');
        if (isset($_SESSION['tai_khoan'])) {
            header('Location: index.php', true, 303);
            return;
        }
        $isRegister = $page === 'register';
        $errors = [];
        $values = ['ho_ten' => '', 'email' => '', 'so_dien_thoai' => ''];
        $message = $_SESSION['auth_message'] ?? '';
        unset($_SESSION['auth_message']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values = [
                'ho_ten' => trim($this->input('ho_ten')),
                'email' => strtolower(trim($this->input('email'))),
                'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
            ];
            $password = $this->input('mat_khau');
            if (!hash_equals($_SESSION['csrf_token'], $this->input('csrf_token'))) {
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.';
                http_response_code(403);
            } else {
                if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Vui lòng nhập email đúng định dạng.';
                }
                if ($password === '') $errors['mat_khau'] = 'Vui lòng nhập mật khẩu.';
                if ($isRegister) {
                    $length = mb_strlen($values['ho_ten'], 'UTF-8');
                    if ($length < 2 || $length > 120) $errors['ho_ten'] = 'Họ tên cần từ 2 đến 120 ký tự.';
                    if (!preg_match('/^\+?[0-9]{9,15}$/', $values['so_dien_thoai'])) {
                        $errors['so_dien_thoai'] = 'Số điện thoại cần có từ 9 đến 15 chữ số.';
                    }
                    if (mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
                        $errors['mat_khau'] = 'Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte.';
                    }
                    if ($password !== $this->input('xac_nhan_mat_khau')) {
                        $errors['xac_nhan_mat_khau'] = 'Mật khẩu xác nhận chưa trùng khớp.';
                    }
                }
                if (!$errors) {
                    try {
                        $model = new TaiKhoanModel(getDatabaseConnection());
                        $account = $model->findByEmail($values['email']);
                        if ($isRegister) {
                            if ($account) {
                                $errors['email'] = 'Email này đã được đăng ký.';
                            } else {
                                $model->createCustomer($values['ho_ten'], $values['email'], $values['so_dien_thoai'], password_hash($password, PASSWORD_DEFAULT));
                                $_SESSION['auth_message'] = 'Đăng ký thành công. Bạn có thể đăng nhập ngay.';
                                header('Location: index.php?page=login', true, 303);
                                return;
                            }
                        } else {
                            // Hash giả giúp đường đi email không tồn tại vẫn kiểm tra mật khẩu.
                            $hash = $account['mat_khau'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                            $verified = strlen($password) <= 72 && strpos($password, "\0") === false && password_verify($password, $hash);
                            if (!$account || !$verified || (int) $account['dang_hoat_dong'] !== 1) {
                                $message = 'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.';
                            } else {
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
                        }
                    } catch (PDOException $exception) {
                        if ($isRegister && ($exception->errorInfo[1] ?? 0) === 1062) {
                            $errors['email'] = 'Email này đã được đăng ký.';
                        } else {
                            error_log('OnTrip auth database error: ' . $exception->getMessage());
                            http_response_code(503);
                            $message = 'Chưa thể kết nối dịch vụ tài khoản. Vui lòng thử lại sau.';
                        }
                    }
                }
                if ($errors) $message = 'Vui lòng kiểm tra các thông tin bên dưới.';
            }
        }
        $csrfToken = $_SESSION['csrf_token'];
        require __DIR__ . '/../views/tai_khoan/layout.php';
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
            echo 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
            return;
        }
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        session_destroy();
        header('Location: index.php', true, 303);
    }
}
