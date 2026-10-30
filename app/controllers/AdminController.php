<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tai_khoan.php';
require_once __DIR__ . '/../models/admin.php';
require_once __DIR__ . '/../services/AvatarUpload.php';

/** Đăng nhập, kiểm tra quyền và xử lý tài khoản của phiên quản trị. */
class AdminController
{
    private function input(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';
    }

    private function setAccountSession(array $account): void
    {
        $_SESSION['tai_khoan'] = [
            'id_tai_khoan' => (int) $account['id_tai_khoan'], 'ho_ten' => $account['ho_ten'],
            'email' => $account['email'], 'vai_tro' => $account['vai_tro'],
        ];
        $_SESSION['admin_credential_stamp'] = hash('sha256', $account['mat_khau']);
    }

    private function allowed(?array $account): bool
    {
        return $account !== null && (int) $account['dang_hoat_dong'] === 1
            && in_array($account['vai_tro'], ['admin', 'nhan_vien'], true);
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
                if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Vui lòng nhập email đúng định dạng.';
                if ($password === '') $errors['mat_khau'] = 'Vui lòng nhập mật khẩu.';
                if (!$errors) {
                    try {
                        $model = new TaiKhoanModel(getDatabaseConnection());
                        $account = $model->findByEmail($values['email']);
                        $hash = $account['mat_khau'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
                        $verified = strlen($password) <= 72 && strpos($password, "\0") === false && password_verify($password, $hash);
                        if ($this->allowed($account) && $verified) {
                            session_regenerate_id(true);
                            $this->setAccountSession($account);
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
                } else $message = 'Vui lòng kiểm tra các thông tin bên dưới.';
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
        $methods = in_array($section, ['profile', 'password'], true) ? ['GET', 'POST'] : ['GET'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $methods));
            return;
        }
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        $model = new TaiKhoanModel(getDatabaseConnection());
        $account = $model->findById((int) $_SESSION['tai_khoan']['id_tai_khoan']);
        $stamp = $account ? hash('sha256', $account['mat_khau']) : '';
        if (!$this->allowed($account) || (isset($_SESSION['admin_credential_stamp']) && !hash_equals($_SESSION['admin_credential_stamp'], $stamp))) {
            unset($_SESSION['tai_khoan'], $_SESSION['admin_credential_stamp']);
            http_response_code(403);
            require __DIR__ . '/../views/admin/forbidden.php';
            return;
        }
        // Tương thích phiên đã đăng nhập trước khi bổ sung chức năng đổi mật khẩu.
        $_SESSION['admin_credential_stamp'] = $stamp;
        $roleName = $account['vai_tro'] === 'admin' ? 'Quản trị viên' : 'Nhân viên';
        $csrfToken = $_SESSION['csrf_token'];
        $avatarUrl = AvatarUpload::url($account['hinh_anh'] ?? null);
        $adminSection = in_array($section, ['profile', 'password'], true) ? $section : 'dashboard';
        if ($adminSection === 'profile') {
            $this->profile($model, $account, $roleName, $csrfToken);
            return;
        }
        if ($adminSection === 'password') {
            $this->password($model, $account, $roleName, $csrfToken);
            return;
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $dashboard = (new AdminModel(getDatabaseConnection()))->getOverview($now);
        $todayLabel = $now->format('d/m/Y');
        require __DIR__ . '/../views/admin/index.php';
    }

    private function profile(TaiKhoanModel $model, array $account, string $roleName, string $csrfToken): void
    {
        $adminSection = 'profile';
        $avatarUrl = AvatarUpload::url($account['hinh_anh'] ?? null);
        $profileValues = ['ho_ten' => $account['ho_ten'], 'email' => $account['email'], 'so_dien_thoai' => $account['so_dien_thoai']];
        $profileErrors = [];
        $profileMessage = $_SESSION['admin_profile_message'] ?? '';
        $profileSuccess = $profileMessage !== '';
        $connection = getDatabaseConnection();
        unset($_SESSION['admin_profile_message']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $profileSuccess = false;
            $newAvatar = null;
            $profileValues = [
                'ho_ten' => trim($this->input('ho_ten')), 'email' => strtolower(trim($this->input('email'))),
                'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
            ];
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $profileMessage = 'Phiên gửi biểu mẫu không hợp lệ hoặc dữ liệu vượt giới hạn tải lên. Vui lòng tải lại trang và thử lại.';
            } else {
                $nameLength = mb_strlen($profileValues['ho_ten'], 'UTF-8');
                if ($nameLength < 2 || $nameLength > 120 || !mb_check_encoding($profileValues['ho_ten'], 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/u', $profileValues['ho_ten'])) {
                    $profileErrors['ho_ten'] = 'Họ và tên cần từ 2 đến 120 ký tự hợp lệ.';
                }
                if (strlen($profileValues['email']) > 254 || !filter_var($profileValues['email'], FILTER_VALIDATE_EMAIL)) $profileErrors['email'] = 'Vui lòng nhập email đúng định dạng.';
                if (!preg_match('/^\+?[0-9]{9,15}$/', $profileValues['so_dien_thoai'])) $profileErrors['so_dien_thoai'] = 'Số điện thoại cần có từ 9 đến 15 chữ số.';
                try {
                    if (!isset($profileErrors['email'])) {
                        $existing = $model->findByEmail($profileValues['email']);
                        if ($existing && (int) $existing['id_tai_khoan'] !== (int) $account['id_tai_khoan']) $profileErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                    }
                    if (!$profileErrors) {
                        $file = $_FILES['hinh_anh'] ?? [];
                        $newAvatar = AvatarUpload::save(is_array($file) ? $file : []);
                        $connection->beginTransaction();
                        $model->updateStaffProfile((int) $account['id_tai_khoan'], $profileValues['ho_ten'], $profileValues['email'], $profileValues['so_dien_thoai'], $newAvatar);
                        $updated = $model->findById((int) $account['id_tai_khoan']);
                        if (!$this->allowed($updated)) {
                            $connection->rollBack();
                            AvatarUpload::delete($newAvatar);
                            unset($_SESSION['tai_khoan']);
                            http_response_code(403);
                            require __DIR__ . '/../views/admin/forbidden.php';
                            return;
                        }
                        $connection->commit();
                        // Nếu có thay đổi mật khẩu ở phiên khác, không cập nhật dấu xác thực của phiên cũ.
                        $_SESSION['tai_khoan'] = [
                            'id_tai_khoan' => (int) $updated['id_tai_khoan'], 'ho_ten' => $updated['ho_ten'],
                            'email' => $updated['email'], 'vai_tro' => $updated['vai_tro'],
                        ];
                        if ($newAvatar !== null) AvatarUpload::delete($account['hinh_anh'] ?? null);
                        $_SESSION['admin_profile_message'] = 'Đã lưu thông tin cá nhân thành công.';
                        header('Location: index.php?action=profile', true, 303);
                        return;
                    }
                } catch (InvalidArgumentException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    AvatarUpload::delete($newAvatar);
                    $profileErrors['hinh_anh'] = $exception->getMessage();
                } catch (PDOException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    AvatarUpload::delete($newAvatar);
                    if (($exception->errorInfo[1] ?? 0) === 1062) $profileErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                    else {
                        error_log('OnTrip profile database error: ' . $exception->getMessage());
                        http_response_code(503);
                        $profileMessage = 'Chưa thể lưu thông tin. Vui lòng kiểm tra cập nhật database và thử lại sau.';
                    }
                } catch (RuntimeException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    AvatarUpload::delete($newAvatar);
                    error_log('OnTrip avatar error: ' . $exception->getMessage());
                    $profileErrors['hinh_anh'] = 'Chưa thể lưu ảnh đại diện. Vui lòng thử lại sau.';
                }
                if ($profileErrors) $profileMessage = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
            }
        }
        require __DIR__ . '/../views/admin/profile.php';
    }

    private function password(TaiKhoanModel $model, array $account, string $roleName, string $csrfToken): void
    {
        $adminSection = 'password';
        $avatarUrl = AvatarUpload::url($account['hinh_anh'] ?? null);
        $passwordErrors = [];
        $passwordMessage = $_SESSION['admin_password_message'] ?? '';
        $passwordSuccess = $passwordMessage !== '';
        unset($_SESSION['admin_password_message']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $passwordSuccess = false;
            $current = $this->input('mat_khau_hien_tai');
            $next = $this->input('mat_khau_moi');
            $confirm = $this->input('xac_nhan_mat_khau');
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $passwordMessage = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } else {
                $currentValid = $current !== '' && strlen($current) <= 72 && strpos($current, "\0") === false && password_verify($current, $account['mat_khau']);
                if (!$currentValid) $passwordErrors['mat_khau_hien_tai'] = 'Mật khẩu hiện tại không đúng.';
                if (!mb_check_encoding($next, 'UTF-8') || mb_strlen($next, 'UTF-8') < 8 || strlen($next) > 72 || strpos($next, "\0") !== false) $passwordErrors['mat_khau_moi'] = 'Mật khẩu mới cần ít nhất 8 ký tự và tối đa 72 byte.';
                elseif (password_verify($next, $account['mat_khau'])) $passwordErrors['mat_khau_moi'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
                if ($confirm === '' || $confirm !== $next) $passwordErrors['xac_nhan_mat_khau'] = 'Mật khẩu xác nhận chưa trùng khớp.';
                if (!$passwordErrors) {
                    try {
                        $newHash = password_hash($next, PASSWORD_DEFAULT);
                        if ($model->updateStaffPassword((int) $account['id_tai_khoan'], $account['mat_khau'], $newHash)) {
                            session_regenerate_id(true);
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            $_SESSION['admin_credential_stamp'] = hash('sha256', $newHash);
                            $_SESSION['admin_password_message'] = 'Đã đổi mật khẩu thành công. Lần đăng nhập tiếp theo hãy dùng mật khẩu mới.';
                            header('Location: index.php?action=password', true, 303);
                            return;
                        }
                        $passwordMessage = 'Tài khoản vừa có thay đổi. Vui lòng tải lại trang và thử lại.';
                    } catch (PDOException $exception) {
                        error_log('OnTrip password update error: ' . $exception->getMessage());
                        http_response_code(503);
                        $passwordMessage = 'Chưa thể đổi mật khẩu. Vui lòng thử lại sau.';
                    }
                } else $passwordMessage = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
            }
        }
        require __DIR__ . '/../views/admin/password.php';
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
