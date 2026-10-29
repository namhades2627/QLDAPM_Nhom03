<?php

/** Dữ liệu tổng quan chỉ đọc; không thay đổi nghiệp vụ tour. */
class AdminModel
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function getOverview(DateTimeImmutable $now): array
    {
        $totals = $this->connection->query(
            "SELECT COUNT(*) AS tours, COALESCE(SUM(trang_thai = 'published'), 0) AS published FROM tour"
        )->fetch();
        $destinations = $this->connection->query('SELECT COUNT(*) FROM diem_den WHERE dang_hoat_dong = 1')->fetchColumn();
        $query = $this->connection->prepare(
            "SELECT COUNT(*) FROM lich_khoi_hanh WHERE trang_thai = 'open' AND ngay_khoi_hanh >= ?"
        );
        $query->execute([$now->format('Y-m-d')]);
        $scheduleCount = (int) $query->fetchColumn();
        $query = $this->connection->prepare(
            "SELECT t.ma_tour, t.ten_tour, l.ngay_khoi_hanh, l.diem_khoi_hanh, l.gia_nguoi_lon
             FROM lich_khoi_hanh l JOIN tour t ON t.id_tour = l.id_tour
             WHERE l.trang_thai = 'open' AND l.ngay_khoi_hanh >= ?
             ORDER BY l.ngay_khoi_hanh, l.id_lich_khoi_hanh LIMIT 5"
        );
        $query->execute([$now->format('Y-m-d')]);
        $schedules = $query->fetchAll();
        $groups = $this->connection->query(
            'SELECT k.loai_tour, COUNT(*) AS total FROM tour t
             JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh
             JOIN khu_vuc_tour k ON k.ma_khu_vuc = d.ma_khu_vuc GROUP BY k.loai_tour'
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        return [
            'tours' => (int) $totals['tours'], 'published' => (int) $totals['published'],
            'destinations' => (int) $destinations, 'schedule_count' => $scheduleCount,
            'schedules' => $schedules, 'groups' => $groups,
        ];
    }
}
