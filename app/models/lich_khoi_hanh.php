<?php

/**
 * Quản lý lịch khởi hành và số chỗ của từng chuyến.
 * Chỗ đã đặt luôn được tính từ bảng đơn (trừ đơn đã hủy) nên không có cột đếm dễ lệch.
 */
class LichKhoiHanhModel
{
    public const STATUSES = [
        'open' => 'Đang mở nhận đặt',
        'closed' => 'Đã đóng',
        'cancelled' => 'Đã hủy',
    ];

    /** Biểu thức con đếm số khách của các đơn chưa hủy trên một lịch; dùng chung mọi truy vấn. */
    private const BOOKED_SEATS = "(SELECT COALESCE(SUM(dd.so_nguoi_lon + dd.so_tre_em), 0)
        FROM don_dat_tour dd
        WHERE dd.id_lich_khoi_hanh = l.id_lich_khoi_hanh AND dd.trang_thai <> 'da_huy')";

    /** @var PDO Kết nối database được truyền từ Controller. */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    }

    /** Tour dùng cho bộ lọc và ô chọn của form. */
    public function getTourOptions(): array
    {
        return $this->connection->query(
            'SELECT id_tour, ma_tour, ten_tour, so_ngay, trang_thai FROM tour ORDER BY ten_tour, id_tour'
        )->fetchAll();
    }

    /** Số liệu tổng quan cho đầu trang quản lý lịch. */
    public function getSummary(): array
    {
        $row = $this->connection->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(l.trang_thai = \'open\'), 0) AS open_count,
                    COALESCE(SUM(l.trang_thai = \'open\' AND l.ngay_khoi_hanh >= CURDATE()), 0) AS upcoming,
                    COALESCE(SUM(l.so_cho), 0) AS seats,
                    COALESCE(SUM(' . self::BOOKED_SEATS . '), 0) AS booked
             FROM lich_khoi_hanh l'
        )->fetch() ?: [];
        $summary = [
            'total' => (int) ($row['total'] ?? 0),
            'open' => (int) ($row['open_count'] ?? 0),
            'upcoming' => (int) ($row['upcoming'] ?? 0),
            'seats' => (int) ($row['seats'] ?? 0),
            'booked' => (int) ($row['booked'] ?? 0),
        ];
        $summary['fill_rate'] = $summary['seats'] > 0
            ? (int) round($summary['booked'] * 100 / $summary['seats'])
            : 0;
        return $summary;
    }

    /**
     * Danh sách lịch có lọc theo từ khóa, tour, trạng thái và khoảng thời gian.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function getManagedSchedules(array $filters, int $page, int $perPage = 10): array
    {
        $conditions = [];
        $parameters = [];
        if (($filters['status'] ?? '') !== '' && isset(self::STATUSES[$filters['status']])) {
            $conditions[] = 'l.trang_thai = ?';
            $parameters[] = $filters['status'];
        }
        if (($filters['tour'] ?? '') !== '') {
            $conditions[] = 'l.id_tour = ?';
            $parameters[] = (int) $filters['tour'];
        }
        $today = $this->now()->format('Y-m-d');
        if (($filters['scope'] ?? '') === 'upcoming') {
            $conditions[] = 'l.ngay_khoi_hanh >= ?';
            $parameters[] = $today;
        } elseif (($filters['scope'] ?? '') === 'past') {
            $conditions[] = 'l.ngay_khoi_hanh < ?';
            $parameters[] = $today;
        }
        if (($filters['q'] ?? '') !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $conditions[] = '(t.ma_tour LIKE ? OR t.ten_tour LIKE ? OR l.diem_khoi_hanh LIKE ?)';
            array_push($parameters, $like, $like, $like);
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $count = $this->connection->prepare(
            'SELECT COUNT(*) FROM lich_khoi_hanh l JOIN tour t ON t.id_tour = l.id_tour' . $where
        );
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->connection->prepare(
            'SELECT l.id_lich_khoi_hanh, l.id_tour, l.ngay_khoi_hanh, l.ngay_ve, l.diem_khoi_hanh,
                    l.gia_nguoi_lon, l.gia_tre_em, l.so_cho, l.han_dat, l.trang_thai,
                    t.ma_tour, t.ten_tour, t.so_ngay,
                    ' . self::BOOKED_SEATS . ' AS booked
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour' . $where . '
             ORDER BY CASE WHEN l.ngay_khoi_hanh >= ? THEN 0 ELSE 1 END,
                      CASE WHEN l.ngay_khoi_hanh >= ? THEN l.ngay_khoi_hanh END ASC,
                      CASE WHEN l.ngay_khoi_hanh < ? THEN l.ngay_khoi_hanh END DESC,
                      l.id_lich_khoi_hanh
             LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute(array_merge($parameters, [$today, $today, $today]));

        return [
            'items' => array_map([$this, 'normalizeSchedule'], $statement->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /** Một lịch kèm số chỗ đã đặt; $lock dùng khi sửa để tránh đặt chỗ đồng thời. */
    public function findManagedSchedule(int $id, bool $lock = false): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $statement = $this->connection->prepare(
            'SELECT l.id_lich_khoi_hanh, l.id_tour, l.ngay_khoi_hanh, l.ngay_ve, l.diem_khoi_hanh,
                    l.gia_nguoi_lon, l.gia_tre_em, l.so_cho, l.han_dat, l.trang_thai,
                    t.ma_tour, t.ten_tour, t.so_ngay, t.trang_thai AS tour_status,
                    ' . self::BOOKED_SEATS . ' AS booked,
                    (SELECT COUNT(*) FROM don_dat_tour dd WHERE dd.id_lich_khoi_hanh = l.id_lich_khoi_hanh) AS order_count
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             WHERE l.id_lich_khoi_hanh = ?' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute([$id]);
        $schedule = $statement->fetch();
        return $schedule ? $this->normalizeSchedule($schedule) : null;
    }

    /** Danh sách khách theo chuyến: đơn đặt và người tham gia của một lịch. */
    public function getScheduleCustomers(int $id): ?array
    {
        $schedule = $this->findManagedSchedule($id);
        if ($schedule === null) {
            return null;
        }
        $statement = $this->connection->prepare(
            "SELECT dd.id_don_dat, dd.ma_don, dd.ho_ten, dd.email, dd.so_dien_thoai, dd.dia_chi,
                    dd.so_nguoi_lon, dd.so_tre_em, dd.tong_thanh_toan, dd.trang_thai, dd.ghi_chu,
                    dd.tao_luc, tt.ma_giao_dich
             FROM don_dat_tour dd
             LEFT JOIN thanh_toan tt ON tt.id_don_dat = dd.id_don_dat AND tt.trang_thai = 'thanh_cong'
             WHERE dd.id_lich_khoi_hanh = ?
             ORDER BY dd.tao_luc, dd.id_don_dat"
        );
        $statement->execute([$id]);
        $orders = $statement->fetchAll();
        $participants = $this->getParticipantsBySchedule($id);

        $totals = ['orders' => 0, 'guests' => 0, 'cancelled_orders' => 0, 'revenue' => 0];
        foreach ($orders as &$order) {
            $order['so_nguoi_lon'] = (int) $order['so_nguoi_lon'];
            $order['so_tre_em'] = (int) $order['so_tre_em'];
            $order['so_khach'] = $order['so_nguoi_lon'] + $order['so_tre_em'];
            $order['tong_thanh_toan'] = (int) $order['tong_thanh_toan'];
            $order['participants'] = $participants[(int) $order['id_don_dat']] ?? [];
            if ($order['trang_thai'] === 'da_huy') {
                $totals['cancelled_orders']++;
            } else {
                $totals['orders']++;
                $totals['guests'] += $order['so_khach'];
                $totals['revenue'] += $order['tong_thanh_toan'];
            }
        }
        unset($order);

        return ['schedule' => $schedule, 'orders' => $orders, 'totals' => $totals];
    }

    /** @return array<int, array<int, array<string, mixed>>> Người tham gia nhóm theo id đơn. */
    private function getParticipantsBySchedule(int $scheduleId): array
    {
        $statement = $this->connection->prepare(
            'SELECT ct.id_don_dat, ct.thu_tu, ct.ho_ten, ct.loai_khach, ct.ngay_sinh, ct.ghi_chu
             FROM chi_tiet_don_dat ct
             JOIN don_dat_tour dd ON dd.id_don_dat = ct.id_don_dat
             WHERE dd.id_lich_khoi_hanh = ?
             ORDER BY ct.id_don_dat, ct.thu_tu'
        );
        $statement->execute([$scheduleId]);
        $grouped = [];
        foreach ($statement->fetchAll() as $participant) {
            $grouped[(int) $participant['id_don_dat']][] = [
                'ho_ten' => $participant['ho_ten'],
                'loai_khach' => $participant['loai_khach'],
                'ngay_sinh' => $participant['ngay_sinh'],
                'ghi_chu' => (string) $participant['ghi_chu'],
            ];
        }
        return $grouped;
    }

    private function normalizeSchedule(array $schedule): array
    {
        foreach (['id_lich_khoi_hanh', 'id_tour', 'so_ngay', 'gia_nguoi_lon', 'gia_tre_em', 'so_cho', 'booked'] as $key) {
            $schedule[$key] = (int) $schedule[$key];
        }
        $schedule['seats_left'] = max(0, $schedule['so_cho'] - $schedule['booked']);
        $schedule['order_count'] = (int) ($schedule['order_count'] ?? 0);
        $schedule['is_upcoming'] = $schedule['ngay_khoi_hanh'] >= $this->now()->format('Y-m-d');
        $schedule['is_sold_out'] = $schedule['seats_left'] < 1;
        $schedule['deadline_passed'] = $schedule['han_dat'] !== null && $schedule['han_dat'] < $this->now()->format('Y-m-d H:i:s');
        $schedule['status_label'] = self::STATUSES[$schedule['trang_thai']] ?? $schedule['trang_thai'];
        $schedule['accepting'] = $schedule['trang_thai'] === 'open'
            && $schedule['is_upcoming'] && !$schedule['deadline_passed'] && !$schedule['is_sold_out'];
        return $schedule;
    }

    /**
     * Kiểm tra dữ liệu form. $current là lịch đang sửa (null khi thêm mới).
     *
     * @return array<string, string> Lỗi theo tên trường, rỗng khi hợp lệ.
     */
    public function validateSchedule(array $values, array $tours, ?array $current = null): array
    {
        $errors = [];
        $today = $this->now()->format('Y-m-d');

        $tourId = (int) ($values['id_tour'] ?? 0);
        $tour = null;
        foreach ($tours as $option) {
            if ((int) $option['id_tour'] === $tourId) {
                $tour = $option;
                break;
            }
        }
        if ($tour === null) {
            $errors['id_tour'] = 'Vui lòng chọn tour cho lịch khởi hành.';
        } elseif ($current !== null && (int) $current['id_tour'] !== $tourId && $current['booked'] > 0) {
            $errors['id_tour'] = 'Lịch đã có khách đặt nên không đổi được tour.';
        }

        $departure = $this->parseDate((string) ($values['ngay_khoi_hanh'] ?? ''));
        if ($departure === null) {
            $errors['ngay_khoi_hanh'] = 'Vui lòng chọn ngày khởi hành hợp lệ.';
        } elseif ($departure < $today && ($current === null || $current['ngay_khoi_hanh'] !== $departure)) {
            $errors['ngay_khoi_hanh'] = 'Ngày khởi hành không được ở quá khứ.';
        }

        $returnDate = $this->parseDate((string) ($values['ngay_ve'] ?? ''));
        if ($returnDate === null) {
            $errors['ngay_ve'] = 'Vui lòng chọn ngày về hợp lệ.';
        } elseif ($departure !== null && $returnDate < $departure) {
            $errors['ngay_ve'] = 'Ngày về phải bằng hoặc sau ngày khởi hành.';
        }

        $origin = trim((string) ($values['diem_khoi_hanh'] ?? ''));
        if (mb_strlen($origin, 'UTF-8') < 2) {
            $errors['diem_khoi_hanh'] = 'Vui lòng nhập điểm khởi hành (ít nhất 2 ký tự).';
        } elseif (mb_strlen($origin, 'UTF-8') > 120) {
            $errors['diem_khoi_hanh'] = 'Điểm khởi hành tối đa 120 ký tự.';
        }

        foreach (['gia_nguoi_lon' => 'Giá người lớn', 'gia_tre_em' => 'Giá trẻ em'] as $field => $label) {
            $amount = $this->parseAmount((string) ($values[$field] ?? ''));
            if ($amount === null) {
                $errors[$field] = $label . ' phải là số tiền hợp lệ (từ 0 đến 9.999.999.999).';
            }
        }

        $capacity = filter_var($values['so_cho'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($capacity === false || $capacity === null) {
            $errors['so_cho'] = 'Số chỗ phải là số nguyên từ 1 đến 65.535.';
        } elseif ($current !== null && $capacity < $current['booked']) {
            $errors['so_cho'] = 'Số chỗ không được nhỏ hơn ' . $current['booked'] . ' chỗ đã đặt.';
        }

        $deadlineInput = trim((string) ($values['han_dat'] ?? ''));
        if ($deadlineInput !== '') {
            $deadline = $this->parseDateTime($deadlineInput);
            if ($deadline === null) {
                $errors['han_dat'] = 'Hạn đặt không đúng định dạng ngày giờ.';
            } elseif ($departure !== null && $deadline->format('Y-m-d') > $departure) {
                $errors['han_dat'] = 'Hạn đặt phải trước hoặc trong ngày khởi hành.';
            } elseif ($deadline < $this->now() && ($current === null || $current['han_dat'] !== $deadline->format('Y-m-d H:i:s'))) {
                $errors['han_dat'] = 'Hạn đặt đã qua, vui lòng chọn thời điểm khác.';
            }
        }

        if (!isset(self::STATUSES[(string) ($values['trang_thai'] ?? '')])) {
            $errors['trang_thai'] = 'Trạng thái lịch không hợp lệ.';
        }

        return $errors;
    }

    /** Thêm hoặc cập nhật một lịch; trả về id lịch đã lưu. */
    public function saveManagedSchedule(array $values, ?int $id = null): int
    {
        $origin = trim($values['diem_khoi_hanh']);
        $deadlineInput = trim((string) ($values['han_dat'] ?? ''));
        $deadline = $deadlineInput === '' ? null : $this->parseDateTime($deadlineInput);
        $parameters = [
            (int) $values['id_tour'],
            $values['ngay_khoi_hanh'],
            $values['ngay_ve'],
            $origin,
            (int) $this->parseAmount($values['gia_nguoi_lon']),
            (int) $this->parseAmount($values['gia_tre_em']),
            (int) $values['so_cho'],
            $deadline === null ? null : $deadline->format('Y-m-d H:i:s'),
            $values['trang_thai'],
        ];

        $this->connection->beginTransaction();
        try {
            if ($id === null) {
                $statement = $this->connection->prepare(
                    'INSERT INTO lich_khoi_hanh (id_tour, ngay_khoi_hanh, ngay_ve, diem_khoi_hanh, gia_nguoi_lon,
                        gia_tre_em, so_cho, han_dat, trang_thai)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $statement->execute($parameters);
                $id = (int) $this->connection->lastInsertId();
            } else {
                // Khóa dòng để số chỗ không bị cắt xuống dưới số khách vừa đặt trong lúc sửa.
                $locked = $this->findManagedSchedule($id, true);
                if ($locked === null) {
                    throw new DomainException('Không tìm thấy lịch khởi hành cần cập nhật.');
                }
                if ((int) $parameters[6] < $locked['booked']) {
                    throw new DomainException('Số chỗ không được nhỏ hơn ' . $locked['booked'] . ' chỗ đã đặt.');
                }
                if ((int) $parameters[0] !== $locked['id_tour'] && $locked['booked'] > 0) {
                    throw new DomainException('Lịch đã có khách đặt nên không đổi được tour.');
                }
                $parameters[] = $id;
                $statement = $this->connection->prepare(
                    'UPDATE lich_khoi_hanh SET id_tour = ?, ngay_khoi_hanh = ?, ngay_ve = ?, diem_khoi_hanh = ?,
                        gia_nguoi_lon = ?, gia_tre_em = ?, so_cho = ?, han_dat = ?, trang_thai = ?
                     WHERE id_lich_khoi_hanh = ?'
                );
                $statement->execute($parameters);
            }
            $this->connection->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    /** Xóa lịch chưa có đơn đặt; lịch đã có đơn phải chuyển trạng thái hủy. */
    public function deleteManagedSchedule(int $id): void
    {
        $this->connection->beginTransaction();
        try {
            $schedule = $this->findManagedSchedule($id, true);
            if ($schedule === null) {
                throw new DomainException('Không tìm thấy lịch khởi hành cần xóa.');
            }
            $statement = $this->connection->prepare('SELECT COUNT(*) FROM don_dat_tour WHERE id_lich_khoi_hanh = ?');
            $statement->execute([$id]);
            $orders = (int) $statement->fetchColumn();
            if ($orders > 0) {
                throw new DomainException('Lịch còn ' . $orders . ' đơn đặt nên không thể xóa. Hãy chuyển lịch sang trạng thái “Đã hủy”.');
            }
            $this->connection->prepare('DELETE FROM lich_khoi_hanh WHERE id_lich_khoi_hanh = ?')->execute([$id]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function parseDateTime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value, new DateTimeZone('Asia/Ho_Chi_Minh'));
            if ($date !== false && $date->format($format) === $value) {
                return $date;
            }
        }
        return null;
    }

    /** Giá là số nguyên đồng, không âm và vừa DECIMAL(12,0). */
    private function parseAmount(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }
        $amount = (int) $value;
        return $amount <= 9999999999 ? $amount : null;
    }
}
