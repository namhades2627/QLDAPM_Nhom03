# Nhật ký thay đổi database OnTrip

`database/ontrip.sql` là **file SQL duy nhất** chứa cấu trúc, dữ liệu cài đặt và các khối cập nhật database đã import. Không tạo file SQL cập nhật riêng.

Mỗi lần sửa bảng, cột, khóa, ràng buộc hoặc dữ liệu cài đặt trong `ontrip.sql`, người/AI thực hiện phải cập nhật nhật ký này trong cùng lần sửa. Ghi rõ ngày, thay đổi, ảnh hưởng dữ liệu, vị trí khối SQL và cách áp dụng. Không ghi ở đây các thao tác sử dụng website thông thường như đổi mật khẩu hoặc sửa hồ sơ cá nhân.

## Cách áp dụng

- **Máy mới:** import toàn bộ `ontrip.sql`.
- **Máy đã import:** chọn database `ontrip` trong phpMyAdmin, mở tab SQL và chạy khối cập nhật được ghi trong nhật ký. Các khối nằm ngay trong `ontrip.sql`, có dấu `BEGIN CAP_NHAT_...` và `END CAP_NHAT_...`.
- Sửa file trong Git không tự cập nhật database trên máy thành viên khác. Người nhận code cần áp dụng khối SQL tương ứng.
- Lệnh `CREATE TABLE IF NOT EXISTS` không tự bổ sung/đổi cột của bảng cũ; phải có khối nâng cấp đi kèm khi thay schema.
- Seed đang giữ dữ liệu đã tồn tại khi nhập lại; không dùng nhập lại seed để ép ghi đè dữ liệu người dùng đã chỉnh. Đối chiếu nhật ký trước khi chạy cập nhật.

## 31/10/2026 — Bổ sung ba bảng đặt tour, người tham gia và thanh toán mô phỏng

| Nội dung | Chi tiết |
| --- | --- |
| Bảng mới | `don_dat_tour` (đơn đặt tour), `chi_tiet_don_dat` (người tham gia), `thanh_toan` (thanh toán mô phỏng). |
| Nguồn cập nhật | Khối `CAP_NHAT_2026_10_31_DON_DAT_TOUR` trong `ontrip.sql`, ngay sau khối `CAP_NHAT_2026_10_30_HINH_ANH`. |
| Máy mới | Import toàn bộ `ontrip.sql`; ba bảng được tạo cùng các bảng khác. |
| Máy đã import | Chạy khối `BEGIN CAP_NHAT_2026_10_31_DON_DAT_TOUR` … `END CAP_NHAT_2026_10_31_DON_DAT_TOUR`. |
| Chạy lại | Khối chỉ dùng `CREATE TABLE IF NOT EXISTS`, không xóa và không ghi đè đơn đã có. |
| Khóa ngoại | `don_dat_tour.id_tai_khoan` → `tai_khoan`, `don_dat_tour.id_lich_khoi_hanh` → `lich_khoi_hanh` (RESTRICT): không xóa được tài khoản hoặc lịch khởi hành đã có đơn. `chi_tiet_don_dat` và `thanh_toan` → `don_dat_tour` (CASCADE). |
| Đơn giá tại thời điểm đặt | `gia_nguoi_lon`, `gia_tre_em`, `tong_goc`, `muc_giam`, `tien_giam`, `tong_thanh_toan`, `ma_tour`, `ten_tour`, `ngay_khoi_hanh`, `diem_khoi_hanh` là ảnh chụp dữ liệu; sửa bảng `tour` hoặc `lich_khoi_hanh` sau đó không làm đổi đơn cũ. |
| Số chỗ còn lại | Không thêm cột vào `lich_khoi_hanh`. Chỗ còn lại = `so_cho` − tổng `so_nguoi_lon + so_tre_em` của các đơn không ở trạng thái `da_huy`, tính trong `DonDatModel`. |
| Trạng thái đơn | `cho_xac_nhan`, `da_xac_nhan`, `hoan_thanh`, `da_huy`. |
| Thanh toán | Mỗi đơn tối đa một bản ghi `thanh_toan` (`uq_thanh_toan_don_dat`); dữ liệu sinh ra bởi nút thanh toán mô phỏng, không kết nối cổng thanh toán thật. |
| Các phần giữ nguyên | Bảng `tour`, `lich_khoi_hanh`, `tai_khoan` và toàn bộ dữ liệu seed hiện có; không đổi tên cột cũ. |

Đã áp dụng cho database `ontrip` trên máy phát triển và kiểm tra lại bằng `SHOW TABLES` / `SHOW CREATE TABLE`. Các máy khác cần chạy khối trên khi pull code.

## 30/10/2026 — Chuẩn hóa cột ảnh tài khoản và gộp cập nhật SQL

| Nội dung | Chi tiết |
| --- | --- |
| Cột hiện tại | `tai_khoan.hinh_anh` — `VARCHAR(255) DEFAULT NULL`, lưu đường dẫn ảnh tương đối. |
| Tên cũ | `anh_dai_dien`; code Model, Controller và trường upload đã đổi sang `hinh_anh`. |
| Nguồn cập nhật | Khối `CAP_NHAT_2026_10_30_HINH_ANH` trong `ontrip.sql`, ngay sau phần tạo bảng `tai_khoan`. |
| Database chưa có cột ảnh | Khối cập nhật thêm cột `hinh_anh`. |
| Database có cột `anh_dai_dien` | Đổi tên thành `hinh_anh`, giữ nguyên các đường dẫn ảnh đã lưu. |
| Database đã có `hinh_anh` | Chạy lại không thêm cột trùng hoặc đặt lại giá trị ảnh. |
| Trường hợp có cả hai cột | Gộp các giá trị tương thích rồi bỏ tên cũ; nếu hai giá trị khác nhau trên cùng bản ghi, dừng và báo lỗi để đối chiếu, không tự chọn một giá trị để ghi đè. |
| File đã gộp | Nội dung `cap_nhat_anh_dai_dien.sql` đã chuyển vào `ontrip.sql`; file cập nhật riêng được xóa. |
| Các phần giữ nguyên | Email, hash mật khẩu, vai trò, trạng thái, dữ liệu tour và file ảnh đã upload. |

Khối nâng cấp dùng một procedure tạm để kiểm tra trạng thái schema; procedure được xóa sau khi chạy thành công, không phải chức năng nghiệp vụ của ứng dụng. Nếu có dữ liệu ảnh xung đột, đối chiếu hai cột rồi chạy lại khối này.

Đã áp dụng cho database `ontrip` trên máy phát triển. Các máy khác cần chạy khối trên khi pull code. Chức năng lưu hồ sơ và đổi mật khẩu tiếp tục sử dụng bảng `tai_khoan`; không thêm bảng phân quyền hoặc bảng ảnh riêng.

Đã kiểm tra trên database thử riêng: cài mới, import lại, thêm cột khi chưa có, đổi tên cột cũ và giữ đường dẫn, chạy lại khối cập nhật, gộp dữ liệu tương thích và dừng khi dữ liệu xung đột (13 kiểm tra). Đã chạy lại 39 kiểm tra tài khoản/upload với tên `hinh_anh`; dữ liệu thử đã được dọn.

## Mốc trước lần chuẩn hóa này

Phần hồ sơ đã bổ sung cột `anh_dai_dien` nullable để lưu ảnh đại diện và đã có xử lý upload; đây là tên cũ được thay thế bởi `hinh_anh` ở thay đổi phía trên. Các mục lịch sử cũ hơn không được suy đoán; xem Git của `ontrip.sql` khi cần đối chiếu.
