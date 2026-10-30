# Nhật ký thay đổi database `ontrip`

Quy ước của nhóm:

- **Không export đè `database/ontrip.sql`.** Tệp đó chỉ là bản cài đặt ban đầu, giữ nguyên để mọi người import một lần là chạy được.
- Mỗi lần tạo bảng hoặc thêm cột mới, viết **một script SQL riêng** trong thư mục `database/`, đặt tên theo `YYYY-MM-DD_so-thu-tu_mo-ta-ngan.sql` để biết thứ tự chạy.
- Script phải **an toàn khi chạy lại** (`CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, `INSERT ... WHERE NOT EXISTS`) và không xóa dữ liệu đang có.
- Ghi một dòng vào nhật ký này để người khác biết cần import tệp nào, bắt buộc hay tùy chọn.

Cách import: mở **phpMyAdmin → chọn database `ontrip` → tab Import → Choose File → Go**. Kiểm tra lại bằng tab **Structure** (đủ cột/bảng) và **Browse** (dữ liệu mẫu nếu có).

## Danh sách thay đổi

| Ngày | Tệp script | Nội dung | Bắt buộc | Trạng thái trên máy phát triển |
| --- | --- | --- | --- | --- |
| 30/10/2026 | [`2026-10-30_01_them_ngay_ve_va_bang_don_dat_tour.sql`](2026-10-30_01_them_ngay_ve_va_bang_don_dat_tour.sql) | Thêm cột `lich_khoi_hanh.ngay_ve` (và điền ngày về cho 144 lịch demo), tạo bảng `don_dat_tour` | **Có** — thiếu thì trang chi tiết tour và trang lịch khởi hành của quản trị sẽ lỗi | Đã import |
| 30/10/2026 | [`2026-10-30_02_du_lieu_mau_don_dat_tour.sql`](2026-10-30_02_du_lieu_mau_don_dat_tour.sql) | Hai đơn mẫu `OTDEMO0001`, `OTDEMO0002` để thấy chỗ còn giảm và danh sách khách theo chuyến | Không — chỉ để xem trước giao diện | Đã import |

Thứ tự chạy: `ontrip.sql` (máy mới) → `..._01_...sql` → `..._02_...sql` (nếu muốn có dữ liệu mẫu).

## Chi tiết từng thay đổi

### 30/10/2026 — Module lịch khởi hành và số chỗ

- `lich_khoi_hanh.ngay_ve DATE NULL`: ngày về của chuyến. Để trống thì hệ thống suy ra từ `ngay_khoi_hanh` + `tour.so_ngay − 1`; script đã điền sẵn cho dữ liệu demo.
- Bảng `don_dat_tour`: đơn đặt tour. Lưu `so_nguoi_lon`, `so_tre_em`, **đơn giá tại thời điểm đặt**, `tong_goc`, `muc_giam`, `tien_giam`, `tong_thanh_toan`, `trang_thai` (`cho_xac_nhan`, `da_xac_nhan`, `da_huy`, `hoan_thanh`), thời điểm tạo/cập nhật.
- Chỗ còn **không được lưu thành cột** mà tính động: `so_cho − tổng (so_nguoi_lon + so_tre_em)` của các đơn `cho_xac_nhan`/`da_xac_nhan`. Vì vậy không cần script đồng bộ số chỗ khi hủy đơn.
- Hai đơn mẫu thuộc tài khoản `khachhang@gmail.com`, chạy lại không nhân đôi.
