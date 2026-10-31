<?php

/**
 * Đọc và ghi đơn đặt tour.
 * Mọi mức giá, mức giảm và tổng tiền đều được tính lại từ dữ liệu lịch khởi hành trong database,
 * không tin số tiền do trình duyệt gửi lên.
 */
class DonDatModel
{
    /** Nhãn trạng thái đơn dùng chung cho View khách hàng và View quản trị. */
    public const STATUS_LABELS = [
        'cho_xac_nhan' => 'Chờ xác nhận',
        'da_xac_nhan' => 'Đã xác nhận',
        'hoan_thanh' => 'Hoàn thành',
        'da_huy' => 'Đã hủy',
    ];

    /** Phương thức thanh toán mô phỏng. */
    public const PAYMENT_METHODS = [
        'chuyen_khoan' => 'Chuyển khoản ngân hàng',
        'the' => 'Thẻ ngân hàng',
        'vi_dien_tu' => 'Ví điện tử',
    ];

    /** @var PDO Kết nối database được truyền từ Controller. */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /** Ưu đãi nhóm: 5–9 khách giảm 5%, từ 10 khách giảm 8%, chỉ áp dụng mức cao nhất. */
    public static function discountRate(int $guests): int
    {
        return $guests >= 10 ? 8 : ($guests >= 5 ? 5 : 0);
    }

    /** Tính tiền của một đơn theo đơn giá lịch khởi hành; làm tròn tới đồng. */
    public static function calculateTotals(int $adults, int $children, int $adultPrice, int $childPrice): array
    {
        $subtotal = $adults * $adultPrice + $children * $childPrice;
        $rate = self::discountRate($adults + $children);
        $discount = (int) round($subtotal * $rate / 100);

        return [
            'so_nguoi_lon' => $adults,
            'so_tre_em' => $children,
            'so_khach' => $adults + $children,
            'tong_goc' => $subtotal,
            'muc_giam' => $rate,
            'tien_giam' => $discount,
            'tong_thanh_toan' => $subtotal - $discount,
        ];
    }

    /** Đếm số khách của các đơn chưa hủy trên một lịch khởi hành. */
    private function soldSeats(int $scheduleId): int
    {
        $statement = $this->connection->prepare(
            "SELECT COALESCE(SUM(so_nguoi_lon + so_tre_em), 0)
             FROM don_dat_tour
             WHERE id_lich_khoi_hanh = ? AND trang_thai <> 'da_huy'"
        );
        $statement->execute([$scheduleId]);
        return (int) $statement->fetchColumn();
    }

    /** Các lịch còn nhận đặt của một tour đã công bố, kèm số chỗ còn lại. */
    public function getTourSchedules(string $type, string $code): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $statement = $this->connection->prepare(
            "SELECT l.id_lich_khoi_hanh AS id, l.ngay_khoi_hanh AS date, l.diem_khoi_hanh AS origin,
                    l.gia_nguoi_lon AS adult_price, l.gia_tre_em AS child_price,
                    l.so_cho AS capacity, l.han_dat, t.ten_tour,
                    l.so_cho - COALESCE((
                        SELECT SUM(dd.so_nguoi_lon + dd.so_tre_em) FROM don_dat_tour dd
                        WHERE dd.id_lich_khoi_hanh = l.id_lich_khoi_hanh AND dd.trang_thai <> 'da_huy'
                    ), 0) AS seats_left
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh
             JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc
             WHERE t.ma_tour = ? AND k.loai_tour = ? AND t.trang_thai = 'published' AND d.dang_hoat_dong = 1
               AND l.trang_thai = 'open' AND l.ngay_khoi_hanh >= ? AND (l.han_dat IS NULL OR l.han_dat >= ?)
             ORDER BY l.ngay_khoi_hanh, l.id_lich_khoi_hanh"
        );
        $statement->execute([$code, $type, $now->format('Y-m-d'), $now->format('Y-m-d H:i:s')]);

        $schedules = [];
        foreach ($statement->fetchAll() as $schedule) {
            $schedule['id'] = (int) $schedule['id'];
            $schedule['adult_price'] = (int) $schedule['adult_price'];
            $schedule['child_price'] = (int) $schedule['child_price'];
            $schedule['capacity'] = (int) $schedule['capacity'];
            $schedule['seats_left'] = max(0, (int) $schedule['seats_left']);
            $schedules[] = $schedule;
        }
        return $schedules;
    }

    /** Thông tin tour và một lịch khởi hành còn nhận đặt; null khi không hợp lệ. */
    public function findSchedule(int $scheduleId): ?array
    {
        if ($scheduleId <= 0) {
            return null;
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $statement = $this->connection->prepare(
            "SELECT l.id_lich_khoi_hanh AS id, l.id_tour, t.ma_tour, t.ten_tour, t.so_ngay, t.duong_dan_anh AS image,
                    d.ten_diem_den AS destination, k.loai_tour AS type,
                    l.ngay_khoi_hanh AS date, l.diem_khoi_hanh AS origin, l.gia_nguoi_lon AS adult_price,
                    l.gia_tre_em AS child_price, l.so_cho AS capacity, l.han_dat,
                    l.so_cho - COALESCE((
                        SELECT SUM(dd.so_nguoi_lon + dd.so_tre_em) FROM don_dat_tour dd
                        WHERE dd.id_lich_khoi_hanh = l.id_lich_khoi_hanh AND dd.trang_thai <> 'da_huy'
                    ), 0) AS seats_left
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh
             JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc
             WHERE l.id_lich_khoi_hanh = ? AND t.trang_thai = 'published' AND d.dang_hoat_dong = 1
               AND l.trang_thai = 'open' AND l.ngay_khoi_hanh >= ? AND (l.han_dat IS NULL OR l.han_dat >= ?)"
        );
        $statement->execute([$scheduleId, $now->format('Y-m-d'), $now->format('Y-m-d H:i:s')]);
        $schedule = $statement->fetch();
        if (!$schedule) {
            return null;
        }
        $schedule['id'] = (int) $schedule['id'];
        $schedule['id_tour'] = (int) $schedule['id_tour'];
        $schedule['so_ngay'] = (int) $schedule['so_ngay'];
        $schedule['adult_price'] = (int) $schedule['adult_price'];
        $schedule['child_price'] = (int) $schedule['child_price'];
        $schedule['capacity'] = (int) $schedule['capacity'];
        $schedule['seats_left'] = max(0, (int) $schedule['seats_left']);
        return $schedule;
    }

    private function maDonExists(string $maDon): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM don_dat_tour WHERE ma_don = ?');
        $statement->execute([$maDon]);
        return (bool) $statement->fetchColumn();
    }

    private function makeMaDon(DateTimeImmutable $now): string
    {
        do {
            $maDon = 'OD' . $now->format('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->maDonExists($maDon));
        return $maDon;
    }

    /**
     * Tạo đơn đặt tour trong một transaction: khóa lịch khởi hành, kiểm tra chỗ còn lại,
     * chụp lại đơn giá và ghi danh sách người tham gia.
     *
     * @param array<int, array<string, mixed>> $participants Mỗi phần tử có ho_ten, loai_khach, ngay_sinh, ghi_chu.
     * @throws DomainException Khi lịch đóng, hết chỗ hoặc dữ liệu khách không hợp lệ.
     */
    public function createOrder(int $accountId, int $scheduleId, array $contact, array $participants): array
    {
        $adults = 0;
        $children = 0;
        foreach ($participants as $participant) {
            if (($participant['loai_khach'] ?? '') === 'tre_em') {
                $children++;
            } else {
                $adults++;
            }
        }
        if ($adults < 1) {
            throw new DomainException('Đơn đặt cần ít nhất một người lớn.');
        }
        if ($adults + $children > 99) {
            throw new DomainException('Mỗi đơn đặt nhận tối đa 99 khách.');
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $this->connection->beginTransaction();
        try {
            $lock = $this->connection->prepare(
                "SELECT l.id_lich_khoi_hanh, l.ngay_khoi_hanh, l.diem_khoi_hanh, l.gia_nguoi_lon,
                        l.gia_tre_em, l.so_cho, l.han_dat, l.trang_thai, t.ma_tour, t.ten_tour
                 FROM lich_khoi_hanh l
                 JOIN tour t ON t.id_tour = l.id_tour
                 WHERE l.id_lich_khoi_hanh = ? AND t.trang_thai = 'published'
                 FOR UPDATE"
            );
            $lock->execute([$scheduleId]);
            $schedule = $lock->fetch();
            if (!$schedule || $schedule['trang_thai'] !== 'open'
                || $schedule['ngay_khoi_hanh'] < $now->format('Y-m-d')
                || ($schedule['han_dat'] !== null && $schedule['han_dat'] < $now->format('Y-m-d H:i:s'))) {
                throw new DomainException('Lịch khởi hành này đã đóng hoặc đã qua hạn đặt.');
            }

            $seatsLeft = (int) $schedule['so_cho'] - $this->soldSeats($scheduleId);
            if ($seatsLeft <= 0) {
                throw new DomainException('Lịch khởi hành này đã hết chỗ.');
            }
            if ($adults + $children > $seatsLeft) {
                throw new DomainException('Lịch khởi hành này chỉ còn ' . $seatsLeft . ' chỗ.');
            }

            $totals = self::calculateTotals(
                $adults,
                $children,
                (int) $schedule['gia_nguoi_lon'],
                (int) $schedule['gia_tre_em']
            );
            $maDon = $this->makeMaDon($now);
            $insert = $this->connection->prepare(
                'INSERT INTO don_dat_tour (ma_don, id_tai_khoan, id_lich_khoi_hanh, ma_tour, ten_tour, ngay_khoi_hanh,
                    diem_khoi_hanh, gia_nguoi_lon, gia_tre_em, so_nguoi_lon, so_tre_em, tong_goc, muc_giam, tien_giam,
                    tong_thanh_toan, ho_ten, email, so_dien_thoai, dia_chi, ghi_chu, trang_thai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $maDon,
                $accountId,
                $scheduleId,
                $schedule['ma_tour'],
                $schedule['ten_tour'],
                $schedule['ngay_khoi_hanh'],
                $schedule['diem_khoi_hanh'],
                (int) $schedule['gia_nguoi_lon'],
                (int) $schedule['gia_tre_em'],
                $totals['so_nguoi_lon'],
                $totals['so_tre_em'],
                $totals['tong_goc'],
                $totals['muc_giam'],
                $totals['tien_giam'],
                $totals['tong_thanh_toan'],
                $contact['ho_ten'] ?? '',
                $contact['email'] ?? '',
                $contact['so_dien_thoai'] ?? '',
                ($contact['dia_chi'] ?? '') === '' ? null : $contact['dia_chi'],
                ($contact['ghi_chu'] ?? '') === '' ? null : $contact['ghi_chu'],
                'cho_xac_nhan',
            ]);
            $orderId = (int) $this->connection->lastInsertId();

            $insertParticipant = $this->connection->prepare(
                'INSERT INTO chi_tiet_don_dat (id_don_dat, thu_tu, ho_ten, loai_khach, ngay_sinh, ghi_chu)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $order = 0;
            foreach ($participants as $participant) {
                $order++;
                $birthday = $participant['ngay_sinh'] ?? '';
                $note = $participant['ghi_chu'] ?? '';
                $insertParticipant->execute([
                    $orderId,
                    $order,
                    $participant['ho_ten'] ?? '',
                    ($participant['loai_khach'] ?? '') === 'tre_em' ? 'tre_em' : 'nguoi_lon',
                    $birthday === '' ? null : $birthday,
                    $note === '' ? null : $note,
                ]);
            }

            $this->connection->commit();
            return ['id_don_dat' => $orderId, 'ma_don' => $maDon, 'totals' => $totals, 'schedule' => $schedule];
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    private function orderSelect(): string
    {
        return "SELECT dd.*, tt.phuong_thuc, tt.ma_giao_dich, tt.so_tien AS so_tien_da_tra, tt.tao_luc AS thanh_toan_luc
                FROM don_dat_tour dd
                LEFT JOIN thanh_toan tt ON tt.id_don_dat = dd.id_don_dat AND tt.trang_thai = 'thanh_cong'";
    }

    /** @return array<int, array<string, mixed>> */
    public function getAccountOrders(int $accountId): array
    {
        $statement = $this->connection->prepare(
            $this->orderSelect() . ' WHERE dd.id_tai_khoan = ? ORDER BY dd.tao_luc DESC, dd.id_don_dat DESC'
        );
        $statement->execute([$accountId]);
        return array_map([$this, 'normalizeOrder'], $statement->fetchAll());
    }

    /** Một đơn của đúng chủ tài khoản; trả về null khi không có hoặc không thuộc tài khoản. */
    public function findAccountOrder(int $accountId, string $maDon): ?array
    {
        $statement = $this->connection->prepare($this->orderSelect() . ' WHERE dd.ma_don = ? AND dd.id_tai_khoan = ?');
        $statement->execute([$maDon, $accountId]);
        $order = $statement->fetch();
        return $order ? $this->hydrateOrder($order) : null;
    }

    public function findOrderById(int $orderId): ?array
    {
        $statement = $this->connection->prepare($this->orderSelect() . ' WHERE dd.id_don_dat = ?');
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        return $order ? $this->hydrateOrder($order) : null;
    }

    /** Tra cứu đơn theo mã đơn, dùng cho khu vực quản trị. */
    public function findOrderByCode(string $maDon): ?array
    {
        $statement = $this->connection->prepare($this->orderSelect() . ' WHERE dd.ma_don = ?');
        $statement->execute([$maDon]);
        $order = $statement->fetch();
        return $order ? $this->hydrateOrder($order) : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function getParticipants(int $orderId): array
    {
        $statement = $this->connection->prepare(
            'SELECT ho_ten, loai_khach, ngay_sinh, ghi_chu FROM chi_tiet_don_dat WHERE id_don_dat = ? ORDER BY thu_tu'
        );
        $statement->execute([$orderId]);
        return $statement->fetchAll();
    }

    private function normalizeOrder(array $order): array
    {
        foreach (['id_don_dat', 'so_nguoi_lon', 'so_tre_em', 'gia_nguoi_lon', 'gia_tre_em',
            'tong_goc', 'muc_giam', 'tien_giam', 'tong_thanh_toan'] as $key) {
            $order[$key] = (int) $order[$key];
        }
        $order['so_khach'] = $order['so_nguoi_lon'] + $order['so_tre_em'];
        $order['da_thanh_toan'] = $order['ma_giao_dich'] !== null;
        $order['trang_thai_nhan'] = self::STATUS_LABELS[$order['trang_thai']] ?? $order['trang_thai'];
        return $order;
    }

    private function hydrateOrder(array $order): array
    {
        $order = $this->normalizeOrder($order);
        $order['participants'] = $this->getParticipants($order['id_don_dat']);
        return $order;
    }

    /** Khách tự hủy đơn đang chờ xác nhận. */
    public function cancelAccountOrder(int $accountId, string $maDon): void
    {
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'SELECT id_don_dat, trang_thai FROM don_dat_tour WHERE ma_don = ? AND id_tai_khoan = ? FOR UPDATE'
            );
            $statement->execute([$maDon, $accountId]);
            $order = $statement->fetch();
            if (!$order) {
                throw new DomainException('Không tìm thấy đơn đặt này.');
            }
            if ($order['trang_thai'] !== 'cho_xac_nhan') {
                throw new DomainException('Chỉ hủy được đơn đang chờ xác nhận. Vui lòng liên hệ OnTrip.');
            }
            $this->connection->prepare("UPDATE don_dat_tour SET trang_thai = 'da_huy' WHERE id_don_dat = ?")
                ->execute([(int) $order['id_don_dat']]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    /** Thanh toán mô phỏng: ghi nhận giao dịch thành công cho đơn của chính khách hàng. */
    public function payAccountOrder(int $accountId, string $maDon, string $method): string
    {
        if (!isset(self::PAYMENT_METHODS[$method])) {
            throw new DomainException('Phương thức thanh toán không hợp lệ.');
        }
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare(
                'SELECT id_don_dat, trang_thai, tong_thanh_toan FROM don_dat_tour WHERE ma_don = ? AND id_tai_khoan = ? FOR UPDATE'
            );
            $statement->execute([$maDon, $accountId]);
            $order = $statement->fetch();
            if (!$order) {
                throw new DomainException('Không tìm thấy đơn đặt này.');
            }
            if ($order['trang_thai'] === 'da_huy') {
                throw new DomainException('Đơn đã hủy nên không thể thanh toán.');
            }
            if ($order['trang_thai'] === 'hoan_thanh') {
                throw new DomainException('Đơn đã hoàn thành nên không thể thanh toán.');
            }
            $paid = $this->connection->prepare('SELECT 1 FROM thanh_toan WHERE id_don_dat = ?');
            $paid->execute([(int) $order['id_don_dat']]);
            if ($paid->fetchColumn()) {
                throw new DomainException('Đơn này đã được thanh toán.');
            }
            $maGiaoDich = 'TT' . strtoupper(bin2hex(random_bytes(5)));
            $this->connection->prepare(
                "INSERT INTO thanh_toan (id_don_dat, phuong_thuc, so_tien, ma_giao_dich, trang_thai)
                 VALUES (?, ?, ?, ?, 'thanh_cong')"
            )->execute([(int) $order['id_don_dat'], $method, (int) $order['tong_thanh_toan'], $maGiaoDich]);
            $this->connection->commit();
            return $maGiaoDich;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    /** Trạng thái được phép chuyển tới từ trạng thái hiện tại. */
    public static function allowedTransitions(string $status): array
    {
        return match ($status) {
            'cho_xac_nhan' => ['da_xac_nhan', 'da_huy'],
            'da_xac_nhan' => ['hoan_thanh', 'da_huy'],
            default => [],
        };
    }

    /** Quản trị đổi trạng thái đơn theo đúng luồng nghiệp vụ. */
    public function updateStatus(int $orderId, string $status): bool
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            throw new DomainException('Trạng thái đơn không hợp lệ.');
        }
        $this->connection->beginTransaction();
        try {
            $statement = $this->connection->prepare('SELECT trang_thai FROM don_dat_tour WHERE id_don_dat = ? FOR UPDATE');
            $statement->execute([$orderId]);
            $current = $statement->fetchColumn();
            if ($current === false) {
                throw new DomainException('Không tìm thấy đơn đặt này.');
            }
            if ($current === $status) {
                $this->connection->commit();
                return false;
            }
            if (!in_array($status, self::allowedTransitions((string) $current), true)) {
                throw new DomainException(
                    'Không thể chuyển đơn từ “' . (self::STATUS_LABELS[$current] ?? $current)
                    . '” sang “' . self::STATUS_LABELS[$status] . '”.'
                );
            }
            $this->connection->prepare('UPDATE don_dat_tour SET trang_thai = ? WHERE id_don_dat = ?')
                ->execute([$status, $orderId]);
            $this->connection->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $exception;
        }
    }

    /** Số liệu tổng quan cho trang quản lý đơn. */
    public function getAdminSummary(): array
    {
        $summary = ['total' => 0, 'cho_xac_nhan' => 0, 'da_xac_nhan' => 0, 'hoan_thanh' => 0, 'da_huy' => 0, 'doanh_thu' => 0];
        $statement = $this->connection->query(
            "SELECT COUNT(*) AS total,
                    SUM(trang_thai = 'cho_xac_nhan') AS cho_xac_nhan,
                    SUM(trang_thai = 'da_xac_nhan') AS da_xac_nhan,
                    SUM(trang_thai = 'hoan_thanh') AS hoan_thanh,
                    SUM(trang_thai = 'da_huy') AS da_huy
             FROM don_dat_tour"
        );
        $row = $statement->fetch();
        if ($row) {
            foreach ($summary as $key => $value) {
                if ($key !== 'doanh_thu') {
                    $summary[$key] = (int) ($row[$key] ?? 0);
                }
            }
        }
        $summary['doanh_thu'] = (int) $this->connection->query(
            "SELECT COALESCE(SUM(tt.so_tien), 0)
             FROM thanh_toan tt
             JOIN don_dat_tour dd ON dd.id_don_dat = tt.id_don_dat
             WHERE tt.trang_thai = 'thanh_cong' AND dd.trang_thai <> 'da_huy'"
        )->fetchColumn();
        return $summary;
    }

    /**
     * Danh sách đơn cho quản trị, có lọc theo trạng thái và từ khóa.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function getAdminOrders(string $status, string $keyword, int $page, int $perPage = 10): array
    {
        $conditions = [];
        $parameters = [];
        if ($status !== '' && isset(self::STATUS_LABELS[$status])) {
            $conditions[] = 'dd.trang_thai = ?';
            $parameters[] = $status;
        }
        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $conditions[] = '(dd.ma_don LIKE ? OR dd.ho_ten LIKE ? OR dd.email LIKE ? OR dd.so_dien_thoai LIKE ? OR dd.ten_tour LIKE ?)';
            array_push($parameters, $like, $like, $like, $like, $like);
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $count = $this->connection->prepare('SELECT COUNT(*) FROM don_dat_tour dd' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $this->connection->prepare(
            $this->orderSelect() . $where . ' ORDER BY dd.tao_luc DESC, dd.id_don_dat DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($parameters);
        return [
            'items' => array_map([$this, 'normalizeOrder'], $statement->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }
}
