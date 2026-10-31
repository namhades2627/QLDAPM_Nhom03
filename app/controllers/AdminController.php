<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tai_khoan.php';
require_once __DIR__ . '/../models/admin.php';


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
        require __DIR__ . '/../views/tai_khoan/layout.php';
    }

    public function index(string $section = 'dashboard'): void
    {
        header('Cache-Control: no-store');
        $methods = in_array($section, ['profile', 'password', 'accounts', 'customers'], true) ? ['GET', 'POST'] : ['GET'];
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
        $avatarUrl = self::avatarUrl($account['hinh_anh'] ?? null);
        $adminSection = in_array($section, ['profile', 'password', 'accounts', 'customers'], true) ? $section : 'dashboard';
        if (in_array($adminSection, ['accounts', 'customers'], true)) {
            // Kiểm tra phía máy chủ, kể cả khi gửi trực tiếp URL hoặc POST.
            if ($account['vai_tro'] !== 'admin') {
                http_response_code(403);
                $errorMessage = $adminSection === 'customers' ? 'Chỉ quản trị viên được quản lý khách hàng.' : 'Chỉ quản trị viên được quản lý tài khoản.';
                require __DIR__ . '/../views/admin/forbidden.php';
                return;
            }
            $this->accounts($model, $account, $roleName, $csrfToken, $adminSection);
            return;
        }
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

    private function accounts(TaiKhoanModel $model, array $account, string $roleName, string $csrfToken, string $adminSection): void
    {
        $isCustomerManagement = $adminSection === 'customers';
        $accountGroup = $isCustomerManagement ? 'customers' : 'staff';
        $avatarUrl = self::avatarUrl($account['hinh_anh'] ?? null);
        $accountRoles = $isCustomerManagement ? ['khach_hang' => 'Khách hàng'] : ['admin' => 'Quản trị viên', 'nhan_vien' => 'Nhân viên'];
        $query = static function (string $key): string {
            return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
        };
        $accountFilters = [
            'q' => mb_substr($query('q'), 0, 120, 'UTF-8'),
            'role' => isset($accountRoles[$query('role')]) ? $query('role') : '',
            'status' => in_array($query('status'), ['0', '1'], true) ? $query('status') : '',
        ];
        $pageNumber = filter_var($query('p'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        // Chỉ giữ các tham số danh sách đã được kiểm tra khi chuyển trang hoặc lưu form.
        $accountsUrl = static function (array $extra = []) use ($accountFilters, $pageNumber, $adminSection): string {
            $parameters = array_merge(['action' => $adminSection], $accountFilters, ['p' => $pageNumber], $extra);
            return 'index.php?' . http_build_query(array_filter($parameters, static function ($value): bool {
                return $value !== '' && $value !== null;
            }));
        };
        $messageKey = 'admin_' . $adminSection . '_message';
        $accountMessage = $_SESSION[$messageKey] ?? '';
        $accountSuccess = $accountMessage !== '';
        unset($_SESSION[$messageKey]);
        $accountErrors = [];
        $accountFormMode = !$isCustomerManagement && $query('mode') === 'create' ? 'create' : '';
        $editAccountId = 0;
        $deleteAccount = null;
        $accountValues = ['ho_ten' => '', 'email' => '', 'so_dien_thoai' => '', 'vai_tro' => $isCustomerManagement ? 'khach_hang' : 'nhan_vien', 'dang_hoat_dong' => '1'];
        $allowedOperations = $isCustomerManagement ? ['update', 'status', 'delete'] : ['create', 'update', 'status', 'delete'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accountSuccess = false;
            $operation = $this->input('operation');
            $targetId = filter_var($this->input('id_tai_khoan'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            if ($operation === 'delete' && $targetId > 0 && $targetId !== (int) $account['id_tai_khoan']) {
                $deleteAccount = $model->findManagedAccount($targetId, $accountGroup);
            }
            if ($operation === 'update' || ($operation === 'create' && !$isCustomerManagement)) {
                $accountFormMode = $operation === 'create' ? 'create' : 'edit';
                $editAccountId = $targetId;
                $accountValues = [
                    'ho_ten' => trim($this->input('ho_ten')),
                    'email' => strtolower(trim($this->input('email'))),
                    'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
                    'vai_tro' => $operation === 'create' ? 'nhan_vien' : $this->input('vai_tro'),
                    'dang_hoat_dong' => $operation === 'create' ? '1' : $this->input('dang_hoat_dong'),
                ];
            }
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $accountMessage = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } elseif (!in_array($operation, $allowedOperations, true) || ($operation !== 'create' && $targetId === 0)) {
                http_response_code(400);
                $accountMessage = 'Yêu cầu quản lý tài khoản không hợp lệ.';
            } else {
                try {
                    $managerId = (int) $account['id_tai_khoan'];
                    $stamp = $_SESSION['admin_credential_stamp'];
                    if ($operation === 'delete') {
                        if ($this->input('confirm_delete') !== '1') throw new DomainException('Vui lòng xác nhận xóa tài khoản trước khi tiếp tục.');
                        $deletedAvatar = $model->deleteManagedAccount($managerId, $stamp, $targetId, $accountGroup);
                        self::deleteAvatar($deletedAvatar);
                        $_SESSION[$messageKey] = $isCustomerManagement ? 'Đã xóa tài khoản khách hàng.' : 'Đã xóa tài khoản.';
                    } elseif ($operation === 'status') {
                        $status = $this->input('dang_hoat_dong');
                        if (!in_array($status, ['0', '1'], true)) throw new DomainException('Trạng thái tài khoản không hợp lệ.');
                        $model->updateManagedAccount($managerId, $stamp, $targetId, ['dang_hoat_dong' => (int) $status], $accountGroup);
                        $_SESSION[$messageKey] = $status === '1' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.';
                    } else {
                        $password = $operation === 'create' ? $this->input('mat_khau') : '';
                        $nameLength = mb_strlen($accountValues['ho_ten'], 'UTF-8');
                        if ($nameLength < 2 || $nameLength > 120 || !mb_check_encoding($accountValues['ho_ten'], 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/u', $accountValues['ho_ten'])) {
                            $accountErrors['ho_ten'] = 'Họ tên cần từ 2 đến 120 ký tự hợp lệ.';
                        }
                        if (strlen($accountValues['email']) > 254 || !filter_var($accountValues['email'], FILTER_VALIDATE_EMAIL)) $accountErrors['email'] = 'Vui lòng nhập email đúng định dạng.';
                        if (!preg_match('/^\+?[0-9]{9,15}$/', $accountValues['so_dien_thoai'])) $accountErrors['so_dien_thoai'] = 'Số điện thoại cần từ 9 đến 15 chữ số.';
                        if (!isset($accountRoles[$accountValues['vai_tro']])) $accountErrors['vai_tro'] = 'Vui lòng chọn vai trò hợp lệ.';
                        if (!in_array($accountValues['dang_hoat_dong'], ['0', '1'], true)) $accountErrors['dang_hoat_dong'] = 'Vui lòng chọn trạng thái hợp lệ.';
                        if ($operation === 'create') {
                            if (!mb_check_encoding($password, 'UTF-8') || mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) $accountErrors['mat_khau'] = 'Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte.';
                            if ($password !== $this->input('xac_nhan_mat_khau')) $accountErrors['xac_nhan_mat_khau'] = 'Mật khẩu xác nhận chưa trùng khớp.';
                        }
                        if ($accountErrors) {
                            http_response_code(422);
                            $accountMessage = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
                        } elseif ($operation === 'create') {
                            $model->createEmployee($managerId, $stamp, $accountValues['ho_ten'], $accountValues['email'], $accountValues['so_dien_thoai'], password_hash($password, PASSWORD_DEFAULT));
                            $_SESSION[$messageKey] = 'Đã tạo tài khoản nhân viên. Nhân viên có thể đăng nhập bằng email và mật khẩu vừa tạo.';
                        } else {
                            $changes = $accountValues;
                            $changes['dang_hoat_dong'] = (int) $changes['dang_hoat_dong'];
                            $model->updateManagedAccount($managerId, $stamp, $targetId, $changes, $accountGroup);
                            $_SESSION[$messageKey] = $isCustomerManagement ? 'Đã lưu thông tin khách hàng.' : 'Đã lưu thay đổi tài khoản.';
                        }
                    }
                    if (!$accountErrors) {
                        header('Location: ' . $accountsUrl(), true, 303);
                        return;
                    }
                } catch (DomainException $exception) {
                    http_response_code(409);
                    $accountMessage = $exception->getMessage();
                } catch (PDOException $exception) {
                    if (($exception->errorInfo[1] ?? 0) === 1451) {
                        http_response_code(409);
                        $accountMessage = 'Tài khoản đã có dữ liệu liên quan nên không thể xóa. Hãy khóa tài khoản để giữ lịch sử.';
                    } elseif (($exception->errorInfo[1] ?? 0) === 1062) {
                        http_response_code(422);
                        $accountErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                        $accountMessage = 'Vui lòng kiểm tra lại địa chỉ email.';
                    } else {
                        error_log('OnTrip account management error: ' . $exception->getMessage());
                        http_response_code(503);
                        $accountMessage = 'Chưa thể lưu tài khoản. Vui lòng thử lại sau.';
                    }
                }
            }
        } elseif ($query('edit') !== '') {
            $editAccountId = filter_var($query('edit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            if (!$isCustomerManagement && $editAccountId === (int) $account['id_tai_khoan']) {
                header('Location: index.php?action=profile', true, 303);
                return;
            }
            $target = $editAccountId > 0 ? $model->findManagedAccount($editAccountId, $accountGroup) : null;
            if (!$target) {
                http_response_code(404);
                $accountSuccess = false;
                $accountMessage = 'Không tìm thấy tài khoản cần chỉnh sửa.';
            } else {
                $accountFormMode = 'edit';
                foreach (array_keys($accountValues) as $key) $accountValues[$key] = (string) $target[$key];
            }
        } elseif ($query('delete') !== '') {
            $deleteId = filter_var($query('delete'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            $accountSuccess = false;
            if (!$isCustomerManagement && $deleteId === (int) $account['id_tai_khoan']) {
                http_response_code(409);
                $accountMessage = 'Không thể xóa tài khoản đang đăng nhập của chính mình.';
            } else {
                $deleteAccount = $deleteId > 0 ? $model->findManagedAccount($deleteId, $accountGroup) : null;
                if (!$deleteAccount) {
                    http_response_code(404);
                    $accountMessage = 'Không tìm thấy tài khoản cần xóa.';
                }
            }
        }
        $accountList = $model->getManagedAccounts($accountFilters['q'], $accountFilters['role'], $accountFilters['status'], $pageNumber, $accountGroup);
        require __DIR__ . '/../views/admin/tai_khoan.php';
    }

    private function profile(TaiKhoanModel $model, array $account, string $roleName, string $csrfToken): void
    {
        $adminSection = 'profile';
        $avatarUrl = self::avatarUrl($account['hinh_anh'] ?? null);
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
                        $newAvatar = self::saveAvatar(is_array($file) ? $file : []);
                        $connection->beginTransaction();
                        $model->updateStaffProfile((int) $account['id_tai_khoan'], $profileValues['ho_ten'], $profileValues['email'], $profileValues['so_dien_thoai'], $newAvatar);
                        $updated = $model->findById((int) $account['id_tai_khoan']);
                        if (!$this->allowed($updated)) {
                            $connection->rollBack();
                            self::deleteAvatar($newAvatar);
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
                        if ($newAvatar !== null) self::deleteAvatar($account['hinh_anh'] ?? null);
                        $_SESSION['admin_profile_message'] = 'Đã lưu thông tin cá nhân thành công.';
                        header('Location: index.php?action=profile', true, 303);
                        return;
                    }
                } catch (InvalidArgumentException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
                    $profileErrors['hinh_anh'] = $exception->getMessage();
                } catch (PDOException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
                    if (($exception->errorInfo[1] ?? 0) === 1062) $profileErrors['email'] = 'Email này đã được tài khoản khác sử dụng.';
                    else {
                        error_log('OnTrip profile database error: ' . $exception->getMessage());
                        http_response_code(503);
                        $profileMessage = 'Chưa thể lưu thông tin. Vui lòng kiểm tra cập nhật database và thử lại sau.';
                    }
                } catch (RuntimeException $exception) {
                    if ($connection->inTransaction()) $connection->rollBack();
                    self::deleteAvatar($newAvatar);
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
        $avatarUrl = self::avatarUrl($account['hinh_anh'] ?? null);
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
    /** Kiểm tra, lưu và dọn ảnh đại diện của tài khoản quản trị. */

    public static function avatarUrl(?string $path): string
    {
        return self::isManagedAvatar($path) && is_file(__DIR__ . '/../../public/' . $path)
            ? '../public/' . $path : '';
    }

    private static function isManagedAvatar(?string $path): bool
    {
        return $path !== null && preg_match('~^uploads/avatars/[a-f0-9]{32}\.(jpg|png|gif|webp)$~D', $path) === 1;
    }

    private static function deleteAvatar(?string $path): void
    {
        if (self::isManagedAvatar($path)) {
            $file = __DIR__ . '/../../public/' . $path;
            if (is_file($file) && !unlink($file)) error_log('OnTrip: cannot remove old avatar.');
        }
    }

    /** Trả về đường dẫn tương đối; null nếu không chọn ảnh mới. */
    private static function saveAvatar(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Không tải được ảnh. Ảnh tối đa 5 MB; vui lòng chọn lại.');
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
        if ($image === false) throw new InvalidArgumentException('Không đọc được ảnh này. Vui lòng chọn ảnh khác.');
        $directory = __DIR__ . '/../../public/uploads/avatars';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Không tạo được thư mục ảnh đại diện.');
        }
        $path = 'uploads/avatars/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
        $target = __DIR__ . '/../../public/' . $path;
        try {
            // Có GD thì mã hóa lại; nếu không, ảnh được phục vụ bằng handler tĩnh trong .htaccess.
            if ($image === null) $saved = move_uploaded_file($temp, $target);
            elseif ($mime === 'image/jpeg') $saved = imagejpeg($image, $target, 85);
            elseif ($mime === 'image/png') $saved = imagepng($image, $target, 6);
            elseif ($mime === 'image/gif') $saved = imagegif($image, $target);
            else $saved = imagewebp($image, $target, 85);
            if (!$saved) throw new RuntimeException('Không ghi được ảnh đại diện.');
        } catch (Throwable $exception) {
            self::deleteAvatar($path);
            throw $exception;
        } finally {
            if ($image !== null) imagedestroy($image);
        }
        return $path;
    }
}
