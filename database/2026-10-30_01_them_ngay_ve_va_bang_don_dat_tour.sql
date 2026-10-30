-- =============================================================================
-- OnTrip — Cập nhật cấu trúc cho module Lịch khởi hành và số chỗ
-- Ngày tạo   : 30/10/2026
-- Áp dụng cho: database `ontrip` (MariaDB 10.4+ / MySQL 5.7+)
-- Thứ tự chạy: sau khi đã import database/ontrip.sql
-- Chạy lại    : an toàn (dùng IF NOT EXISTS và điều kiện WHERE ... IS NULL)
-- Ghi chú     : xem database/NHAT_KY.md
-- =============================================================================

USE `ontrip`;

-- 1. Ngày về của từng chuyến.
--    NULL = chưa nhập; khi đó hệ thống suy ra từ ngày đi + số ngày của tour.
--    MariaDB hỗ trợ ADD COLUMN IF NOT EXISTS. Trên MySQL 8 chưa có cú pháp này,
--    hãy chạy riêng câu ALTER bên dưới sau khi kiểm tra cột chưa tồn tại.
ALTER TABLE `lich_khoi_hanh`
    ADD COLUMN IF NOT EXISTS `ngay_ve` DATE DEFAULT NULL
    COMMENT 'NULL thì suy ra từ ngày đi + số ngày của tour' AFTER `ngay_khoi_hanh`;

-- 2. Điền ngày về cho các chuyến đang để trống.
--    Ngày về = ngày đi + (số ngày tour − 1).
UPDATE `lich_khoi_hanh` l
JOIN `tour` t ON t.id_tour = l.id_tour
SET l.ngay_ve = DATE_ADD(l.ngay_khoi_hanh, INTERVAL t.so_ngay - 1 DAY)
WHERE l.ngay_ve IS NULL;

-- 3. Bảng đơn đặt tour.
--    Mỗi đơn thuộc một lịch khởi hành và giữ chỗ theo số khách.
--    Chỗ còn = lich_khoi_hanh.so_cho − tổng khách của các đơn đang giữ chỗ
--    (trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')).
--    Đơn giá và số tiền được lưu tại thời điểm đặt để đổi giá sau này
--    không làm thay đổi đơn cũ.
CREATE TABLE IF NOT EXISTS `don_dat_tour` (
    `id_don_dat` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma_don` VARCHAR(24) NOT NULL,
    `id_lich_khoi_hanh` BIGINT UNSIGNED NOT NULL,
    `id_tai_khoan` BIGINT UNSIGNED NOT NULL,
    `ho_ten` VARCHAR(120) NOT NULL,
    `email` VARCHAR(254) NOT NULL,
    `so_dien_thoai` VARCHAR(20) NOT NULL,
    `so_nguoi_lon` TINYINT UNSIGNED NOT NULL,
    `so_tre_em` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `gia_nguoi_lon` DECIMAL(12,0) NOT NULL COMMENT 'Đơn giá tại thời điểm đặt',
    `gia_tre_em` DECIMAL(12,0) NOT NULL COMMENT 'Đơn giá tại thời điểm đặt',
    `tong_goc` DECIMAL(14,0) NOT NULL,
    `muc_giam` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Phần trăm ưu đãi nhóm',
    `tien_giam` DECIMAL(14,0) NOT NULL DEFAULT 0,
    `tong_thanh_toan` DECIMAL(14,0) NOT NULL,
    `ghi_chu` VARCHAR(500) DEFAULT NULL,
    `trang_thai` ENUM('cho_xac_nhan', 'da_xac_nhan', 'da_huy', 'hoan_thanh')
        NOT NULL DEFAULT 'cho_xac_nhan',
    `tao_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cap_nhat_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_don_dat`),
    UNIQUE KEY `uq_don_dat_ma_don` (`ma_don`),
    KEY `idx_don_dat_lich_trang_thai` (`id_lich_khoi_hanh`, `trang_thai`),
    KEY `idx_don_dat_tai_khoan` (`id_tai_khoan`, `tao_luc`),
    CONSTRAINT `chk_don_dat_nguoi_lon` CHECK (`so_nguoi_lon` >= 1),
    CONSTRAINT `chk_don_dat_tong_tien` CHECK (`tong_thanh_toan` >= 0),
    CONSTRAINT `fk_don_dat_lich` FOREIGN KEY (`id_lich_khoi_hanh`)
        REFERENCES `lich_khoi_hanh` (`id_lich_khoi_hanh`) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT `fk_don_dat_tai_khoan` FOREIGN KEY (`id_tai_khoan`)
        REFERENCES `tai_khoan` (`id_tai_khoan`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
