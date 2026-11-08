<?php

/**
 * DanhGiaModel: Quản lý đánh giá, xếp hạng sao và nhận xét của khách hàng về tour.
 */
class DanhGiaModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->ensureTableExists();
    }

    /**
     * Tự động khởi tạo bảng danh_gia_tour nếu chưa tồn tại
     */
    public function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `danh_gia_tour` (
            `id_danh_gia` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_tour` BIGINT UNSIGNED NOT NULL,
            `id_tai_khoan` BIGINT UNSIGNED NOT NULL,
            `id_don_dat` BIGINT UNSIGNED DEFAULT NULL,
            `so_sao` TINYINT UNSIGNED NOT NULL,
            `tieu_de` VARCHAR(150) DEFAULT NULL,
            `noi_dung` TEXT NOT NULL,
            `trang_thai` ENUM('hien_thi', 'an') NOT NULL DEFAULT 'hien_thi',
            `tao_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `cap_nhat_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_danh_gia`),
            KEY `idx_reviews_tour` (`id_tour`, `trang_thai`, `tao_luc`),
            KEY `idx_reviews_account` (`id_tai_khoan`),
            KEY `idx_reviews_order` (`id_don_dat`),
            CONSTRAINT `chk_reviews_rating` CHECK (`so_sao` >= 1 AND `so_sao` <= 5),
            CONSTRAINT `fk_reviews_tour` FOREIGN KEY (`id_tour`)
                REFERENCES `tour` (`id_tour`) ON UPDATE RESTRICT ON DELETE CASCADE,
            CONSTRAINT `fk_reviews_account` FOREIGN KEY (`id_tai_khoan`)
                REFERENCES `tai_khoan` (`id_tai_khoan`) ON UPDATE RESTRICT ON DELETE CASCADE,
            CONSTRAINT `fk_reviews_order` FOREIGN KEY (`id_don_dat`)
                REFERENCES `don_dat_tour` (`id_don_dat`) ON UPDATE SET NULL ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->connection->exec($sql);

        // Nạp dữ liệu mẫu ban đầu nếu bảng đang rỗng
        $count = (int) $this->connection->query("SELECT COUNT(*) FROM `danh_gia_tour`")->fetchColumn();
        if ($count === 0) {
            $sampleSql = "INSERT INTO `danh_gia_tour` (`id_tour`, `id_tai_khoan`, `so_sao`, `tieu_de`, `noi_dung`, `tao_luc`) VALUES
                (1, 3, 5, 'Chuyến đi Sapa tuyệt vời ngoài mong đợi', 'Gia đình mình vừa hoàn thành chuyến đi 3 ngày 2 đêm rất vui. Khách sạn sạch sẽ, cảnh Fansipan hùng vĩ, hướng dẫn viên OnTrip nhiệt tình chu đáo. Sẽ tiếp tục ủng hộ công ty trong những chuyến đi tiếp theo!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
                (1, 3, 5, 'Dịch vụ chu đáo, đồ ăn ngon', 'Thời tiết se lạnh rất thích, đi cáp treo Fansipan ngắm mây bồng bềnh tuyệt đẹp. Đồ ăn đặc sản vùng cao hợp khẩu vị.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
                (4, 3, 5, 'Trải nghiệm du thuyền Hạ Long đẳng cấp', 'Vịnh Hạ Long rất đẹp, dịch vụ du thuyền chuyên nghiệp, phòng ốc tiện nghi. Chèo thuyền kayak xuyên hang rất thú vị, cả nhà ai cũng ưng ý!', DATE_SUB(NOW(), INTERVAL 7 DAY)),
                (9, 3, 5, 'Bà Nà Hills rất đẹp, lịch trình hợp lý', 'Đà Nẵng thời tiết đẹp, các điểm tham quan sắp xếp vừa phải không bị mệt. Cảm ơn OnTrip nhiều vì chuyến du lịch ý nghĩa.', DATE_SUB(NOW(), INTERVAL 14 DAY)),
                (2, 3, 5, 'Cung đường Hà Giang hùng vĩ tuyệt đẹp', 'Sông Nho Quế xanh ngắt như ngọc, đèo Mã Pí Lèng hùng vĩ. Bác tài xế tay lái rất cẩn thận, đi an tâm tuyệt đối.', DATE_SUB(NOW(), INTERVAL 18 DAY));";
            try {
                $this->connection->exec($sampleSql);
            } catch (Throwable $e) {
                // Bỏ qua nếu dữ liệu mẫu dính ràng buộc tài khoản
            }
        }
    }

    /**
     * Lấy danh sách đánh giá của một tour
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTourReviews(int $tourId, ?int $starFilter = null, int $limit = 30, int $offset = 0): array
    {
        $starCondition = '';
        $params = [$tourId];
        if ($starFilter !== null && $starFilter >= 1 && $starFilter <= 5) {
            $starCondition = ' AND dg.so_sao = ?';
            $params[] = $starFilter;
        }

        $sql = "SELECT dg.id_danh_gia, dg.id_tour, dg.id_tai_khoan, dg.id_don_dat, dg.so_sao,
                       dg.tieu_de, dg.noi_dung, dg.tao_luc, dg.cap_nhat_luc,
                       tk.ho_ten, tk.hinh_anh, tk.vai_tro,
                       CASE WHEN dd.id_don_dat IS NOT NULL THEN 1 ELSE 0 END AS da_dat_tour
                FROM danh_gia_tour dg
                JOIN tai_khoan tk ON tk.id_tai_khoan = dg.id_tai_khoan
                LEFT JOIN don_dat_tour dd ON dd.id_tai_khoan = dg.id_tai_khoan 
                                         AND dd.ma_tour = (SELECT ma_tour FROM tour WHERE id_tour = dg.id_tour)
                                         AND dd.trang_thai IN ('da_xac_nhan', 'hoan_thanh')
                WHERE dg.id_tour = ? AND dg.trang_thai = 'hien_thi' $starCondition
                GROUP BY dg.id_danh_gia
                ORDER BY dg.tao_luc DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê điểm số và phân bố sao của tour
     *
     * @return array{count: int, average: float, stars: array<int, int>, percentages: array<int, int>}
     */
    public function getTourRatingStats(int $tourId): array
    {
        $sql = "SELECT so_sao, COUNT(*) as count 
                FROM danh_gia_tour 
                WHERE id_tour = ? AND trang_thai = 'hien_thi' 
                GROUP BY so_sao";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$tourId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stars = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $totalCount = 0;
        $totalScore = 0;

        foreach ($rows as $row) {
            $star = (int) $row['so_sao'];
            $c = (int) $row['count'];
            if (isset($stars[$star])) {
                $stars[$star] = $c;
                $totalCount += $c;
                $totalScore += $star * $c;
            }
        }

        $average = $totalCount > 0 ? round($totalScore / $totalCount, 1) : 5.0;

        $percentages = [];
        foreach ($stars as $s => $c) {
            $percentages[$s] = $totalCount > 0 ? (int) round(($c / $totalCount) * 100) : 0;
        }

        return [
            'count'       => $totalCount,
            'average'     => $average,
            'stars'       => $stars,
            'percentages' => $percentages,
        ];
    }

    /**
     * Lấy đánh giá của một khách hàng cụ thể cho tour này (nếu đã từng đánh giá)
     */
    public function getUserReview(int $tourId, int $accountId): ?array
    {
        $sql = "SELECT dg.*, tk.ho_ten, tk.hinh_anh 
                FROM danh_gia_tour dg
                JOIN tai_khoan tk ON tk.id_tai_khoan = dg.id_tai_khoan
                WHERE dg.id_tour = ? AND dg.id_tai_khoan = ? 
                LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$tourId, $accountId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Kiểm tra xem khách hàng đã từng đặt tour này hay chưa
     */
    public function hasUserBookedTour(int $tourId, int $accountId): bool
    {
        $sql = "SELECT 1 FROM don_dat_tour dd
                JOIN tour t ON t.ma_tour = dd.ma_tour
                WHERE t.id_tour = ? AND dd.id_tai_khoan = ? AND dd.trang_thai IN ('da_xac_nhan', 'hoan_thanh')
                LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$tourId, $accountId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Lấy ID đơn đặt tour gần nhất của khách (nếu có) để gắn vào đánh giá
     */
    public function getUserOrderIdForTour(int $tourId, int $accountId): ?int
    {
        $sql = "SELECT dd.id_don_dat FROM don_dat_tour dd
                JOIN tour t ON t.ma_tour = dd.ma_tour
                WHERE t.id_tour = ? AND dd.id_tai_khoan = ? AND dd.trang_thai IN ('da_xac_nhan', 'hoan_thanh')
                ORDER BY dd.tao_luc DESC LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$tourId, $accountId]);
        $val = $stmt->fetchColumn();
        return $val ? (int) $val : null;
    }

    /**
     * Thêm mới hoặc cập nhật đánh giá
     *
     * @return array{success: bool, message: string}
     */
    public function saveReview(int $tourId, int $accountId, int $soSao, string $noiDung, string $tieuDe = '', ?int $orderId = null): array
    {
        $soSao = max(1, min(5, $soSao));
        $noiDung = trim($noiDung);
        $tieuDe = trim($tieuDe);

        if (mb_strlen($noiDung, 'UTF-8') < 5) {
            return [
                'success' => false,
                'message' => 'Nội dung đánh giá cần có ít nhất 5 ký tự để chia sẻ cảm nhận của bạn.'
            ];
        }

        if (mb_strlen($noiDung, 'UTF-8') > 3000) {
            return [
                'success' => false,
                'message' => 'Nội dung đánh giá không được vượt quá 3000 ký tự.'
            ];
        }

        // Kiểm tra xem đã có đánh giá chưa
        $existing = $this->getUserReview($tourId, $accountId);

        if ($existing) {
            $stmt = $this->connection->prepare(
                "UPDATE danh_gia_tour 
                 SET so_sao = ?, tieu_de = ?, noi_dung = ?, trang_thai = 'hien_thi'
                 WHERE id_danh_gia = ?"
            );
            $stmt->execute([$soSao, $tieuDe !== '' ? $tieuDe : null, $noiDung, $existing['id_danh_gia']]);
            return [
                'success' => true,
                'message' => 'Cập nhật đánh giá của bạn thành công!'
            ];
        }

        $stmt = $this->connection->prepare(
            "INSERT INTO danh_gia_tour (id_tour, id_tai_khoan, id_don_dat, so_sao, tieu_de, noi_dung, trang_thai)
             VALUES (?, ?, ?, ?, ?, ?, 'hien_thi')"
        );
        $stmt->execute([$tourId, $accountId, $orderId, $soSao, $tieuDe !== '' ? $tieuDe : null, $noiDung]);

        return [
            'success' => true,
            'message' => 'Cảm ơn bạn! Đánh giá của bạn đã được ghi nhận thành công.'
        ];
    }

    /**
     * Xóa đánh giá (chính chủ hoặc admin)
     */
    public function deleteReview(int $reviewId, int $accountId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            $stmt = $this->connection->prepare("DELETE FROM danh_gia_tour WHERE id_danh_gia = ?");
            return $stmt->execute([$reviewId]);
        }

        $stmt = $this->connection->prepare("DELETE FROM danh_gia_tour WHERE id_danh_gia = ? AND id_tai_khoan = ?");
        return $stmt->execute([$reviewId, $accountId]);
    }

    /**
     * Dành cho quản trị: Lấy danh sách tất cả đánh giá
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function getReviewsForAdmin(int $page = 1, int $perPage = 15, string $search = '', ?int $tourId = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if ($tourId !== null && $tourId > 0) {
            $where[] = 'dg.id_tour = ?';
            $params[] = $tourId;
        }

        if ($search !== '') {
            $where[] = '(t.ten_tour LIKE ? OR tk.ho_ten LIKE ? OR dg.noi_dung LIKE ?)';
            $keyword = '%' . $search . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->connection->prepare(
            "SELECT COUNT(*) FROM danh_gia_tour dg
             JOIN tour t ON t.id_tour = dg.id_tour
             JOIN tai_khoan tk ON tk.id_tai_khoan = dg.id_tai_khoan
             WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT dg.*, t.ten_tour, t.ma_tour, tk.ho_ten, tk.email
                FROM danh_gia_tour dg
                JOIN tour t ON t.id_tour = dg.id_tour
                JOIN tai_khoan tk ON tk.id_tai_khoan = dg.id_tai_khoan
                WHERE $whereClause
                ORDER BY dg.tao_luc DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Dành cho quản trị: Ẩn hoặc Hiện đánh giá
     */
    public function toggleReviewStatus(int $reviewId): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE danh_gia_tour 
             SET trang_thai = IF(trang_thai = 'hien_thi', 'an', 'hien_thi')
             WHERE id_danh_gia = ?"
        );
        return $stmt->execute([$reviewId]);
    }
}
