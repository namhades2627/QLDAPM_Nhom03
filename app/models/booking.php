<?php

require_once __DIR__ . '/GroupPricing.php';

/**
 * Lịch khởi hành, chỗ còn và đơn đặt tour.
 *
 * Quy ước chỗ: so_cho là sức chứa riêng của từng lịch; chỗ đã dùng là tổng
 * (so_nguoi_lon + so_tre_em) của các đơn chưa hủy. Chỗ còn = so_cho - chỗ đã dùng.
 * Đơn ở trạng thái cho_xac_nhan vẫn giữ chỗ để khách không bị mất suất trong lúc chờ.
 */
class ScheduleModel
{
    /** Các trạng thái đơn còn giữ chỗ. */
    public const HOLDING_STATUSES = ['cho_xac_nhan', 'da_xac_nhan'];

    /** Số khách tối đa cho một đơn, tránh giá trị quá lớn từ biểu mẫu. */
    public const MAX_GUESTS_PER_ORDER = 50;

    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /** Điều kiện SQL dùng lại ở nhiều truy vấn để chỉ lấy lịch còn nhận đặt. */
    private function openScheduleCondition(): string
    {
        return "l.trang_thai = 'open' AND l.ngay_khoi_hanh >= CURDATE()
                AND (l.han_dat IS NULL OR l.han_dat >= NOW())";
    }

    private function seatsSubquery(): string
    {
        return "LEFT JOIN (
                    SELECT id_lich_khoi_hanh, SUM(so_nguoi_lon + so_tre_em) AS so_khach
                    FROM don_dat_tour
                    WHERE trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')
                    GROUP BY id_lich_khoi_hanh
                ) dk ON dk.id_lich_khoi_hanh = l.id_lich_khoi_hanh";
    }

    private const SCHEDULE_COLUMNS = "l.id_lich_khoi_hanh, l.id_tour, l.ngay_khoi_hanh, l.ngay_ve,
                l.diem_khoi_hanh, l.gia_nguoi_lon, l.gia_tre_em, l.so_cho, l.han_dat, l.trang_thai,
                COALESCE(dk.so_khach, 0) AS so_khach_da_dat,
                GREATEST(l.so_cho - COALESCE(dk.so_khach, 0), 0) AS so_cho_con";

    /** Lịch của một tour (theo mã tour) kèm chỗ còn, dùng cho trang đặt tour. */
    public function getSchedulesByTourCode(string $tourCode): array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::SCHEDULE_COLUMNS . '
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             ' . $this->seatsSubquery() . '
             WHERE t.ma_tour = ? AND ' . $this->openScheduleCondition() . '
             ORDER BY l.ngay_khoi_hanh, l.id_lich_khoi_hanh'
        );
        $statement->execute([$tourCode]);
        return $statement->fetchAll();
    }

    /** Một lịch kèm thông tin tour để kiểm tra trước khi ghi đơn. */
    public function findSchedule(int $scheduleId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::SCHEDULE_COLUMNS . ', t.ma_tour, t.ten_tour, t.so_ngay, t.duong_dan_anh
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             ' . $this->seatsSubquery() . '
             WHERE l.id_lich_khoi_hanh = ?'
        );
        $statement->execute([$scheduleId]);
        $schedule = $statement->fetch();
        return $schedule ?: null;
    }

    /**
     * Lý do một lịch không thể nhận đơn mới (null nghĩa là còn nhận đặt).
     * Dùng chung cho trang xác nhận và bước ghi đơn để thông điệp luôn khớp nhau.
     */
    public function blockReason(array $schedule): ?string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        if (($schedule['trang_thai'] ?? '') !== 'open') {
            return 'Lịch khởi hành này đã đóng nhận đặt.';
        }
        if (($schedule['ngay_khoi_hanh'] ?? '') < $now->format('Y-m-d')) {
            return 'Lịch khởi hành này đã qua ngày đi.';
        }
        $deadline = $schedule['han_dat'] ?? null;
        if ($deadline !== null && $deadline !== '' && $deadline < $now->format('Y-m-d H:i:s')) {
            return 'Lịch khởi hành này đã hết hạn đặt chỗ.';
        }
        return null;
    }

    /**
     * Tạo đơn trong một transaction: khóa lịch, kiểm tra lại hạn đặt và chỗ còn,
     * tính tiền ở server rồi mới ghi. Trả về mã đơn vừa tạo.
     *
     * @return array{ok:bool, error:string, ma_don:?string, don:?array}
     */
    public function createBooking(int $scheduleId, int $accountId, array $input): array
    {
        $adults = (int) ($input['so_nguoi_lon'] ?? 0);
        $children = (int) ($input['so_tre_em'] ?? 0);
        $guests = $adults + $children;
        if ($adults < 1) {
            return ['ok' => false, 'error' => 'Đơn cần ít nhất 1 người lớn.', 'ma_don' => null, 'don' => null];
        }
        if ($guests > self::MAX_GUESTS_PER_ORDER) {
            return ['ok' => false, 'error' => 'Mỗi đơn đặt tối đa ' . self::MAX_GUESTS_PER_ORDER . ' khách.', 'ma_don' => null, 'don' => null];
        }

        try {
            $this->connection->beginTransaction();
            $statement = $this->connection->prepare(
                "SELECT id_lich_khoi_hanh, id_tour, ngay_khoi_hanh, ngay_ve, gia_nguoi_lon, gia_tre_em,
                        so_cho, han_dat, trang_thai
                 FROM lich_khoi_hanh WHERE id_lich_khoi_hanh = ? FOR UPDATE"
            );
            $statement->execute([$scheduleId]);
            $schedule = $statement->fetch();
            if (!$schedule) {
                $this->connection->rollBack();
                return ['ok' => false, 'error' => 'Lịch khởi hành không tồn tại.', 'ma_don' => null, 'don' => null];
            }

            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
            $blocked = $this->blockReason($schedule);
            if ($blocked !== null) {
                $this->connection->rollBack();
                return ['ok' => false, 'error' => $blocked, 'ma_don' => null, 'don' => null];
            }

            $statement = $this->connection->prepare(
                'SELECT COALESCE(SUM(so_nguoi_lon + so_tre_em), 0) FROM don_dat_tour
                 WHERE id_lich_khoi_hanh = ? AND trang_thai IN (?, ?)'
            );
            $statement->execute([$scheduleId, ...self::HOLDING_STATUSES]);
            $booked = (int) $statement->fetchColumn();
            $seatsLeft = (int) $schedule['so_cho'] - $booked;
            if ($guests > $seatsLeft) {
                $this->connection->rollBack();
                $message = $seatsLeft > 0
                    ? 'Lịch này chỉ còn ' . $seatsLeft . ' chỗ, không đủ cho ' . $guests . ' khách.'
                    : 'Lịch khởi hành này đã hết chỗ.';
                return ['ok' => false, 'error' => $message, 'ma_don' => null, 'don' => null];
            }

            // Tiền luôn tính lại ở server; giá trị từ biểu mẫu chỉ để hiển thị.
            $pricing = GroupPricing::calculate(
                $adults,
                $children,
                (int) $schedule['gia_nguoi_lon'],
                (int) $schedule['gia_tre_em']
            );
            $maDon = $this->generateOrderCode($now);
            $statement = $this->connection->prepare(
                'INSERT INTO don_dat_tour
                    (ma_don, id_lich_khoi_hanh, id_tai_khoan, ho_ten, email, so_dien_thoai,
                     so_nguoi_lon, so_tre_em, gia_nguoi_lon, gia_tre_em, tong_goc, muc_giam,
                     tien_giam, tong_thanh_toan, ghi_chu, trang_thai)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $maDon, $scheduleId, $accountId,
                $input['ho_ten'], $input['email'], $input['so_dien_thoai'],
                $adults, $children,
                (int) $schedule['gia_nguoi_lon'], (int) $schedule['gia_tre_em'],
                $pricing['subtotal'], $pricing['rate'], $pricing['discount'], $pricing['total'],
                $input['ghi_chu'] !== '' ? $input['ghi_chu'] : null, 'cho_xac_nhan',
            ]);
            $orderId = (int) $this->connection->lastInsertId();
            $this->connection->commit();
        } catch (PDOException $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            error_log('OnTrip booking error: ' . $exception->getMessage());
            return ['ok' => false, 'error' => 'Không thể tạo đơn lúc này, vui lòng thử lại.', 'ma_don' => null, 'don' => null];
        }

        return ['ok' => true, 'error' => '', 'ma_don' => $maDon, 'don' => $this->findOrderById($orderId)];
    }

    /** Mã đơn dễ đọc, không trùng nhờ kiểm tra trong bảng. */
    private function generateOrderCode(DateTimeImmutable $now): string
    {
        $statement = $this->connection->prepare('SELECT 1 FROM don_dat_tour WHERE ma_don = ?');
        do {
            $code = 'OT' . $now->format('ymd') . strtoupper(bin2hex(random_bytes(3)));
            $statement->execute([$code]);
        } while ($statement->fetchColumn() !== false);
        return $code;
    }

    public function findOrderById(int $orderId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT d.*, l.ngay_khoi_hanh, l.ngay_ve, l.diem_khoi_hanh, l.han_dat, l.trang_thai AS lich_trang_thai,
                    t.ma_tour, t.ten_tour, t.so_ngay, t.duong_dan_anh
             FROM don_dat_tour d
             JOIN lich_khoi_hanh l ON l.id_lich_khoi_hanh = d.id_lich_khoi_hanh
             JOIN tour t ON t.id_tour = l.id_tour
             WHERE d.id_don_dat = ?'
        );
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        return $order ?: null;
    }

    /** Tra đơn theo mã, chỉ trả về khi đúng chủ sở hữu. */
    public function findOrderByCode(string $code, int $accountId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id_don_dat FROM don_dat_tour WHERE ma_don = ? AND id_tai_khoan = ?'
        );
        $statement->execute([$code, $accountId]);
        $id = $statement->fetchColumn();
        return $id === false ? null : $this->findOrderById((int) $id);
    }

    public function getOrdersForAccount(int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT d.*, l.ngay_khoi_hanh, l.ngay_ve, l.diem_khoi_hanh, l.trang_thai AS lich_trang_thai,
                    t.ma_tour, t.ten_tour, t.so_ngay, t.duong_dan_anh
             FROM don_dat_tour d
             JOIN lich_khoi_hanh l ON l.id_lich_khoi_hanh = d.id_lich_khoi_hanh
             JOIN tour t ON t.id_tour = l.id_tour
             WHERE d.id_tai_khoan = ?
             ORDER BY d.tao_luc DESC, d.id_don_dat DESC'
        );
        $statement->execute([$accountId]);
        return $statement->fetchAll();
    }

    /**
     * Khách tự hủy đơn: chỉ khi đơn còn giữ chỗ và lịch chưa khởi hành.
     * Hủy xong chỗ được trả lại ngay vì đơn không còn tính vào tổng khách.
     */
    public function cancelOrder(int $orderId, int $accountId): array
    {
        $statement = $this->connection->prepare(
            'SELECT d.id_don_dat, d.trang_thai, l.ngay_khoi_hanh
             FROM don_dat_tour d
             JOIN lich_khoi_hanh l ON l.id_lich_khoi_hanh = d.id_lich_khoi_hanh
             WHERE d.id_don_dat = ? AND d.id_tai_khoan = ?'
        );
        $statement->execute([$orderId, $accountId]);
        $order = $statement->fetch();
        if (!$order) {
            return ['ok' => false, 'error' => 'Không tìm thấy đơn của bạn.'];
        }
        if (!in_array($order['trang_thai'], self::HOLDING_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Đơn này không thể hủy ở trạng thái hiện tại.'];
        }
        $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        if ($order['ngay_khoi_hanh'] <= $today) {
            return ['ok' => false, 'error' => 'Tour đã hoặc đang khởi hành, không thể hủy.'];
        }
        $statement = $this->connection->prepare(
            "UPDATE don_dat_tour SET trang_thai = 'da_huy'
             WHERE id_don_dat = ? AND trang_thai IN (?, ?)"
        );
        $statement->execute([$orderId, ...self::HOLDING_STATUSES]);
        if ($statement->rowCount() !== 1) {
            return ['ok' => false, 'error' => 'Đơn vừa được cập nhật, vui lòng tải lại trang.'];
        }
        return ['ok' => true, 'error' => ''];
    }

    /**
     * Danh sách lịch cho quản trị: lọc theo tour, khoảng ngày đi và trạng thái.
     *
     * @param array{tour:string,from:string,to:string,status:string} $filters
     */
    public function getSchedulesForAdmin(array $filters): array
    {
        $conditions = ['1 = 1'];
        $parameters = [];
        if ($filters['tour'] !== '') {
            $conditions[] = 't.ma_tour = ?';
            $parameters[] = $filters['tour'];
        }
        if ($filters['from'] !== '') {
            $conditions[] = 'l.ngay_khoi_hanh >= ?';
            $parameters[] = $filters['from'];
        }
        if ($filters['to'] !== '') {
            $conditions[] = 'l.ngay_khoi_hanh <= ?';
            $parameters[] = $filters['to'];
        }
        if (in_array($filters['status'], ['open', 'closed', 'cancelled'], true)) {
            $conditions[] = 'l.trang_thai = ?';
            $parameters[] = $filters['status'];
        }
        $statement = $this->connection->prepare(
            'SELECT ' . self::SCHEDULE_COLUMNS . ', t.ma_tour, t.ten_tour, t.so_ngay,
                    COALESCE(don.so_don, 0) AS so_don
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             ' . $this->seatsSubquery() . '
             LEFT JOIN (
                 SELECT id_lich_khoi_hanh, COUNT(*) AS so_don
                 FROM don_dat_tour WHERE trang_thai <> \'da_huy\'
                 GROUP BY id_lich_khoi_hanh
             ) don ON don.id_lich_khoi_hanh = l.id_lich_khoi_hanh
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY l.ngay_khoi_hanh, t.ma_tour'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /** Danh sách tour cho bộ lọc ở trang quản trị lịch khởi hành. */
    public function getTourOptions(): array
    {
        return $this->connection
            ->query('SELECT ma_tour, ten_tour FROM tour ORDER BY ten_tour')
            ->fetchAll();
    }

    /** Số liệu nhanh cho đầu trang quản trị lịch. */
    public function getScheduleStats(): array
    {
        $row = $this->connection->query(
            "SELECT COUNT(*) AS tong_lich,
                    COALESCE(SUM(trang_thai = 'open' AND ngay_khoi_hanh >= CURDATE()), 0) AS lich_mo,
                    COALESCE(SUM(so_cho), 0) AS tong_cho
             FROM lich_khoi_hanh"
        )->fetch();
        $booked = $this->connection->query(
            "SELECT COALESCE(SUM(so_nguoi_lon + so_tre_em), 0) FROM don_dat_tour
             WHERE trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')"
        )->fetchColumn();
        return [
            'tong_lich' => (int) $row['tong_lich'],
            'lich_mo' => (int) $row['lich_mo'],
            'tong_cho' => (int) $row['tong_cho'],
            'cho_da_dat' => (int) $booked,
        ];
    }

    /** Một lịch cho trang quản trị; đọc được cả lịch đã đóng hay đã hủy. */
    public function getScheduleDetail(int $scheduleId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT ' . self::SCHEDULE_COLUMNS . ', t.ma_tour, t.ten_tour, t.so_ngay, t.duong_dan_anh
             FROM lich_khoi_hanh l
             JOIN tour t ON t.id_tour = l.id_tour
             ' . $this->seatsSubquery() . '
             WHERE l.id_lich_khoi_hanh = ?'
        );
        $statement->execute([$scheduleId]);
        $schedule = $statement->fetch();
        return $schedule ?: null;
    }

    /** Danh sách khách của một chuyến, kèm tiền đã chốt trong đơn. */
    public function getPassengers(int $scheduleId): array
    {
        $statement = $this->connection->prepare(
            'SELECT id_don_dat, ma_don, ho_ten, email, so_dien_thoai, so_nguoi_lon, so_tre_em,
                    gia_nguoi_lon, gia_tre_em, tong_goc, muc_giam, tien_giam, tong_thanh_toan,
                    trang_thai, ghi_chu, tao_luc
             FROM don_dat_tour
             WHERE id_lich_khoi_hanh = ?
             ORDER BY FIELD(trang_thai, \'cho_xac_nhan\', \'da_xac_nhan\', \'hoan_thanh\', \'da_huy\'), tao_luc'
        );
        $statement->execute([$scheduleId]);
        return $statement->fetchAll();
    }

    /** Nhân viên/admin đổi trạng thái đơn; hủy đơn là trả chỗ ngay. */
    public function updateOrderStatus(int $orderId, string $status): array
    {
        $allowed = ['cho_xac_nhan', 'da_xac_nhan', 'hoan_thanh', 'da_huy'];
        if (!in_array($status, $allowed, true)) {
            return ['ok' => false, 'error' => 'Trạng thái đơn không hợp lệ.'];
        }
        $statement = $this->connection->prepare(
            'SELECT id_don_dat, trang_thai FROM don_dat_tour WHERE id_don_dat = ?'
        );
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        if (!$order) {
            return ['ok' => false, 'error' => 'Không tìm thấy đơn.'];
        }
        if ($order['trang_thai'] === $status) {
            return ['ok' => false, 'error' => 'Đơn đã ở trạng thái này.'];
        }
        if ($order['trang_thai'] === 'da_huy' && $status !== 'da_huy') {
            return ['ok' => false, 'error' => 'Đơn đã hủy thì không mở lại được.'];
        }
        $statement = $this->connection->prepare(
            'UPDATE don_dat_tour SET trang_thai = ? WHERE id_don_dat = ?'
        );
        $statement->execute([$status, $orderId]);
        return ['ok' => true, 'error' => ''];
    }
}
