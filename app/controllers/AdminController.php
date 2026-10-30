<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tai_khoan.php';
require_once __DIR__ . '/../models/admin.php';
require_once __DIR__ . '/../models/booking.php';

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

    public function index(): void
    {
        header('Cache-Control: no-store');
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        // Đọc lại để quyền bị đổi hoặc tài khoản bị khóa có hiệu lực ngay.
        $account = $this->authorize();
        if ($account === null) return;
        $roleName = $account['vai_tro'] === 'admin' ? 'Quản trị viên' : 'Nhân viên';
        $csrfToken = $_SESSION['csrf_token'];
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $dashboard = (new AdminModel(getDatabaseConnection()))->getOverview($now);
        $todayLabel = $now->format('d/m/Y');
        require __DIR__ . '/../views/admin/index.php';
    }

    private function queryString(string $name): string
    {
        return isset($_GET[$name]) && is_string($_GET[$name]) ? trim($_GET[$name]) : '';
    }

    /** Chỉ nhận ngày đúng định dạng Y-m-d, tránh tham số lạ lọt vào SQL. */
    private function isDate(string $value): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $parsed !== false && $parsed->format('Y-m-d') === $value;
    }

    /**
     * Xác thực lại quyền quản trị cho mọi trang trong khu vực admin.
     * Trả về null (và xuất trang 403) khi tài khoản không còn hợp lệ.
     */
    private function authorize(): ?array
    {
        $sessionAccount = $_SESSION['tai_khoan'] ?? null;
        if (!is_array($sessionAccount)) {
            http_response_code(403);
            require __DIR__ . '/../views/admin/forbidden.php';
            return null;
        }
        $account = (new TaiKhoanModel(getDatabaseConnection()))->findByEmail((string) ($sessionAccount['email'] ?? ''));
        $allowed = $account
            && (int) $account['id_tai_khoan'] === (int) ($sessionAccount['id_tai_khoan'] ?? 0)
            && (int) $account['dang_hoat_dong'] === 1
            && in_array($account['vai_tro'], ['admin', 'nhan_vien'], true);
        if (!$allowed) {
            unset($_SESSION['tai_khoan']);
            http_response_code(403);
            require __DIR__ . '/../views/admin/forbidden.php';
            return null;
        }
        return $account;
    }

    private function flash(string $message): void
    {
        $_SESSION['admin_message'] = $message;
    }

    private function takeFlash(): string
    {
        $message = isset($_SESSION['admin_message']) && is_string($_SESSION['admin_message'])
            ? $_SESSION['admin_message'] : '';
        unset($_SESSION['admin_message']);
        return $message;
    }

    private function adminContext(): ?array
    {
        $account = $this->authorize();
        if ($account === null) return null;
        return [
            'account' => $account,
            'roleName' => $account['vai_tro'] === 'admin' ? 'Quản trị viên' : 'Nhân viên',
            'csrfToken' => $_SESSION['csrf_token'],
            'todayLabel' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('d/m/Y'),
        ];
    }

    /** Danh sách lịch khởi hành kèm số chỗ đã đặt và chỗ còn. */
    public function schedules(): void
    {
        header('Cache-Control: no-store');
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        $context = $this->adminContext();
        if ($context === null) return;
        extract($context);

        $model = new ScheduleModel(getDatabaseConnection());
        $filters = [
            'tour' => preg_match('/^[A-Za-z0-9_-]{1,30}$/', $this->queryString('tour')) ? $this->queryString('tour') : '',
            'from' => $this->isDate($this->queryString('from')) ? $this->queryString('from') : '',
            'to' => $this->isDate($this->queryString('to')) ? $this->queryString('to') : '',
            'status' => $this->queryString('status'),
        ];
        $schedules = $model->getSchedulesForAdmin($filters);
        $stats = $model->getScheduleStats();
        $tourOptions = $model->getTourOptions();
        $message = $this->takeFlash();
        require __DIR__ . '/../views/admin/schedules.php';
    }

    /** Chi tiết một lịch: thông tin chuyến và danh sách khách đã đặt. */
    public function schedule(): void
    {
        header('Cache-Control: no-store');
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        $context = $this->adminContext();
        if ($context === null) return;
        extract($context);

        $scheduleId = filter_var($this->queryString('id'), FILTER_VALIDATE_INT);
        $model = new ScheduleModel(getDatabaseConnection());
        $schedule = $scheduleId ? $model->getScheduleDetail((int) $scheduleId) : null;
        if ($schedule === null) {
            $this->flash('Không tìm thấy lịch khởi hành cần xem.');
            header('Location: index.php?action=schedules', true, 303);
            return;
        }
        $passengers = $model->getPassengers((int) $schedule['id_lich_khoi_hanh']);
        $summary = ['tong_khach' => 0, 'cho_giu' => 0, 'cho_huy' => 0, 'doanh_thu' => 0];
        foreach ($passengers as $passenger) {
            $guests = (int) $passenger['so_nguoi_lon'] + (int) $passenger['so_tre_em'];
            $summary['tong_khach'] += $guests;
            if ($passenger['trang_thai'] === 'da_huy') {
                $summary['cho_huy'] += $guests;
            } else {
                $summary['cho_giu'] += $guests;
                $summary['doanh_thu'] += (int) $passenger['tong_thanh_toan'];
            }
        }
        $message = $this->takeFlash();
        require __DIR__ . '/../views/admin/schedule_detail.php';
    }

    /** Nhân viên/admin đổi trạng thái đơn; hủy đơn là trả chỗ ngay cho chuyến. */
    public function updateOrderStatus(): void
    {
        header('Cache-Control: no-store');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?action=login', true, 303);
            return;
        }
        $scheduleId = filter_var($this->input('id_lich_khoi_hanh'), FILTER_VALIDATE_INT);
        $back = $scheduleId ? 'index.php?action=schedule&id=' . (int) $scheduleId : 'index.php?action=schedules';
        if ($this->authorize() === null) return;
        if (!hash_equals((string) $_SESSION['csrf_token'], $this->input('csrf_token'))) {
            $this->flash('Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.');
            header('Location: ' . $back, true, 303);
            return;
        }
        $orderId = filter_var($this->input('id_don_dat'), FILTER_VALIDATE_INT);
        $result = $orderId
            ? (new ScheduleModel(getDatabaseConnection()))->updateOrderStatus((int) $orderId, $this->input('trang_thai'))
            : ['ok' => false, 'error' => 'Đơn không hợp lệ.'];
        $this->flash($result['ok'] ? 'Đã cập nhật trạng thái đơn.' : $result['error']);
        header('Location: ' . $back, true, 303);
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
