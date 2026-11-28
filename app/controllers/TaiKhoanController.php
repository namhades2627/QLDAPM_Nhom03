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

    /**
     * Đích quay lại sau khi đăng nhập (ví dụ trang đặt tour vừa bấm).
     * Chỉ chấp nhận đường dẫn tương đối trong website để tránh open redirect.
     */
    private function redirectTarget(): string
    {
        $raw = $_GET['redirect'] ?? null;
        if (!is_string($raw)) {
            return 'index.php';
        }
        $target = trim($raw);
        if ($target === '' || strlen($target) > 2048
            || strpos($target, '//') !== false
            || strpos($target, '..') !== false
            || strpos($target, '\\') !== false
            || preg_match('/[\x00-\x1F\x7F]/', $target)) {
            return 'index.php';
        }
        return preg_match('#^[A-Za-z0-9_./-]+\.php(\?|\#|$)#', $target) ? $target : 'index.php';
    }

    public function form(string $page): void
    {
        header('Cache-Control: no-store');
        $isRegister = $page === 'register';
        $redirect = $this->redirectTarget();
        $redirectQuery = $redirect === 'index.php' ? '' : '&redirect=' . rawurlencode($redirect);
        $authRedirect = $redirect === 'index.php' ? '' : $redirect;
        $authFormAction = 'index.php?page=' . ($isRegister ? 'register' : 'login') . $redirectQuery;
        if (isset($_SESSION['tai_khoan'])) {
            header('Location: ' . $redirect, true, 303);
            return;
        }
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
                                header('Location: index.php?page=login' . $redirectQuery, true, 303);
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
                                // Quay lại đúng trang khách đang xem trước khi bấm đăng nhập (ví dụ trang đặt tour).
                                header('Location: ' . $redirect, true, 303);
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

    public function profile(): void
    {
        header('Cache-Control: no-store');
        if (!isset($_SESSION['tai_khoan']['id_tai_khoan'])) {
            header('Location: index.php?page=login&redirect=' . rawurlencode('index.php?page=profile'), true, 303);
            return;
        }

        $connection = getDatabaseConnection();
        $model = new TaiKhoanModel($connection);
        $account = $model->findById((int) $_SESSION['tai_khoan']['id_tai_khoan']);
        if (!$account || (int) $account['dang_hoat_dong'] !== 1) {
            unset($_SESSION['tai_khoan']);
            $_SESSION['auth_message'] = 'Phiên đăng nhập không hợp lệ hoặc tài khoản đã bị khóa.';
            header('Location: index.php?page=login', true, 303);
            return;
        }

        $profileValues = [
            'ho_ten' => (string) ($account['ho_ten'] ?? ''),
            'email' => (string) ($account['email'] ?? ''),
            'so_dien_thoai' => (string) ($account['so_dien_thoai'] ?? ''),
        ];
        $profileErrors = [];
        $passwordErrors = [];
        $profileMessage = '';
        $profileSuccess = false;
        $passwordMessage = '';
        $passwordSuccess = false;

        $flash = $_SESSION['customer_profile_flash'] ?? null;
        unset($_SESSION['customer_profile_flash']);
        if (is_array($flash)) {
            if (($flash['type'] ?? '') === 'profile') {
                $profileMessage = (string) ($flash['message'] ?? '');
                $profileSuccess = $profileMessage !== '';
            } elseif (($flash['type'] ?? '') === 'password') {
                $passwordMessage = (string) ($flash['message'] ?? '');
                $passwordSuccess = $passwordMessage !== '';
            }
        }

        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formType = $this->input('form_type');
            if ($csrfToken === '' || !hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $csrfError = 'Phiên gửi biểu mẫu không hợp lệ hoặc tệp tải lên vượt giới hạn cho phép. Vui lòng tải lại trang và thử lại.';
                if ($formType === 'password') {
                    $passwordMessage = $csrfError;
                } else {
                    $profileMessage = $csrfError;
                }
            } elseif ($formType === 'profile') {
                $newAvatar = null;
                $profileValues = [
                    'ho_ten' => trim($this->input('ho_ten')),
                    'email' => strtolower(trim($this->input('email'))),
                    'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
                ];
                $nameLength = mb_strlen($profileValues['ho_ten'], 'UTF-8');
                if ($nameLength < 2 || $nameLength > 120 || !mb_check_encoding($profileValues['ho_ten'], 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/u', $profileValues['ho_ten'])) {
                    $profileErrors['ho_ten'] = 'Họ và tên cần từ 2 đến 120 ký tự hợp lệ.';
                }
                if (strlen($profileValues['email']) > 254 || !filter_var($profileValues['email'], FILTER_VALIDATE_EMAIL)) {
                    $profileErrors['email'] = 'Vui lòng nhập email đúng định dạng.';
                }
                if (!preg_match('/^\+?[0-9]{9,15}$/', $profileValues['so_dien_thoai'])) {
                    $profileErrors['so_dien_thoai'] = 'Số điện thoại cần có từ 9 đến 15 chữ số.';
                }

                try {
                    if (!isset($profileErrors['email'])) {
                        $existing = $model->findByEmail($profileValues['email']);
                        if ($existing && (int) $existing['id_tai_khoan'] !== (int) $account['id_tai_khoan']) {
                            $profileErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                        }
                    }
                    if (!$profileErrors) {
                        $file = $_FILES['hinh_anh'] ?? [];
                        $newAvatar = self::saveAvatar(is_array($file) ? $file : []);
                        $connection->beginTransaction();
                        $model->updateAccountProfile(
                            (int) $account['id_tai_khoan'],
                            $profileValues['ho_ten'],
                            $profileValues['email'],
                            $profileValues['so_dien_thoai'],
                            $newAvatar
                        );
                        $updated = $model->findById((int) $account['id_tai_khoan']);
                        if (!$updated || (int) $updated['dang_hoat_dong'] !== 1) {
                            $connection->rollBack();
                            self::deleteAvatar($newAvatar);
                            unset($_SESSION['tai_khoan']);
                            $_SESSION['auth_message'] = 'Phiên đăng nhập không hợp lệ hoặc tài khoản đã bị khóa.';
                            header('Location: index.php?page=login', true, 303);
                            return;
                        }
                        $connection->commit();
                        $_SESSION['tai_khoan'] = [
                            'id_tai_khoan' => (int) $updated['id_tai_khoan'],
                            'ho_ten' => $updated['ho_ten'],
                            'email' => $updated['email'],
                            'vai_tro' => $updated['vai_tro'],
                        ];
                        if ($newAvatar !== null && ($account['hinh_anh'] ?? null) !== $newAvatar) {
                            self::deleteAvatar($account['hinh_anh'] ?? null);
                        }
                        $_SESSION['customer_profile_flash'] = [
                            'type' => 'profile',
                            'message' => 'Đã lưu thông tin hồ sơ cá nhân thành công.',
                        ];
                        header('Location: index.php?page=profile', true, 303);
                        return;
                    }
                } catch (InvalidArgumentException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
                    $profileErrors['hinh_anh'] = $exception->getMessage();
                } catch (PDOException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
                    if (($exception->errorInfo[1] ?? 0) === 1062) {
                        $profileErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                    } else {
                        error_log('OnTrip customer profile database error: ' . $exception->getMessage());
                        http_response_code(503);
                        $profileMessage = 'Chưa thể lưu thông tin. Vui lòng thử lại sau.';
                    }
                } catch (RuntimeException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
                    error_log('OnTrip customer avatar error: ' . $exception->getMessage());
                    $profileErrors['hinh_anh'] = 'Chưa thể lưu ảnh đại diện. Vui lòng thử lại sau.';
                }
                if ($profileErrors && $profileMessage === '') {
                    $profileMessage = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
                }
            } elseif ($formType === 'password') {
                $current = $this->input('mat_khau_hien_tai');
                $next = $this->input('mat_khau_moi');
                $confirm = $this->input('xac_nhan_mat_khau');
                $currentValid = $current !== '' && strlen($current) <= 72 && strpos($current, "\0") === false
                    && password_verify($current, (string) $account['mat_khau']);
                if (!$currentValid) {
                    $passwordErrors['mat_khau_hien_tai'] = 'Mật khẩu hiện tại không đúng.';
                }
                if (!mb_check_encoding($next, 'UTF-8') || mb_strlen($next, 'UTF-8') < 8 || strlen($next) > 72 || strpos($next, "\0") !== false) {
                    $passwordErrors['mat_khau_moi'] = 'Mật khẩu mới cần ít nhất 8 ký tự và tối đa 72 byte.';
                } elseif (password_verify($next, (string) $account['mat_khau'])) {
                    $passwordErrors['mat_khau_moi'] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
                }
                if ($confirm === '' || $confirm !== $next) {
                    $passwordErrors['xac_nhan_mat_khau'] = 'Mật khẩu xác nhận chưa trùng khớp.';
                }
                if (!$passwordErrors) {
                    try {
                        $newHash = password_hash($next, PASSWORD_DEFAULT);
                        if ($model->updateAccountPassword((int) $account['id_tai_khoan'], (string) $account['mat_khau'], $newHash)) {
                            session_regenerate_id(true);
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            $_SESSION['customer_profile_flash'] = [
                                'type' => 'password',
                                'message' => 'Đã đổi mật khẩu thành công. Lần đăng nhập tiếp theo hãy sử dụng mật khẩu mới.',
                            ];
                            header('Location: index.php?page=profile#doi-mat-khau', true, 303);
                            return;
                        }
                        $passwordMessage = 'Tài khoản vừa có thay đổi. Vui lòng tải lại trang và thử lại.';
                    } catch (PDOException $exception) {
                        error_log('OnTrip customer password update error: ' . $exception->getMessage());
                        http_response_code(503);
                        $passwordMessage = 'Chưa thể đổi mật khẩu. Vui lòng thử lại sau.';
                    }
                } else {
                    $passwordMessage = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
                }
            } else {
                http_response_code(400);
                $profileMessage = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
            }
        }

        $avatarUrl = self::avatarUrl($account['hinh_anh'] ?? null);
        $csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
        require __DIR__ . '/../views/tai_khoan/profile.php';
    }

    public static function avatarUrl(?string $path): string
    {
        if (!is_string($path) || $path === '') {
            return '';
        }
        $normalized = str_replace('\\', '/', trim($path));
        if (preg_match('~^assets/images/users/[A-Za-z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$~D', $normalized) !== 1) {
            return '';
        }
        return is_file(__DIR__ . '/../../public/' . $normalized) ? $normalized : '';
    }

    private static function isManagedAvatar(?string $path): bool
    {
        return $path !== null && preg_match('~^assets/images/users/[a-f0-9]{32}\.(jpg|png|gif|webp)$~D', $path) === 1;
    }

    private static function deleteAvatar(?string $path): void
    {
        if (self::isManagedAvatar($path)) {
            $file = __DIR__ . '/../../public/' . $path;
            if (is_file($file) && !unlink($file)) {
                error_log('OnTrip: cannot remove old customer avatar.');
            }
        }
    }

    /** Kiểm tra, chuẩn hóa và lưu ảnh đại diện khách hàng; trả về null nếu không chọn ảnh mới. */
    private static function saveAvatar(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Không tải được ảnh. Ảnh tối đa 5 MB; vui lòng chọn lại.');
        }
        $temp = $file['tmp_name'] ?? '';
        if (!is_string($temp) || !is_uploaded_file($temp) || filesize($temp) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Ảnh tải lên không hợp lệ hoặc vượt quá 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $size = @getimagesize($temp);
        if (!isset($types[$mime]) || !$size || ($size['mime'] ?? '') !== $mime) {
            throw new InvalidArgumentException('Vui lòng chọn ảnh JPG, PNG, GIF hoặc WEBP hợp lệ.');
        }
        if ($size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 12000000) {
            throw new InvalidArgumentException('Ảnh quá lớn. Vui lòng chọn ảnh tối đa 4096 × 4096 và 12 triệu điểm ảnh.');
        }
        $image = extension_loaded('gd') ? @imagecreatefromstring(file_get_contents($temp)) : null;
        if ($image === false) {
            throw new InvalidArgumentException('Không đọc được ảnh này. Vui lòng chọn ảnh khác.');
        }
        $directory = __DIR__ . '/../../public/assets/images/users';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Không tạo được thư mục ảnh đại diện.');
        }
        $path = 'assets/images/users/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
        $target = __DIR__ . '/../../public/' . $path;
        try {
            if ($image === null) {
                $saved = move_uploaded_file($temp, $target);
            } elseif ($mime === 'image/jpeg') {
                $saved = imagejpeg($image, $target, 85);
            } elseif ($mime === 'image/png') {
                $saved = imagepng($image, $target, 6);
            } elseif ($mime === 'image/gif') {
                $saved = imagegif($image, $target);
            } else {
                $saved = imagewebp($image, $target, 85);
            }
            if (!$saved) {
                throw new RuntimeException('Không ghi được ảnh đại diện.');
            }
        } catch (Throwable $exception) {
            self::deleteAvatar($path);
            throw $exception;
        } finally {
            if ($image !== null) {
                imagedestroy($image);
            }
        }
        return $path;
    }
}
