<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/don_dat.php';

/** Tiếp nhận yêu cầu đặt tour và quản lý đơn đặt của khách hàng đã đăng nhập. */
class DonDatController
{
    private DonDatModel $model;

    public function __construct()
    {
        $this->model = new DonDatModel(getDatabaseConnection());
    }

    private function input(string $name): string
    {
        return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : '';
    }

    private function queryString(string $name): string
    {
        return is_string($_GET[$name] ?? null) ? trim($_GET[$name]) : '';
    }

    private function queryInteger(string $name): int
    {
        $value = filter_var($this->queryString($name), FILTER_VALIDATE_INT);
        return $value === false || $value < 1 ? 0 : $value;
    }

    /** Danh sách người tham gia gửi lên theo bốn mảng song song. */
    private function submittedParticipants(): array
    {
        $names = $_POST['khach']['ho_ten'] ?? [];
        $types = $_POST['khach']['loai_khach'] ?? [];
        $birthdays = $_POST['khach']['ngay_sinh'] ?? [];
        $notes = $_POST['khach']['ghi_chu'] ?? [];
        if (!is_array($names) || !is_array($types) || !is_array($birthdays) || !is_array($notes)) {
            return [];
        }
        $names = array_values($names);
        $types = array_values($types);
        $birthdays = array_values($birthdays);
        $notes = array_values($notes);
        $participants = [];
        foreach ($names as $index => $name) {
            $participants[] = [
                'ho_ten' => is_string($name) ? trim($name) : '',
                'loai_khach' => (($types[$index] ?? '') === 'tre_em') ? 'tre_em' : 'nguoi_lon',
                'ngay_sinh' => is_string($birthdays[$index] ?? null) ? trim($birthdays[$index]) : '',
                'ghi_chu' => is_string($notes[$index] ?? null) ? trim($notes[$index]) : '',
            ];
        }
        return $participants;
    }

    /** Kiểm tra ngày sinh: bỏ trống được, nếu nhập phải là ngày hợp lệ trong quá khứ. */
    private function isValidBirthday(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return false;
        }
        $year = (int) $date->format('Y');
        return $year >= 1900 && $value <= date('Y-m-d');
    }

    public function form(): void
    {
        header('Cache-Control: no-store');
        $tourType = $this->queryString('type') === 'international' ? 'international' : 'domestic';
        $code = $this->queryString('id');
        $schedules = $this->model->getTourSchedules($tourType, $code);
        $account = $_SESSION['tai_khoan'] ?? null;
        $authRequired = !is_array($account);
        $csrfToken = $_SESSION['csrf_token'];
        $errors = [];
        $message = '';
        $values = [
            'ho_ten' => is_array($account) ? (string) $account['ho_ten'] : '',
            'email' => is_array($account) ? (string) $account['email'] : '',
            'so_dien_thoai' => '',
            'dia_chi' => '',
            'ghi_chu' => '',
        ];
        $participants = [['ho_ten' => '', 'loai_khach' => 'nguoi_lon', 'ngay_sinh' => '', 'ghi_chu' => '']];
        $selectedId = $this->queryInteger('schedule');
        if ($selectedId === 0 && $schedules) {
            $selectedId = (int) $schedules[0]['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($authRequired) {
                http_response_code(403);
                echo 'Vui lòng đăng nhập để đặt tour.';
                return;
            }
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } else {
                $values = [
                    'ho_ten' => trim($this->input('ho_ten')),
                    'email' => strtolower(trim($this->input('email'))),
                    'so_dien_thoai' => preg_replace('/[\s().-]/', '', $this->input('so_dien_thoai')),
                    'dia_chi' => trim($this->input('dia_chi')),
                    'ghi_chu' => trim($this->input('ghi_chu')),
                ];
                $participants = $this->submittedParticipants() ?: $participants;
                $selectedId = (int) filter_var($this->input('id_lich_khoi_hanh'), FILTER_VALIDATE_INT);

                if (mb_strlen($values['ho_ten'], 'UTF-8') < 2 || mb_strlen($values['ho_ten'], 'UTF-8') > 120) {
                    $errors['ho_ten'] = 'Họ tên người liên hệ cần từ 2 đến 120 ký tự.';
                }
                if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Vui lòng nhập email đúng định dạng.';
                }
                if (!preg_match('/^\+?[0-9]{9,15}$/', $values['so_dien_thoai'])) {
                    $errors['so_dien_thoai'] = 'Số điện thoại cần có từ 9 đến 15 chữ số.';
                }
                if (mb_strlen($values['dia_chi'], 'UTF-8') > 255) {
                    $errors['dia_chi'] = 'Địa chỉ tối đa 255 ký tự.';
                }
                if (mb_strlen($values['ghi_chu'], 'UTF-8') > 500) {
                    $errors['ghi_chu'] = 'Ghi chú tối đa 500 ký tự.';
                }

                $schedule = null;
                foreach ($schedules as $item) {
                    if ((int) $item['id'] === $selectedId) {
                        $schedule = $item;
                        break;
                    }
                }
                if ($schedule === null) {
                    $errors['id_lich_khoi_hanh'] = 'Vui lòng chọn một lịch khởi hành còn nhận đặt.';
                }

                $adults = 0;
                $children = 0;
                foreach ($participants as $index => $participant) {
                    $length = mb_strlen($participant['ho_ten'], 'UTF-8');
                    if ($length < 2 || $length > 120) {
                        $errors['khach-' . $index] = 'Họ tên khách cần từ 2 đến 120 ký tự.';
                    }
                    if (!$this->isValidBirthday($participant['ngay_sinh'])) {
                        $errors['khach-' . $index] = 'Ngày sinh không hợp lệ.';
                    }
                    if (mb_strlen($participant['ghi_chu'], 'UTF-8') > 255) {
                        $errors['khach-' . $index] = 'Ghi chú của khách tối đa 255 ký tự.';
                    }
                    if ($participant['loai_khach'] === 'tre_em') {
                        $children++;
                    } else {
                        $adults++;
                    }
                }
                if ($adults < 1) {
                    $errors['khach'] = 'Đơn đặt cần ít nhất một người lớn.';
                } elseif ($adults + $children > 99) {
                    $errors['khach'] = 'Mỗi đơn đặt nhận tối đa 99 khách.';
                } elseif ($schedule !== null && $adults + $children > $schedule['seats_left']) {
                    $errors['khach'] = 'Lịch này chỉ còn ' . $schedule['seats_left'] . ' chỗ. Vui lòng giảm số khách.';
                }

                if (!$errors) {
                    try {
                        $order = $this->model->createOrder(
                            (int) $account['id_tai_khoan'],
                            (int) $schedule['id'],
                            $values,
                            $participants
                        );
                        header('Location: index.php?page=orders&id=' . rawurlencode($order['ma_don']) . '&created=1', true, 303);
                        return;
                    } catch (DomainException $exception) {
                        $message = $exception->getMessage();
                    }
                } else {
                    $message = 'Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.';
                }
            }
        }

        $selectedSchedule = null;
        foreach ($schedules as $item) {
            if ((int) $item['id'] === $selectedId) {
                $selectedSchedule = $item;
                break;
            }
        }
        $notFound = !$schedules;
        $adultPrice = $selectedSchedule['adult_price'] ?? 0;
        $childPrice = $selectedSchedule['child_price'] ?? 0;
        $adultCount = 0;
        $childCount = 0;
        foreach ($participants as $participant) {
            if ($participant['loai_khach'] === 'tre_em') {
                $childCount++;
            } else {
                $adultCount++;
            }
        }
        $totals = DonDatModel::calculateTotals($adultCount, $childCount, $adultPrice, $childPrice);
        require __DIR__ . '/../views/don_dat/dat_tour.php';
    }

    public function orders(): void
    {
        header('Cache-Control: no-store');
        if (!isset($_SESSION['tai_khoan'])) {
            header('Location: index.php?page=login', true, 303);
            return;
        }
        $accountId = (int) $_SESSION['tai_khoan']['id_tai_khoan'];
        $csrfToken = $_SESSION['csrf_token'];
        $flash = $_SESSION['order_flash'] ?? null;
        unset($_SESSION['order_flash']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $maDon = trim($this->input('ma_don'));
            $operation = $this->input('operation');
            $flash = ['message' => 'Thao tác không hợp lệ.', 'success' => false];
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $flash['message'] = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.';
            } else {
                try {
                    if ($operation === 'pay') {
                        $transactionCode = $this->model->payAccountOrder($accountId, $maDon, $this->input('phuong_thuc'));
                        $flash = [
                            'message' => 'Đã xác nhận thanh toán mô phỏng. Mã giao dịch ' . $transactionCode . '.',
                            'success' => true,
                        ];
                    } elseif ($operation === 'cancel') {
                        $this->model->cancelAccountOrder($accountId, $maDon);
                        $flash = ['message' => 'Đã hủy đơn đặt tour.', 'success' => true];
                    }
                } catch (DomainException $exception) {
                    $flash = ['message' => $exception->getMessage(), 'success' => false];
                }
            }
            $_SESSION['order_flash'] = $flash;
            header('Location: index.php?page=orders&id=' . rawurlencode($maDon), true, 303);
            return;
        }

        $maDon = $this->queryString('id');
        $statusLabels = DonDatModel::STATUS_LABELS;
        $paymentMethods = DonDatModel::PAYMENT_METHODS;
        if ($maDon !== '') {
            $order = $this->model->findAccountOrder($accountId, $maDon);
            if ($order === null) {
                http_response_code(404);
            } elseif (!empty($_GET['created'])) {
                $flash = ['message' => 'Đã gửi yêu cầu đặt tour. OnTrip sẽ xác nhận đơn của bạn.', 'success' => true];
            }
            require __DIR__ . '/../views/don_dat/chi_tiet.php';
            return;
        }
        $orders = $this->model->getAccountOrders($accountId);
        require __DIR__ . '/../views/don_dat/danh_sach.php';
    }
}
