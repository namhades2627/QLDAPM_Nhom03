SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS `ontrip`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ontrip`;

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

-- 8. Dữ liệu demo: 48 tour, mỗi khu vực 8 tour.
-- Giá, lịch trình, ngày đi và sức chứa phục vụ học tập, chưa xác nhận dịch vụ.
-- Bổ sung ảnh tương ứng tại public/assets/images/tours/.
-- Mỗi dòng VALUES là một bản ghi; các nhóm được phân chia bằng chú thích.
-- Tra ID bằng mã tour/tên điểm đến để không phụ thuộc ID tự tăng.
-- ON DUPLICATE KEY UPDATE giữ nguyên bản ghi đã có khi import lại.

START TRANSACTION;

-- 8.1. Điểm đến
INSERT INTO diem_den (ma_khu_vuc, ten_diem_den, duong_dan_anh) VALUES
    -- Miền Bắc
    ('north', 'Hà Nội', 'assets/images/tours/mb01.jpg'),
    ('north', 'Hà Giang', 'assets/images/tours/mb02.jpg'),
    ('north', 'Sa Pa', 'assets/images/tours/mb03.jpg'),
    ('north', 'Hạ Long', 'assets/images/tours/mb04.jpg'),
    ('north', 'Ninh Bình', 'assets/images/tours/mb05.jpg'),
    ('north', 'Cao Bằng', 'assets/images/tours/mb06.jpg'),
    ('north', 'Mộc Châu', 'assets/images/tours/mb07.jpg'),
    ('north', 'Yên Bái', 'assets/images/tours/mb08.jpg'),
    -- Miền Trung
    ('central', 'Đà Nẵng', 'assets/images/tours/mt01.jpg'),
    ('central', 'Huế', 'assets/images/tours/mt02.jpg'),
    ('central', 'Hội An', 'assets/images/tours/mt03.jpg'),
    ('central', 'Phan Thiết', 'assets/images/tours/mt04.jpg'),
    ('central', 'Quy Nhơn', 'assets/images/tours/mt05.jpg'),
    ('central', 'Phú Yên', 'assets/images/tours/mt06.jpg'),
    ('central', 'Nha Trang', 'assets/images/tours/mt07.jpg'),
    ('central', 'Đà Lạt', 'assets/images/tours/mt08.jpg'),
    -- Miền Nam
    ('south', 'Phú Quốc', 'assets/images/tours/mn01.jpg'),
    ('south', 'Côn Đảo', 'assets/images/tours/mn02.jpg'),
    ('south', 'Cần Thơ', 'assets/images/tours/mn03.jpg'),
    ('south', 'An Giang', 'assets/images/tours/mn04.jpg'),
    ('south', 'Đồng Nai', 'assets/images/tours/mn05.jpg'),
    ('south', 'Kiên Giang', 'assets/images/tours/mn06.jpg'),
    ('south', 'Cà Mau', 'assets/images/tours/mn07.jpg'),
    ('south', 'Tây Ninh', 'assets/images/tours/mn08.jpg'),
    -- Châu Á
    ('asia', 'Nhật Bản', 'assets/images/tours/as01.jpg'),
    ('asia', 'Hàn Quốc', 'assets/images/tours/as02.jpg'),
    ('asia', 'Thái Lan', 'assets/images/tours/as03.jpg'),
    ('asia', 'Singapore', 'assets/images/tours/as04.jpg'),
    ('asia', 'Malaysia', 'assets/images/tours/as05.jpg'),
    ('asia', 'Đài Loan', 'assets/images/tours/as06.jpg'),
    ('asia', 'Trung Quốc', 'assets/images/tours/as07.jpg'),
    ('asia', 'Indonesia', 'assets/images/tours/as08.jpg'),
    -- Châu Âu
    ('europe', 'Anh', 'assets/images/tours/eu01.jpg'),
    ('europe', 'Thụy Sĩ', 'assets/images/tours/eu02.jpg'),
    ('europe', 'Ý', 'assets/images/tours/eu03.jpg'),
    ('europe', 'Đức', 'assets/images/tours/eu04.jpg'),
    ('europe', 'Thổ Nhĩ Kỳ', 'assets/images/tours/eu05.jpg'),
    ('europe', 'Hà Lan', 'assets/images/tours/eu06.jpg'),
    ('europe', 'Đan Mạch', 'assets/images/tours/eu07.jpg'),
    ('europe', 'Nga', 'assets/images/tours/eu08.jpg'),
    -- Châu Mỹ – Châu Úc
    ('americas-oceania', 'Hoa Kỳ', 'assets/images/tours/us01.jpg'),
    ('americas-oceania', 'Canada', 'assets/images/tours/us02.jpg'),
    ('americas-oceania', 'Mexico', 'assets/images/tours/us03.jpg'),
    ('americas-oceania', 'Brazil', 'assets/images/tours/us04.jpg'),
    ('americas-oceania', 'Costa Rica', 'assets/images/tours/us05.jpg'),
    ('americas-oceania', 'Argentina', 'assets/images/tours/us06.jpg'),
    ('americas-oceania', 'Australia', 'assets/images/tours/us07.jpg'),
    ('americas-oceania', 'New Zealand', 'assets/images/tours/us08.jpg')
ON DUPLICATE KEY UPDATE id_diem_den = id_diem_den;

-- 8.2. Thông tin tour
INSERT INTO tour (ma_tour, id_diem_den_chinh, ten_tour, gioi_thieu, so_ngay, duong_dan_anh, trang_thai, noi_bat) VALUES
    -- Miền Bắc

    ('mb01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Hà Nội'), 'Hà Nội – Sapa – Fansipan – Lào Cai – Hạ Long', 'Hành trình 5 ngày theo tuyến Hà Nội – Sapa – Fansipan – Lào Cai – Hạ Long. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 5, 'assets/images/tours/mb01.jpg', 'published', 0),

    ('mb02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Hà Giang'), 'Hà Giang – Đồng Văn – Sông Nho Quế – Pác Bó – Bản Giốc – Ba Bể', 'Hành trình 6 ngày theo tuyến Hà Giang – Đồng Văn – Sông Nho Quế – Pác Bó – Bản Giốc – Ba Bể. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/mb02.jpg', 'published', 1),

    ('mb03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Sa Pa'), 'Sapa – Lai Châu – Lào Cai', 'Hành trình 3 ngày theo tuyến Sapa – Lai Châu – Lào Cai. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mb03.jpg', 'published', 0),
    ('mb04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Hạ Long'), 'Hạ Long – Du thuyền – Hang Sửng Sốt', 'Hành trình 3 ngày khám phá Hạ Long, kết hợp Tuần Châu và vịnh Hạ Long, Hang Sửng Sốt và Ti Tốp. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mb04.jpg', 'published', 0),
    ('mb05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Ninh Bình'), 'Ninh Bình – Tràng An – Hang Múa', 'Hành trình 2 ngày khám phá Ninh Bình, kết hợp Hoa Lư và Tràng An, Hang Múa và Bái Đính. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 2, 'assets/images/tours/mb05.jpg', 'published', 0),
    ('mb06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Cao Bằng'), 'Cao Bằng – Bản Giốc – Pác Bó', 'Hành trình 3 ngày khám phá Cao Bằng, kết hợp Núi Mắt Thần, Bản Giốc và Ngườm Ngao. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mb06.jpg', 'published', 0),
    ('mb07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Mộc Châu'), 'Mộc Châu – Đồi chè – Thác Dải Yếm', 'Hành trình 2 ngày khám phá Mộc Châu, kết hợp Đồi chè và bản Áng, Thác Dải Yếm. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 2, 'assets/images/tours/mb07.jpg', 'published', 0),

    ('mb08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'north' AND ten_diem_den = 'Yên Bái'), 'Yên Bái – Hồ Thác Bà', 'Hành trình 3 ngày theo tuyến Yên Bái – Hồ Thác Bà. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mb08.jpg', 'published', 0),
    -- Miền Trung
    ('mt01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Đà Nẵng'), 'Đà Nẵng – Bà Nà Hills – Sơn Trà', 'Hành trình 3 ngày khám phá Đà Nẵng, kết hợp Mỹ Khê và cầu Rồng, Bà Nà Hills và Cầu Vàng. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt01.jpg', 'published', 1),

    ('mt02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Huế'), 'Huế – Đại Nội – Cố Đô Huế', 'Hành trình 3 ngày theo tuyến Huế – Đại Nội – Cố Đô Huế. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt02.jpg', 'published', 0),

    ('mt03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Hội An'), 'Hội An – Phố Cổ Hội An – Rừng dừa Bảy Mẫu', 'Hành trình 3 ngày theo tuyến Hội An – Phố Cổ Hội An – Rừng dừa Bảy Mẫu. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt03.jpg', 'published', 0),

    ('mt04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Phan Thiết'), 'Phan Thiết – Mũi Né – Bàu Trắng', 'Hành trình 3 ngày theo tuyến Phan Thiết – Mũi Né – Bàu Trắng. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt04.jpg', 'published', 0),

    ('mt05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Quy Nhơn'), 'Quy Nhơn – Kỳ Co – Biển Hòn Khô', 'Hành trình 3 ngày theo tuyến Quy Nhơn – Kỳ Co – Biển Hòn Khô. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt05.jpg', 'published', 0),
    ('mt06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Phú Yên'), 'Phú Yên – Gành Đá Đĩa – Mũi Điện', 'Hành trình 3 ngày khám phá Phú Yên, kết hợp Tuy Hòa và tháp Nhạn, Gành Đá Đĩa và đầm Ô Loan. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt06.jpg', 'published', 0),
    ('mt07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Nha Trang'), 'Nha Trang – Vịnh Nha Trang – Tháp Bà', 'Hành trình 3 ngày khám phá Nha Trang, kết hợp Biển Nha Trang và tháp Trầm Hương, Vịnh Nha Trang. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt07.jpg', 'published', 0),

    ('mt08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'central' AND ten_diem_den = 'Đà Lạt'), 'Đà Lạt – Lâm Đồng – Thung lũng vàng', 'Hành trình 3 ngày theo tuyến Đà Lạt – Lâm Đồng – Thung lũng vàng. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mt08.jpg', 'published', 0),
    -- Miền Nam
    ('mn01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Phú Quốc'), 'Phú Quốc – Hòn Thơm – Grand World', 'Hành trình 4 ngày khám phá Phú Quốc, kết hợp Dinh Cậu, Hòn Thơm và Nam đảo. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 4, 'assets/images/tours/mn01.jpg', 'published', 1),

    ('mn02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Côn Đảo'), 'Côn Đảo – Núi Chúa', 'Hành trình 3 ngày theo tuyến Côn Đảo – Núi Chúa. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mn02.jpg', 'published', 0),
    ('mn03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Cần Thơ'), 'Cần Thơ – Chợ nổi Cái Răng – Cồn Sơn', 'Hành trình 2 ngày khám phá Cần Thơ, kết hợp Ninh Kiều và nhà cổ Bình Thủy, Chợ nổi Cái Răng và Cồn Sơn. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 2, 'assets/images/tours/mn03.jpg', 'published', 0),

    ('mn04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'An Giang'), 'An Giang – Rừng tràm Trà Sư', 'Hành trình 3 ngày theo tuyến An Giang – Rừng tràm Trà Sư. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 3, 'assets/images/tours/mn04.jpg', 'published', 0),

    ('mn05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Đồng Nai'), 'Đồng Nai – Nam Cát Tiên', 'Hành trình 2 ngày theo tuyến Đồng Nai – Nam Cát Tiên. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 2, 'assets/images/tours/mn05.jpg', 'published', 0),

    ('mn06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Kiên Giang'), 'Kiên Giang – Đảo Nam Du', 'Hành trình 2 ngày theo tuyến Kiên Giang – Đảo Nam Du. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 2, 'assets/images/tours/mn06.jpg', 'published', 0),
    ('mn07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Cà Mau'), 'Cà Mau – Đất Mũi – Rừng U Minh Hạ', 'Hành trình 3 ngày khám phá Cà Mau, kết hợp Thành phố Cà Mau, Đất Mũi. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 3, 'assets/images/tours/mn07.jpg', 'published', 0),
    ('mn08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'south' AND ten_diem_den = 'Tây Ninh'), 'Tây Ninh – Núi Bà Đen – Tòa Thánh', 'Hành trình 2 ngày khám phá Tây Ninh, kết hợp Núi Bà Đen, Tòa Thánh và làng nghề bánh tráng. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 2, 'assets/images/tours/mn08.jpg', 'published', 0),
    -- Châu Á

    ('as01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Nhật Bản'), 'Nhật Bản – Kyoto – Đền Kiyomizu-dera', 'Hành trình 6 ngày theo tuyến Nhật Bản – Kyoto – Đền Kiyomizu-dera. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/as01.jpg', 'published', 1),
    ('as02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Hàn Quốc'), 'Hàn Quốc – Seoul – Nami – Suwon', 'Hành trình 5 ngày khám phá Hàn Quốc, kết hợp Gyeongbokgung và Bukchon, Đảo Nami. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 5, 'assets/images/tours/as02.jpg', 'published', 0),

    ('as03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Thái Lan'), 'Thái Lan – Bangkok – Đảo San Hô Coral', 'Hành trình 4 ngày theo tuyến Thái Lan – Bangkok – Đảo San Hô Coral. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 4, 'assets/images/tours/as03.jpg', 'published', 0),
    ('as04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Singapore'), 'Singapore – Gardens by the Bay – Sentosa', 'Hành trình 4 ngày khám phá Singapore, kết hợp Gardens by the Bay và Marina Bay, Đảo Sentosa. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 4, 'assets/images/tours/as04.jpg', 'published', 0),

    ('as05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Malaysia'), 'Malaysia – Kuala Lumpur – Penang', 'Hành trình 4 ngày theo tuyến Malaysia – Kuala Lumpur – Penang. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 4, 'assets/images/tours/as05.jpg', 'published', 0),

    ('as06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Đài Loan'), 'Đài Loan – Đài Bắc – Đài Trung', 'Hành trình 5 ngày theo tuyến Đài Loan – Đài Bắc – Đài Trung. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 5, 'assets/images/tours/as06.jpg', 'published', 0),

    ('as07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Trung Quốc'), 'Trung Quốc – Phượng Hoàng Cổ Trấn – Trương Gia Giới', 'Hành trình 6 ngày theo tuyến Trung Quốc – Phượng Hoàng Cổ Trấn – Trương Gia Giới. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/as07.jpg', 'published', 0),

    ('as08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'asia' AND ten_diem_den = 'Indonesia'), 'Indonesia – Nusa Dua – Đền Tanah Lot', 'Hành trình 5 ngày theo tuyến Indonesia – Nusa Dua – Đền Tanah Lot. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 5, 'assets/images/tours/as08.jpg', 'published', 0),
    -- Châu Âu

    ('eu01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Anh'), 'Anh – Scotland – Wales', 'Hành trình 6 ngày theo tuyến Anh – Scotland – Wales. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu01.jpg', 'published', 0),

    ('eu02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Thụy Sĩ'), 'Thụy Sĩ – Lucerne – Wasserturm', 'Hành trình 6 ngày theo tuyến Thụy Sĩ – Lucerne – Wasserturm. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu02.jpg', 'published', 0),

    ('eu03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Ý'), 'Ý – Lombardy – Hồ Como', 'Hành trình 7 ngày theo tuyến Ý – Lombardy – Hồ Como. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 7, 'assets/images/tours/eu03.jpg', 'published', 0),

    ('eu04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Đức'), 'Đức – Munich – Cologne – Cầu Hohenzollern', 'Hành trình 6 ngày theo tuyến Đức – Munich – Cologne – Cầu Hohenzollern. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu04.jpg', 'published', 0),

    ('eu05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Thổ Nhĩ Kỳ'), 'Thổ Nhĩ Kỳ – Istanbul – Canakkale', 'Hành trình 6 ngày theo tuyến Thổ Nhĩ Kỳ – Istanbul – Canakkale. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu05.jpg', 'published', 0),

    ('eu06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Hà Lan'), 'Hà Lan – Amsterdam – Zaanse Schans', 'Hành trình 5 ngày theo tuyến Hà Lan – Amsterdam – Zaanse Schans. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 5, 'assets/images/tours/eu06.jpg', 'published', 0),

    ('eu07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Đan Mạch'), 'Đan Mạch – Copenhagen – Đài phun nước Gefion', 'Hành trình 6 ngày theo tuyến Đan Mạch – Copenhagen – Đài phun nước Gefion. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu07.jpg', 'published', 0),

    ('eu08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'europe' AND ten_diem_den = 'Nga'), 'Nga – Moscow – Murmansk', 'Hành trình 6 ngày theo tuyến Nga – Moscow – Murmansk. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 6, 'assets/images/tours/eu08.jpg', 'published', 0),
    -- Châu Mỹ – Châu Úc
    ('us01', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Hoa Kỳ'), 'Hoa Kỳ – Los Angeles – Las Vegas – Grand Canyon', 'Hành trình 8 ngày khám phá Hoa Kỳ, kết hợp Hollywood và Santa Monica, Di chuyển đến Las Vegas. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 8, 'assets/images/tours/us01.jpg', 'published', 0),
    ('us02', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Canada'), 'Canada – Vancouver – Whistler – Victoria', 'Hành trình 7 ngày khám phá Canada, kết hợp Stanley và Granville, Whistler. Dành thời gian tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Chương trình, giá và lịch đi là dữ liệu demo của dự án OnTrip, chưa phải dịch vụ kinh doanh đã xác nhận.', 7, 'assets/images/tours/us02.jpg', 'published', 0),

    ('us03', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Mexico'), 'Mexico – San Miguel de Allende – Guanajuato', 'Hành trình 8 ngày theo tuyến Mexico – San Miguel de Allende – Guanajuato. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 8, 'assets/images/tours/us03.jpg', 'published', 0),

    ('us04', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Brazil'), 'Brazil – Rio de Janeiro', 'Hành trình 8 ngày theo tuyến Brazil – Rio de Janeiro. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 8, 'assets/images/tours/us04.jpg', 'published', 0),

    ('us05', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Costa Rica'), 'Costa Rica – Vườn Quốc Gia Manuel Antonio', 'Hành trình 8 ngày theo tuyến Costa Rica – Vườn Quốc Gia Manuel Antonio. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 8, 'assets/images/tours/us05.jpg', 'published', 0),

    ('us06', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Argentina'), 'Argentina – Công viên quốc gia Tierra del Fuego', 'Hành trình 8 ngày theo tuyến Argentina – Công viên quốc gia Tierra del Fuego. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 8, 'assets/images/tours/us06.jpg', 'published', 0),

    ('us07', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'Australia'), 'Australia – Sydney – Blue Mountains', 'Hành trình 7 ngày theo tuyến Australia – Sydney – Blue Mountains. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 7, 'assets/images/tours/us07.jpg', 'published', 0),

    ('us08', (SELECT id_diem_den FROM diem_den WHERE ma_khu_vuc = 'americas-oceania' AND ten_diem_den = 'New Zealand'), 'New Zealand – Auckland – Rotorua', 'Hành trình 7 ngày theo tuyến New Zealand – Auckland – Rotorua. Chương trình tập trung vào các điểm tham quan trong tuyến, kết hợp tìm hiểu cảnh quan, văn hóa và ẩm thực địa phương. Ngày di chuyển có thời gian nghỉ ngơi phù hợp. Nội dung, giá và lịch đi là dữ liệu demo của OnTrip, chưa phải chương trình kinh doanh đã xác nhận.', 7, 'assets/images/tours/us08.jpg', 'published', 0)
ON DUPLICATE KEY UPDATE id_tour = id_tour;

-- 8.3. Nhu cầu của tour
INSERT INTO tour_nhu_cau (id_tour, ma_nhu_cau) VALUES
    -- Miền Bắc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), 'adventure'),
    -- Miền Trung
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), 'relaxation'),
    -- Miền Nam
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), 'adventure'),
    -- Châu Á
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 'nature'),
    -- Châu Âu
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 'nature'),
    -- Châu Mỹ – Châu Úc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 'relaxation'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 'food'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 'entertainment'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 'adventure'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 'culture'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 'nature'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 'adventure')
ON DUPLICATE KEY UPDATE id_tour = id_tour;

-- 8.4. Lịch trình theo ngày
INSERT INTO lich_trinh_ngay (id_tour, ngay_thu, tieu_de, noi_dung) VALUES
    -- Miền Bắc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 1, 'Hà Nội – Khởi hành đến Sa Pa', 'Ngày 1: Hà Nội – Khởi hành đến Sa Pa. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 2, 'Sa Pa – Khám phá thị trấn', 'Ngày 2: Sa Pa – Khám phá thị trấn. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 3, 'Fansipan – Lào Cai', 'Ngày 3: Fansipan – Lào Cai. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 4, 'Lào Cai – Hạ Long', 'Ngày 4: Lào Cai – Hạ Long. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), 5, 'Vịnh Hạ Long – Trở về Hà Nội', 'Ngày 5: Vịnh Hạ Long – Trở về Hà Nội. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 1, 'Hà Giang – Cao nguyên Đồng Văn', 'Ngày 1: Hà Giang – Cao nguyên Đồng Văn. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 2, 'Sông Nho Quế – Di chuyển đến Cao Bằng', 'Ngày 2: Sông Nho Quế – Di chuyển đến Cao Bằng. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 3, 'Pác Bó – Tìm hiểu lịch sử', 'Ngày 3: Pác Bó – Tìm hiểu lịch sử. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 4, 'Thác Bản Giốc', 'Ngày 4: Thác Bản Giốc. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 5, 'Cao Bằng – Hồ Ba Bể', 'Ngày 5: Cao Bằng – Hồ Ba Bể. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), 6, 'Hồ Ba Bể – Kết thúc hành trình', 'Ngày 6: Hồ Ba Bể – Kết thúc hành trình. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), 1, 'Lào Cai – Sa Pa', 'Ngày 1: Lào Cai – Sa Pa. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), 2, 'Sa Pa – Lai Châu', 'Ngày 2: Sa Pa – Lai Châu. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), 3, 'Lai Châu – Lào Cai', 'Ngày 3: Lai Châu – Lào Cai. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), 1, 'Tuần Châu và vịnh Hạ Long', 'Tập trung tại Hà Nội, di chuyển đến Hạ Long. Tham quan Tuần Châu và vịnh Hạ Long, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), 2, 'Hang Sửng Sốt và Ti Tốp', 'Ăn sáng tại khách sạn. Tham quan Hang Sửng Sốt và Ti Tốp, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Hạ Long.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), 3, 'Bình minh trên vịnh', 'Ăn sáng và trả phòng. Tham quan Bình minh trên vịnh, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về Hà Nội, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), 1, 'Hoa Lư và Tràng An', 'Tập trung tại Hà Nội, di chuyển đến Ninh Bình. Tham quan Hoa Lư và Tràng An, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), 2, 'Hang Múa và Bái Đính', 'Ăn sáng và trả phòng. Tham quan Hang Múa và Bái Đính, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về Hà Nội, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), 1, 'Núi Mắt Thần', 'Tập trung tại Hà Nội, di chuyển đến Cao Bằng. Tham quan Núi Mắt Thần, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), 2, 'Bản Giốc và Ngườm Ngao', 'Ăn sáng tại khách sạn. Tham quan Bản Giốc và Ngườm Ngao, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Cao Bằng.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), 3, 'Pác Bó', 'Ăn sáng và trả phòng. Tham quan Pác Bó, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về Hà Nội, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), 1, 'Đồi chè và bản Áng', 'Tập trung tại Hà Nội, di chuyển đến Mộc Châu. Tham quan Đồi chè và bản Áng, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), 2, 'Thác Dải Yếm', 'Ăn sáng và trả phòng. Tham quan Thác Dải Yếm, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về Hà Nội, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), 1, 'Yên Bái – Nhận phòng và nghỉ ngơi', 'Ngày 1: Yên Bái – Nhận phòng và nghỉ ngơi. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), 2, 'Du thuyền hồ Thác Bà', 'Ngày 2: Du thuyền hồ Thác Bà. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), 3, 'Khám phá ven hồ Thác Bà – Trở về', 'Ngày 3: Khám phá ven hồ Thác Bà – Trở về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    -- Miền Trung
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), 1, 'Mỹ Khê và cầu Rồng', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Đà Nẵng. Tham quan Mỹ Khê và cầu Rồng, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), 2, 'Bà Nà Hills và Cầu Vàng', 'Ăn sáng tại khách sạn. Tham quan Bà Nà Hills và Cầu Vàng, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Đà Nẵng.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), 3, 'Sơn Trà và Ngũ Hành Sơn', 'Ăn sáng và trả phòng. Tham quan Sơn Trà và Ngũ Hành Sơn, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), 1, 'Huế – Đại Nội', 'Ngày 1: Huế – Đại Nội. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), 2, 'Cố đô Huế – Các lăng tẩm', 'Ngày 2: Cố đô Huế – Các lăng tẩm. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), 3, 'Sông Hương – Kết thúc hành trình', 'Ngày 3: Sông Hương – Kết thúc hành trình. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), 1, 'Phố cổ Hội An – Chùa Cầu', 'Ngày 1: Phố cổ Hội An – Chùa Cầu. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), 2, 'Rừng dừa Bảy Mẫu', 'Ngày 2: Rừng dừa Bảy Mẫu. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), 3, 'Văn hóa và ẩm thực Hội An', 'Ngày 3: Văn hóa và ẩm thực Hội An. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), 1, 'Phan Thiết – Biển Mũi Né', 'Ngày 1: Phan Thiết – Biển Mũi Né. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), 2, 'Bàu Trắng – Cảnh quan đồi cát', 'Ngày 2: Bàu Trắng – Cảnh quan đồi cát. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), 3, 'Mũi Né – Kết thúc hành trình', 'Ngày 3: Mũi Né – Kết thúc hành trình. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), 1, 'Quy Nhơn – Tham quan thành phố', 'Ngày 1: Quy Nhơn – Tham quan thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), 2, 'Kỳ Co – Trải nghiệm biển', 'Ngày 2: Kỳ Co – Trải nghiệm biển. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), 3, 'Hòn Khô – Kết thúc hành trình', 'Ngày 3: Hòn Khô – Kết thúc hành trình. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), 1, 'Tuy Hòa và tháp Nhạn', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Phú Yên. Tham quan Tuy Hòa và tháp Nhạn, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), 2, 'Gành Đá Đĩa và đầm Ô Loan', 'Ăn sáng tại khách sạn. Tham quan Gành Đá Đĩa và đầm Ô Loan, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Phú Yên.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), 3, 'Mũi Điện và Bãi Môn', 'Ăn sáng và trả phòng. Tham quan Mũi Điện và Bãi Môn, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), 1, 'Biển Nha Trang và tháp Trầm Hương', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Nha Trang. Tham quan Biển Nha Trang và tháp Trầm Hương, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), 2, 'Vịnh Nha Trang', 'Ăn sáng tại khách sạn. Tham quan Vịnh Nha Trang, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Nha Trang.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), 3, 'Tháp Bà Ponagar và tắm khoáng', 'Ăn sáng và trả phòng. Tham quan Tháp Bà Ponagar và tắm khoáng, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), 1, 'Đà Lạt – Khám phá thành phố', 'Ngày 1: Đà Lạt – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), 2, 'Thung lũng Vàng', 'Ngày 2: Thung lũng Vàng. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), 3, 'Đà Lạt – Cảnh quan cao nguyên Lâm Đồng', 'Ngày 3: Đà Lạt – Cảnh quan cao nguyên Lâm Đồng. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    -- Miền Nam
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 1, 'Dinh Cậu', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Phú Quốc. Tham quan Dinh Cậu, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 2, 'Hòn Thơm và Nam đảo', 'Ăn sáng tại khách sạn. Tham quan Hòn Thơm và Nam đảo, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Phú Quốc.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 3, 'Grand World và Bắc đảo', 'Ăn sáng tại khách sạn. Tham quan Grand World và Bắc đảo, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Phú Quốc.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), 4, 'Hàm Ninh', 'Ăn sáng và trả phòng. Tham quan Hàm Ninh, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), 1, 'Côn Đảo – Nhận phòng và nghỉ ngơi', 'Ngày 1: Côn Đảo – Nhận phòng và nghỉ ngơi. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), 2, 'Trải nghiệm thiên nhiên tại Núi Chúa', 'Ngày 2: Trải nghiệm thiên nhiên tại Núi Chúa. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), 3, 'Côn Đảo – Kết thúc hành trình', 'Ngày 3: Côn Đảo – Kết thúc hành trình. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), 1, 'Ninh Kiều và nhà cổ Bình Thủy', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Cần Thơ. Tham quan Ninh Kiều và nhà cổ Bình Thủy, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), 2, 'Chợ nổi Cái Răng và Cồn Sơn', 'Ăn sáng và trả phòng. Tham quan Chợ nổi Cái Răng và Cồn Sơn, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), 1, 'An Giang – Khởi hành đến Châu Đốc', 'Ngày 1: An Giang – Khởi hành đến Châu Đốc. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), 2, 'Rừng tràm Trà Sư', 'Ngày 2: Rừng tràm Trà Sư. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), 3, 'An Giang – Văn hóa địa phương và trở về', 'Ngày 3: An Giang – Văn hóa địa phương và trở về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), 1, 'Đồng Nai – Khởi hành đến Nam Cát Tiên', 'Ngày 1: Đồng Nai – Khởi hành đến Nam Cát Tiên. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), 2, 'Nam Cát Tiên – Trải nghiệm thiên nhiên và trở về', 'Ngày 2: Nam Cát Tiên – Trải nghiệm thiên nhiên và trở về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), 1, 'Kiên Giang – Rạch Giá – Đảo Nam Du', 'Ngày 1: Kiên Giang – Rạch Giá – Đảo Nam Du. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), 2, 'Khám phá đảo Nam Du – Trở về đất liền', 'Ngày 2: Khám phá đảo Nam Du – Trở về đất liền. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), 1, 'Thành phố Cà Mau', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Cà Mau. Tham quan Thành phố Cà Mau, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), 2, 'Đất Mũi', 'Ăn sáng tại khách sạn. Tham quan Đất Mũi, tìm hiểu cảnh quan và sinh hoạt địa phương. Nghỉ ăn trưa, tiếp tục trải nghiệm theo tuyến; buổi tối tự do và nghỉ đêm tại Cà Mau.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), 3, 'Rừng U Minh Hạ', 'Ăn sáng và trả phòng. Tham quan Rừng U Minh Hạ, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), 1, 'Núi Bà Đen', 'Tập trung tại TP. Hồ Chí Minh, di chuyển đến Tây Ninh. Tham quan Núi Bà Đen, nghe giới thiệu về văn hóa địa phương, dùng bữa và nhận phòng nghỉ ngơi.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), 2, 'Tòa Thánh và làng nghề bánh tráng', 'Ăn sáng và trả phòng. Tham quan Tòa Thánh và làng nghề bánh tráng, thưởng thức ẩm thực và mua đặc sản địa phương. Trở về TP. Hồ Chí Minh, kết thúc hành trình.'),
    -- Châu Á
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 1, 'Khởi hành đến Nhật Bản – Di chuyển đến Kyoto', 'Ngày 1: Khởi hành đến Nhật Bản – Di chuyển đến Kyoto. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 2, 'Kyoto – Khám phá văn hóa địa phương', 'Ngày 2: Kyoto – Khám phá văn hóa địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 3, 'Kyoto – Chùa Kiyomizu-dera', 'Ngày 3: Kyoto – Chùa Kiyomizu-dera. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 4, 'Kyoto – Khu phố truyền thống', 'Ngày 4: Kyoto – Khu phố truyền thống. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 5, 'Kyoto – Ẩm thực và trải nghiệm địa phương', 'Ngày 5: Kyoto – Ẩm thực và trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 1, 'Khởi hành đến Seoul', 'Chương trình ngày 1: Khởi hành đến Seoul. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 2, 'Gyeongbokgung và Bukchon', 'Chương trình ngày 2: Gyeongbokgung và Bukchon. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 3, 'Đảo Nami', 'Chương trình ngày 3: Đảo Nami. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 4, 'Suwon và thành Hwaseong', 'Chương trình ngày 4: Suwon và thành Hwaseong. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), 5, 'Myeongdong và trở về Việt Nam', 'Chương trình ngày 5: Myeongdong và trở về Việt Nam. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 1, 'Khởi hành đến Bangkok', 'Ngày 1: Khởi hành đến Bangkok. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 2, 'Bangkok – Di chuyển đến Pattaya', 'Ngày 2: Bangkok – Di chuyển đến Pattaya. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 3, 'Đảo San Hô Coral – Trải nghiệm biển', 'Ngày 3: Đảo San Hô Coral – Trải nghiệm biển. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), 4, 'Pattaya – Bangkok – Trở về Việt Nam', 'Ngày 4: Pattaya – Bangkok – Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 1, 'Khởi hành và công viên Merlion', 'Chương trình ngày 1: Khởi hành và công viên Merlion. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 2, 'Gardens by the Bay và Marina Bay', 'Chương trình ngày 2: Gardens by the Bay và Marina Bay. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 3, 'Đảo Sentosa', 'Chương trình ngày 3: Đảo Sentosa. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), 4, 'Chinatown và trở về Việt Nam', 'Chương trình ngày 4: Chinatown và trở về Việt Nam. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 1, 'Khởi hành đến Kuala Lumpur', 'Ngày 1: Khởi hành đến Kuala Lumpur. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 2, 'Kuala Lumpur – Di chuyển đến Penang', 'Ngày 2: Kuala Lumpur – Di chuyển đến Penang. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 3, 'Penang – Văn hóa và ẩm thực địa phương', 'Ngày 3: Penang – Văn hóa và ẩm thực địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), 4, 'Penang – Trở về Việt Nam', 'Ngày 4: Penang – Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 1, 'Khởi hành đến Đài Bắc', 'Ngày 1: Khởi hành đến Đài Bắc. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 2, 'Đài Bắc – Tham quan thành phố', 'Ngày 2: Đài Bắc – Tham quan thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 3, 'Đài Bắc – Di chuyển đến Đài Trung', 'Ngày 3: Đài Bắc – Di chuyển đến Đài Trung. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 4, 'Đài Trung – Văn hóa và ẩm thực', 'Ngày 4: Đài Trung – Văn hóa và ẩm thực. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), 5, 'Trở về Việt Nam', 'Ngày 5: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 1, 'Khởi hành đến Trung Quốc – Di chuyển theo tuyến', 'Ngày 1: Khởi hành đến Trung Quốc – Di chuyển theo tuyến. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 2, 'Trương Gia Giới – Khám phá cảnh quan', 'Ngày 2: Trương Gia Giới – Khám phá cảnh quan. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 3, 'Trương Gia Giới – Trải nghiệm thiên nhiên', 'Ngày 3: Trương Gia Giới – Trải nghiệm thiên nhiên. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 4, 'Di chuyển đến Phượng Hoàng Cổ Trấn', 'Ngày 4: Di chuyển đến Phượng Hoàng Cổ Trấn. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 5, 'Phượng Hoàng Cổ Trấn – Khám phá phố cổ', 'Ngày 5: Phượng Hoàng Cổ Trấn – Khám phá phố cổ. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 1, 'Khởi hành đến Bali', 'Ngày 1: Khởi hành đến Bali. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 2, 'Nusa Dua – Nghỉ dưỡng bên biển', 'Ngày 2: Nusa Dua – Nghỉ dưỡng bên biển. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 3, 'Đền Tanah Lot', 'Ngày 3: Đền Tanah Lot. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 4, 'Nusa Dua – Trải nghiệm địa phương', 'Ngày 4: Nusa Dua – Trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), 5, 'Trở về Việt Nam', 'Ngày 5: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    -- Châu Âu
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 1, 'Khởi hành đến Anh', 'Ngày 1: Khởi hành đến Anh. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 2, 'Khám phá nước Anh – Di chuyển đến Wales', 'Ngày 2: Khám phá nước Anh – Di chuyển đến Wales. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 3, 'Wales – Trải nghiệm địa phương', 'Ngày 3: Wales – Trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 4, 'Di chuyển đến Scotland', 'Ngày 4: Di chuyển đến Scotland. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 5, 'Scotland – Khám phá cảnh quan và văn hóa', 'Ngày 5: Scotland – Khám phá cảnh quan và văn hóa. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), 6, 'Khởi hành về Việt Nam', 'Ngày 6: Khởi hành về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 1, 'Khởi hành đến Thụy Sĩ', 'Ngày 1: Khởi hành đến Thụy Sĩ. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 2, 'Di chuyển đến Lucerne – Khám phá phố cổ', 'Ngày 2: Di chuyển đến Lucerne – Khám phá phố cổ. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 3, 'Lucerne – Tháp nước Wasserturm', 'Ngày 3: Lucerne – Tháp nước Wasserturm. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 4, 'Lucerne – Cảnh quan ven hồ', 'Ngày 4: Lucerne – Cảnh quan ven hồ. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 5, 'Lucerne – Văn hóa và trải nghiệm địa phương', 'Ngày 5: Lucerne – Văn hóa và trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 1, 'Khởi hành đến Ý', 'Ngày 1: Khởi hành đến Ý. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 2, 'Milan – Khám phá vùng Lombardy', 'Ngày 2: Milan – Khám phá vùng Lombardy. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 3, 'Lombardy – Di chuyển đến hồ Como', 'Ngày 3: Lombardy – Di chuyển đến hồ Como. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 4, 'Hồ Como – Cảnh quan ven hồ', 'Ngày 4: Hồ Como – Cảnh quan ven hồ. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 5, 'Hồ Como – Trải nghiệm địa phương', 'Ngày 5: Hồ Como – Trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 6, 'Lombardy – Nghỉ ngơi và chuẩn bị hành lý', 'Ngày 6: Lombardy – Nghỉ ngơi và chuẩn bị hành lý. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), 7, 'Trở về Việt Nam', 'Ngày 7: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 1, 'Khởi hành đến Munich', 'Ngày 1: Khởi hành đến Munich. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 2, 'Munich – Khám phá thành phố', 'Ngày 2: Munich – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 3, 'Munich – Di chuyển đến Cologne', 'Ngày 3: Munich – Di chuyển đến Cologne. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 4, 'Cologne – Cầu Hohenzollern', 'Ngày 4: Cologne – Cầu Hohenzollern. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 5, 'Cologne – Văn hóa địa phương', 'Ngày 5: Cologne – Văn hóa địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 1, 'Khởi hành đến Istanbul', 'Ngày 1: Khởi hành đến Istanbul. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 2, 'Istanbul – Khám phá thành phố', 'Ngày 2: Istanbul – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 3, 'Istanbul – Di chuyển đến Canakkale', 'Ngày 3: Istanbul – Di chuyển đến Canakkale. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 4, 'Canakkale – Văn hóa và cảnh quan', 'Ngày 4: Canakkale – Văn hóa và cảnh quan. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 5, 'Canakkale – Trở lại Istanbul', 'Ngày 5: Canakkale – Trở lại Istanbul. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 1, 'Khởi hành đến Amsterdam', 'Ngày 1: Khởi hành đến Amsterdam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 2, 'Amsterdam – Kênh đào và phố cổ', 'Ngày 2: Amsterdam – Kênh đào và phố cổ. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 3, 'Làng cối xay gió Zaanse Schans', 'Ngày 3: Làng cối xay gió Zaanse Schans. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 4, 'Amsterdam – Văn hóa và trải nghiệm địa phương', 'Ngày 4: Amsterdam – Văn hóa và trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), 5, 'Trở về Việt Nam', 'Ngày 5: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 1, 'Khởi hành đến Copenhagen', 'Ngày 1: Khởi hành đến Copenhagen. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 2, 'Copenhagen – Khám phá thành phố', 'Ngày 2: Copenhagen – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 3, 'Đài phun nước Gefion', 'Ngày 3: Đài phun nước Gefion. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 4, 'Copenhagen – Văn hóa và đời sống', 'Ngày 4: Copenhagen – Văn hóa và đời sống. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 5, 'Copenhagen – Trải nghiệm địa phương', 'Ngày 5: Copenhagen – Trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), 6, 'Trở về Việt Nam', 'Ngày 6: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 1, 'Khởi hành đến Moscow', 'Ngày 1: Khởi hành đến Moscow. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 2, 'Moscow – Khám phá thành phố', 'Ngày 2: Moscow – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 3, 'Moscow – Di chuyển đến Murmansk', 'Ngày 3: Moscow – Di chuyển đến Murmansk. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 4, 'Murmansk – Cảnh quan và văn hóa địa phương', 'Ngày 4: Murmansk – Cảnh quan và văn hóa địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 5, 'Murmansk – Chuẩn bị hành trình trở về', 'Ngày 5: Murmansk – Chuẩn bị hành trình trở về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), 6, 'Khởi hành về Việt Nam', 'Ngày 6: Khởi hành về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    -- Châu Mỹ – Châu Úc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 1, 'Khởi hành đến Los Angeles', 'Chương trình ngày 1: Khởi hành đến Los Angeles. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 2, 'Hollywood và Santa Monica', 'Chương trình ngày 2: Hollywood và Santa Monica. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 3, 'Di chuyển đến Las Vegas', 'Chương trình ngày 3: Di chuyển đến Las Vegas. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 4, 'Grand Canyon', 'Chương trình ngày 4: Grand Canyon. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 5, 'Las Vegas', 'Chương trình ngày 5: Las Vegas. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 6, 'Trở lại Los Angeles', 'Chương trình ngày 6: Trở lại Los Angeles. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 7, 'Khởi hành về Việt Nam', 'Chương trình ngày 7: Khởi hành về Việt Nam. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), 8, 'Về đến Việt Nam', 'Chương trình ngày 8: Về đến Việt Nam. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 1, 'Khởi hành đến Vancouver', 'Chương trình ngày 1: Khởi hành đến Vancouver. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 2, 'Stanley và Granville', 'Chương trình ngày 2: Stanley và Granville. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 3, 'Whistler', 'Chương trình ngày 3: Whistler. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 4, 'Di chuyển đến Victoria', 'Chương trình ngày 4: Di chuyển đến Victoria. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 5, 'Butchart và cảng Victoria', 'Chương trình ngày 5: Butchart và cảng Victoria. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 6, 'Vancouver và khởi hành về', 'Chương trình ngày 6: Vancouver và khởi hành về. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), 7, 'Về đến Việt Nam', 'Chương trình ngày 7: Về đến Việt Nam. Ngày tham quan bố trí thời gian tìm hiểu điểm đến, dùng bữa và nghỉ ngơi; ngày di chuyển dành cho thủ tục, nối chuyến hoặc nhận phòng. Thứ tự hoạt động có thể điều chỉnh theo giờ bay và điều kiện thực tế. Đây là chương trình demo, chưa xác nhận dịch vụ.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 1, 'Khởi hành đến Mexico – Nối chuyến quốc tế', 'Ngày 1: Khởi hành đến Mexico – Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 2, 'Đến Mexico – Nghỉ ngơi sau chuyến bay', 'Ngày 2: Đến Mexico – Nghỉ ngơi sau chuyến bay. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 3, 'Di chuyển đến San Miguel de Allende', 'Ngày 3: Di chuyển đến San Miguel de Allende. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 4, 'San Miguel de Allende – Khám phá thành phố', 'Ngày 4: San Miguel de Allende – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 5, 'Di chuyển đến Guanajuato', 'Ngày 5: Di chuyển đến Guanajuato. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 6, 'Guanajuato – Khám phá văn hóa địa phương', 'Ngày 6: Guanajuato – Khám phá văn hóa địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 7, 'Khởi hành về Việt Nam', 'Ngày 7: Khởi hành về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), 8, 'Về đến Việt Nam', 'Ngày 8: Về đến Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 1, 'Khởi hành đến Brazil – Nối chuyến quốc tế', 'Ngày 1: Khởi hành đến Brazil – Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 2, 'Rio de Janeiro – Nghỉ ngơi sau chuyến bay', 'Ngày 2: Rio de Janeiro – Nghỉ ngơi sau chuyến bay. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 3, 'Rio de Janeiro – Khám phá thành phố', 'Ngày 3: Rio de Janeiro – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 4, 'Rio de Janeiro – Cảnh quan và văn hóa', 'Ngày 4: Rio de Janeiro – Cảnh quan và văn hóa. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 5, 'Rio de Janeiro – Trải nghiệm biển', 'Ngày 5: Rio de Janeiro – Trải nghiệm biển. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 6, 'Khởi hành về Việt Nam', 'Ngày 6: Khởi hành về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 7, 'Nối chuyến quốc tế', 'Ngày 7: Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), 8, 'Về đến Việt Nam', 'Ngày 8: Về đến Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 1, 'Khởi hành đến Costa Rica – Nối chuyến quốc tế', 'Ngày 1: Khởi hành đến Costa Rica – Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 2, 'Đến San Jose – Nghỉ ngơi sau chuyến bay', 'Ngày 2: Đến San Jose – Nghỉ ngơi sau chuyến bay. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 3, 'Di chuyển đến khu vực Manuel Antonio', 'Ngày 3: Di chuyển đến khu vực Manuel Antonio. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 4, 'Vườn quốc gia Manuel Antonio – Khám phá thiên nhiên', 'Ngày 4: Vườn quốc gia Manuel Antonio – Khám phá thiên nhiên. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 5, 'Manuel Antonio – Cảnh quan và nghỉ ngơi', 'Ngày 5: Manuel Antonio – Cảnh quan và nghỉ ngơi. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 6, 'Trở lại San Jose – Khởi hành về', 'Ngày 6: Trở lại San Jose – Khởi hành về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 7, 'Nối chuyến quốc tế', 'Ngày 7: Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), 8, 'Về đến Việt Nam', 'Ngày 8: Về đến Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 1, 'Khởi hành đến Argentina – Nối chuyến quốc tế', 'Ngày 1: Khởi hành đến Argentina – Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 2, 'Buenos Aires – Nghỉ ngơi sau chuyến bay', 'Ngày 2: Buenos Aires – Nghỉ ngơi sau chuyến bay. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 3, 'Di chuyển đến Ushuaia', 'Ngày 3: Di chuyển đến Ushuaia. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 4, 'Công viên quốc gia Tierra del Fuego', 'Ngày 4: Công viên quốc gia Tierra del Fuego. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 5, 'Ushuaia – Cảnh quan và trải nghiệm địa phương', 'Ngày 5: Ushuaia – Cảnh quan và trải nghiệm địa phương. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 6, 'Trở lại Buenos Aires – Khởi hành về', 'Ngày 6: Trở lại Buenos Aires – Khởi hành về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 7, 'Nối chuyến quốc tế', 'Ngày 7: Nối chuyến quốc tế. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), 8, 'Về đến Việt Nam', 'Ngày 8: Về đến Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 1, 'Khởi hành đến Sydney', 'Ngày 1: Khởi hành đến Sydney. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 2, 'Sydney – Nhà hát Opera và khu vực cảng', 'Ngày 2: Sydney – Nhà hát Opera và khu vực cảng. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 3, 'Blue Mountains – Khám phá cảnh quan', 'Ngày 3: Blue Mountains – Khám phá cảnh quan. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 4, 'Blue Mountains – Trải nghiệm thiên nhiên', 'Ngày 4: Blue Mountains – Trải nghiệm thiên nhiên. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 5, 'Trở lại Sydney – Khám phá thành phố', 'Ngày 5: Trở lại Sydney – Khám phá thành phố. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 6, 'Sydney – Nghỉ ngơi và chuẩn bị hành lý', 'Ngày 6: Sydney – Nghỉ ngơi và chuẩn bị hành lý. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), 7, 'Trở về Việt Nam', 'Ngày 7: Trở về Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),

    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 1, 'Khởi hành đến Auckland', 'Ngày 1: Khởi hành đến Auckland. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 2, 'Auckland – Khám phá thành phố', 'Ngày 2: Auckland – Khám phá thành phố. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 3, 'Auckland – Di chuyển đến Rotorua', 'Ngày 3: Auckland – Di chuyển đến Rotorua. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 4, 'Rotorua – Văn hóa Maori', 'Ngày 4: Rotorua – Văn hóa Maori. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 5, 'Rotorua – Cảnh quan địa nhiệt', 'Ngày 5: Rotorua – Cảnh quan địa nhiệt. Dành thời gian tham quan theo tuyến, tìm hiểu cảnh quan và đời sống địa phương. Bố trí thời gian dùng bữa, nghỉ ngơi và trở về nơi lưu trú sau chương trình. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 6, 'Trở lại Auckland – Khởi hành về', 'Ngày 6: Trở lại Auckland – Khởi hành về. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), 7, 'Về đến Việt Nam', 'Ngày 7: Về đến Việt Nam. Bố trí phương tiện theo tuyến, thời gian làm thủ tục và nghỉ ngơi. Các điểm tham quan trong ngày được sắp xếp theo giờ đến và điều kiện thực tế. Lịch trình demo; dịch vụ, giờ bay, thời tiết và điều kiện tham quan cần được xác nhận trước khi tổ chức.')

ON DUPLICATE KEY UPDATE id_lich_trinh_ngay = id_lich_trinh_ngay;

-- 8.5. Lịch khởi hành
-- Bảng tạm chỉ tồn tại trong phiên import để tránh thêm trùng lịch.
CREATE TEMPORARY TABLE du_lieu_lich_khoi_hanh LIKE lich_khoi_hanh;
INSERT INTO du_lieu_lich_khoi_hanh (id_tour, ngay_khoi_hanh, diem_khoi_hanh, gia_nguoi_lon, gia_tre_em, so_cho, han_dat, trang_thai) VALUES
    -- Miền Bắc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), '2026-12-05', 'Hà Nội', 3290000, 2303000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), '2027-01-09', 'Hà Nội', 3290000, 2303000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb01'), '2027-02-13', 'Hà Nội', 3290000, 2303000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), '2026-12-12', 'Hà Nội', 4590000, 3213000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), '2027-01-16', 'Hà Nội', 4590000, 3213000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb02'), '2027-02-20', 'Hà Nội', 4590000, 3213000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), '2026-12-19', 'Hà Nội', 4890000, 3423000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), '2027-01-23', 'Hà Nội', 4890000, 3423000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb03'), '2027-02-27', 'Hà Nội', 4890000, 3423000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), '2026-12-26', 'Hà Nội', 5290000, 3703000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), '2027-01-30', 'Hà Nội', 5290000, 3703000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb04'), '2027-03-06', 'Hà Nội', 5290000, 3703000, 30, '2027-03-03 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), '2026-12-05', 'Hà Nội', 2990000, 2093000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), '2027-01-09', 'Hà Nội', 2990000, 2093000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb05'), '2027-02-13', 'Hà Nội', 2990000, 2093000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), '2026-12-12', 'Hà Nội', 3990000, 2793000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), '2027-01-16', 'Hà Nội', 3990000, 2793000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb06'), '2027-02-20', 'Hà Nội', 3990000, 2793000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), '2026-12-19', 'Hà Nội', 2590000, 1813000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), '2027-01-23', 'Hà Nội', 2590000, 1813000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb07'), '2027-02-27', 'Hà Nội', 2590000, 1813000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), '2026-12-26', 'Hà Nội', 2890000, 2023000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), '2027-01-30', 'Hà Nội', 2890000, 2023000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mb08'), '2027-03-06', 'Hà Nội', 2890000, 2023000, 30, '2027-03-03 17:00:00', 'open'),
    -- Miền Trung
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), '2026-12-05', 'TP. Hồ Chí Minh', 4990000, 3493000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), '2027-01-09', 'TP. Hồ Chí Minh', 4990000, 3493000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt01'), '2027-02-13', 'TP. Hồ Chí Minh', 4990000, 3493000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), '2026-12-12', 'TP. Hồ Chí Minh', 3890000, 2723000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), '2027-01-16', 'TP. Hồ Chí Minh', 3890000, 2723000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt02'), '2027-02-20', 'TP. Hồ Chí Minh', 3890000, 2723000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), '2026-12-19', 'TP. Hồ Chí Minh', 4190000, 2933000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), '2027-01-23', 'TP. Hồ Chí Minh', 4190000, 2933000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt03'), '2027-02-27', 'TP. Hồ Chí Minh', 4190000, 2933000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), '2026-12-26', 'TP. Hồ Chí Minh', 4690000, 3283000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), '2027-01-30', 'TP. Hồ Chí Minh', 4690000, 3283000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt04'), '2027-03-06', 'TP. Hồ Chí Minh', 4690000, 3283000, 30, '2027-03-03 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), '2026-12-05', 'TP. Hồ Chí Minh', 4290000, 3003000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), '2027-01-09', 'TP. Hồ Chí Minh', 4290000, 3003000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt05'), '2027-02-13', 'TP. Hồ Chí Minh', 4290000, 3003000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), '2026-12-12', 'TP. Hồ Chí Minh', 3990000, 2793000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), '2027-01-16', 'TP. Hồ Chí Minh', 3990000, 2793000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt06'), '2027-02-20', 'TP. Hồ Chí Minh', 3990000, 2793000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), '2026-12-19', 'TP. Hồ Chí Minh', 4490000, 3143000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), '2027-01-23', 'TP. Hồ Chí Minh', 4490000, 3143000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt07'), '2027-02-27', 'TP. Hồ Chí Minh', 4490000, 3143000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), '2026-12-26', 'TP. Hồ Chí Minh', 3490000, 2443000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), '2027-01-30', 'TP. Hồ Chí Minh', 3490000, 2443000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mt08'), '2027-03-06', 'TP. Hồ Chí Minh', 3490000, 2443000, 30, '2027-03-03 17:00:00', 'open'),
    -- Miền Nam
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), '2026-12-05', 'TP. Hồ Chí Minh', 5990000, 4193000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), '2027-01-09', 'TP. Hồ Chí Minh', 5990000, 4193000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn01'), '2027-02-13', 'TP. Hồ Chí Minh', 5990000, 4193000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), '2026-12-12', 'TP. Hồ Chí Minh', 6990000, 4893000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), '2027-01-16', 'TP. Hồ Chí Minh', 6990000, 4893000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn02'), '2027-02-20', 'TP. Hồ Chí Minh', 6990000, 4893000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), '2026-12-19', 'TP. Hồ Chí Minh', 2490000, 1743000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), '2027-01-23', 'TP. Hồ Chí Minh', 2490000, 1743000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn03'), '2027-02-27', 'TP. Hồ Chí Minh', 2490000, 1743000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), '2026-12-26', 'TP. Hồ Chí Minh', 2790000, 1953000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), '2027-01-30', 'TP. Hồ Chí Minh', 2790000, 1953000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn04'), '2027-03-06', 'TP. Hồ Chí Minh', 2790000, 1953000, 30, '2027-03-03 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), '2026-12-05', 'TP. Hồ Chí Minh', 2290000, 1603000, 30, '2026-12-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), '2027-01-09', 'TP. Hồ Chí Minh', 2290000, 1603000, 30, '2027-01-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn05'), '2027-02-13', 'TP. Hồ Chí Minh', 2290000, 1603000, 30, '2027-02-10 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), '2026-12-12', 'TP. Hồ Chí Minh', 1990000, 1393000, 30, '2026-12-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), '2027-01-16', 'TP. Hồ Chí Minh', 1990000, 1393000, 30, '2027-01-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn06'), '2027-02-20', 'TP. Hồ Chí Minh', 1990000, 1393000, 30, '2027-02-17 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), '2026-12-19', 'TP. Hồ Chí Minh', 3290000, 2303000, 30, '2026-12-16 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), '2027-01-23', 'TP. Hồ Chí Minh', 3290000, 2303000, 30, '2027-01-20 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn07'), '2027-02-27', 'TP. Hồ Chí Minh', 3290000, 2303000, 30, '2027-02-24 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), '2026-12-26', 'TP. Hồ Chí Minh', 1890000, 1323000, 30, '2026-12-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), '2027-01-30', 'TP. Hồ Chí Minh', 1890000, 1323000, 30, '2027-01-27 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'mn08'), '2027-03-06', 'TP. Hồ Chí Minh', 1890000, 1323000, 30, '2027-03-03 17:00:00', 'open'),
    -- Châu Á
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), '2026-12-05', 'TP. Hồ Chí Minh', 32990000, 23093000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), '2027-01-09', 'TP. Hồ Chí Minh', 32990000, 23093000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as01'), '2027-02-13', 'TP. Hồ Chí Minh', 32990000, 23093000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), '2026-12-12', 'TP. Hồ Chí Minh', 15990000, 11193000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), '2027-01-16', 'TP. Hồ Chí Minh', 15990000, 11193000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as02'), '2027-02-20', 'TP. Hồ Chí Minh', 15990000, 11193000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), '2026-12-19', 'TP. Hồ Chí Minh', 8990000, 6293000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), '2027-01-23', 'TP. Hồ Chí Minh', 8990000, 6293000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as03'), '2027-02-27', 'TP. Hồ Chí Minh', 8990000, 6293000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), '2026-12-26', 'TP. Hồ Chí Minh', 12990000, 9093000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), '2027-01-30', 'TP. Hồ Chí Minh', 12990000, 9093000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as04'), '2027-03-06', 'TP. Hồ Chí Minh', 12990000, 9093000, 25, '2027-02-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), '2026-12-05', 'TP. Hồ Chí Minh', 9990000, 6993000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), '2027-01-09', 'TP. Hồ Chí Minh', 9990000, 6993000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as05'), '2027-02-13', 'TP. Hồ Chí Minh', 9990000, 6993000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), '2026-12-12', 'TP. Hồ Chí Minh', 16990000, 11893000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), '2027-01-16', 'TP. Hồ Chí Minh', 16990000, 11893000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as06'), '2027-02-20', 'TP. Hồ Chí Minh', 16990000, 11893000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), '2026-12-19', 'TP. Hồ Chí Minh', 22990000, 16093000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), '2027-01-23', 'TP. Hồ Chí Minh', 22990000, 16093000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as07'), '2027-02-27', 'TP. Hồ Chí Minh', 22990000, 16093000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), '2026-12-26', 'TP. Hồ Chí Minh', 14990000, 10493000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), '2027-01-30', 'TP. Hồ Chí Minh', 14990000, 10493000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'as08'), '2027-03-06', 'TP. Hồ Chí Minh', 14990000, 10493000, 25, '2027-02-13 17:00:00', 'open'),
    -- Châu Âu
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), '2026-12-05', 'TP. Hồ Chí Minh', 62990000, 44093000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), '2027-01-09', 'TP. Hồ Chí Minh', 62990000, 44093000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu01'), '2027-02-13', 'TP. Hồ Chí Minh', 62990000, 44093000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), '2026-12-12', 'TP. Hồ Chí Minh', 79990000, 55993000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), '2027-01-16', 'TP. Hồ Chí Minh', 79990000, 55993000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu02'), '2027-02-20', 'TP. Hồ Chí Minh', 79990000, 55993000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), '2026-12-19', 'TP. Hồ Chí Minh', 65990000, 46193000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), '2027-01-23', 'TP. Hồ Chí Minh', 65990000, 46193000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu03'), '2027-02-27', 'TP. Hồ Chí Minh', 65990000, 46193000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), '2026-12-26', 'TP. Hồ Chí Minh', 59990000, 41993000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), '2027-01-30', 'TP. Hồ Chí Minh', 59990000, 41993000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu04'), '2027-03-06', 'TP. Hồ Chí Minh', 59990000, 41993000, 25, '2027-02-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), '2026-12-05', 'TP. Hồ Chí Minh', 63990000, 44793000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), '2027-01-09', 'TP. Hồ Chí Minh', 63990000, 44793000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu05'), '2027-02-13', 'TP. Hồ Chí Minh', 63990000, 44793000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), '2026-12-12', 'TP. Hồ Chí Minh', 58990000, 41293000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), '2027-01-16', 'TP. Hồ Chí Minh', 58990000, 41293000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu06'), '2027-02-20', 'TP. Hồ Chí Minh', 58990000, 41293000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), '2026-12-19', 'TP. Hồ Chí Minh', 61990000, 43393000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), '2027-01-23', 'TP. Hồ Chí Minh', 61990000, 43393000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu07'), '2027-02-27', 'TP. Hồ Chí Minh', 61990000, 43393000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), '2026-12-26', 'TP. Hồ Chí Minh', 54990000, 38493000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), '2027-01-30', 'TP. Hồ Chí Minh', 54990000, 38493000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'eu08'), '2027-03-06', 'TP. Hồ Chí Minh', 54990000, 38493000, 25, '2027-02-13 17:00:00', 'open'),
    -- Châu Mỹ – Châu Úc
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), '2026-12-05', 'TP. Hồ Chí Minh', 85990000, 60193000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), '2027-01-09', 'TP. Hồ Chí Minh', 85990000, 60193000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us01'), '2027-02-13', 'TP. Hồ Chí Minh', 85990000, 60193000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), '2026-12-12', 'TP. Hồ Chí Minh', 82990000, 58093000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), '2027-01-16', 'TP. Hồ Chí Minh', 82990000, 58093000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us02'), '2027-02-20', 'TP. Hồ Chí Minh', 82990000, 58093000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), '2026-12-19', 'TP. Hồ Chí Minh', 89990000, 62993000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), '2027-01-23', 'TP. Hồ Chí Minh', 89990000, 62993000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us03'), '2027-02-27', 'TP. Hồ Chí Minh', 89990000, 62993000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), '2026-12-26', 'TP. Hồ Chí Minh', 109990000, 76993000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), '2027-01-30', 'TP. Hồ Chí Minh', 109990000, 76993000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us04'), '2027-03-06', 'TP. Hồ Chí Minh', 109990000, 76993000, 25, '2027-02-13 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), '2026-12-05', 'TP. Hồ Chí Minh', 119990000, 83993000, 25, '2026-11-14 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), '2027-01-09', 'TP. Hồ Chí Minh', 119990000, 83993000, 25, '2026-12-19 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us05'), '2027-02-13', 'TP. Hồ Chí Minh', 119990000, 83993000, 25, '2027-01-23 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), '2026-12-12', 'TP. Hồ Chí Minh', 114990000, 80493000, 25, '2026-11-21 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), '2027-01-16', 'TP. Hồ Chí Minh', 114990000, 80493000, 25, '2026-12-26 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us06'), '2027-02-20', 'TP. Hồ Chí Minh', 114990000, 80493000, 25, '2027-01-30 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), '2026-12-19', 'TP. Hồ Chí Minh', 52990000, 37093000, 25, '2026-11-28 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), '2027-01-23', 'TP. Hồ Chí Minh', 52990000, 37093000, 25, '2027-01-02 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us07'), '2027-02-27', 'TP. Hồ Chí Minh', 52990000, 37093000, 25, '2027-02-06 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), '2026-12-26', 'TP. Hồ Chí Minh', 69990000, 48993000, 25, '2026-12-05 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), '2027-01-30', 'TP. Hồ Chí Minh', 69990000, 48993000, 25, '2027-01-09 17:00:00', 'open'),
    ((SELECT id_tour FROM tour WHERE ma_tour = 'us08'), '2027-03-06', 'TP. Hồ Chí Minh', 69990000, 48993000, 25, '2027-02-13 17:00:00', 'open');

-- Chỉ thêm lịch chưa có; giữ nguyên giá và sức chứa đã chỉnh sửa.
INSERT INTO lich_khoi_hanh (id_tour, ngay_khoi_hanh, diem_khoi_hanh, gia_nguoi_lon, gia_tre_em, so_cho, han_dat, trang_thai)
SELECT moi.id_tour, moi.ngay_khoi_hanh, moi.diem_khoi_hanh, moi.gia_nguoi_lon, moi.gia_tre_em, moi.so_cho, moi.han_dat, moi.trang_thai
FROM du_lieu_lich_khoi_hanh AS moi
WHERE NOT EXISTS (
    SELECT 1 FROM lich_khoi_hanh AS cu
    WHERE cu.id_tour = moi.id_tour
      AND cu.ngay_khoi_hanh = moi.ngay_khoi_hanh
      AND cu.diem_khoi_hanh = moi.diem_khoi_hanh
);
DROP TEMPORARY TABLE du_lieu_lich_khoi_hanh;

COMMIT;
