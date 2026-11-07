<?php

require_once __DIR__ . '/don_dat.php';

/** Đọc dữ liệu tour; không tạo HTML và không đọc trực tiếp $_GET. */
class TourModel
{
    public const STATUSES = ['draft' => 'Bản nháp', 'published' => 'Đã công bố', 'archived' => 'Lưu trữ'];
    public const SEASONS = ['tat_ca' => 'Quanh năm', 'xuan' => 'Mùa xuân', 'ha' => 'Mùa hè', 'thu' => 'Mùa thu', 'dong' => 'Mùa đông'];
    /** @var PDO Kết nối database được truyền từ Controller. */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getRegions(string $type): array
    {
        $statement = $this->connection->prepare(
            'SELECT ma_khu_vuc, ten_khu_vuc FROM khu_vuc_tour WHERE loai_tour = ? ORDER BY thu_tu'
        );
        $statement->execute([$type]);
        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getNeeds(): array
    {
        return $this->connection->query(
            'SELECT ma_nhu_cau, ten_nhu_cau FROM nhu_cau WHERE dang_hoat_dong = 1 ORDER BY thu_tu'
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getPublishedTours(string $type, ?string $code = null): array
    {
        $codeCondition = $code !== null ? ' AND t.ma_tour = ?' : '';
        $statement = $this->connection->prepare(
            "SELECT t.id_tour, t.ma_tour AS id, t.ten_tour AS name, t.gioi_thieu AS introduction,
                    t.so_ngay AS days, t.duong_dan_anh AS image, t.noi_bat AS featured, t.mua_vu AS season, t.phan_tram_giam AS discount_percent, t.nhan_uu_dai AS promo_tag,
                    k.loai_tour AS type,
                    d.ten_diem_den AS destination, d.id_diem_den AS destination_id, d.ma_khu_vuc AS region
             FROM tour t
             JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh
             JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc
             WHERE k.loai_tour = ? AND t.trang_thai = 'published' AND d.dang_hoat_dong = 1
             $codeCondition ORDER BY k.thu_tu, t.ma_tour"
        );
        $statement->execute($code !== null ? [$type, $code] : [$type]);
        $tours = $statement->fetchAll();
        if (!$tours) {
            return [];
        }

        // Đọc lịch và nhu cầu theo lô, tránh truy vấn riêng cho từng thẻ tour.
        $ids = array_column($tours, 'id_tour');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $statement = $this->connection->prepare(
            "SELECT l.id_lich_khoi_hanh AS id, l.id_tour, l.ngay_khoi_hanh AS date,
                    l.diem_khoi_hanh AS origin, l.gia_nguoi_lon AS adult_price,
                    l.gia_tre_em AS child_price, l.so_cho AS capacity,
                    GREATEST(l.so_cho - COALESCE((
                        SELECT SUM(dd.so_nguoi_lon + dd.so_tre_em) FROM don_dat_tour dd
                        WHERE dd.id_lich_khoi_hanh = l.id_lich_khoi_hanh AND dd.trang_thai <> 'da_huy'
                    ), 0), 0) AS seats_left
             FROM lich_khoi_hanh l
             WHERE l.id_tour IN ($placeholders) AND l.trang_thai = 'open'
               AND l.ngay_khoi_hanh >= ? AND (l.han_dat IS NULL OR l.han_dat >= ?)
             ORDER BY l.ngay_khoi_hanh, l.id_lich_khoi_hanh"
        );
        $parameters = array_merge($ids, [
            $now->format('Y-m-d'),
            $now->format('Y-m-d H:i:s'),
        ]);
        $statement->execute($parameters);
        $schedules = [];
        foreach ($statement->fetchAll() as $schedule) {
            $schedule['adult_price'] = (int) $schedule['adult_price'];
            $schedule['child_price'] = (int) $schedule['child_price'];
            $schedule['capacity'] = (int) $schedule['capacity'];
            $schedule['seats_left'] = (int) $schedule['seats_left'];
            $schedules[$schedule['id_tour']][] = $schedule;
        }

        $statement = $this->connection->prepare(
            "SELECT tn.id_tour, tn.ma_nhu_cau FROM tour_nhu_cau tn
             JOIN nhu_cau n ON n.ma_nhu_cau = tn.ma_nhu_cau
             WHERE tn.id_tour IN ($placeholders) AND n.dang_hoat_dong = 1 ORDER BY n.thu_tu"
        );
        $statement->execute($ids);
        $needs = [];
        foreach ($statement->fetchAll() as $need) {
            $needs[$need['id_tour']][] = $need['ma_nhu_cau'];
        }

        foreach ($tours as &$tour) {
            $tour['days'] = (int) $tour['days'];
            $tour['duration'] = $tour['days'] . ' ngày ' . ($tour['days'] - 1) . ' đêm';
            $tour['needs'] = $needs[$tour['id_tour']] ?? [];
            $tour['schedules'] = $schedules[$tour['id_tour']] ?? [];
            $tour['dates'] = array_values(array_unique(array_column($tour['schedules'], 'date')));
            $tour['origins'] = array_values(array_unique(array_column($tour['schedules'], 'origin')));
            $tour['origin'] = implode(', ', $tour['origins']);
            // Giá từ là giá thấp nhất trong các lịch còn mở nhận đặt.
            $tour['price'] = $tour['schedules'] ? min(array_column($tour['schedules'], 'adult_price')) : null;
        }
        unset($tour);
        return $tours;
    }

    /** Đếm mỗi tour một lần, dù tour có nhiều lịch còn mở. */
    public function getAvailableDestinations(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $statement = $this->connection->prepare(
            "SELECT d.id_diem_den AS id, d.ten_diem_den AS name, d.duong_dan_anh AS image,
                    k.loai_tour AS type, COUNT(DISTINCT t.id_tour) AS tour_count,
                    MAX(t.noi_bat) AS featured
             FROM diem_den d
             JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc
             JOIN tour t ON t.id_diem_den_chinh = d.id_diem_den AND t.trang_thai = 'published'
             WHERE d.dang_hoat_dong = 1 AND EXISTS (
                 SELECT 1 FROM lich_khoi_hanh l WHERE l.id_tour = t.id_tour
                   AND l.trang_thai = 'open' AND l.ngay_khoi_hanh >= ?
                   AND (l.han_dat IS NULL OR l.han_dat >= ?)
             )
             GROUP BY d.id_diem_den, d.ten_diem_den, d.duong_dan_anh, k.loai_tour
             ORDER BY tour_count DESC, featured DESC, d.id_diem_den"
        );
        $statement->execute([$now->format('Y-m-d'), $now->format('Y-m-d H:i:s')]);
        return $statement->fetchAll();
    }

    /** Tạm tính nhóm theo giá lịch đi; dùng chung quy tắc ưu đãi với DonDatModel. */
    public function addGroupEstimates(array $tours, int $adults, int $children): array
    {
        foreach ($tours as &$tour) {
            foreach ($tour['schedules'] as &$schedule) {
                $totals = DonDatModel::calculateTotals($adults, $children, $schedule['adult_price'], $schedule['child_price']);
                $schedule['group_total'] = $totals['tong_thanh_toan'];
            }
            unset($schedule);
        }
        unset($tour);
        return $tours;
    }

    private function normalizeSearch(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $groups = ['a' => 'àáạảãâầấậẩẫăằắặẳẵ', 'e' => 'èéẹẻẽêềếệểễ',
            'i' => 'ìíịỉĩ', 'o' => 'òóọỏõôồốộổỗơờớợởỡ', 'u' => 'ùúụủũưừứựửữ',
            'y' => 'ỳýỵỷỹ', 'd' => 'đ'];
        foreach ($groups as $replacement => $characters) {
            $value = preg_replace('/[' . $characters . ']/u', $replacement, $value);
        }
        return $value;
    }

    /** Cùng một lịch phải khớp ngày, nơi xuất phát, đơn giá và ngân sách nhóm. */
    public function filterTours(array $tours, array $filters): array
    {
        $keyword = $this->normalizeSearch($filters['q']);
        return array_values(array_filter($tours, function (array $tour) use ($filters, $keyword): bool {
            if ($filters['region'] !== '' && $tour['region'] !== $filters['region']) return false;
            if ($filters['destination'] > 0 && (int) $tour['destination_id'] !== $filters['destination']) return false;
            if ($filters['days'] > 0 && (int) $tour['days'] !== $filters['days']) return false;
            if ($keyword !== '' && strpos($this->normalizeSearch($tour['name'] . ' ' . $tour['destination']), $keyword) === false) return false;
            if ($filters['needs'] && !array_intersect($filters['needs'], $tour['needs'])) return false;
            $requiresSchedule = $filters['destination'] > 0 || $filters['group_search'] || $filters['date'] !== '' || $filters['origin'] !== '' || $filters['price'] !== '' || $filters['budget'] > 0;
            if (!$requiresSchedule) return true;
            foreach ($tour['schedules'] as $schedule) {
                if ($filters['group_search'] && $filters['adults'] + $filters['children'] > (int) $schedule['capacity']) continue;
                if ($filters['date'] !== '' && $schedule['date'] !== $filters['date']) continue;
                if ($filters['origin'] !== '' && $schedule['origin'] !== $filters['origin']) continue;
                if ($filters['price'] !== '') {
                    [$minimum, $maximum] = explode('-', $filters['price']);
                    if ($schedule['adult_price'] < (int) $minimum || ($maximum !== '' && $schedule['adult_price'] >= (int) $maximum)) continue;
                }
                if ($filters['budget'] > 0 && $schedule['group_total'] > $filters['budget']) continue;
                return true;
            }
            return false;
        }));
    }

    /** Ưu tiên tour được đánh dấu nổi bật; bổ sung tour đã công bố nếu chưa đủ bốn. */
    public function getFeaturedTours(): array
    {
        $tours = array_merge(
            $this->getPublishedTours('domestic'),
            $this->getPublishedTours('international')
        );
        usort($tours, static function (array $a, array $b): int {
            $priority = (int) $b['featured'] <=> (int) $a['featured'];
            return $priority !== 0 ? $priority : strcmp($a['id'], $b['id']);
        });
        return array_slice($tours, 0, 4);
    }

    /** Các phương thức quản trị dùng cùng bảng tour, giữ nguyên truy vấn công khai phía trên. */
    public function getManagementOptions(): array
    {
        return [
            'destinations' => $this->connection->query('SELECT d.id_diem_den, d.ten_diem_den, d.dang_hoat_dong, k.ten_khu_vuc, k.loai_tour
                FROM diem_den d JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc ORDER BY k.loai_tour, k.thu_tu, d.ten_diem_den')->fetchAll(),
            'needs' => $this->connection->query('SELECT ma_nhu_cau, ten_nhu_cau, dang_hoat_dong FROM nhu_cau ORDER BY thu_tu, ma_nhu_cau')->fetchAll(),
        ];
    }

    private function managedSelect(): string
    {
        return "SELECT t.*, d.ten_diem_den, d.dang_hoat_dong AS diem_den_hoat_dong, k.ten_khu_vuc, k.loai_tour,
            (SELECT COUNT(*) FROM lich_khoi_hanh l WHERE l.id_tour = t.id_tour) AS schedule_count,
            (SELECT COUNT(*) FROM don_dat_tour o JOIN lich_khoi_hanh l ON l.id_lich_khoi_hanh = o.id_lich_khoi_hanh WHERE l.id_tour = t.id_tour) AS order_count,
            (SELECT MIN(l.ngay_khoi_hanh) FROM lich_khoi_hanh l WHERE l.id_tour = t.id_tour AND l.trang_thai = 'open'
                AND l.ngay_khoi_hanh >= ? AND (l.han_dat IS NULL OR l.han_dat >= ?)) AS next_departure
            FROM tour t JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc";
    }

    private function managementTime(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        return [$now->format('Y-m-d'), $now->format('Y-m-d H:i:s')];
    }

    public function getManagedTours(array $filters, int $page): array
    {
        $where = [];
        $parameters = [];
        if ($filters['q'] !== '') {
            $where[] = '(t.ma_tour LIKE ? OR t.ten_tour LIKE ? OR d.ten_diem_den LIKE ?)';
            $parameters = array_fill(0, 3, '%' . $filters['q'] . '%');
        }
        foreach (['type' => 'k.loai_tour', 'status' => 't.trang_thai', 'destination' => 't.id_diem_den_chinh'] as $filter => $column) {
            if ($filters[$filter] !== '') { $where[] = $column . ' = ?'; $parameters[] = $filters[$filter]; }
        }
        if ($filters['need'] !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM tour_nhu_cau n WHERE n.id_tour = t.id_tour AND n.ma_nhu_cau = ?)';
            $parameters[] = $filters['need'];
        }
        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $query = $this->connection->prepare('SELECT COUNT(*) FROM tour t JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh
            JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc' . $condition);
        $query->execute($parameters);
        $total = (int) $query->fetchColumn();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $page), $pages);
        $query = $this->connection->prepare($this->managedSelect() . $condition . ' ORDER BY t.cap_nhat_luc DESC, t.id_tour DESC LIMIT 10 OFFSET ' . (($page - 1) * 10));
        $query->execute(array_merge($this->managementTime(), $parameters));
        $summary = $this->connection->query("SELECT COUNT(*) AS total, COALESCE(SUM(trang_thai = 'draft'), 0) AS draft,
            COALESCE(SUM(trang_thai = 'published'), 0) AS published, COALESCE(SUM(trang_thai = 'archived'), 0) AS archived FROM tour")->fetch();
        return ['items' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'summary' => $summary];
    }

    public function findManagedTour(string $id, bool $lock = false): ?array
    {
        if (!preg_match('/^[1-9][0-9]{0,19}$/D', $id)) return null;
        $query = $this->connection->prepare($this->managedSelect() . ' WHERE t.id_tour = ?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute(array_merge($this->managementTime(), [$id]));
        $tour = $query->fetch();
        if (!$tour) return null;
        $query = $this->connection->prepare('SELECT ma_nhu_cau FROM tour_nhu_cau WHERE id_tour = ? ORDER BY ma_nhu_cau');
        $query->execute([$id]);
        $tour['needs'] = $query->fetchAll(PDO::FETCH_COLUMN);
        $query = $this->connection->prepare('SELECT ngay_thu, tieu_de, noi_dung FROM lich_trinh_ngay WHERE id_tour = ? ORDER BY ngay_thu');
        $query->execute([$id]);
        $tour['itinerary'] = $query->fetchAll();
        return $tour;
    }

    /** Ảnh thư viện có thể dùng chung; không chấp nhận đường dẫn ra ngoài vùng ảnh. */
    public static function isAllowedTourImage(string $path): bool
    {
        if (!preg_match('~^assets/images/(?:tours/)?[^/\\\\]+\.(?:jpg|jpeg|png|webp|gif|svg)$~iD', $path)) return false;
        $root = realpath(__DIR__ . '/../../public/assets/images');
        $file = realpath(__DIR__ . '/../../public/' . $path);
        return $root !== false && $file !== false && is_file($file) && strpos($file, $root . DIRECTORY_SEPARATOR) === 0;
    }

    public function validateManagedTour(array $values, ?array $current, ?array $lockedOptions = null): array
    {
        $errors = [];
        $textValid = static function (string $value, int $minimum, int $maximum, bool $multiline = false): bool {
            return mb_check_encoding($value, 'UTF-8') && mb_strlen($value, 'UTF-8') >= $minimum
                && mb_strlen($value, 'UTF-8') <= $maximum && !preg_match($multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u', $value);
        };
        // Đơn đặt hiện chụp mã tour trong VARCHAR(20), nên mã mới giới hạn tương ứng.
        if (!$current && !preg_match('/^[a-z][a-z0-9_-]{0,19}$/D', $values['ma_tour'])) $errors['ma_tour'] = 'Mã gồm 1–20 ký tự: chữ thường không dấu, số, - hoặc _; bắt đầu bằng chữ.';
        if (!$textValid($values['ten_tour'], 5, 255)) $errors['ten_tour'] = 'Tên tour cần từ 5 đến 255 ký tự hợp lệ.';
        if (!$textValid($values['gioi_thieu'], 0, 10000, true)) $errors['gioi_thieu'] = 'Giới thiệu tối đa 10.000 ký tự hợp lệ.';
        if (!ctype_digit($values['so_ngay']) || (int) $values['so_ngay'] < 1 || (int) $values['so_ngay'] > 255) $errors['so_ngay'] = 'Số ngày là số nguyên từ 1 đến 255.';
        if (!isset(self::STATUSES[$values['trang_thai']])) $errors['trang_thai'] = 'Vui lòng chọn trạng thái hợp lệ.';
        if (!isset(self::SEASONS[$values['mua_vu']])) $errors['mua_vu'] = 'Vui lòng chọn mùa vụ hợp lệ.';
        if (!in_array($values['noi_bat'], ['0', '1'], true)) $errors['noi_bat'] = 'Lựa chọn nổi bật không hợp lệ.';
        if (!ctype_digit($values['phan_tram_giam']) || (int) $values['phan_tram_giam'] > 99) $errors['phan_tram_giam'] = 'Phần trăm từ 0 đến 99.';
        if (!$textValid($values['nhan_uu_dai'], 0, 80)) $errors['nhan_uu_dai'] = 'Nhãn ưu đãi tối đa 80 ký tự hợp lệ.';
        if ($values['duong_dan_anh'] !== '' && !self::isAllowedTourImage($values['duong_dan_anh'])) $errors['duong_dan_anh'] = 'Vui lòng chọn ảnh có sẵn hoặc tải ảnh mới hợp lệ.';
        $options = $lockedOptions ?? $this->getManagementOptions();
        $destinations = array_column($options['destinations'], null, 'id_diem_den');
        $destination = $destinations[$values['id_diem_den_chinh']] ?? null;
        if (!$destination || !preg_match('/^[1-9][0-9]{0,19}$/D', $values['id_diem_den_chinh'])) $errors['id_diem_den_chinh'] = 'Vui lòng chọn điểm đến đang tồn tại.';
        elseif (!(int) $destination['dang_hoat_dong'] && (!$current || (string) $current['id_diem_den_chinh'] !== $values['id_diem_den_chinh'] || $values['trang_thai'] === 'published')) {
            $errors['id_diem_den_chinh'] = 'Điểm đến đang ngừng hoạt động. Chọn điểm đến khác hoặc lưu tour ở trạng thái nháp/lưu trữ.';
        }
        if ($current && (int) $current['order_count'] > 0) {
            if ((string) $current['id_diem_den_chinh'] !== $values['id_diem_den_chinh']) $errors['id_diem_den_chinh'] = 'Tour đã có đơn đặt nên giữ cố định điểm đến.';
            if ((int) $current['so_ngay'] !== (int) $values['so_ngay']) $errors['so_ngay'] = 'Tour đã có đơn đặt nên giữ cố định số ngày.';
        }
        $needs = array_column($options['needs'], null, 'ma_nhu_cau');
        foreach ($values['needs'] as $need) {
            if (!isset($needs[$need]) || (!(int) $needs[$need]['dang_hoat_dong'] && !in_array($need, $current['needs'] ?? [], true))) {
                $errors['needs'] = 'Chỉ chọn nhu cầu đang hoạt động; có thể giữ hoặc bỏ nhu cầu cũ đã ngừng hoạt động.';
            }
        }
        if (count($values['itinerary']) > 255) $errors['itinerary'] = 'Lịch trình tối đa 255 ngày.';
        foreach ($values['itinerary'] as $index => $day) {
            if ($index >= (int) $values['so_ngay']) {
                if ($day['tieu_de'] !== '' || $day['noi_dung'] !== '') $errors['itinerary'] = 'Còn nội dung lịch trình vượt số ngày. Áp dụng lại số ngày trước khi lưu.';
                continue;
            }
            $required = $values['trang_thai'] === 'published' || $day['tieu_de'] !== '' || $day['noi_dung'] !== '';
            if ($required && !$textValid($day['tieu_de'], 2, 255)) $errors['day_' . $index . '_title'] = 'Tiêu đề ngày cần từ 2 đến 255 ký tự.';
            if ($required && !$textValid($day['noi_dung'], 1, 10000, true)) $errors['day_' . $index . '_content'] = 'Nội dung ngày cần từ 1 đến 10.000 ký tự.';
        }
        if ($values['trang_thai'] === 'published' && count($values['itinerary']) < (int) $values['so_ngay']) $errors['itinerary'] = 'Cần nhập đủ lịch trình từng ngày trước khi công bố.';
        return $errors;
    }

    private function authorizeTourManager(int $actorId, string $stamp): void
    {
        $query = $this->connection->prepare('SELECT vai_tro, dang_hoat_dong, mat_khau FROM tai_khoan WHERE id_tai_khoan = ? FOR UPDATE');
        $query->execute([$actorId]);
        $actor = $query->fetch();
        if (!$actor || !(int) $actor['dang_hoat_dong'] || !in_array($actor['vai_tro'], ['admin', 'nhan_vien'], true)
            || !hash_equals(hash('sha256', $actor['mat_khau']), $stamp)) throw new DomainException('Phiên quản trị không còn quyền thao tác. Vui lòng đăng nhập lại.', 403);
    }

    public function saveManagedTour(int $actorId, string $stamp, ?string $id, array $values): int
    {
        $this->connection->beginTransaction();
        try {
            $this->authorizeTourManager($actorId, $stamp);
            $current = $id === null ? null : $this->findManagedTour($id, true);
            if ($id !== null && !$current) throw new DomainException('Tour không còn tồn tại. Vui lòng tải lại danh sách.');
            // Khóa danh mục rồi kiểm tra lại trạng thái để không gắn dữ liệu đã bị thu hồi.
            $query = $this->connection->prepare('SELECT id_diem_den, dang_hoat_dong FROM diem_den WHERE id_diem_den = ? FOR UPDATE');
            $query->execute([$values['id_diem_den_chinh']]);
            $lockedOptions = ['destinations' => $query->fetchAll(), 'needs' => []];
            if ($values['needs']) {
                $query = $this->connection->prepare('SELECT ma_nhu_cau, dang_hoat_dong FROM nhu_cau WHERE ma_nhu_cau IN (' . implode(',', array_fill(0, count($values['needs']), '?')) . ') ORDER BY ma_nhu_cau FOR UPDATE');
                $query->execute($values['needs']);
                $lockedOptions['needs'] = $query->fetchAll();
            }
            // Dùng kết quả đọc có khóa, tránh đọc lại snapshot cũ ở mức REPEATABLE READ.
            $errors = $this->validateManagedTour($values, $current, $lockedOptions);
            if ($errors) throw new DomainException(reset($errors));
            $columns = ['ten_tour', 'id_diem_den_chinh', 'gioi_thieu', 'so_ngay', 'duong_dan_anh', 'trang_thai', 'noi_bat', 'mua_vu', 'phan_tram_giam', 'nhan_uu_dai'];
            $data = [];
            foreach ($columns as $column) $data[$column] = $values[$column] === '' && in_array($column, ['duong_dan_anh', 'nhan_uu_dai'], true) ? null : $values[$column];
            if ($current) {
                $query = $this->connection->prepare('UPDATE tour SET ' . implode(', ', array_map(static fn($column) => $column . ' = ?', array_keys($data))) . ' WHERE id_tour = ?');
                $query->execute(array_merge(array_values($data), [$id]));
            } else {
                $data['ma_tour'] = $values['ma_tour'];
                $query = $this->connection->prepare('INSERT INTO tour (' . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')');
                $query->execute(array_values($data));
                $id = $this->connection->lastInsertId();
            }
            $query = $this->connection->prepare('DELETE FROM tour_nhu_cau WHERE id_tour = ?');
            $query->execute([$id]);
            $query = $this->connection->prepare('INSERT INTO tour_nhu_cau (id_tour, ma_nhu_cau) VALUES (?, ?)');
            foreach ($values['needs'] as $need) $query->execute([$id, $need]);
            $query = $this->connection->prepare('DELETE FROM lich_trinh_ngay WHERE id_tour = ?');
            $query->execute([$id]);
            $query = $this->connection->prepare('INSERT INTO lich_trinh_ngay (id_tour, ngay_thu, tieu_de, noi_dung) VALUES (?, ?, ?, ?)');
            foreach (array_slice($values['itinerary'], 0, (int) $values['so_ngay']) as $index => $day) {
                if ($day['tieu_de'] !== '' || $day['noi_dung'] !== '') $query->execute([$id, $index + 1, $day['tieu_de'], $day['noi_dung']]);
            }
            $this->connection->commit();
            return (int) $id;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    public function deleteManagedTour(int $actorId, string $stamp, string $id): void
    {
        $this->connection->beginTransaction();
        try {
            $this->authorizeTourManager($actorId, $stamp);
            $tour = $this->findManagedTour($id, true);
            if (!$tour) throw new DomainException('Tour không còn tồn tại.');
            if ((int) $tour['schedule_count'] > 0 || (int) $tour['order_count'] > 0) throw new DomainException('Tour đã có lịch khởi hành hoặc đơn đặt nên không thể xóa. Hãy chuyển sang Lưu trữ để giữ dữ liệu.');
            $query = $this->connection->prepare('DELETE FROM tour WHERE id_tour = ?');
            $query->execute([$id]);
            // Lịch trình và nhu cầu riêng của tour được dọn bằng FK CASCADE; ảnh thư viện giữ lại vì có thể dùng chung.
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    public function findPublishedTour(string $type, string $code): ?array
    {
        foreach ($this->getPublishedTours($type, $code) as $tour) {
            if ($tour['id'] === $code) {
                $statement = $this->connection->prepare(
                    'SELECT ngay_thu AS day, tieu_de AS title, noi_dung AS content
                     FROM lich_trinh_ngay WHERE id_tour = ? ORDER BY ngay_thu'
                );
                $statement->execute([$tour['id_tour']]);
                $tour['itinerary'] = $statement->fetchAll();
                return $tour;
            }
        }
        return null;
    }
}
