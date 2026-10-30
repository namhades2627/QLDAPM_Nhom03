-- =============================================================================
-- OnTrip — Dữ liệu mẫu cho bảng đơn đặt tour (tùy chọn)
-- Ngày tạo   : 30/10/2026
-- Áp dụng cho: database `ontrip`
-- Thứ tự chạy: sau file 2026-10-30_01_them_ngay_ve_va_bang_don_dat_tour.sql
-- Chạy lại    : an toàn (mã đơn cố định + NOT EXISTS, không nhân đôi đơn)
-- Mục đích    : có sẵn vài đơn để xem chỗ còn giảm và danh sách khách theo chuyến.
--               Có thể xóa hai đơn này khi chạy dữ liệu thật.
-- Ghi chú     : xem database/NHAT_KY.md
-- =============================================================================

USE `ontrip`;

-- OTDEMO0001: 2 người lớn + 1 trẻ em trên chuyến đầu tiên của tour mb01, đã xác nhận.
-- 3 khách nên ưu đãi nhóm 0%; số tiền tính từ đơn giá của chính lịch đó.
INSERT INTO `don_dat_tour`
    (`ma_don`, `id_lich_khoi_hanh`, `id_tai_khoan`, `ho_ten`, `email`, `so_dien_thoai`,
     `so_nguoi_lon`, `so_tre_em`, `gia_nguoi_lon`, `gia_tre_em`, `tong_goc`, `muc_giam`,
     `tien_giam`, `tong_thanh_toan`, `ghi_chu`, `trang_thai`)
SELECT 'OTDEMO0001', l.id_lich_khoi_hanh, tk.id_tai_khoan, tk.ho_ten, tk.email, tk.so_dien_thoai,
       2, 1, l.gia_nguoi_lon, l.gia_tre_em,
       2 * l.gia_nguoi_lon + 1 * l.gia_tre_em, 0, 0,
       2 * l.gia_nguoi_lon + 1 * l.gia_tre_em,
       'Đơn mẫu: 2 người lớn, 1 trẻ em', 'da_xac_nhan'
FROM `lich_khoi_hanh` l
JOIN `tour` t ON t.id_tour = l.id_tour
JOIN `tai_khoan` tk ON tk.email = 'khachhang@gmail.com'
WHERE t.ma_tour = 'mb01' AND l.ngay_khoi_hanh = '2026-12-05'
  AND NOT EXISTS (SELECT 1 FROM `don_dat_tour` d WHERE d.ma_don = 'OTDEMO0001')
LIMIT 1;

-- OTDEMO0002: 1 người lớn trên chuyến đầu tiên của tour mt01, đang chờ xác nhận.
INSERT INTO `don_dat_tour`
    (`ma_don`, `id_lich_khoi_hanh`, `id_tai_khoan`, `ho_ten`, `email`, `so_dien_thoai`,
     `so_nguoi_lon`, `so_tre_em`, `gia_nguoi_lon`, `gia_tre_em`, `tong_goc`, `muc_giam`,
     `tien_giam`, `tong_thanh_toan`, `ghi_chu`, `trang_thai`)
SELECT 'OTDEMO0002', l.id_lich_khoi_hanh, tk.id_tai_khoan, tk.ho_ten, tk.email, tk.so_dien_thoai,
       1, 0, l.gia_nguoi_lon, l.gia_tre_em,
       l.gia_nguoi_lon, 0, 0, l.gia_nguoi_lon,
       'Đơn mẫu chờ xác nhận', 'cho_xac_nhan'
FROM `lich_khoi_hanh` l
JOIN `tour` t ON t.id_tour = l.id_tour
JOIN `tai_khoan` tk ON tk.email = 'khachhang@gmail.com'
WHERE t.ma_tour = 'mt01' AND l.ngay_khoi_hanh = '2026-12-05'
  AND NOT EXISTS (SELECT 1 FROM `don_dat_tour` d WHERE d.ma_don = 'OTDEMO0002')
LIMIT 1;
