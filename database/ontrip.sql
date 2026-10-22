SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS `tripon`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tripon`;

-- 1. Vùng dùng cho menu
CREATE TABLE IF NOT EXISTS `khu_vuc_tour` (
    `ma_khu_vuc` VARCHAR(32) NOT NULL,
    `ten_khu_vuc` VARCHAR(80) NOT NULL,
    `loai_tour` ENUM('domestic', 'international') NOT NULL,
    `thu_tu` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`ma_khu_vuc`),
    KEY `idx_regions_type_order` (`loai_tour`, `thu_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Điểm đến
CREATE TABLE IF NOT EXISTS `diem_den` (
    `id_diem_den` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma_khu_vuc` VARCHAR(32) NOT NULL,
    `ten_diem_den` VARCHAR(120) NOT NULL,
    `duong_dan_anh` VARCHAR(255) DEFAULT NULL,
    `dang_hoat_dong` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_diem_den`),
    UNIQUE KEY `uq_destinations_region_name` (`ma_khu_vuc`, `ten_diem_den`),
    CONSTRAINT `fk_destinations_region` FOREIGN KEY (`ma_khu_vuc`)
        REFERENCES `khu_vuc_tour` (`ma_khu_vuc`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Nhu cầu
CREATE TABLE IF NOT EXISTS `nhu_cau` (
    `ma_nhu_cau` VARCHAR(32) NOT NULL,
    `ten_nhu_cau` VARCHAR(100) NOT NULL,
    `thu_tu` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `dang_hoat_dong` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ma_nhu_cau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tour
CREATE TABLE IF NOT EXISTS `tour` (
    `id_tour` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma_tour` VARCHAR(30) NOT NULL,
    `id_diem_den_chinh` BIGINT UNSIGNED NOT NULL,
    `ten_tour` VARCHAR(255) NOT NULL,
    `gioi_thieu` TEXT DEFAULT NULL,
    `so_ngay` TINYINT UNSIGNED NOT NULL,
    `duong_dan_anh` VARCHAR(255) DEFAULT NULL,
    `trang_thai` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `noi_bat` TINYINT(1) NOT NULL DEFAULT 0,
    `tao_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cap_nhat_luc` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_tour`),
    UNIQUE KEY `uq_tours_code` (`ma_tour`),
    KEY `idx_tours_destination_status` (`id_diem_den_chinh`, `trang_thai`),
    KEY `idx_tours_featured_status` (`noi_bat`, `trang_thai`),
    CONSTRAINT `chk_tours_duration` CHECK (`so_ngay` > 0),
    CONSTRAINT `fk_tours_destination` FOREIGN KEY (`id_diem_den_chinh`)
        REFERENCES `diem_den` (`id_diem_den`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Lich trình tour theo ngày
CREATE TABLE IF NOT EXISTS `lich_trinh_ngay` (
    `id_lich_trinh_ngay` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_tour` BIGINT UNSIGNED NOT NULL,
    `ngay_thu` TINYINT UNSIGNED NOT NULL,
    `tieu_de` VARCHAR(255) NOT NULL,
    `noi_dung` TEXT NOT NULL,
    PRIMARY KEY (`id_lich_trinh_ngay`),
    UNIQUE KEY `uq_itinerary_tour_day` (`id_tour`, `ngay_thu`),
    CONSTRAINT `chk_itinerary_day` CHECK (`ngay_thu` > 0),
    CONSTRAINT `fk_itinerary_tour` FOREIGN KEY (`id_tour`)
        REFERENCES `tour` (`id_tour`) ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Nhu cầu tour
CREATE TABLE IF NOT EXISTS `tour_nhu_cau` (
    `id_tour` BIGINT UNSIGNED NOT NULL,
    `ma_nhu_cau` VARCHAR(32) NOT NULL,
    PRIMARY KEY (`id_tour`, `ma_nhu_cau`),
    KEY `idx_tour_needs_need` (`ma_nhu_cau`, `id_tour`),
    CONSTRAINT `fk_tour_needs_tour` FOREIGN KEY (`id_tour`)
        REFERENCES `tour` (`id_tour`) ON UPDATE RESTRICT ON DELETE CASCADE,
    CONSTRAINT `fk_tour_needs_need` FOREIGN KEY (`ma_nhu_cau`)
        REFERENCES `nhu_cau` (`ma_nhu_cau`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Lịch khởi hành tour
CREATE TABLE IF NOT EXISTS `lich_khoi_hanh` (
    `id_lich_khoi_hanh` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_tour` BIGINT UNSIGNED NOT NULL,
    `ngay_khoi_hanh` DATE NOT NULL,
    `diem_khoi_hanh` VARCHAR(120) NOT NULL,
    `gia_nguoi_lon` DECIMAL(12,0) NOT NULL,
    `gia_tre_em` DECIMAL(12,0) NOT NULL,
    `so_cho` SMALLINT UNSIGNED NOT NULL,
    `han_dat` DATETIME DEFAULT NULL,
    `trang_thai` ENUM('open', 'closed', 'cancelled') NOT NULL DEFAULT 'open',
    PRIMARY KEY (`id_lich_khoi_hanh`),
    KEY `idx_schedules_tour_date` (`id_tour`, `ngay_khoi_hanh`),
    KEY `idx_schedules_status_date` (`trang_thai`, `ngay_khoi_hanh`),
    CONSTRAINT `chk_schedules_adult_price` CHECK (`gia_nguoi_lon` >= 0),
    CONSTRAINT `chk_schedules_child_price` CHECK (`gia_tre_em` >= 0),
    CONSTRAINT `chk_schedules_capacity` CHECK (`so_cho` > 0),
    CONSTRAINT `fk_schedules_tour` FOREIGN KEY (`id_tour`)
        REFERENCES `tour` (`id_tour`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `khu_vuc_tour` (`ma_khu_vuc`, `ten_khu_vuc`, `loai_tour`, `thu_tu`) VALUES
    ('north', 'Miền Bắc', 'domestic', 1),
    ('central', 'Miền Trung', 'domestic', 2),
    ('south', 'Miền Nam', 'domestic', 3),
    ('asia', 'Châu Á', 'international', 1),
    ('europe', 'Châu Âu', 'international', 2),
    ('americas-oceania', 'Châu Mỹ – Châu Úc', 'international', 3);

INSERT IGNORE INTO `nhu_cau` (`ma_nhu_cau`, `ten_nhu_cau`, `thu_tu`) VALUES
    ('relaxation', 'Nghỉ dưỡng', 1),
    ('nature', 'Khám phá thiên nhiên', 2),
    ('culture', 'Văn hóa – lịch sử', 3),
    ('food', 'Ẩm thực', 4),
    ('entertainment', 'Vui chơi – giải trí', 5),
    ('adventure', 'Vận động – trải nghiệm', 6);
