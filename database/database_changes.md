# Nhật ký thay đổi database OnTrip

`database/ontrip.sql` là **file SQL duy nhất** chứa cấu trúc, dữ liệu cài đặt và các khối cập nhật database đã import. Không tạo file SQL cập nhật riêng.

Mỗi lần sửa bảng, cột, khóa, ràng buộc hoặc dữ liệu cài đặt trong `ontrip.sql`, người/AI thực hiện phải cập nhật nhật ký này trong cùng lần sửa. Ghi rõ ngày, thay đổi, ảnh hưởng dữ liệu, vị trí khối SQL và cách áp dụng. Không ghi ở đây các thao tác sử dụng website thông thường như đổi mật khẩu hoặc sửa hồ sơ cá nhân.

## 13/11/2026 — Bổ sung cột giới tính và CCCD cho người tham gia đặt tour

- **Thay đổi:** Bổ sung cột `gioi_tinh` ENUM('nam', 'nu') NOT NULL DEFAULT 'nam' và `cccd` VARCHAR(30) DEFAULT NULL cho bảng `chi_tiet_don_dat` để phục vụ quản lý thông tin hành khách tham gia chuyến đi chi tiết.
- **Ảnh hưởng dữ liệu:** Không làm thay đổi hoặc mất dữ liệu hiện có. Các bản ghi người tham gia cũ tự nhận `gioi_tinh = 'nam'` và `cccd = NULL`.
- **Vị trí:** Khối `BEGIN CAP_NHAT_2026_11_13_HANH_KHACH_CCCD_GIOI_TINH` / `END CAP_NHAT_2026_11_13_HANH_KHACH_CCCD_GIOI_TINH` ở cuối file `ontrip.sql`, và cập nhật câu `CREATE TABLE IF NOT EXISTS chi_tiet_don_dat`.
- **Máy đã có database:** Chọn database `ontrip` trong phpMyAdmin và chạy khối SQL trên (hoặc chạy lệnh `ALTER TABLE chi_tiet_don_dat ADD COLUMN IF NOT EXISTS gioi_tinh ENUM('nam', 'nu') NOT NULL DEFAULT 'nam' AFTER loai_khach; ALTER TABLE chi_tiet_don_dat ADD COLUMN IF NOT EXISTS cccd VARCHAR(30) DEFAULT NULL AFTER ngay_sinh;`).
- **Đã kiểm tra:** Chạy lệnh trên MariaDB của XAMPP máy phát triển thành công; bảng `chi_tiet_don_dat` có đủ 9 cột.

## 13/11/2026 — Sửa lỗi `#1005` khi tạo bảng `danh_gia_tour` (khóa ngoại sai kiểu)

- **Nguyên nhân:** `danh_gia_tour.id_tour` khai báo `INT UNSIGNED` nhưng `tour.id_tour` là `BIGINT UNSIGNED`. Hai kiểu không khớp tuyệt đối nên MySQL trả `errno: 150 "Foreign key constraint is incorrectly formed"` cho `fk_danh_gia_tour`, và cả câu `CREATE TABLE` thất bại.
- **Thay đổi:** đổi `danh_gia_tour.id_tour` từ `INT UNSIGNED` thành `BIGINT UNSIGNED` cho khớp `tour.id_tour`. Hai cột còn lại (`id_tai_khoan`, `id_don_dat`) vốn đã đúng `BIGINT UNSIGNED` nên giữ nguyên.
- Chỉ sửa kiểu cột trong phần CREATE; không đổi tên bảng/cột, không đổi chỉ mục, không đổi hành vi `ON DELETE` (`CASCADE` cho tour/tài khoản, `SET NULL` cho đơn đặt). Model `app/models/danh_gia.php` đã dùng `BIGINT UNSIGNED` từ trước nên không cần sửa.
- **Vị trí:** khối `BEGIN CAP_NHAT_2026_11_08_DANH_GIA_TOUR` / `END CAP_NHAT_2026_11_08_DANH_GIA_TOUR` trong `ontrip.sql`.
- **Máy đã có database:** không cần thao tác nếu bảng `danh_gia_tour` đã tồn tại và hoạt động (cột đã đúng `BIGINT`). Nếu lần import trước dừng ở khối này thì bảng chưa được tạo — chạy lại riêng khối trên sau khi lấy code mới. Nếu bảng đã tạo được nhưng cột `id_tour` là `INT` (tạo từ phiên bản SQL cũ), cần `ALTER TABLE danh_gia_tour MODIFY id_tour BIGINT UNSIGNED NOT NULL;` rồi kiểm tra lại ràng buộc.
- **Đã kiểm tra:** import toàn bộ `ontrip.sql` vào database tạm trên MySQL của XAMPP — tạo đủ 13 bảng, `danh_gia_tour` có 3 khóa ngoại với `id_tour`/`id_tai_khoan`/`id_don_dat` đều `bigint(20) unsigned`. Database tạm đã được xóa.

## 09/11/2026 — Hiển thị số lượng khách đã đặt tour thành công trên thẻ tour

- Cập nhật truy vấn tính tổng số lượng khách hàng đã đặt tour (`SUM(so_nguoi_lon + so_tre_em)`) theo tour trong `TourModel::getPublishedTours()`, chỉ tính các đơn đặt tour không bị hủy (`trang_thai <> 'da_huy'`).
- Hiển thị thông tin số lượng khách đã đặt ngay dưới phần thời gian khởi hành trên tất cả các thẻ tour tại trang chủ (`app/views/home/index.php`), trang danh sách tour (`app/views/tour/index.php`) và thanh thông tin nhanh ở chi tiết tour (`app/views/tour/detail.php`).
- Bổ sung seed dữ liệu mẫu các đơn đặt tour đã xác nhận và hoàn thành cho các tour phổ biến (Sapa, Hà Giang, Hạ Long, Bà Nà Hills).
- **Vị trí:** khối `BEGIN CAP_NHAT_2026_11_09_DON_DAT_SEED` / `END CAP_NHAT_2026_11_09_DON_DAT_SEED` ở cuối `ontrip.sql`. Dùng `WHERE NOT EXISTS` an toàn khi chạy lại nhiều lần.
- **Máy đã có database:** chọn `ontrip` trong phpMyAdmin → SQL → chạy khối trên.

## 08/11/2026 — Tính năng đánh giá tour cho khách hàng

- Thêm bảng `danh_gia_tour`, lưu đánh giá của khách hàng cho tour bao gồm: số sao (1-5), tiêu đề, nội dung nhận xét, tài khoản đánh giá, đơn đặt liên kết (để xác nhận đã trải nghiệm tour), trạng thái hiển thị (`hien_thi`, `an`), thời điểm tạo và cập nhật.
- Ràng buộc khóa ngoại: Xóa tour hoặc tài khoản sẽ xóa đánh giá tương ứng (`ON DELETE CASCADE`), xóa đơn đặt sẽ giữ đánh giá nhưng gán null `id_don_dat` (`ON DELETE SET NULL`).
- Tích hợp tính năng đánh giá trực tiếp trên trang chi tiết tour: biểu đồ tổng quan sao (điểm trung bình, thanh tỷ lệ % 1-5 sao), bộ lọc xem theo số sao, form gửi/chỉnh sửa đánh giá cho khách hàng đã đăng nhập, huy hiệu "✓ Đã trải nghiệm tour" cho khách đã đặt tour thành công, nút xóa/sửa đánh giá cá nhân.
- Tích hợp nút đánh giá nhanh từ danh sách và chi tiết đơn đặt tour đã xác nhận/hoàn thành.
- **Vị trí:** khối `BEGIN CAP_NHAT_2026_11_08_DANH_GIA_TOUR` / `END CAP_NHAT_2026_11_08_DANH_GIA_TOUR` trong `ontrip.sql`. Khối CREATE TABLE IF NOT EXISTS này an toàn khi chạy lại.
- **Máy đã có database:** chọn `ontrip` trong phpMyAdmin → SQL → chạy riêng khối trên.

## 08/11/2026 — Thông báo đơn đặt cho khách hàng

- Thêm bảng `thong_bao`, lưu chủ đơn, đơn đặt, loại sự kiện, tiêu đề/nội dung, trạng thái đã đọc và thời điểm tạo. Khóa ngoại gắn với tài khoản/đơn; xóa đơn sẽ xóa thông báo của đơn. Khóa duy nhất `(id_don_dat, loai_thong_bao)` chống sự kiện trùng.
- Sự kiện áp dụng: xác nhận đơn, hủy đơn, ghi nhận thanh toán mô phỏng. Thông báo và thay đổi đơn/thanh toán nằm trong cùng transaction; không tạo thông báo từ dữ liệu lịch sử có trước khi triển khai.
- **Vị trí:** khối `BEGIN CAP_NHAT_2026_11_08_THONG_BAO` / `END CAP_NHAT_2026_11_08_THONG_BAO` ngay sau phần tạo bảng đơn đặt/thanh toán trong `ontrip.sql`. Khối CREATE TABLE IF NOT EXISTS này dùng được cho cả cài mới và nâng cấp, có thể chạy lại.
- **Máy đã có database:** chọn `ontrip` trong phpMyAdmin → SQL → chạy riêng khối trên. Không cần xóa database hay import lại dữ liệu. Database máy phát triển hiện tại đã được áp dụng; Git không tự áp dụng schema trên máy thành viên khác.
- **Đã kiểm tra:** chạy khối hai lần, không sinh thông báo hồi tố; kiểm tra chống trùng, đọc theo chủ đơn, phân trang và rollback thông báo cùng transaction. Các đơn có trước triển khai được giữ nguyên.

## Cách áp dụng

- **Máy mới:** import toàn bộ `ontrip.sql`.
- **Máy đã import:** chọn database `ontrip` trong phpMyAdmin, mở tab SQL và chạy khối cập nhật được ghi trong nhật ký. Các khối nằm ngay trong `ontrip.sql`, có dấu `BEGIN CAP_NHAT_...` và `END CAP_NHAT_...`.
- Sửa file trong Git không tự cập nhật database trên máy thành viên khác. Người nhận code cần áp dụng khối SQL tương ứng.
- Lệnh `CREATE TABLE IF NOT EXISTS` không tự bổ sung/đổi cột của bảng cũ; phải có khối nâng cấp đi kèm khi thay schema.
- Seed đang giữ dữ liệu đã tồn tại khi nhập lại; không dùng nhập lại seed để ép ghi đè dữ liệu người dùng đã chỉnh. Đối chiếu nhật ký trước khi chạy cập nhật.

## 04/11/2026 — Bổ sung ngày về cho lịch khởi hành

- Bảng `lich_khoi_hanh` chỉ có `ngay_khoi_hanh`, chưa có ngày về nên module lịch khởi hành không lưu được ngày kết thúc chuyến. Bổ sung cột `ngay_ve` DATE, đặt sau `ngay_khoi_hanh`.
- Khối `BEGIN CAP_NHAT_2026_11_04_NGAY_VE_LICH` … `END CAP_NHAT_2026_11_04_NGAY_VE_LICH` nằm trong `ontrip.sql`, ngay sau `CREATE TABLE lich_khoi_hanh` và trước bảng `tai_khoan`. Khối dùng procedure tạm, chỉ thêm cột khi còn thiếu, rồi điền ngày về cho lịch cũ bằng `ngay_khoi_hanh + (tour.so_ngay − 1)`; chạy lại không ghi đè ngày về đã có.
- Cột cho phép NULL để không chặn bản ghi không suy ra được ngày về; form quản trị lịch khởi hành bắt buộc nhập ngày về và kiểm tra ngày về không trước ngày đi.
- Seed 144 lịch mẫu cũng điền ngày về theo công thức trên trước khi chèn, nên cài mới và nhập lại seed cho kết quả giống nhau; `INSERT … SELECT` vẫn chỉ thêm lịch chưa có.
- Không đổi giá, số chỗ, hạn đặt, trạng thái, đơn đặt hay thanh toán. Đơn cũ giữ nguyên thông tin đã chụp; ngày về chỉ đọc từ `lich_khoi_hanh` khi hiển thị.
- **Máy mới:** import toàn bộ `ontrip.sql`. **Máy đã import:** chọn database `ontrip`, chạy riêng khối trên trong tab SQL của phpMyAdmin.
- Đã áp dụng khối vào database `ontrip` trên máy phát triển ngày 04/11/2026 và kiểm tra trang chi tiết tour, form đặt tour cùng trang quản trị lịch khởi hành. Các máy khác cần áp dụng khối tương ứng.

## 01/11/2026 — Nâng cấp thông tin tour/lịch được chụp lại trong đơn đặt cũ

- Bảng `don_dat_tour` bản cũ thiếu `ma_tour`, `ten_tour`, `ngay_khoi_hanh`, `diem_khoi_hanh`, `dia_chi`. Model đọc `dd.*` nên các View danh sách/chi tiết báo `Undefined array key`; thao tác tạo đơn và tìm kiếm tên tour cũng cần các cột này.
- Khối `BEGIN CAP_NHAT_2026_11_01_THONG_TIN_DON_DAT` … `END CAP_NHAT_2026_11_01_THONG_TIN_DON_DAT` nằm ngay sau `CAP_NHAT_2026_10_31_DON_DAT_TOUR` trong `ontrip.sql`. Khối chỉ bổ sung cột thiếu, điền riêng giá trị NULL từ `lich_khoi_hanh` và `tour`, rồi đặt bốn cột thông tin tour/lịch thành NOT NULL như phần CREATE. `dia_chi` để NULL vì đơn cũ không lưu địa chỉ.
- Giữ nguyên mã đơn, khách hàng, số khách, giá, tổng tiền, trạng thái, thanh toán và các mốc thời gian. Giá trị chụp lại đã có không bị ghi đè khi chạy lại, kể cả khi tour/lịch hiện tại đã thay đổi. Nếu đơn thiếu thông tin và mất liên kết tour/lịch, khối dừng để đối chiếu, không tự gán dữ liệu giả.
- **Giới hạn khôi phục:** đơn cũ chưa lưu thông tin tour/lịch chỉ có thể lấy từ dữ liệu hiện tại, không thể xác nhận đó là tên/ngày/điểm đi tại thời điểm đặt. Khối không tính lại tiền đơn cũ.
- **Máy mới:** import toàn bộ `ontrip.sql`. **Máy đã import:** chọn database `ontrip`, chạy riêng khối trên trong tab SQL của phpMyAdmin; không cần xóa database. Khi vừa pull code mới, kiểm tra toàn bộ các khối nâng cấp chưa áp dụng trong nhật ký thay vì chỉ import lại các lệnh CREATE.
- Đã kiểm tra trên bản sao database cũ: giữ nguyên tiền/trạng thái/mốc thời gian, thanh toán và người tham gia; điền đủ thông tin thiếu; chạy lại; nâng cấp dở dang; giữ thông tin đơn khi tour/lịch thay đổi; truy vấn danh sách/chi tiết khách hàng, tìm kiếm quản trị và tạo đơn mới. Cài mới cũng chạy thành công. Database thử đã được xóa.
- Đã sao lưu rồi áp dụng khối này vào database `ontrip` trên máy phát triển ngày 01/11/2026, khôi phục thông tin cho hai đơn cũ. Kiểm tra render danh sách/chi tiết ở quản trị và khách hàng, cùng form đặt tour: không còn warning thiếu khóa hoặc ngày `01/01/1970`. Các máy khác vẫn cần áp dụng khối nâng cấp tương ứng.

## 01/11/2026 — Bổ sung nâng cấp mùa vụ và ưu đãi cho bảng tour cũ

- Nguyên nhân lỗi `#1054 Unknown column 'mua_vu'`: phần CREATE và seed đã dùng ba cột mới, nhưng `CREATE TABLE IF NOT EXISTS` không nâng cấp bảng `tour` đã tồn tại. Database cũ còn thiếu `phan_tram_giam`, `nhan_uu_dai` và hai chỉ mục tương ứng.
- Khối `BEGIN CAP_NHAT_2026_11_01_MUA_VU_UU_DAI` … `END CAP_NHAT_2026_11_01_MUA_VU_UU_DAI` trong `ontrip.sql`, ngay sau CREATE bảng `tour` và trước seed, bổ sung riêng từng cột/chỉ mục còn thiếu. Khối dùng procedure tạm và xóa procedure sau khi chạy; chạy lại không thêm trùng hoặc ghi đè giá trị hiện có.
- Cột bổ sung: `mua_vu` ENUM(`tat_ca`, `xuan`, `ha`, `thu`, `dong`) mặc định `tat_ca`; `phan_tram_giam` TINYINT UNSIGNED mặc định `0`; `nhan_uu_dai` VARCHAR(80) nullable mặc định NULL. Chỉ mục: `idx_tours_season_status`, `idx_tours_promo_status`.
- **Máy mới:** import toàn bộ `ontrip.sql`. **Máy đã import hoặc vừa import bị lỗi:** chọn database `ontrip`, mở tab SQL trong phpMyAdmin và chạy khối trên. Có thể import lại toàn bộ file đã sửa nếu cần tiếp tục các phần seed chưa chạy.
- Không xóa bảng, tài khoản, tour hoặc đơn đặt. Tour cũ nhận giá trị mặc định cho cột vừa thêm; seed dùng `ON DUPLICATE KEY UPDATE id_tour = id_tour` nên không tự gán mùa vụ/ưu đãi mẫu vào tour đã có. Đơn đặt cũ giữ nguyên giá đã chụp.
- Chỉ sửa file không cập nhật database đang chạy; các máy thành viên cần áp dụng khối SQL.
- Đã kiểm tra trên database thử riêng: cài mới (48 tour, 144 lịch, 3 tài khoản mẫu), import lại, tái hiện lỗi 1054 khi thiếu cột, nâng cấp bảng cũ, chạy khối nhiều lần, nâng cấp khi mới có một phần cột/chỉ mục và giữ giá trị đã chỉnh. Database thử đã được xóa; chưa áp dụng khối này vào database `ontrip` đang dùng.

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
