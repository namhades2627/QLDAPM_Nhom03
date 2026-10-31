<?php

require_once __DIR__ . '/don_dat.php';

/** Đọc dữ liệu tour; không tạo HTML và không đọc trực tiếp $_GET. */
class TourModel
{
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
                    t.so_ngay AS days, t.duong_dan_anh AS image, t.noi_bat AS featured,
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
