<?php

require_once __DIR__ . '/don_dat.php';

/**
 * Thống kê đơn đặt tour, lượt khách và doanh thu cho trang quản trị.
 * Chỉ đọc dữ liệu từ don_dat_tour và thanh_toan; không làm thay đổi trạng thái đơn.
 */
class ThongKeModel
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /** Danh sách tour phục vụ bộ lọc thống kê. */
    public function getTourOptions(): array
    {
        return $this->connection->query(
            'SELECT ma_tour, ten_tour FROM tour ORDER BY ten_tour ASC'
        )->fetchAll();
    }

    /** Xây dựng mệnh đề WHERE an toàn từ bộ lọc đã kiểm tra ở Controller. */
    private function buildWhere(array $filters, string $dateExpr = 'd.tao_luc'): array
    {
        $conditions = [];
        $params = [];
        if (($filters['from'] ?? '') !== '') {
            $conditions[] = $dateExpr . ' >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if (($filters['to'] ?? '') !== '') {
            $conditions[] = $dateExpr . ' <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if (($filters['tour'] ?? '') !== '') {
            $conditions[] = 'd.ma_tour = ?';
            $params[] = $filters['tour'];
        }
        return [
            $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '',
            $params,
        ];
    }

    /** Tổng hợp các chỉ số KPI đặt tour và doanh thu. */
    public function getOverviewStats(array $filters): array
    {
        [$where, $params] = $this->buildWhere($filters, 'd.tao_luc');
        $sql = "SELECT
                    COUNT(*) AS total_orders,
                    COALESCE(SUM(d.trang_thai = 'cho_xac_nhan'), 0) AS cho_xac_nhan,
                    COALESCE(SUM(d.trang_thai = 'da_xac_nhan'), 0) AS da_xac_nhan,
                    COALESCE(SUM(d.trang_thai = 'hoan_thanh'), 0) AS hoan_thanh,
                    COALESCE(SUM(d.trang_thai = 'da_huy'), 0) AS da_huy,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN d.so_nguoi_lon ELSE 0 END), 0) AS total_adults,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN d.so_tre_em ELSE 0 END), 0) AS total_children,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN (d.so_nguoi_lon + d.so_tre_em) ELSE 0 END), 0) AS total_guests,
                    COALESCE(SUM(CASE WHEN tt.id_thanh_toan IS NOT NULL AND d.trang_thai <> 'da_huy' THEN 1 ELSE 0 END), 0) AS paid_orders,
                    COALESCE(SUM(CASE WHEN tt.id_thanh_toan IS NOT NULL AND d.trang_thai <> 'da_huy' THEN tt.so_tien ELSE 0 END), 0) AS collected_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai IN ('da_xac_nhan', 'hoan_thanh') AND tt.id_thanh_toan IS NULL THEN d.tong_thanh_toan ELSE 0 END), 0) AS pending_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai = 'cho_xac_nhan' AND tt.id_thanh_toan IS NULL THEN d.tong_thanh_toan ELSE 0 END), 0) AS waiting_confirm_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN d.tong_thanh_toan ELSE 0 END), 0) AS expected_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN (d.tien_giam + d.tien_giam_voucher) ELSE 0 END), 0) AS total_discount
                FROM don_dat_tour d
                LEFT JOIN thanh_toan tt ON tt.id_don_dat = d.id_don_dat AND tt.trang_thai = 'thanh_cong'" . $where;
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $totalOrders = (int) ($row['total_orders'] ?? 0);
        $cancelled = (int) ($row['da_huy'] ?? 0);

        return [
            'total_orders' => $totalOrders,
            'cho_xac_nhan' => (int) ($row['cho_xac_nhan'] ?? 0),
            'da_xac_nhan' => (int) ($row['da_xac_nhan'] ?? 0),
            'hoan_thanh' => (int) ($row['hoan_thanh'] ?? 0),
            'da_huy' => $cancelled,
            'total_adults' => (int) ($row['total_adults'] ?? 0),
            'total_children' => (int) ($row['total_children'] ?? 0),
            'total_guests' => (int) ($row['total_guests'] ?? 0),
            'paid_orders' => (int) ($row['paid_orders'] ?? 0),
            'collected_revenue' => (float) ($row['collected_revenue'] ?? 0),
            'pending_revenue' => (float) ($row['pending_revenue'] ?? 0),
            'waiting_confirm_revenue' => (float) ($row['waiting_confirm_revenue'] ?? 0),
            'expected_revenue' => (float) ($row['expected_revenue'] ?? 0),
            'total_discount' => (float) ($row['total_discount'] ?? 0),
            'cancel_rate' => $totalOrders > 0 ? round(($cancelled / $totalOrders) * 100, 1) : 0.0,
        ];
    }

    /** Chuỗi thống kê theo tháng hoặc ngày (tối đa 12 mốc gần nhất). */
    public function getTimeSeries(array $filters, string $groupBy = 'month'): array
    {
        [$where, $params] = $this->buildWhere($filters, 'd.tao_luc');
        $format = $groupBy === 'day' ? '%Y-%m-%d' : '%Y-%m';
        $sql = "SELECT
                    DATE_FORMAT(d.tao_luc, '$format') AS period_key,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(d.trang_thai = 'da_huy'), 0) AS cancelled_count,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN (d.so_nguoi_lon + d.so_tre_em) ELSE 0 END), 0) AS guest_count,
                    COALESCE(SUM(CASE WHEN tt.id_thanh_toan IS NOT NULL AND d.trang_thai <> 'da_huy' THEN 1 ELSE 0 END), 0) AS paid_count,
                    COALESCE(SUM(CASE WHEN tt.id_thanh_toan IS NOT NULL AND d.trang_thai <> 'da_huy' THEN tt.so_tien ELSE 0 END), 0) AS collected_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN d.tong_thanh_toan ELSE 0 END), 0) AS expected_revenue
                FROM don_dat_tour d
                LEFT JOIN thanh_toan tt ON tt.id_don_dat = d.id_don_dat AND tt.trang_thai = 'thanh_cong'" . $where . "
                GROUP BY period_key
                ORDER BY period_key DESC
                LIMIT 12";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $rows = array_reverse($stmt->fetchAll());
        foreach ($rows as &$row) {
            $key = (string) $row['period_key'];
            $row['period_label'] = $groupBy === 'day'
                ? date('d/m/Y', strtotime($key))
                : (preg_match('/^(\d{4})-(\d{2})$/', $key, $m) ? ('Thg ' . $m[2] . '/' . $m[1]) : $key);
        }
        unset($row);
        return $rows;
    }

    /** Top tour theo doanh thu đã thu và lượt đặt. */
    public function getTopTours(array $filters, int $limit = 10): array
    {
        [$where, $params] = $this->buildWhere($filters, 'd.tao_luc');
        $limit = max(1, min($limit, 50));
        $sql = "SELECT
                    d.ma_tour,
                    MAX(d.ten_tour) AS ten_tour,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(d.trang_thai = 'hoan_thanh'), 0) AS completed_count,
                    COALESCE(SUM(d.trang_thai = 'da_huy'), 0) AS cancelled_count,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN (d.so_nguoi_lon + d.so_tre_em) ELSE 0 END), 0) AS guest_count,
                    COALESCE(SUM(CASE WHEN tt.id_thanh_toan IS NOT NULL AND d.trang_thai <> 'da_huy' THEN tt.so_tien ELSE 0 END), 0) AS collected_revenue,
                    COALESCE(SUM(CASE WHEN d.trang_thai <> 'da_huy' THEN d.tong_thanh_toan ELSE 0 END), 0) AS expected_revenue
                FROM don_dat_tour d
                LEFT JOIN thanh_toan tt ON tt.id_don_dat = d.id_don_dat AND tt.trang_thai = 'thanh_cong'" . $where . "
                GROUP BY d.ma_tour
                ORDER BY collected_revenue DESC, expected_revenue DESC, order_count DESC
                LIMIT " . $limit;
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Cơ cấu doanh thu đã thu theo phương thức thanh toán. */
    public function getPaymentBreakdown(array $filters): array
    {
        [$where, $params] = $this->buildWhere($filters, 'd.tao_luc');
        $extra = ($where === '' ? ' WHERE ' : $where . ' AND ') . "tt.trang_thai = 'thanh_cong' AND d.trang_thai <> 'da_huy'";
        $sql = "SELECT
                    tt.phuong_thuc,
                    COUNT(*) AS tx_count,
                    COALESCE(SUM(tt.so_tien), 0) AS total_amount
                FROM thanh_toan tt
                INNER JOIN don_dat_tour d ON d.id_don_dat = tt.id_don_dat" . $extra . "
                GROUP BY tt.phuong_thuc
                ORDER BY total_amount DESC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $grand = 0.0;
        foreach ($rows as $row) {
            $grand += (float) $row['total_amount'];
        }
        foreach ($rows as &$row) {
            $amt = (float) $row['total_amount'];
            $row['label'] = DonDatModel::paymentMethodLabel((string) $row['phuong_thuc']);
            $row['percent'] = $grand > 0 ? round(($amt / $grand) * 100, 1) : 0.0;
        }
        unset($row);
        return $rows;
    }

    /** Danh sách các giao dịch thanh toán thành công gần đây trong phạm vi lọc. */
    public function getRecentTransactions(array $filters, int $limit = 10): array
    {
        [$where, $params] = $this->buildWhere($filters, 'd.tao_luc');
        $extra = ($where === '' ? ' WHERE ' : $where . ' AND ') . "tt.trang_thai = 'thanh_cong'";
        $limit = max(1, min($limit, 30));
        $sql = "SELECT
                    tt.ma_giao_dich,
                    tt.phuong_thuc,
                    tt.so_tien,
                    tt.tao_luc AS thanh_toan_luc,
                    d.ma_don,
                    d.ten_tour,
                    d.ho_ten,
                    d.trang_thai AS trang_thai_don
                FROM thanh_toan tt
                INNER JOIN don_dat_tour d ON d.id_don_dat = tt.id_don_dat" . $extra . "
                ORDER BY tt.tao_luc DESC, tt.id_thanh_toan DESC
                LIMIT " . $limit;
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
