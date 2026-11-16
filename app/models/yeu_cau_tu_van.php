<?php

class YeuCauTuVanModel
{
    public const TOPICS = [
        'tu_van_tour' => 'Tư vấn chọn tour & lịch trình',
        'dat_tour_doan' => 'Tour đoàn / gia đình',
        'thanh_toan_ve' => 'Hỗ trợ đặt chỗ & thanh toán',
        'gop_y_dich_vu' => 'Góp ý & hợp tác dịch vụ',
    ];
    public const STATUSES = ['moi' => 'Mới', 'dang_xu_ly' => 'Đang xử lý', 'da_xu_ly' => 'Đã xử lý'];
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /** Khóa các bộ đếm dùng chung để giới hạn vẫn đúng khi gửi đồng thời/nhiều session. */
    public function create(array $values, string $formToken, string $ip, ?int $accountId): array
    {
        $ownerAccount = $this->customerAccount($accountId ?? 0);
        if (!$ownerAccount) throw new DomainException('Vui lòng đăng nhập tài khoản khách hàng để trò chuyện.');
        $fingerprint = hash('sha256', json_encode(['account' => $accountId, 'values' => $values], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $tokenHash = hash('sha256', $formToken);
        $window = intdiv(time(), 900);
        $limits = [hash('sha256', 'ip|' . $ip . '|' . $window) => 5,
            hash('sha256', 'email|' . $values['email'] . '|' . $window) => 3];
        ksort($limits);
        $this->connection->exec('DELETE FROM gioi_han_tu_van WHERE het_han < NOW() LIMIT 100');
        $this->connection->beginTransaction();
        try {
            foreach ($limits as $key => $limit) {
                $this->connection->prepare('INSERT INTO gioi_han_tu_van (khoa, so_luot, het_han) VALUES (?, 0, DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE khoa = khoa')->execute([$key]);
            }
            $existing = $this->connection->prepare('SELECT id_yeu_cau, id_tai_khoan, ma_yeu_cau, dau_van FROM yeu_cau_tu_van WHERE ma_gui = ? FOR UPDATE');
            $existing->execute([$tokenHash]);
            $row = $existing->fetch();
            if ($row) {
                if ((int) $row['id_tai_khoan'] !== $accountId || !hash_equals($row['dau_van'], $fingerprint)) throw new DomainException('Biểu mẫu này đã được gửi với nội dung khác. Vui lòng tải lại trang.');
                $this->connection->commit();
                return ['id' => (int) $row['id_yeu_cau'], 'code' => $row['ma_yeu_cau'], 'duplicate' => true];
            }
            $existing = $this->connection->prepare('SELECT id_yeu_cau, ma_yeu_cau FROM yeu_cau_tu_van WHERE dau_van = ? AND tao_luc >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) LIMIT 1 FOR UPDATE');
            $existing->execute([$fingerprint]);
            $duplicate = $existing->fetch();
            if ($duplicate) {
                $this->connection->commit();
                return ['id' => (int) $duplicate['id_yeu_cau'], 'code' => $duplicate['ma_yeu_cau'], 'duplicate' => true];
            }
            foreach ($limits as $key => $limit) {
                $counter = $this->connection->prepare('SELECT so_luot FROM gioi_han_tu_van WHERE khoa = ? FOR UPDATE');
                $counter->execute([$key]);
                if ((int) $counter->fetchColumn() >= $limit) throw new OverflowException('Bạn đã gửi nhiều yêu cầu. Vui lòng thử lại sau ít phút hoặc gọi hotline để được hỗ trợ.');
            }
            // Kiểm tra lại tài khoản dưới khóa trước khi ghi hội thoại.
            $owner = $this->connection->prepare("SELECT id_tai_khoan FROM tai_khoan WHERE id_tai_khoan = ? AND dang_hoat_dong = 1 AND vai_tro = 'khach_hang' FOR UPDATE");
            $owner->execute([$accountId ?? 0]);
            $ownerId = $owner->fetchColumn();
            if (!$ownerId) throw new DomainException('Tài khoản không còn quyền gửi tin nhắn.');
            $code = 'TV' . strtoupper(bin2hex(random_bytes(8)));
            $this->connection->prepare('INSERT INTO yeu_cau_tu_van (ma_yeu_cau, id_tai_khoan, ho_ten, so_dien_thoai, email, chu_de, noi_dung, ma_gui, dau_van) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$code, $ownerId, $values['ho_ten'], $values['so_dien_thoai'], $values['email'], $values['chu_de'], $values['noi_dung'], $tokenHash, $fingerprint]);
            $id = (int) $this->connection->lastInsertId();
            foreach ($limits as $key => $limit) {
                $this->connection->prepare('UPDATE gioi_han_tu_van SET so_luot = so_luot + 1 WHERE khoa = ?')->execute([$key]);
            }
            $this->connection->commit();
            return ['id' => $id, 'code' => $code, 'duplicate' => false];
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    public function getStaff(): array
    {
        return $this->connection->query("SELECT id_tai_khoan, ho_ten FROM tai_khoan WHERE vai_tro IN ('admin','nhan_vien') AND dang_hoat_dong = 1 ORDER BY ho_ten, id_tai_khoan")->fetchAll();
    }

    public function summary(): array
    {
        $result = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($this->connection->query('SELECT trang_thai, COUNT(*) AS total FROM yeu_cau_tu_van GROUP BY trang_thai')->fetchAll() as $row) $result[$row['trang_thai']] = (int) $row['total'];
        return $result;
    }

    private function select(): string
    {
        return 'SELECT y.*, t.ho_ten AS nguoi_phu_trach, u.ho_ten AS nguoi_cap_nhat FROM yeu_cau_tu_van y LEFT JOIN tai_khoan t ON t.id_tai_khoan = y.id_nguoi_phu_trach LEFT JOIN tai_khoan u ON u.id_tai_khoan = y.id_nguoi_cap_nhat';
    }

    public function find(int $id): ?array
    {
        $query = $this->connection->prepare($this->select() . ' WHERE y.id_yeu_cau = ?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function listRequests(array $filters, int $page): array
    {
        $conditions = []; $parameters = [];
        if ($filters['q'] !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $conditions[] = '(y.ma_yeu_cau LIKE ? OR y.ho_ten LIKE ? OR y.email LIKE ? OR y.so_dien_thoai LIKE ?)';
            array_push($parameters, $like, $like, $like, $like);
        }
        if ($filters['status'] !== '') { $conditions[] = 'y.trang_thai = ?'; $parameters[] = $filters['status']; }
        if ($filters['owner'] !== '') {
            $conditions[] = $filters['owner'] === '0' ? 'y.id_nguoi_phu_trach IS NULL' : 'y.id_nguoi_phu_trach = ?';
            if ($filters['owner'] !== '0') $parameters[] = (int) $filters['owner'];
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $count = $this->connection->prepare('SELECT COUNT(*) FROM yeu_cau_tu_van y' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 10)); $page = max(1, min($pages, $page));
        $query = $this->connection->prepare($this->select() . $where . ' ORDER BY y.cap_nhat_luc DESC, y.id_yeu_cau DESC LIMIT 10 OFFSET ' . (($page - 1) * 10));
        $query->execute($parameters);
        return ['items' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function customerAccount(int $id): ?array
    {
        $query = $this->connection->prepare("SELECT id_tai_khoan, ho_ten, email, so_dien_thoai FROM tai_khoan WHERE id_tai_khoan = ? AND vai_tro = 'khach_hang' AND dang_hoat_dong = 1");
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function customerRequest(int $id, int $accountId): ?array
    {
        $query = $this->connection->prepare('SELECT id_yeu_cau, chu_de, noi_dung, trang_thai, tao_luc, cap_nhat_luc FROM yeu_cau_tu_van WHERE id_yeu_cau = ? AND id_tai_khoan = ?');
        $query->execute([$id, $accountId]);
        return $query->fetch() ?: null;
    }

    public function customerList(int $accountId, int $page): array
    {
        $query = $this->connection->prepare('SELECT COUNT(*) FROM yeu_cau_tu_van WHERE id_tai_khoan = ?');
        $query->execute([$accountId]); $total = (int) $query->fetchColumn();
        $pages = max(1, (int) ceil($total / 10)); $page = max(1, min($pages, $page));
        $query = $this->connection->prepare('SELECT id_yeu_cau, chu_de, noi_dung, trang_thai, tao_luc, cap_nhat_luc FROM yeu_cau_tu_van WHERE id_tai_khoan = ? ORDER BY cap_nhat_luc DESC, id_yeu_cau DESC LIMIT 10 OFFSET ' . (($page - 1) * 10));
        $query->execute([$accountId]);
        return ['items' => $query->fetchAll(), 'page' => $page, 'pages' => $pages];
    }

    /** Controller đã kiểm tra chủ hội thoại/quyền quản trị trước khi gọi đọc tin. */
    public function messages(int $id, int $after = 0, int $before = 0): array
    {
        $where = 'id_yeu_cau = ?'; $parameters = [$id];
        if ($after > 0) { $where .= ' AND id_tin_nhan > ?'; $parameters[] = $after; }
        if ($before > 0) { $where .= ' AND id_tin_nhan < ?'; $parameters[] = $before; }
        $query = $this->connection->prepare('SELECT id_tin_nhan, ten_nguoi_gui, vai_tro_gui, noi_dung, tao_luc FROM tin_nhan_tu_van WHERE ' . $where . ' ORDER BY id_tin_nhan ' . ($after > 0 ? 'ASC' : 'DESC') . ' LIMIT 50');
        $query->execute($parameters); $rows = $query->fetchAll();
        if ($after === 0) $rows = array_reverse($rows);
        $hasOlder = false;
        if ($rows) {
            $older = $this->connection->prepare('SELECT 1 FROM tin_nhan_tu_van WHERE id_yeu_cau = ? AND id_tin_nhan < ? LIMIT 1');
            $older->execute([$id, $rows[0]['id_tin_nhan']]); $hasOlder = (bool) $older->fetchColumn();
        }
        return ['items' => $rows, 'has_older' => $hasOlder];
    }

    public function sendMessage(int $id, int $actorId, string $content, string $token, bool $isStaff, string $stamp = ''): array
    {
        $content = trim($content);
        if (mb_strlen($content, 'UTF-8') < 1 || mb_strlen($content, 'UTF-8') > 2000) throw new DomainException('Tin nhắn cần từ 1 đến 2.000 ký tự.');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new DomainException('Mã gửi không hợp lệ. Vui lòng tải lại cuộc trò chuyện.');
        $this->connection->beginTransaction();
        try {
            $query = $this->connection->prepare('SELECT ho_ten, vai_tro, mat_khau, dang_hoat_dong FROM tai_khoan WHERE id_tai_khoan = ? FOR UPDATE');
            $query->execute([$actorId]); $actor = $query->fetch();
            if (!$actor || !(int) $actor['dang_hoat_dong'] || ($isStaff
                ? !in_array($actor['vai_tro'], ['admin','nhan_vien'], true) || !hash_equals($stamp, hash('sha256', $actor['mat_khau']))
                : $actor['vai_tro'] !== 'khach_hang')) throw new DomainException('Tài khoản không có quyền gửi tin nhắn.');
            $query = $this->connection->prepare('SELECT id_tai_khoan, trang_thai, id_nguoi_phu_trach FROM yeu_cau_tu_van WHERE id_yeu_cau = ? FOR UPDATE');
            $query->execute([$id]); $thread = $query->fetch();
            if (!$thread || !$thread['id_tai_khoan'] || (!$isStaff && (int) $thread['id_tai_khoan'] !== $actorId)) throw new DomainException('Không tìm thấy cuộc trò chuyện của khách hàng.');
            $key = hash('sha256', $actorId . '|' . $id . '|' . $token);
            $query = $this->connection->prepare('SELECT id_tin_nhan, noi_dung FROM tin_nhan_tu_van WHERE ma_gui = ?');
            $query->execute([$key]); $existing = $query->fetch();
            if ($existing) {
                if ($existing['noi_dung'] !== $content) throw new DomainException('Tin nhắn này đã gửi với nội dung khác. Vui lòng tải lại cuộc trò chuyện.');
                $this->connection->commit();
                return ['id' => (int) $existing['id_tin_nhan'], 'duplicate' => true];
            }
            $query = $this->connection->prepare('SELECT COUNT(*) FROM tin_nhan_tu_van WHERE id_nguoi_gui = ? AND tao_luc >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)');
            $query->execute([$actorId]);
            if ((int) $query->fetchColumn() >= 20) throw new OverflowException('Bạn đang gửi quá nhanh. Vui lòng chờ một phút.');
            $this->connection->prepare('INSERT INTO tin_nhan_tu_van (id_yeu_cau, id_nguoi_gui, ten_nguoi_gui, vai_tro_gui, noi_dung, ma_gui) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$id, $actorId, $actor['ho_ten'], $isStaff ? 'nhan_vien' : 'khach_hang', $content, $key]);
            $messageId = (int) $this->connection->lastInsertId();
            $status = $isStaff || $thread['trang_thai'] !== 'moi' ? 'dang_xu_ly' : 'moi';
            $owner = $thread['id_nguoi_phu_trach'] ?: ($isStaff ? $actorId : null);
            $changed = $status !== $thread['trang_thai'] || $owner !== $thread['id_nguoi_phu_trach'];
            $this->connection->prepare('UPDATE yeu_cau_tu_van SET trang_thai = ?, id_nguoi_phu_trach = ?, cap_nhat_luc = CURRENT_TIMESTAMP, phien_ban = phien_ban + ? WHERE id_yeu_cau = ?')->execute([$status, $owner, (int) $changed, $id]);
            if ($isStaff) {
                $this->connection->prepare("INSERT INTO thong_bao (id_tai_khoan, id_tin_nhan, loai_thong_bao, tieu_de, noi_dung) VALUES (?, ?, 'phan_hoi_tu_van', 'OnTrip đã trả lời bạn', 'Bạn có tin nhắn tư vấn mới. Mở cuộc trò chuyện để xem phản hồi.')")
                    ->execute([(int) $thread['id_tai_khoan'], $messageId]);
            }
            $this->connection->commit();
            return ['id' => $messageId, 'duplicate' => false];
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $values, int $actorId, string $stamp): void
    {
        $this->connection->beginTransaction();
        try {
            $actor = $this->connection->prepare("SELECT mat_khau FROM tai_khoan WHERE id_tai_khoan = ? AND vai_tro IN ('admin','nhan_vien') AND dang_hoat_dong = 1 FOR UPDATE");
            $actor->execute([$actorId]); $hash = $actor->fetchColumn();
            if (!$hash || !hash_equals($stamp, hash('sha256', $hash))) throw new DomainException('Phiên quản trị không còn hợp lệ. Vui lòng đăng nhập lại.');
            $query = $this->connection->prepare('SELECT trang_thai, phien_ban FROM yeu_cau_tu_van WHERE id_yeu_cau = ? FOR UPDATE');
            $query->execute([$id]); $row = $query->fetch();
            if (!$row) throw new DomainException('Không tìm thấy yêu cầu tư vấn.');
            if ((int) $row['phien_ban'] !== $values['phien_ban']) throw new DomainException('Yêu cầu đã được người khác cập nhật. Vui lòng tải lại trang trước khi lưu.');
            $allowed = ['moi' => ['moi','dang_xu_ly'], 'dang_xu_ly' => ['dang_xu_ly','da_xu_ly'], 'da_xu_ly' => ['da_xu_ly']];
            if (!in_array($values['trang_thai'], $allowed[$row['trang_thai']], true)) throw new DomainException('Chỉ chuyển trạng thái theo thứ tự Mới → Đang xử lý → Đã xử lý.');
            if ($values['id_nguoi_phu_trach'] > 0) {
                $staff = $this->connection->prepare("SELECT 1 FROM tai_khoan WHERE id_tai_khoan = ? AND vai_tro IN ('admin','nhan_vien') AND dang_hoat_dong = 1 FOR UPDATE");
                $staff->execute([$values['id_nguoi_phu_trach']]);
                if (!$staff->fetchColumn()) throw new DomainException('Người phụ trách phải là admin/nhân viên đang hoạt động.');
            } elseif ($values['trang_thai'] !== 'moi') throw new DomainException('Vui lòng chọn người phụ trách trước khi tiếp nhận yêu cầu.');
            if (mb_strlen($values['ghi_chu'], 'UTF-8') > 4000) throw new DomainException('Ghi chú tối đa 4.000 ký tự.');
            if ($values['trang_thai'] === 'da_xu_ly' && trim($values['ghi_chu']) === '') throw new DomainException('Vui lòng ghi kết quả liên hệ trước khi đánh dấu đã xử lý.');
            $this->connection->prepare('UPDATE yeu_cau_tu_van SET trang_thai = ?, id_nguoi_phu_trach = ?, ghi_chu = ?, id_nguoi_cap_nhat = ?, phien_ban = phien_ban + 1, cap_nhat_luc = CURRENT_TIMESTAMP WHERE id_yeu_cau = ?')
                ->execute([$values['trang_thai'], $values['id_nguoi_phu_trach'] ?: null, $values['ghi_chu'], $actorId, $id]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }
}
