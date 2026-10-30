<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/booking.php';
require_once __DIR__ . '/../models/tai_khoan.php';

/**
 * Luồng đặt tour của khách: xác nhận thông tin, tạo đơn, xem và hủy đơn.
 * Mọi kiểm tra chỗ/hạn đặt đều làm lại ở server, không tin số liệu trên trình duyệt.
 */
class BookingController
{
    private ScheduleModel $schedules;
    private TaiKhoanModel $accounts;

    public function __construct()
    {
        $connection = getDatabaseConnection();
        $this->schedules = new ScheduleModel($connection);
        $this->accounts = new TaiKhoanModel($connection);
    }

    private function input(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? trim($_POST[$name]) : '';
    }

    private function queryString(string $name): string
    {
        return isset($_GET[$name]) && is_string($_GET[$name]) ? trim($_GET[$name]) : '';
    }

    private function currentAccount(): ?array
    {
        return $_SESSION['tai_khoan'] ?? null;
    }

    /** Chưa đăng nhập thì quay về trang đăng nhập, không lộ dữ liệu đơn. */
    private function requireAccount(): ?array
    {
        $account = $this->currentAccount();
        if ($account === null) {
            header('Location: index.php?page=login', true, 303);
            return null;
        }
        return $account;
    }

    private function validCsrf(): bool
    {
        $token = $this->input('csrf_token');
        return $token !== '' && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function flash(string $message): void
    {
        $_SESSION['booking_message'] = $message;
    }

    /** Trang chi tiết tour để quay lại sau khi báo lỗi. */
    private function redirectToTour(): void
    {
        $tourType = $this->input('type') === 'international' ? 'international' : 'domestic';
        $tourCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $this->input('ma_tour'));
        $target = $tourCode !== ''
            ? 'index.php?page=tour-detail&type=' . $tourType . '&id=' . rawurlencode($tourCode)
            : 'index.php?page=tours&type=' . $tourType;
        header('Location: ' . $target, true, 303);
    }

    /** Đọc số khách từ form, chặn giá trị âm và tổng vượt giới hạn một đơn. */
    private function readGuests(): array
    {
        $adults = filter_var($this->input('so_nguoi_lon'), FILTER_VALIDATE_INT);
        $children = filter_var($this->input('so_tre_em'), FILTER_VALIDATE_INT);
        $adults = $adults === false ? 0 : $adults;
        $children = $children === false ? 0 : $children;
        if ($adults < 1 || $children < 0 || $adults + $children > ScheduleModel::MAX_GUESTS_PER_ORDER) {
            return [0, 0, 'Số khách không hợp lệ: cần ít nhất 1 người lớn và tối đa '
                . ScheduleModel::MAX_GUESTS_PER_ORDER . ' khách mỗi đơn.'];
        }
        return [$adults, $children, ''];
    }

    /** Bước 1: kiểm tra lịch và hiển thị trang xác nhận. Bước 2: ghi đơn. */
    public function form(): void
    {
        header('Cache-Control: no-store');
        if ($this->requireAccount() === null) return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=tours', true, 303);
            return;
        }
        if (!$this->validCsrf()) {
            $this->flash('Phiên gửi biểu mẫu không hợp lệ. Vui lòng chọn lại lịch khởi hành.');
            $this->redirectToTour();
            return;
        }

        $scheduleId = filter_var($this->input('id_lich_khoi_hanh'), FILTER_VALIDATE_INT);
        $schedule = $scheduleId ? $this->schedules->findSchedule((int) $scheduleId) : null;
        if ($schedule === null) {
            $this->flash('Lịch khởi hành không tồn tại hoặc đã ngừng nhận đặt.');
            $this->redirectToTour();
            return;
        }
        $blocked = $this->schedules->blockReason($schedule);
        if ($blocked !== null) {
            $this->flash($blocked . ' Vui lòng chọn lịch khác.');
            $this->redirectToTour();
            return;
        }

        [$adults, $children, $guestError] = $this->readGuests();
        if ($guestError !== '') {
            $this->flash($guestError);
            $this->redirectToTour();
            return;
        }
        $guests = $adults + $children;
        if ((int) $schedule['so_cho_con'] <= 0) {
            $this->flash('Lịch khởi hành này đã hết chỗ. Vui lòng chọn lịch khác.');
            $this->redirectToTour();
            return;
        }
        if ($guests > (int) $schedule['so_cho_con']) {
            $this->flash('Lịch này chỉ còn ' . (int) $schedule['so_cho_con'] . ' chỗ, không đủ cho ' . $guests . ' khách.');
            $this->redirectToTour();
            return;
        }

        $account = $this->currentAccount();
        $values = [
            'ho_ten' => $account['ho_ten'] ?? '',
            'email' => $account['email'] ?? '',
            'so_dien_thoai' => '',
            'ghi_chu' => '',
        ];
        $profile = $this->accounts->findById((int) $account['id_tai_khoan']);
        if ($profile !== null) {
            $values['so_dien_thoai'] = (string) $profile['so_dien_thoai'];
        }

        if ($this->queryString('action') === 'create') {
            $this->create($schedule, $adults, $children, $values);
            return;
        }

        $errors = [];
        $pricing = GroupPricing::calculate($adults, $children, (int) $schedule['gia_nguoi_lon'], (int) $schedule['gia_tre_em']);
        require __DIR__ . '/../views/bookings/confirm.php';
    }

    /** Ghi đơn sau khi khách xác nhận thông tin liên hệ. */
    private function create(array $schedule, int $adults, int $children, array $values): void
    {
        $values = [
            'ho_ten' => $this->input('ho_ten'),
            'email' => strtolower($this->input('email')),
            'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
            'ghi_chu' => mb_substr($this->input('ghi_chu'), 0, 500, 'UTF-8'),
        ];
        $errors = [];
        $nameLength = mb_strlen($values['ho_ten'], 'UTF-8');
        if ($nameLength < 2 || $nameLength > 120) {
            $errors['ho_ten'] = 'Họ tên cần từ 2 đến 120 ký tự.';
        }
        if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Vui lòng nhập email đúng định dạng.';
        }
        if (!preg_match('/^\+?[0-9]{9,15}$/', $values['so_dien_thoai'])) {
            $errors['so_dien_thoai'] = 'Số điện thoại cần có từ 9 đến 15 chữ số.';
        }

        $pricing = GroupPricing::calculate($adults, $children, (int) $schedule['gia_nguoi_lon'], (int) $schedule['gia_tre_em']);
        if ($errors) {
            http_response_code(422);
            require __DIR__ . '/../views/bookings/confirm.php';
            return;
        }

        $account = $this->currentAccount();
        $result = $this->schedules->createBooking((int) $schedule['id_lich_khoi_hanh'], (int) $account['id_tai_khoan'], [
            'ho_ten' => $values['ho_ten'],
            'email' => $values['email'],
            'so_dien_thoai' => $values['so_dien_thoai'],
            'so_nguoi_lon' => $adults,
            'so_tre_em' => $children,
            'ghi_chu' => $values['ghi_chu'],
        ]);
        if ($result['ok']) {
            header('Location: index.php?page=booking-success&code=' . rawurlencode((string) $result['ma_don']), true, 303);
            return;
        }
        http_response_code(409);
        $errors['form'] = $result['error'];
        require __DIR__ . '/../views/bookings/confirm.php';
    }

    public function success(): void
    {
        header('Cache-Control: no-store');
        $account = $this->requireAccount();
        if ($account === null) return;
        $code = preg_replace('/[^A-Za-z0-9]/', '', $this->queryString('code'));
        $order = $code !== '' ? $this->schedules->findOrderByCode($code, (int) $account['id_tai_khoan']) : null;
        if ($order === null) {
            http_response_code(404);
        }
        require __DIR__ . '/../views/bookings/success.php';
    }

    public function myBookings(): void
    {
        header('Cache-Control: no-store');
        $account = $this->requireAccount();
        if ($account === null) return;
        $orders = $this->schedules->getOrdersForAccount((int) $account['id_tai_khoan']);
        $message = $_SESSION['booking_message'] ?? '';
        unset($_SESSION['booking_message']);
        require __DIR__ . '/../views/bookings/my_bookings.php';
    }

    /** Khách tự hủy đơn của mình; chỗ được trả lại ngay sau khi hủy. */
    public function cancel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }
        $account = $this->requireAccount();
        if ($account === null) return;
        if (!$this->validCsrf()) {
            http_response_code(403);
            $this->flash('Phiên gửi biểu mẫu không hợp lệ. Vui lòng thử lại.');
            header('Location: index.php?page=my-bookings', true, 303);
            return;
        }
        $orderId = filter_var($this->input('id_don_dat'), FILTER_VALIDATE_INT);
        $result = $orderId ? $this->schedules->cancelOrder((int) $orderId, (int) $account['id_tai_khoan'])
            : ['ok' => false, 'error' => 'Đơn không hợp lệ.'];
        $this->flash($result['ok'] ? 'Đã hủy đơn và trả lại chỗ cho chuyến đi.' : $result['error']);
        header('Location: index.php?page=my-bookings', true, 303);
    }
}
