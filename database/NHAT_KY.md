# Nhật ký thay đổi database `ontrip`

Quy ước của nhóm:

- **`database/ontrip.sql` là nguồn duy nhất.** Mọi thay đổi cấu trúc và dữ liệu mẫu đều được gộp thẳng vào tệp này rồi export lại; không tạo script rời.
- Máy mới chỉ cần **import một tệp duy nhất**: `database/ontrip.sql`.
- Tệp phải **an toàn khi chạy lại**: dùng `CREATE TABLE IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS`, `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE`, `INSERT ... WHERE NOT EXISTS`, `UPDATE ... WHERE <cột> IS NULL`. Nhờ vậy import lại trên database đang có dữ liệu sẽ **bổ sung phần còn thiếu** mà không xóa đơn, không nhân đôi bản ghi, không ghi đè giá đã chỉnh.
- Sau khi sửa `ontrip.sql`, ghi một dòng vào nhật ký này để người khác biết cần import lại.

Cách import: mở **phpMyAdmin → tab Import → Choose File → `ontrip.sql` → Go** (tệp tự chạy `CREATE DATABASE IF NOT EXISTS ontrip` và `USE ontrip`, không cần chọn database trước). Kiểm tra lại bằng tab **Structure** (đủ bảng/cột) và **Browse** (dữ liệu mẫu).

## Danh sách thay đổi

| Ngày | Nội dung | Vị trí trong `ontrip.sql` | Bắt buộc |
| --- | --- | --- | --- |
| 30/10/2026 | Thêm cột `lich_khoi_hanh.ngay_ve` và điền ngày về cho 144 lịch demo | mục **7. Lịch khởi hành tour**, mục **9.6** (câu `UPDATE` điền ngày về) | **Có** — thiếu thì trang chi tiết tour và trang lịch khởi hành của quản trị sẽ lỗi |
| 30/10/2026 | Tạo bảng `don_dat_tour` | mục **8. Đơn đặt tour** | **Có** — bảng lưu đơn đặt tour của module lịch khởi hành và số chỗ |
| 30/10/2026 | Hai đơn mẫu `OTDEMO0001`, `OTDEMO0002` | mục **10. Đơn đặt tour mẫu** (cuối tệp) | Không — chỉ để xem trước giao diện, có thể xóa khi chạy dữ liệu thật |

Bản `ontrip.sql` hiện tại tạo **9 bảng**: `khu_vuc_tour`, `diem_den`, `nhu_cau`, `tour`, `lich_trinh_ngay`, `tour_nhu_cau`, `lich_khoi_hanh`, `tai_khoan`, `don_dat_tour`.

## Chi tiết từng thay đổi

### 30/10/2026 — Module lịch khởi hành và số chỗ

Đã gộp trực tiếp vào `ontrip.sql`, không cần import tệp phụ.

- `lich_khoi_hanh.ngay_ve DATE NULL`: ngày về của chuyến. Để trống thì hệ thống suy ra từ `ngay_khoi_hanh` + `tour.so_ngay − 1`; câu `UPDATE` ở mục 9.6 điền sẵn cho dữ liệu demo. Ngay sau `CREATE TABLE` có `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` để database cũ (import trước 30/10/2026) tự được bổ sung cột khi import lại.
- Bảng `don_dat_tour`: đơn đặt tour. Lưu `so_nguoi_lon`, `so_tre_em`, **đơn giá tại thời điểm đặt**, `tong_goc`, `muc_giam`, `tien_giam`, `tong_thanh_toan`, `trang_thai` (`cho_xac_nhan`, `da_xac_nhan`, `da_huy`, `hoan_thanh`), thời điểm tạo/cập nhật. Khóa ngoại tới `lich_khoi_hanh` và `tai_khoan`.
- Chỗ còn **không lưu thành cột** mà tính động: `so_cho − tổng (so_nguoi_lon + so_tre_em)` của các đơn `cho_xac_nhan`/`da_xac_nhan`. Vì vậy không cần đồng bộ số chỗ khi hủy đơn.
- Hai đơn mẫu thuộc tài khoản `khachhang@gmail.com`, chạy lại không nhân đôi (`NOT EXISTS` theo `ma_don`).

## Đã kiểm tra

- Import `ontrip.sql` vào database trống: 9 bảng, 48 tour, 144 lịch khởi hành, 0 lịch thiếu `ngay_ve`, 2 đơn mẫu đúng số tiền.
- Import lại lần hai: số bản ghi không đổi (48 tour, 144 lịch, 2 đơn, 3 tài khoản).
- Mô phỏng database cũ (xóa cột `ngay_ve` và bảng `don_dat_tour`) rồi import lại: cột và bảng được tạo lại, `ngay_ve` được điền đủ.
