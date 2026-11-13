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

    /** Danh sách người tham gia gửi lên, hỗ trợ mảng passengers[] và tương thích mảng khach[]. */
    private function submittedParticipants(): array
    {
        if (isset($_POST['passengers']) && is_array($_POST['passengers'])) {
            $participants = [];
            foreach ($_POST['passengers'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $participants[] = [
                    'ho_ten' => is_string($p['ho_ten'] ?? null) ? trim($p['ho_ten']) : '',
                    'loai_khach' => (($p['loai_khach'] ?? '') === 'tre_em') ? 'tre_em' : 'nguoi_lon',
                    'gioi_tinh' => (($p['gioi_tinh'] ?? '') === 'nu') ? 'nu' : 'nam',
                    'ngay_sinh' => is_string($p['ngay_sinh'] ?? null) ? trim($p['ngay_sinh']) : '',
                    'cccd' => is_string($p['cccd'] ?? null) ? trim($p['cccd']) : '',
                    'ghi_chu' => is_string($p['ghi_chu'] ?? null) ? trim($p['ghi_chu']) : '',
                ];
            }
            return $participants;
        }

        $names = $_POST['khach']['ho_ten'] ?? [];
        $types = $_POST['khach']['loai_khach'] ?? [];
        $genders = $_POST['khach']['gioi_tinh'] ?? [];
        $birthdays = $_POST['khach']['ngay_sinh'] ?? [];
        $cccds = $_POST['khach']['cccd'] ?? [];
        $notes = $_POST['khach']['ghi_chu'] ?? [];
        if (!is_array($names) || !is_array($types)) {
            return [];
        }
        $names = array_values($names);
        $types = array_values($types);
        $genders = is_array($genders) ? array_values($genders) : [];
        $birthdays = is_array($birthdays) ? array_values($birthdays) : [];
        $cccds = is_array($cccds) ? array_values($cccds) : [];
        $notes = is_array($notes) ? array_values($notes) : [];
        $participants = [];
        foreach ($names as $index => $name) {
            $participants[] = [
                'ho_ten' => is_string($name) ? trim($name) : '',
                'loai_khach' => (($types[$index] ?? '') === 'tre_em') ? 'tre_em' : 'nguoi_lon',
                'gioi_tinh' => (($genders[$index] ?? '') === 'nu') ? 'nu' : 'nam',
                'ngay_sinh' => is_string($birthdays[$index] ?? null) ? trim($birthdays[$index]) : '',
                'cccd' => is_string($cccds[$index] ?? null) ? trim($cccds[$index]) : '',
                'ghi_chu' => is_string($notes[$index] ?? null) ? trim($notes[$index]) : '',
            ];
        }
        return $participants;
    }

    /** Tính tuổi dựa trên ngày sinh và ngày tham chiếu (ngày khởi hành hoặc hiện tại). */
    private function calculateAge(string $birthday, string $refDateStr): ?int
    {
        $birth = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);
        $ref = DateTimeImmutable::createFromFormat('!Y-m-d', $refDateStr);
        if (!$birth || !$ref) {
            return null;
        }
        $diff = $birth->diff($ref);
        return $diff->invert ? -1 : $diff->y;
    }

    /** Kiểm tra ngày sinh: bắt buộc là ngày hợp lệ trong quá khứ. */
    private function isValidBirthday(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return false;
        }
        $year = (int) $date->format('Y');
        return $year >= 1900 && $value <= date('Y-m-d');
    }

    /** Lịch khởi hành theo id trong danh sách đang mở nhận đặt. */
    private function findSchedule(array $schedules, int $id): ?array
    {
        foreach ($schedules as $schedule) {
            if ((int) $schedule['id'] === $id) {
                return $schedule;
            }
        }
        return null;
    }

    /** Lịch gần nhất còn chỗ, dùng khi lịch khách chọn không còn nhận đặt. */
    private function nearestAvailableSchedule(array $schedules): ?array
    {
        foreach ($schedules as $schedule) {
            if ((int) $schedule['seats_left'] > 0) {
                return $schedule;
            }
        }
        return null;
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
        $participants = [['ho_ten' => '', 'loai_khach' => 'nguoi_lon', 'gioi_tinh' => 'nam', 'ngay_sinh' => '', 'cccd' => '', 'ghi_chu' => '']];
        // Lịch khởi hành do trang chi tiết tour truyền sang, biểu mẫu không cho chọn lại.
        $selectedId = $this->queryInteger('schedule');

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

                $schedule = $this->findSchedule($schedules, $selectedId);
                if ($schedule === null) {
                    $errors['id_lich_khoi_hanh'] = 'Lịch khởi hành này không còn nhận đặt.';
                }

                $adults = 0;
                $children = 0;
                $depDate = $schedule['ngay_khoi_hanh'] ?? date('Y-m-d');
                foreach ($participants as $index => $participant) {
                    $guestNum = $index + 1;
                    $guestLabel = ($participant['loai_khach'] === 'tre_em' ? 'Trẻ em ' : 'Người lớn ') . $guestNum;
                    $length = mb_strlen($participant['ho_ten'], 'UTF-8');
                    if ($length < 2 || $length > 120) {
                        $errors['khach-' . $index] = 'Họ tên khách thứ ' . $guestNum . ' cần từ 2 đến 120 ký tự.';
                    }
                    if ($participant['ngay_sinh'] === '') {
                        $errors['khach-' . $index] = 'Vui lòng nhập ngày sinh cho khách thứ ' . $guestNum . '.';
                    } elseif (!$this->isValidBirthday($participant['ngay_sinh'])) {
                        $errors['khach-' . $index] = 'Ngày sinh của khách thứ ' . $guestNum . ' không hợp lệ.';
                    } else {
                        $age = $this->calculateAge($participant['ngay_sinh'], $depDate);
                        if ($participant['loai_khach'] === 'nguoi_lon') {
                            if ($age !== null && $age < 12) {
                                $errors['khach-' . $index] = 'Khách thứ ' . $guestNum . ' là Người lớn nhưng ngày sinh tính được ' . $age . ' tuổi (yêu cầu từ 12 tuổi trở lên).';
                            }
                        } else {
                            if ($age !== null && $age >= 12) {
                                $errors['khach-' . $index] = 'Khách thứ ' . $guestNum . ' là Trẻ em nhưng ngày sinh tính được ' . $age . ' tuổi (yêu cầu dưới 12 tuổi).';
                            }
                        }
                    }

                    if ($participant['loai_khach'] === 'nguoi_lon') {
                        $adults++;
                        $cccd = trim($participant['cccd'] ?? '');
                        if ($cccd === '') {
                            $existing = $errors['khach-' . $index] ?? '';
                            $errors['khach-' . $index] = $existing !== '' ? $existing . ' Bắt buộc nhập CCCD / Hộ chiếu cho người lớn.' : 'Bắt buộc nhập CCCD / Hộ chiếu cho khách thứ ' . $guestNum . ' (người lớn).';
                        } elseif (mb_strlen($cccd, 'UTF-8') < 6 || mb_strlen($cccd, 'UTF-8') > 30) {
                            $errors['khach-' . $index] = 'CCCD / Hộ chiếu của khách thứ ' . $guestNum . ' không hợp lệ (từ 6 đến 30 ký tự).';
                        }
                    } else {
                        $children++;
                    }

                    if (mb_strlen($participant['ghi_chu'], 'UTF-8') > 255) {
                        $errors['khach-' . $index] = 'Ghi chú của khách thứ ' . $guestNum . ' tối đa 255 ký tự.';
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

        // Nếu lịch khách chọn không còn nhận đặt thì tạm dùng lịch gần nhất còn chỗ và báo cho khách đổi lịch.
        $selectedSchedule = $this->findSchedule($schedules, $selectedId)
            ?? $this->nearestAvailableSchedule($schedules)
            ?? ($schedules[0] ?? null);
        $scheduleNotice = '';
        if ($selectedSchedule !== null && (int) $selectedSchedule['seats_left'] < 1) {
            $scheduleNotice = 'Lịch khởi hành này đã hết chỗ.';
        } elseif ($selectedSchedule !== null && (int) $selectedSchedule['id'] !== $selectedId) {
            $scheduleNotice = 'Lịch bạn chọn không còn nhận đặt, OnTrip tạm chọn lịch gần nhất còn chỗ.';
        }
        $selectedId = (int) ($selectedSchedule['id'] ?? 0);
        $notFound = !$schedules;
        $bookingUrl = 'index.php?page=booking&type=' . $tourType . '&id=' . rawurlencode($code);
        if ($selectedSchedule !== null) {
            $bookingUrl .= '&schedule=' . $selectedId;
        }
        $loginUrl = 'index.php?page=login&redirect=' . rawurlencode($bookingUrl);
        $registerUrl = 'index.php?page=register&redirect=' . rawurlencode($bookingUrl);
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
            header('Location: index.php?page=login&redirect=' . rawurlencode('index.php?page=orders'), true, 303);
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
