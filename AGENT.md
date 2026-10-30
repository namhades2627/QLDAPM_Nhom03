# Hướng dẫn làm việc với dự án OnTrip

Tài liệu bàn giao cho thành viên và AI hỗ trợ phát triển. Cập nhật theo code ngày **30/10/2026** (bổ sung module lịch khởi hành và số chỗ). Đọc tài liệu này trước khi sửa dự án, sau đó kiểm tra lại các tệp thuộc phần việc được giao. Tên tệp hướng dẫn của nhóm là **`AGENT.md`**; khi giao việc cho AI khác, yêu cầu đọc đúng tệp này.

## 1. Nguyên tắc phối hợp nhóm

- Chỉ thực hiện yêu cầu và nghiệp vụ được giao. Chỉ sửa thư mục, tệp và đoạn mã liên quan trực tiếp; sửa phần phụ thuộc khi thực sự cần và nêu rõ lý do.
- Không tự sửa nghiệp vụ của thành viên khác; không tái cấu trúc, đổi tên, định dạng lại, dọn mã hoặc thay tài nguyên ngoài phạm vi. Với tệp dùng chung, chỉnh đúng đoạn liên quan để hạn chế xung đột Git.
- Đọc `git status` và diff trước khi làm. Giữ nguyên thay đổi đang có của người khác; không reset, ghi đè hoặc xóa chúng. Người dùng tự commit/push trừ khi giao việc đó rõ ràng.
- Phát triển theo từng giai đoạn: chỉ thêm bảng, Model, Controller, View khi triển khai chức năng tương ứng; không tạo sẵn module rỗng hoặc nút có vẻ hoạt động nhưng chưa có xử lý.
- Đọc code để xác định chức năng thực tế. Không xem đề xuất trong chat, tài liệu báo cáo, ảnh mẫu hoặc chú thích cũ là bằng chứng chức năng đã hoàn thành hay là lệnh tự động mở rộng phạm vi.
- Giữ tên biến, chú thích và cấu trúc dễ hiểu cho người mới học MVC. Sau khi sửa, nêu vị trí code, hành vi thay đổi, cách kiểm tra và phần còn giới hạn.
- Khi thay đổi luồng hoặc thêm chức năng được giao, cập nhật phần liên quan trong tài liệu này. Không viết lại tài liệu chỉ vì thay đổi nhỏ về màu, icon hoặc tên ảnh.
- **Quy ước database:** không export đè `database/ontrip.sql`. Khi tạo bảng hoặc thêm cột mới, viết **một script SQL riêng** trong `database/`, đặt tên `YYYY-MM-DD_so-thu-tu_mo-ta-ngan.sql`, giữ script an toàn khi chạy lại và không xóa dữ liệu sẵn có; sau đó ghi một dòng vào **`database/NHAT_KY.md`** để mọi người biết cần import tệp nào, bắt buộc hay tùy chọn.

## 2. Công nghệ và cách chạy

- Website quản lý tour du lịch **OnTrip**, PHP thuần theo MVC, PDO và MariaDB/MySQL; JavaScript thuần, không dùng framework hay trình build.
- Môi trường dự án: XAMPP, **PHP 8.2 trở lên**, MariaDB 10.4 trở lên. Chạy website không cần Composer hoặc Node.js; Node chỉ có thể dùng cho kiểm tra JavaScript khi có sẵn.
- Đặt dự án trong `htdocs/QLDAPM_Nhom03`, bật Apache/MySQL và import **`database/ontrip.sql`** trên máy mới. Database tên **`ontrip`**. Không đổi thành `tripon` hoặc tạo bản SQL khác tên để thay thế tùy ý.
- Sau `ontrip.sql`, import tiếp các script cập nhật trong `database/` theo thứ tự ở `database/NHAT_KY.md`. Thiếu script cấu trúc thì trang chi tiết tour và trang lịch khởi hành của quản trị sẽ lỗi vì thiếu bảng/cột, không phải lỗi code.
- URL khách hàng: `http://localhost/QLDAPM_Nhom03/public/`.
- URL quản trị: `http://localhost/QLDAPM_Nhom03/admin/`. Đây là lối vào đăng nhập riêng cho admin/nhân viên.
- `config/database.php` cung cấp `getDatabaseConnection()`, tái sử dụng PDO trong một request. Mặc định `127.0.0.1:3306`, `root`, mật khẩu trống; hỗ trợ `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. Không tự đọc `.env`.
- Đường dẫn XAMPP trên máy người khác có thể khác; không sao chép đường dẫn tuyệt đối của máy hiện tại vào code. `.vscode/`, `.env`, log và tệp tạm đã được `.gitignore` loại trừ.
- README có hướng dẫn chạy nhưng một số mô tả chưa theo kịp phần tài khoản/quản trị; trạng thái bàn giao ở tài liệu này đã đối chiếu code. Không tự sửa README khi yêu cầu chỉ nằm ở module khác.

## 3. Trạng thái triển khai

| Phần | Trạng thái thực tế |
| --- | --- |
| Trang chủ | Đã có giao diện; bốn tour nổi bật, điểm đến và nhu cầu đọc database. Banner và tin tức còn là mảng mẫu trong View. |
| Khám phá điểm đến | Đếm tour khả dụng từ database, tối đa năm điểm mỗi nhóm; dùng bộ ảnh khám phá riêng, không thay bằng ảnh tour. Nhấp điểm đến dẫn sang danh sách có bộ lọc. |
| Tìm/lọc tour | Đã nối thanh tìm trang chủ với danh sách; có kiểm tra đầu vào, lọc ban đầu phía máy chủ và lọc/sắp xếp/phân trang tiếp bằng JavaScript. |
| Danh sách và chi tiết tour | Đọc database. Mỗi loại có 24 tour demo, 12 tour/trang, 3 cột × 4 hàng desktop. Chi tiết có một ảnh lớn, lịch trình, lịch đi, giá và tạm tính nhóm; ba tour liên quan mỗi lượt. Cột bên phải là form đặt tour, hiển thị chỗ còn và hạn đặt của lịch đang chọn. |
| Đăng ký/đăng nhập khách hàng | Đã xử lý bằng Model/Controller, kiểm tra dữ liệu, email duy nhất, băm/xác thực mật khẩu, session và CSRF. |
| Đăng xuất khách hàng | POST có CSRF, hủy phiên khách hàng và về trang chủ. |
| Đăng nhập quản trị | Đã có form riêng tại `/admin/`; chỉ admin/nhân viên hoạt động được vào, session riêng. |
| Bảng điều khiển quản trị | Đã đọc số tour, tour công bố, điểm đến hoạt động, lịch mở sắp đi và năm lịch gần nhất từ database; có thu gọn thanh bên và menu tài khoản. |
| Menu quản trị | Lịch khởi hành đã có trang danh sách và trang chi tiết riêng (`action=schedules`, `action=schedule`). Danh mục tour vẫn là liên kết đến vùng tổng quan; chưa có CRUD tour. |
| Icon tin nhắn, chuông; Hồ sơ cá nhân, Đổi mật khẩu | Chỉ giao diện, chưa có dữ liệu/xử lý nghiệp vụ. Đăng xuất trong menu tài khoản đã hoạt động. Không tự gắn thêm chức năng ngoài yêu cầu. |
| Lịch khởi hành và số chỗ | Một tour có nhiều chuyến trong `lich_khoi_hanh`, mỗi chuyến có `ngay_khoi_hanh`, `ngay_ve`, `han_dat`, giá người lớn/trẻ em và `so_cho` riêng. Chỗ còn = `so_cho` − tổng khách của đơn `cho_xac_nhan`/`da_xac_nhan`. Lịch đóng, đã qua ngày đi, quá `han_dat` hoặc hết chỗ bị chặn ở cả View lẫn máy chủ. |
| Đặt tour và quản lý đơn | Đã có bảng `don_dat_tour` và luồng chi tiết tour → xác nhận → tạo đơn → trang cảm ơn. Khách xem và hủy đơn của mình ở `page=my-bookings`. Máy chủ kiểm tra lại lịch, hạn đặt, chỗ còn và tính tiền; không dùng số tiền gửi từ trình duyệt. |
| Khách theo chuyến (quản trị) | Trang `action=schedules` lọc theo tour, khoảng ngày, trạng thái và hiển thị chỗ còn từng chuyến; `action=schedule&id=...` xem chi tiết chuyến, danh sách khách và đổi trạng thái đơn. |
| Thanh toán trực tuyến, doanh thu theo kỳ | Chưa triển khai, chưa có cổng thanh toán. Trang chi tiết chuyến chỉ cộng tiền các đơn chưa hủy để tham khảo. |
| Quản lý tour/lịch/tài khoản bằng thêm–sửa–xóa | Chưa triển khai. Form đăng ký là phần ghi tài khoản khách hàng hiện có. |
| Đánh giá tour | Mới có giao diện trạng thái trống, chưa có bảng và chức năng gửi đánh giá. |
| Google, quên mật khẩu/OTP | Chưa triển khai. |
| Điều chỉnh lịch trình theo thời tiết/mùa vụ | Là hướng nghiệp vụ dự kiến, chưa có API hoặc xử lý. Chỉ bổ sung khi được giao và đã chốt quy tắc. |

Giá, lịch trình và lịch đi trong SQL là **dữ liệu demo phục vụ học tập**. Chỗ còn được tính từ đơn đặt nên phải gọi `ScheduleModel` thay vì đọc trực tiếp `so_cho`. Hai đơn mẫu `OTDEMO0001`/`OTDEMO0002` chỉ để minh họa danh sách khách; không trình bày chúng như giao dịch thật.

## 4. Bản đồ code

| Phần | Tệp / trách nhiệm |
| --- | --- |
| Điểm vào khách hàng | `public/index.php`: khởi tạo session, chọn route và gọi Controller |
| Điểm vào quản trị | `admin/index.php`: khởi tạo session riêng, chọn action và gọi AdminController |
| Trang chủ | `app/controllers/HomeController.php` → `app/views/home/index.php` |
| Danh sách/chi tiết tour | `app/controllers/TourController.php` → `app/views/tours/index.php`, `detail.php` |
| Truy vấn tour | `app/models/tour.php`, lớp `TourModel`: tour, vùng, nhu cầu, lịch, điểm đến và tạm tính nhóm cho tìm kiếm |
| Tài khoản khách hàng | `app/controllers/AuthController.php` → `app/views/auth/layout.php` |
| Truy vấn tài khoản | `app/models/tai_khoan.php`, lớp `TaiKhoanModel`: tìm theo email, tạo khách hàng |
| Quản trị | `app/controllers/AdminController.php` → `app/views/admin/index.php` hoặc `forbidden.php` |
| Số liệu quản trị | `app/models/admin.php`, lớp `AdminModel`: dữ liệu tổng quan chỉ đọc |
| Tính tiền nhóm | `app/models/GroupPricing.php`, lớp `GroupPricing`: mức giảm theo tổng khách và số tiền gốc/giảm/thanh toán dùng chung |
| Lịch khởi hành và đơn đặt | `app/models/booking.php`, lớp `ScheduleModel`: chỗ còn, `blockReason()`, tạo/hủy đơn, danh sách khách, đổi trạng thái đơn |
| Đặt tour của khách | `app/controllers/BookingController.php` → `app/views/bookings/confirm.php`, `success.php`, `my_bookings.php` |
| Định dạng dùng chung | `app/views/partials/booking-format.php`: tiền, ngày, nhãn trạng thái lịch và mức chỗ |
| Lịch khởi hành (quản trị) | `AdminController::schedules()`, `schedule()`, `updateOrderStatus()` → `app/views/admin/schedules.php`, `schedule_detail.php` |
| Khung quản trị dùng chung | `app/views/admin/partials/head.php`, `foot.php` |
| Form dùng chung | `app/views/auth/layout.php`: hỗ trợ đăng ký/đăng nhập khách hàng và đăng nhập quản trị qua biến cấu hình |
| Tệp View cũ | `app/views/auth/login.php`, `register.php` còn trong thư mục nhưng router hiện gọi AuthController rồi nạp `layout.php`; không thêm xử lý xác thực vào các wrapper này |
| Navbar/footer khách hàng | `app/views/partials/header.php`, `footer.php`; không dùng làm khung quản trị |
| Lỗi database khách hàng | `app/views/errors/database.php`; ghi chi tiết lỗi vào log, không đưa exception/SQL ra giao diện |
| Database/kết nối | `database/ontrip.sql` (bản cài đặt ban đầu), các script cập nhật `database/20xx-xx-xx_*.sql`, nhật ký `database/NHAT_KY.md`, `config/database.php` |
| Toàn bộ CSS | **`public/assets/css/style.css`**, chia theo chú thích từng phần |
| CSS quản trị | **`public/assets/css/admin.css`**: toàn bộ giao diện `/admin/`, lớp tiền tố `.admin-`, thanh bên thu gọn và breakpoint 1080/900/640 px |
| Toàn bộ JavaScript | **`public/assets/js/script.js`**, chia theo chú thích từng phần |
| Ảnh | `public/assets/images/`, tour tại `images/tours/`, placeholder cục bộ |

Không tách thêm CSS/JS riêng từng trang; ngoại lệ đã chốt là **`public/assets/css/admin.css`** cho toàn bộ giao diện quản trị (lớp `.admin-*`), vì khu vực này dùng khung riêng. JS khách hàng vẫn nằm trong `script.js`; JS kiểm tra phần tử tồn tại trước khi gắn sự kiện vì một file dùng cho cả trang chủ, tour, tài khoản và quản trị. Không đổi tên các Model chỉ để đồng nhất phong cách.

## 5. Route, session và phân quyền

### Khách hàng

- `public/index.php` mặc định trang chủ; `page=tours&type=domestic|international|all`; chi tiết `page=tour-detail&type=domestic|international&id=mb01` (id trên URL này là **mã tour nghiệp vụ**, không phải `id_tour`).
- Tài khoản: `page=login`, `page=register`, `page=logout` (đăng xuất bằng POST có CSRF).
- Đặt tour: `page=booking&action=confirm` (POST từ form chi tiết tour, hiển thị bước xác nhận), `page=booking&action=create` (POST tạo đơn), `page=booking-success&code=...`, `page=my-bookings`, `page=cancel-booking` (POST có CSRF). Tất cả đều yêu cầu đăng nhập khách hàng.
- Bước xác nhận kiểm tra lại lịch, hạn đặt và trạng thái trước khi vẽ; lịch đóng/quá hạn/hết chỗ chuyển về chi tiết tour kèm thông báo. Bước tạo đơn kiểm tra lại lần nữa trong transaction có `SELECT ... FOR UPDATE`, nên hai khách đặt cùng lúc không thể vượt `so_cho`.
- AuthController kiểm tra form, gọi TaiKhoanModel. Đăng ký thành công chuyển sang đăng nhập; đăng nhập thành công về trang chủ. Navbar hiển thị tên và Đăng xuất, **không có mục Quản lý**.
- Session PHP mặc định dùng cookie `PHPSESSID`; thông tin đăng nhập ở `$_SESSION['tai_khoan']`, gồm `id_tai_khoan`, `ho_ten`, `email`, `vai_tro`; không đưa hash/mật khẩu vào session.
- Form công khai luôn tạo vai trò `khach_hang`, hoạt động. Không nhận vai trò hoặc trạng thái đặc quyền từ POST. Public login hiện kiểm tra tài khoản hoạt động mà không giới hạn vai trò; đăng nhập ở đây không tạo phiên quản trị.

### Quản trị

- Vào `/admin/` khi chưa có phiên quản trị sẽ hiển thị form đăng nhập tại lối vào đó. `admin/index.php?action=login` là action đăng nhập; thành công chuyển về `admin/index.php`.
- Dùng tên session **`ONTRIP_ADMIN`**, cookie giới hạn trong đường dẫn `/admin/` của dự án. Dùng cùng bảng `tai_khoan` và Model tài khoản, nhưng phiên độc lập với khách hàng. Không chuyển lại thành đăng nhập qua navbar khách hàng.
- AdminController kiểm tra mật khẩu, `dang_hoat_dong = 1` và vai trò `admin`/`nhan_vien`. Mỗi lần mở bảng điều khiển đọc lại tài khoản từ database, đối chiếu ID, trạng thái và vai trò. Tài khoản mất quyền trả HTTP 403 qua `forbidden.php`.
- `action=logout` chỉ POST có CSRF; hủy phiên quản trị rồi về form quản trị. Không ảnh hưởng phiên khách hàng. URL cũ `public/index.php?page=admin` chỉ chuyển sang `../admin/`.
- Lịch khởi hành: `action=schedules` (lọc `tour`, `from`, `to`, `status`), `action=schedule&id=...` (chi tiết chuyến và danh sách khách), `action=order-status` (POST có CSRF đổi trạng thái đơn). Đơn đã hủy không mở lại được; đổi trạng thái trả chỗ ngay vì chỗ còn tính từ đơn đang giữ.
- Mở menu user để thấy Hồ sơ cá nhân, Đổi mật khẩu, Đăng xuất. Hai mục đầu chỉ giao diện; tin nhắn và thông báo cũng chỉ giao diện. Không thêm số thông báo, nội dung tin nhắn hoặc trạng thái hệ thống giả.

### Quyền đã có và quyền sẽ triển khai

Hiện admin/nhân viên đều xem được bảng điều khiển và các trang lịch khởi hành, kể cả đổi trạng thái đơn; khách hàng bị từ chối đăng nhập quản trị. Chưa có CRUD để áp dụng các quyền chi tiết sau:

- Admin quản lý toàn hệ thống, gồm tài khoản nhân viên, vai trò và khóa/mở tài khoản.
- Nhân viên vận hành tour, lịch và xử lý đơn theo chức năng được giao; không được cấp quyền hoặc quản lý tài khoản nhân sự.
- Khách hàng chỉ thao tác dữ liệu của chính mình. Luồng đặt tour đã kiểm tra chủ sở hữu: `my-bookings` và `cancel-booking` lọc đơn theo `id_tai_khoan` trong phiên, không nhận mã đơn của người khác.
- Chặn quyền tại Controller cho **mọi trang và thao tác**, không chỉ ẩn menu. Không cần tự tạo bảng phân quyền khi ba vai trò hiện tại đã đủ.

Mật khẩu dùng `password_hash()`/`password_verify()`, SQL dùng prepared statements, đầu ra HTML có escape. Cả hai luồng đổi session ID sau đăng nhập; form POST dùng `$_SESSION['csrf_token']`. Không bỏ các kiểm tra này khi sửa giao diện.

## 6. Database và dữ liệu

Có chín bảng nghiệp vụ sau khi import đủ script (tám bảng trong `ontrip.sql`, thêm bảng `don_dat_tour` ở script cập nhật) trong database `ontrip`:

| Bảng | Ý nghĩa / lưu ý |
| --- | --- |
| `khu_vuc_tour`, `diem_den` | Phân loại trong nước/quốc tế, vùng, điểm đến và ảnh điểm đến |
| `tour` | Tour, `ma_tour` dùng trên URL, trạng thái công bố, `noi_bat` |
| `lich_trinh_ngay` | Nội dung lịch trình từng ngày của tour |
| `nhu_cau`, `tour_nhu_cau` | Danh mục nhu cầu và quan hệ nhiều-nhiều với tour |
| `lich_khoi_hanh` | Mỗi dòng là một chuyến: `ngay_khoi_hanh`, **`ngay_ve`**, `diem_khoi_hanh`, `gia_nguoi_lon`, `gia_tre_em`, `so_cho`, `han_dat`, `trang_thai` ENUM(`open`,`closed`,`cancelled`) |
| `don_dat_tour` | Đơn đặt: lịch đi, số khách, đơn giá đã lưu, `tong_goc`, `muc_giam`, `tien_giam`, `tong_thanh_toan`, `trang_thai` ENUM(`cho_xac_nhan`,`da_xac_nhan`,`da_huy`,`hoan_thanh`) |
| `tai_khoan` | Tài khoản khách hàng và admin/nhân viên |

Chỗ còn của một chuyến = `so_cho` − tổng `so_nguoi_lon + so_tre_em` của các đơn có `trang_thai` là `cho_xac_nhan` hoặc `da_xac_nhan`; đơn `da_huy`/`hoan_thanh` không giữ chỗ. `so_cho` chỉ là sức chứa tối đa, không phải chỗ còn. Một lịch chỉ nhận đặt khi `trang_thai = 'open'`, `ngay_khoi_hanh` chưa qua, `han_dat` chưa qua và số khách của đơn không vượt chỗ còn; `ScheduleModel::blockReason()` là nơi duy nhất mô tả các điều kiện này.

`ontrip.sql` chỉ chứa cấu trúc ban đầu (tám bảng). Cột `ngay_ve` và bảng `don_dat_tour` nằm trong script cập nhật `database/2026-10-30_01_them_ngay_ve_va_bang_don_dat_tour.sql`, hai đơn mẫu nằm trong `..._02_du_lieu_mau_don_dat_tour.sql`. Không sửa `ontrip.sql` để thêm các phần này; muốn đổi cấu trúc thì tạo script mới và ghi vào `database/NHAT_KY.md`.

Các tệp liên quan:

| Phần | Tệp |
| --- | --- |
| Điểm vào và chọn trang | `public/index.php` |
| Trang chủ | `app/views/home/index.php` |
| Chuẩn bị tour nổi bật từ database cho trang chủ | `app/controllers/HomeController.php` |
| Danh sách tour trong nước/quốc tế | `app/views/tours/index.php` |
| Chi tiết tour | `app/views/tours/detail.php` |
| Tra cứu và đề xuất tour thông minh chuyên sâu | `app/views/tours/smart_search.php` |
| Thuật toán xếp hạng, mùa vụ, thời tiết và gợi ý ±3 ngày | `app/models/SmartRecommendation.php` (lớp `SmartRecommendationModel`) |
| Dịch vụ API thời tiết Open-Meteo & đồng bộ lịch trình từng ngày | `app/models/WeatherService.php` (lớp `WeatherService`) |
| Đọc tour, nhu cầu, lịch khởi hành và lịch trình | `app/models/tour.php` (lớp `TourModel`) |
| Nhận yêu cầu danh sách/chi tiết, chuẩn bị dữ liệu cho View | `app/controllers/TourController.php` |
| Cấu trúc database ban đầu và danh mục | `database/ontrip.sql` |
| Script cập nhật bảng/cột về sau | `database/2026-10-30_01_them_ngay_ve_va_bang_don_dat_tour.sql` (cột `ngay_ve`, bảng `don_dat_tour`) |
| Nhật ký thay đổi database | `database/NHAT_KY.md` — ghi ngày, tệp script, nội dung, có bắt buộc import hay không |
| Kết nối PDO dùng chung cho Model | `config/database.php` — gọi `getDatabaseConnection()` |
| Navbar và footer dùng chung | `app/views/partials/header.php`, `footer.php` |
| Toàn bộ CSS khách hàng | `public/assets/css/style.css` |
| CSS quản trị | `public/assets/css/admin.css` |
| Toàn bộ JavaScript | `public/assets/js/script.js` |

CSS và JavaScript được chia bằng chú thích theo phần dùng chung, trang chủ, danh sách và chi tiết tour. Khi sửa một phần, tìm chú thích tương ứng trước. Bảng/cột trong `ontrip.sql` dùng tiếng Việt không dấu, dạng `snake_case` (ví dụ `lich_khoi_hanh.ngay_khoi_hanh`). TourModel chỉ đọc tour đã công bố và điểm đến hoạt động; lịch đi phải đang mở, chưa qua ngày khởi hành và chưa hết hạn đặt. Cùng điều kiện đó được kiểm tra lại trong `ScheduleModel::blockReason()` trước khi cho đặt. Không suy ra giá trẻ em bằng tỷ lệ trong View: lấy trực tiếp từ lịch được chọn. Ảnh chưa có dùng placeholder, không tự tạo ảnh hay đổi đường dẫn trong database.

Luồng tour: trình duyệt → `public/index.php` → TourController → TourModel/PDO → View → header/nội dung/footer. Trang chủ đi qua HomeController, đọc tour nổi bật, điểm đến khả dụng và danh mục nhu cầu từ database. Mỗi nhóm hiển thị tối đa năm điểm đến; đếm DISTINCT tour đã công bố, có lịch mở, chưa quá ngày đi hoặc hạn đặt. Banner và tin tức vẫn là dữ liệu mẫu riêng. Form tìm kiếm gửi GET sang `page=tours&type=all`, giữ từ khóa/ngày/số khách/ngân sách/nhu cầu. Ngân sách so với tổng nhóm sau giảm 5% hoặc 8% theo giá của cùng một lịch; sức chứa chỉ là giới hạn tối đa, chưa phải chỗ còn. Controller kiểm tra đầu vào; Model lọc kết quả ban đầu và tạm tính nhóm. JavaScript tiếp tục lọc/sắp xếp/phân trang trên dữ liệu đã tải; trạng thái được giữ trên URL và xóa hết khi reset. Không đưa truy vấn SQL hoặc đọc $_GET vào View.

## Quy tắc nghiệp vụ đã thống nhất

Phần dưới đây là đặc tả nghiệp vụ ưu đãi nhóm. Quy tắc tính tiền đã được triển khai ở `GroupPricing`; các ca kiểm thử vẫn giữ để đối chiếu khi sửa quy tắc.
### Quy tắc khuyến mãi theo nhóm của OnTrip

Ngày thống nhất: 19/10/2026. Đây là phần bổ sung phạm vi cho nghiệp vụ tour, tìm kiếm/đề xuất và đặt tour.

#### 1. Trạng thái triển khai

Đã hiển thị ưu đãi trên trang chủ; danh sách/chi tiết đọc database. Máy chủ tạm tính nhóm cho tìm kiếm và so ngân sách; trang chi tiết tạm tính trên trình duyệt theo lịch được chọn. **Đã chốt phần tính tiền chính thức khi tạo đơn:** `GroupPricing` là nguồn duy nhất tính mức giảm và số tiền, `ScheduleModel::createBooking()` gọi lại trên giá của lịch và lưu kết quả vào `don_dat_tour`. Các ca kiểm tra ở mục 7 đã chạy đúng.

#### 2. Điều kiện và mức giảm

Ưu đãi tự động áp dụng chung cho các tour trong nước và quốc tế, theo số khách **trong một đơn đặt cùng một lịch khởi hành**.

| Tổng số khách trong đơn | Mức giảm trên tổng tiền tour |
| --- | --- |
| 1–4 khách | 0% |
| 5–9 khách | 5% |
| Từ 10 khách | 8% |

- Tổng khách = số người lớn + số trẻ em. Mỗi người tính một khách và một chỗ theo phạm vi hiện tại.
- Số lượng người lớn/trẻ em phải là số nguyên không âm, tổng ít nhất một khách. Điều kiện độ tuổi sẽ được xác định riêng khi thiết kế đặt tour.
- Chỉ tính khách trong chính đơn đó; không cộng các đơn khác, lịch đi khác hoặc số người đã đặt trước đó.
- Không cần mã giảm giá; không giới hạn số lượt nhận ưu đãi. Giới hạn chỗ trống của lịch đi vẫn phải được kiểm tra.
- Chỉ áp dụng một mức cao nhất đủ điều kiện: từ 10 khách giảm 8%, không cộng 5% và 8%.
- Phiên bản này chỉ có ưu đãi nhóm; chưa áp dụng đồng thời giảm theo lịch đi hoặc mã giảm giá.

#### 3. Tính tiền

Giá gốc người lớn và trẻ em vẫn lấy theo **lịch khởi hành được chọn**, không lưu một giá chung cho toàn bộ tour.

```text
Tổng khách = số người lớn + số trẻ em
Tổng tiền gốc = số người lớn × giá người lớn + số trẻ em × giá trẻ em
Tiền giảm = làm tròn (tổng tiền gốc × mức giảm / 100) đến đơn vị đồng
Tổng thanh toán = tổng tiền gốc − tiền giảm
```

Làm tròn tiền giảm đến đồng gần nhất, phần lẻ 0,5 đồng làm tròn lên. Sau đó lấy tổng gốc trừ tiền giảm để các số hiển thị luôn khớp. Dùng số tiền chính xác theo đơn vị đồng khi triển khai lưu trữ và tính toán.

Ví dụ: 4 người lớn giá 6.000.000đ/người và 1 trẻ em giá 4.000.000đ/người có tổng 5 khách. Tổng gốc 28.000.000đ, giảm 5% tương đương 1.400.000đ, thanh toán 26.600.000đ.

#### 4. Kiểm tra khi tạo đơn và bảo toàn giá

1. Phía máy chủ kiểm tra số người lớn/trẻ em, lịch đi, hạn đặt và chỗ còn. Không tin tổng tiền hoặc mức giảm gửi từ trình duyệt.
2. Lấy giá của lịch đi, xác định mức giảm theo tổng khách và tính lại số tiền trước khi tạo đơn.
3. Lưu cùng đơn: số người lớn/trẻ em, đơn giá gốc của từng loại khách, tổng gốc, mức giảm, tiền giảm và tổng thanh toán tại thời điểm tạo.
4. Việc đổi giá tour hoặc chính sách nhóm sau này chỉ ảnh hưởng đơn mới. Xem đơn cũ phải đọc số tiền đã lưu, không tính lại theo chính sách hiện tại.
5. Nếu sau này cho sửa số khách trong đơn, phải kiểm tra lại chỗ, tính lại ưu đãi và hiển thị số tiền mới để khách xác nhận. Không tự thay đổi các đơn đã tạo.
6. Khi hủy đơn, dùng số tiền thực tế đã ghi nhận và quy tắc hủy của tour; việc giải phóng chỗ vẫn tuân thủ quy tắc không trả chỗ hai lần trong báo cáo.

#### 5. Hiển thị, tìm kiếm và đề xuất

- Trang chủ hiển thị rõ hai mức ưu đãi cùng điều kiện tính người lớn + trẻ em trong một đơn.
- Giá “từ / khách” trên thẻ tour vẫn là giá gốc, chưa giảm theo nhóm khi chưa biết chính xác thành phần khách và lịch đi. Không gạch giá hoặc hiển thị mức giảm 8% như thể mọi đơn đều được hưởng.
- Trang chi tiết/đặt tour sau này hiển thị tổng gốc, mức giảm, tiền giảm và tổng thanh toán sau khi khách chọn lịch và nhập số người lớn/trẻ em.
- Tìm kiếm/đề xuất phải dùng cùng một quy tắc tính giá với đặt tour. Khi đã biết số người lớn/trẻ em, so sánh ngân sách với số tiền sau giảm.
- Thanh tìm tour trang chủ dùng nhãn “Ngân sách”, được hiểu là tổng ngân sách cho nhóm khách đã nhập. Khi triển khai, so sánh trực tiếp ngân sách này với tổng tiền sau giảm.
- Trang danh sách tour dùng bộ lọc “Mức giá”, không nhập người lớn/trẻ em: mức giá ở đây là giá từ cho một người lớn trước ưu đãi nhóm. Chỉ lọc theo đơn giá; chưa tính ưu đãi hay so sánh tổng tiền nhóm khi chưa có thành phần khách cụ thể.
- Giao diện đã tách số người lớn và trẻ em cụ thể, thay bộ chọn số khách theo khoảng. Khi triển khai nghiệp vụ vẫn phải kiểm tra lại số lượng ở phía máy chủ trước khi tính giá và xác định mức giảm.

#### 6. Phân chia trách nhiệm MVC khi triển khai nghiệp vụ

- **Model/phần xử lý nghiệp vụ:** đọc giá lịch đi, xác định ưu đãi và tính tiền bằng một quy tắc dùng chung cho tìm kiếm, đề xuất và đặt tour.
- **Controller:** nhận và kiểm tra đầu vào, gọi xử lý nghiệp vụ, chuyển kết quả sang View.
- **View:** hiển thị thông tin ưu đãi và các số tiền đã tính; không quyết định số tiền thanh toán chính thức.

Chỉ bổ sung các tệp xử lý và bảng dữ liệu khi bắt đầu thực hiện chức năng tương ứng. TourModel và TourController hiện đọc dữ liệu và tạm tính nhóm cho tìm kiếm; chưa có bảng khuyến mãi. Việc tính và lưu giá chính thức ở máy chủ sẽ thực hiện khi làm đặt tour.

#### 7. Các ca kiểm tra dành cho bước triển khai tính tiền

Các ca dưới đây đã chạy đúng với `GroupPricing::calculate()`; giữ lại làm hồi quy. Giả sử mọi khách đều là người lớn, giá 6.000.000đ/người:

| Số khách | Tổng gốc | Mức giảm | Tiền giảm | Thanh toán |
| --- | ---: | ---: | ---: | ---: |
| 1 | 6.000.000đ | 0% | 0đ | 6.000.000đ |
| 4 | 24.000.000đ | 0% | 0đ | 24.000.000đ |
| 5 | 30.000.000đ | 5% | 1.500.000đ | 28.500.000đ |
| 9 | 54.000.000đ | 5% | 2.700.000đ | 51.300.000đ |
| 10 | 60.000.000đ | 8% | 4.800.000đ | 55.200.000đ |
| 11 | 66.000.000đ | 8% | 5.280.000đ | 60.720.000đ |

Cần kiểm tra thêm: nhóm người lớn/trẻ em như ví dụ mục 3; số khách âm, lẻ hoặc bằng 0; lịch hết chỗ/quá hạn; nhập tiền giả từ trình duyệt; quy tắc làm tròn; thay chính sách không đổi giá đơn cũ; thay số khách qua các mốc 4→5 và 9→10; bộ đề xuất và tạo đơn tính cùng một kết quả.
