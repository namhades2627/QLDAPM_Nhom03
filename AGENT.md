# Hướng dẫn làm việc với dự án OnTrip

Tài liệu bàn giao cho thành viên và AI hỗ trợ phát triển. Cập nhật theo code ngày **31/10/2026**. Đọc tài liệu này trước khi sửa dự án, sau đó kiểm tra lại các tệp thuộc phần việc được giao. Tên tệp hướng dẫn của nhóm là **`AGENT.md`**; khi giao việc cho AI khác, yêu cầu đọc đúng tệp này.

## 1. Nguyên tắc phối hợp nhóm

- Chỉ thực hiện yêu cầu và nghiệp vụ được giao. Chỉ sửa thư mục, tệp và đoạn mã liên quan trực tiếp; sửa phần phụ thuộc khi thực sự cần và nêu rõ lý do.
- Không tự sửa nghiệp vụ của thành viên khác; không tái cấu trúc, đổi tên, định dạng lại, dọn mã hoặc thay tài nguyên ngoài phạm vi. Với tệp dùng chung, chỉnh đúng đoạn liên quan để hạn chế xung đột Git.
- **Bảo vệ giao diện dùng chung:** `public/assets/css/style.css` chứa cả khách hàng, tài khoản, quản trị và các module bổ sung. Không thay toàn bộ file bằng bản cũ hoặc cắt phần cuối để thêm CSS cho module của mình. Khi merge, phải giữ các khối ngoài phạm vi, đặc biệt khối `KHUNG QUẢN TRỊ` với các selector `.admin-*`, các media query và hiệu ứng sidebar. Quy tắc tương tự áp dụng cho `script.js`: giữ nguyên handler sidebar và menu tài khoản khi sửa phần khác.
- Giao diện quản trị đã chốt: sidebar tối, bố cục dashboard trắng–cam, thẻ thống kê, bảng lịch, icon tin nhắn/chuông, menu user và sidebar trượt mượt. Nếu không được giao sửa giao diện quản trị, giữ nguyên khung này. Khối CSS chuẩn có trong commit `9ffa8ce`; chỉ dùng để đối chiếu/khôi phục phần bị mất, không checkout toàn bộ file để ghi đè thay đổi mới.
- Trước khi bàn giao thay đổi CSS/JS dùng chung hoặc giải quyết xung đột, mở cả `/public/`, trang chi tiết tour, trang tài khoản và `/admin/` để kiểm tra trực quan phần bị ảnh hưởng. Nếu quản trị hiện thành các dòng chữ dọc, kiểm tra tải stylesheet, sự tồn tại khối `.admin-*` và diff trước khi sửa HTML/Controller. Không báo hoàn tất chỉ dựa trên kiểm tra cú pháp khi chưa kiểm tra bố cục.
- Đọc `git status` và diff trước khi làm. Giữ nguyên thay đổi đang có của người khác; không reset, ghi đè hoặc xóa chúng. Người dùng tự commit/push trừ khi giao việc đó rõ ràng.
- Phát triển theo từng giai đoạn: chỉ thêm bảng, Model, Controller, View khi triển khai chức năng tương ứng; không tạo sẵn module rỗng hoặc nút có vẻ hoạt động nhưng chưa có xử lý.
- Đọc code để xác định chức năng thực tế. Không xem đề xuất trong chat, tài liệu báo cáo, ảnh mẫu hoặc chú thích cũ là bằng chứng chức năng đã hoàn thành hay là lệnh tự động mở rộng phạm vi.
- Giữ tên biến, chú thích và cấu trúc dễ hiểu cho người mới học MVC. Sau khi sửa, nêu vị trí code, hành vi thay đổi, cách kiểm tra và phần còn giới hạn.
- Khi thay đổi luồng hoặc thêm chức năng được giao, cập nhật phần liên quan trong tài liệu này. Không viết lại tài liệu chỉ vì thay đổi nhỏ về màu, icon hoặc tên ảnh.
- **Mọi thay đổi database phải cập nhật cùng lúc `database/ontrip.sql` và `database/database_changes.md` trước khi bàn giao**, không chờ người dùng nhắc lại. Chỉ dùng `ontrip.sql` cho cả cài mới và nâng cấp: khi sửa schema, cập nhật phần CREATE cho máy mới và thêm khối `BEGIN CAP_NHAT_...` / `END CAP_NHAT_...` có thể chạy lại cho database cũ. Không tạo file SQL cập nhật riêng. Nhật ký phải có ngày, nội dung, ảnh hưởng dữ liệu, vị trí khối SQL, cách áp dụng và phần đã kiểm tra; không ghi rằng database máy khác đã cập nhật chỉ vì đã sửa file trong Git.
- Tên cột mới dùng tiếng Việt không dấu, ngắn và dễ nhớ nhưng vẫn rõ nghĩa (ví dụ `hinh_anh`). Không tự đổi hàng loạt các tên cột hiện có ngoài phần việc; khi đổi tên phải đồng bộ Model/Controller/View và giữ dữ liệu cũ.

Phần ảnh đại diện hiện được xử lý trong `AdminController` bằng `avatarUrl()`, `isManagedAvatar()`, `saveAvatar()`, `deleteAvatar()`; không còn file `tai_anh_dai_dien.php` hay thư mục `app/services/`. Giữ đầy đủ kiểm tra ảnh khi sửa các hàm này.

### Quy ước đặt tên file, thư mục và lớp

- Giữ các từ tiếng Anh quen thuộc như `admin`, `home`, `tour`, `login`, `register`, `profile`, `password`, `detail`, `forbidden`; không dịch máy móc thành `QuanTri`, `TrangChu` hoặc tên tiếng Việt tương ứng. Tên riêng OnTrip giữ nguyên.
- Các tên nghiệp vụ còn lại dùng tiếng Việt không dấu, rõ nghĩa; không trộn các dạng `tai_khoan`, `taikhoan`, `accounts` để đặt tên file cho cùng một phần. Dùng `tai_khoan` cho file/thư mục và `TaiKhoan` làm phần tên lớp.
- File/thư mục nghiệp vụ dùng `snake_case`: `tai_khoan.php`, `thoi_tiet.php`, `de_xuat_thong_minh.php`; khi triển khai phần mới có thể dùng `khach_hang`, `lich_khoi_hanh`. Các tên tiếng Anh quen thuộc giữ cách viết thông dụng, như `admin/`, `home/`, `profile.php`, `detail.php`; thư mục View tour thống nhất là `tour/`.
- Controller dùng `PascalCase` và giữ hậu tố `Controller`; tên file trùng tên lớp: `HomeController.php`, `TaiKhoanController.php`, `AdminController.php`, `TourController.php`. Lớp Model dùng `PascalCase` với hậu tố tương ứng: `TaiKhoanModel`, `AdminModel`, `ThoiTietModel`; file Model vẫn dùng `snake_case`, ví dụ `admin.php`.
- Thành phần kỹ thuật dùng tên tiếng Anh thông dụng: `app`, `controllers`, `models`, `views`, `config`, `public`, `assets`, `partials`, `errors`; các file `index.php`, `header.php`, `footer.php`, `layout.php`, `database.php`, `style.css`, `script.js` giữ nguyên. Thư mục gốc `admin/` là lối vào quản trị và giữ URL hiện có.
- Bảng/cột database tiếp tục dùng tiếng Việt không dấu, `snake_case`. Tên tài nguyên ảnh đang được database tham chiếu giữ nguyên; không đổi schema, dữ liệu hoặc tên ảnh chỉ để đồng bộ tên file PHP.
- Đã chuẩn hóa tên file/thư mục/lớp ngày 31/10/2026. Các route GET (`page`, `action`), tên phương thức/biến hiện có, session và selector CSS/JS giữ nguyên trong lần đổi tên này để bảo toàn hành vi. Route là mã điều hướng, không bắt buộc trùng tên file View; ví dụ `action=profile` nạp `admin/profile.php`.
- Mỗi lần đổi tên phải cập nhật đầy đủ `require`/`require_once`, tên lớp/nơi khởi tạo, tham chiếu trong tài liệu và kiểm tra luồng chạy. Không tạo bản sao tên cũ hoặc đổi tên ngoài phạm vi được giao.
- Hai trang quản lý nhân sự/khách hàng hiện dùng chung `app/views/admin/tai_khoan.php`; không tạo thêm `khach_hang.php` chỉ để có đủ tên khi chưa cần tách giao diện/xử lý. Model đề xuất thông minh `de_xuat_thong_minh.php` đang trống; đổi tên không có nghĩa chức năng đề xuất đã hoạt động.

## 2. Công nghệ và cách chạy

- Website quản lý tour du lịch **OnTrip**, PHP thuần theo MVC, PDO và MariaDB/MySQL; JavaScript thuần, không dùng framework hay trình build.
- Môi trường dự án: XAMPP, **PHP 8.2 trở lên**, MariaDB 10.4 trở lên. Chạy website không cần Composer hoặc Node.js; Node chỉ có thể dùng cho kiểm tra JavaScript khi có sẵn.
- Đặt dự án trong `htdocs/QLDAPM_Nhom03`, bật Apache/MySQL và import **`database/ontrip.sql`** trên máy mới. Database tên **`ontrip`**. Không đổi thành `tripon` hoặc tạo bản SQL khác tên để thay thế tùy ý.
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
| Danh sách và chi tiết tour | Đọc database. Mỗi loại có 24 tour demo, 12 tour/trang, 3 cột × 4 hàng desktop. Chi tiết có một ảnh lớn, lịch trình, lịch đi, giá và tạm tính nhóm; ba tour liên quan mỗi lượt. |
| Đăng ký/đăng nhập khách hàng | Đã xử lý bằng Model/Controller, kiểm tra dữ liệu, email duy nhất, băm/xác thực mật khẩu, session và CSRF. |
| Đăng xuất khách hàng | POST có CSRF, hủy phiên khách hàng và về trang chủ. |
| Đăng nhập quản trị | Đã có form riêng tại `/admin/`; chỉ admin/nhân viên hoạt động được vào, session riêng. |
| Bảng điều khiển quản trị | Đã đọc số tour, tour công bố, điểm đến hoạt động, lịch mở sắp đi và năm lịch gần nhất từ database; có thu gọn thanh bên và menu tài khoản. |
| Menu quản trị | Lịch khởi hành và Danh mục tour hiện là liên kết đến vùng tổng quan trên cùng bảng điều khiển; chưa phải trang CRUD riêng. |
| Hồ sơ cá nhân quản trị | `action=profile` hỗ trợ GET/POST multipart: kiểm tra họ tên, điện thoại, email hợp lệ/không trùng, CSRF; cập nhật tài khoản từ ID session. Đã upload/lưu ảnh đại diện thật; không chọn ảnh mới thì giữ ảnh cũ, thay thành công mới xóa ảnh cũ. Đồng bộ thông tin vào session rồi redirect 303. Không đổi vai trò/trạng thái/mật khẩu qua form này. |
| Đổi mật khẩu quản trị | `action=password` hỗ trợ GET/POST có CSRF, xác thực mật khẩu hiện tại bằng `password_verify()`, mật khẩu mới ít nhất 8 ký tự/tối đa 72 byte, khác mật khẩu hiện tại và xác nhận khớp. Lưu bằng `password_hash()`, đổi session ID/CSRF và redirect 303. Không đưa mật khẩu về HTML hoặc log. |
| Icon tin nhắn, chuông | Chỉ giao diện, chưa có dữ liệu/xử lý nghiệp vụ. Đăng xuất trong menu tài khoản đã hoạt động. Không tự gắn thêm chức năng ngoài yêu cầu. |
| Đặt tour, thanh toán, quản lý đơn, doanh thu | **Đã triển khai (31/10/2026).** Khách đăng nhập chọn lịch, nhập liên hệ và người tham gia; máy chủ kiểm tra chỗ còn, tính lại tiền và **chụp giá vào đơn**. Đơn có bốn trạng thái `cho_xac_nhan → da_xac_nhan → hoan_thanh` và `da_huy`. Thanh toán mô phỏng ghi một dòng `thanh_toan` cho mỗi đơn. Quản trị xử lý trạng thái tại `action=orders`. Chưa có cổng thanh toán thật, hoàn tiền, email xác nhận hoặc báo cáo doanh thu theo kỳ. |
| Quản lý tài khoản | `action=accounts` trong HỆ THỐNG chỉ liệt kê admin/nhân viên. Chỉ admin truy cập: tìm/lọc/phân trang, tạo nhân viên, sửa liên hệ, đổi vai trò admin↔nhân viên, khóa/mở và xóa có xác nhận. Không đặt lại mật khẩu người khác. |
| Quản lý khách hàng | `action=customers` trong DỮ LIỆU ĐIỀU HÀNH chỉ liệt kê `khach_hang`. Chỉ admin truy cập: tìm theo họ tên/email/điện thoại, lọc trạng thái, phân trang, sửa liên hệ, khóa/mở và xóa có xác nhận; vai trò khách hàng cố định. Khách hàng vẫn tự đăng ký ở website; chưa có lịch sử đơn hoặc chi tiêu. |
| Quản lý tour/lịch bằng thêm–sửa–xóa | Chưa triển khai. |
| Đánh giá tour | Mới có giao diện trạng thái trống, chưa có bảng và chức năng gửi đánh giá. |
| Google, quên mật khẩu/OTP | Chưa triển khai. |
| Điều chỉnh lịch trình theo thời tiết/mùa vụ | Là hướng nghiệp vụ dự kiến, chưa có API hoặc xử lý. Chỉ bổ sung khi được giao và đã chốt quy tắc. |

Giá, lịch trình và lịch đi trong SQL là **dữ liệu demo phục vụ học tập**. Không trình bày sức chứa thành chỗ còn hoặc dữ liệu minh họa thành giao dịch đã xác nhận.

## 4. Bản đồ code

| Phần | Tệp / trách nhiệm |
| --- | --- |
| Điểm vào khách hàng | `public/index.php`: khởi tạo session, chọn route và gọi Controller |
| Điểm vào quản trị | `admin/index.php`: khởi tạo session riêng, chọn action và gọi AdminController |
| Trang chủ | `app/controllers/HomeController.php` → `app/views/home/index.php` |
| Danh sách/chi tiết tour | `app/controllers/TourController.php` → `app/views/tour/index.php`, `detail.php` |
| Truy vấn tour | `app/models/tour.php`, lớp `TourModel`: tour, vùng, nhu cầu, lịch, điểm đến và tạm tính nhóm cho tìm kiếm |
| Đặt tour và đơn của tôi | `app/controllers/DonDatController.php` → `app/views/don_dat/dat_tour.php`, `danh_sach.php`, `chi_tiet.php` |
| Truy vấn đơn đặt | `app/models/don_dat.php`, lớp `DonDatModel`: bảng giá/ưu đãi, chỗ còn, tạo đơn có transaction, thanh toán mô phỏng, trạng thái và truy vấn quản trị. Đây là **nguồn duy nhất** tính tiền; `TourModel::addGroupEstimates()` và View chỉ hiển thị lại kết quả |
| Tài khoản khách hàng | `app/controllers/TaiKhoanController.php` → `app/views/tai_khoan/layout.php` |
| Truy vấn tài khoản | `app/models/tai_khoan.php`, lớp `TaiKhoanModel`: tìm theo ID/email, tạo khách hàng, cập nhật thông tin hồ sơ admin/nhân viên |
| Quản trị | `app/controllers/AdminController.php` → `app/views/admin/index.php` hoặc `forbidden.php` |
| Giao diện tài khoản quản trị | `app/views/admin/profile.php`, `password.php`; dùng khung `app/views/admin/partials/header.php`, `footer.php` chung với dashboard. Không sao chép hoặc thay lại toàn bộ khung khi thêm nội dung trang. |
| Upload ảnh tài khoản | Các hàm ảnh đại diện trong `app/controllers/AdminController.php`: kiểm tra upload thật, MIME/nội dung ảnh, tối đa 5 MB, tối đa 4096 mỗi chiều và 12 triệu pixel; tên ngẫu nhiên do máy chủ cấp. Có GD thì mã hóa lại ảnh; không có GD thì lưu ảnh đã kiểm tra trong thư mục chỉ phục vụ file tĩnh. |
| Ảnh đại diện đã lưu | `public/uploads/avatars/`, file sinh ra được `.gitignore` loại trừ; giữ `.htaccess` để chặn thực thi script/liệt kê thư mục. `admin/.htaccess` giới hạn upload cho XAMPP/mod_php. Không lưu ảnh cá nhân vào thư mục tour hoặc dùng tên file do người dùng gửi. |
| Cập nhật schema ảnh | `tai_khoan.hinh_anh` là đường dẫn tương đối nullable. Máy mới dùng `ontrip.sql`; máy đã import chạy khối `CAP_NHAT_2026_10_30_HINH_ANH` trong cùng file để thêm/đổi từ tên cũ và giữ dữ liệu. Xem `database/database_changes.md`; không có file SQL nâng cấp riêng. |
| Số liệu quản trị | `app/models/admin.php`, lớp `AdminModel`: dữ liệu tổng quan chỉ đọc |
| Quản lý tài khoản/khách hàng | Hai route `accounts` và `customers` đi qua `AdminController::accounts()` và dùng chung `app/views/admin/tai_khoan.php`, với tiêu đề, biểu mẫu, thống kê và URL theo nhóm. `TaiKhoanModel::getManagedAccounts()`, `findManagedAccount()`, `createEmployee()`, `updateManagedAccount()`, `deleteManagedAccount()` xử lý dữ liệu; hai menu chỉ hiện cho admin. |
| Quản lý đơn đặt | Route `orders` đi qua `AdminController::donDat()` → `app/views/admin/don_dat.php`. `DonDatModel::getAdminOrders()`, `getAdminSummary()`, `updateStatus()` xử lý danh sách, thống kê và chuyển trạng thái; admin và nhân viên đều dùng được. |
| Form dùng chung | `app/views/tai_khoan/layout.php`: hỗ trợ đăng ký/đăng nhập khách hàng và đăng nhập quản trị qua biến cấu hình |
| Tệp View cũ | `app/views/tai_khoan/login.php`, `register.php` còn trong thư mục nhưng router hiện gọi TaiKhoanController rồi nạp `layout.php`; không thêm xử lý xác thực vào các wrapper này |
| Navbar/footer khách hàng | `app/views/partials/header.php`, `footer.php`; không dùng làm khung quản trị |
| Lỗi database khách hàng | `app/views/errors/database.php`; ghi chi tiết lỗi vào log, không đưa exception/SQL ra giao diện |
| Database/kết nối | `database/ontrip.sql`, `config/database.php` |
| Toàn bộ CSS | **`public/assets/css/style.css`**, chia theo chú thích từng phần |
| Toàn bộ JavaScript | **`public/assets/js/script.js`**, chia theo chú thích từng phần |
| Ảnh | `public/assets/images/`, tour tại `images/tours/`, placeholder cục bộ |

Không tách thêm CSS/JS riêng từng trang. CSS quản trị dùng lớp `.admin-*`; JS kiểm tra phần tử tồn tại trước khi gắn sự kiện vì một file dùng cho cả trang chủ, tour, tài khoản và quản trị. Tên Model tuân theo quy ước ở mục 1; không tự đổi tên ngoài phạm vi được giao.

## 5. Route, session và phân quyền

### Khách hàng

- `public/index.php` mặc định trang chủ; `page=tours&type=domestic|international|all`; chi tiết `page=tour-detail&type=domestic|international&id=mb01` (id trên URL này là **mã tour nghiệp vụ**, không phải `id_tour`).
- Tài khoản: `page=login`, `page=register`, `page=logout` (đăng xuất bằng POST có CSRF).
- Đặt tour: `page=booking&type=domestic|international&id=<mã tour>[&schedule=<id_lich_khoi_hanh>]`; đơn của tôi `page=orders`, chi tiết `page=orders&id=<ma_don>`. Cả hai đều yêu cầu đăng nhập khách hàng.
- TaiKhoanController kiểm tra form, gọi TaiKhoanModel. Đăng ký thành công chuyển sang đăng nhập; đăng nhập thành công về trang chủ. Navbar hiển thị tên và Đăng xuất, **không có mục Quản lý**.
- Session PHP mặc định dùng cookie `PHPSESSID`; thông tin đăng nhập ở `$_SESSION['tai_khoan']`, gồm `id_tai_khoan`, `ho_ten`, `email`, `vai_tro`; không đưa hash/mật khẩu vào session.
- Form công khai luôn tạo vai trò `khach_hang`, hoạt động. Không nhận vai trò hoặc trạng thái đặc quyền từ POST. Public login hiện kiểm tra tài khoản hoạt động mà không giới hạn vai trò; đăng nhập ở đây không tạo phiên quản trị.

### Quản trị

- Vào `/admin/` khi chưa có phiên quản trị sẽ hiển thị form đăng nhập tại lối vào đó. `admin/index.php?action=login` là action đăng nhập; thành công chuyển về `admin/index.php`.
- Dùng tên session **`ONTRIP_ADMIN`**, cookie giới hạn trong đường dẫn `/admin/` của dự án. Dùng cùng bảng `tai_khoan` và Model tài khoản, nhưng phiên độc lập với khách hàng. Không chuyển lại thành đăng nhập qua navbar khách hàng.
- AdminController kiểm tra mật khẩu, `dang_hoat_dong = 1` và vai trò `admin`/`nhan_vien`. Mỗi lần mở bảng điều khiển đọc lại tài khoản từ database, đối chiếu ID, trạng thái và vai trò. Tài khoản mất quyền trả HTTP 403 qua `forbidden.php`.
- `action=logout` chỉ POST có CSRF; hủy phiên quản trị rồi về form quản trị. Không ảnh hưởng phiên khách hàng. URL cũ `public/index.php?page=admin` chỉ chuyển sang `../admin/`.
- Mở menu user để vào Hồ sơ cá nhân (`action=profile`), Đổi mật khẩu (`action=password`) hoặc Đăng xuất. Hai trang đi qua kiểm tra session/quyền của AdminController giống dashboard; tài khoản được đọc lại theo ID session để thay email không làm mất phiên. Lưu hồ sơ nhận họ tên/email/điện thoại và ảnh tùy chọn; kiểm tra phía máy chủ kể cả khi tắt JS. Email giữ nguyên của chính mình hợp lệ, trùng tài khoản khác bị từ chối. Không nhận ID mục tiêu/vai trò/trạng thái/đường dẫn ảnh từ form. Thành công redirect 303 và báo một lần, tránh gửi lại khi refresh. Tin nhắn và thông báo vẫn chỉ giao diện.
- Session quản trị có `admin_credential_stamp` là dấu băm từ hash mật khẩu để phát hiện thay đổi thông tin xác thực. Sau đổi mật khẩu, phiên hiện tại được đổi ID/CSRF và tiếp tục đăng nhập; các phiên quản trị khác đã có dấu này bị từ chối ở request kế tiếp. Phiên quản trị cũ chưa có dấu được bổ sung khi đọc lại tài khoản. Cơ chế này chưa thu hồi session khách hàng đang mở ở `/public/`; không tuyên bố đã thu hồi mọi phiên trên toàn website.

### Quyền đã có và quyền sẽ triển khai

Hiện admin/nhân viên đều xem được bảng điều khiển; khách hàng bị từ chối đăng nhập quản trị. Quản lý tài khoản và Quản lý khách hàng hiện chỉ admin; các quyền tour/đơn dưới đây sẽ bổ sung khi triển khai module tương ứng:

- Admin quản lý toàn hệ thống, gồm tài khoản nhân viên, vai trò và khóa/mở tài khoản.
- Nhân viên vận hành tour, lịch và xử lý đơn theo chức năng được giao; không được cấp quyền hoặc quản lý tài khoản nhân sự.
- Khách hàng chỉ thao tác dữ liệu của chính mình. Khi làm đơn đặt phải kiểm tra chủ sở hữu đơn, không chỉ kiểm tra đã đăng nhập.
- Chặn quyền tại Controller cho **mọi trang và thao tác**, không chỉ ẩn menu. Không cần tự tạo bảng phân quyền khi ba vai trò hiện tại đã đủ.

Mật khẩu dùng `password_hash()`/`password_verify()`, SQL dùng prepared statements, đầu ra HTML có escape. Cả hai luồng đổi session ID sau đăng nhập; form POST dùng `$_SESSION['csrf_token']`. Không bỏ các kiểm tra này khi sửa giao diện.

### Quản lý tài khoản và khách hàng — bổ sung 31/10/2026

- URL: `/admin/index.php?action=accounts`. GET hiển thị danh sách hoặc form (`mode=create`, `edit=<id>`, `delete=<id>`); POST chỉ nhận `operation=create|update|status|delete`, có CSRF. Nhân viên bị trả 403 cho cả GET/POST; không chỉ ẩn menu.
- Menu Quản lý tài khoản nằm ngay dưới Bảng điều khiển trong HỆ THỐNG và chỉ hiển thị admin/nhân viên. Menu Quản lý khách hàng nằm trong DỮ LIỆU ĐIỀU HÀNH, URL `action=customers`, chỉ hiển thị khách hàng; không có nút tạo nhân viên hoặc bộ lọc vai trò ở trang này. POST khách hàng chỉ nhận `update|status|delete`; vai trò cố định `khach_hang`. Nhân viên hiện bị chặn ở cả hai mục, không tự mở quyền xem/sửa ngoài phạm vi đã giao.
- Nhóm `staff`/`customers` do Controller chọn từ route, không nhận từ POST. Model áp dụng nhóm ngay trong truy vấn danh sách, thống kê, tải form và kiểm tra lại trên dòng đích đã khóa trước mỗi thao tác ghi. Không thể dùng ID/form của mục này để đọc hoặc sửa/khóa/xóa tài khoản thuộc mục kia; không đổi khách hàng thành nhân sự hoặc nhân sự thành khách hàng qua hai form này.
- Thống kê tài khoản chỉ tính nhân sự (tổng, admin, nhân viên, đang khóa); thống kê khách hàng chỉ tính khách hàng (tổng, hoạt động, khóa, đăng ký trong tháng). Tìm/lọc/phân trang và thông báo sau lưu giữ đúng route, không quay khách hàng về trang nhân sự. Hai mục dùng chung View/CSS/JS để giữ bố cục hiện tại; không tạo bảng khách hàng trùng với `tai_khoan` và không thay schema/seed.
- Dùng bảng `tai_khoan` hiện có, không đổi schema/seed. Tạo mới tại đây luôn là nhân viên hoạt động, bỏ qua vai trò/trạng thái đặc quyền gửi kèm; khách hàng vẫn tự đăng ký ở website.
- Sửa họ tên, email duy nhất, số điện thoại, vai trò và trạng thái; không sửa mật khẩu hoặc ảnh người khác. Mật khẩu tạo nhân viên được kiểm tra/băm ở máy chủ, không đưa lại vào HTML. Thành công redirect 303 và thông báo một lần.
- Dòng tài khoản hiện tại mở Hồ sơ của tôi; Model cũng chặn chỉnh/khóa chính mình qua module này. Luôn giữ ít nhất một admin hoạt động. Các thao tác ghi dùng transaction, khóa các dòng admin theo thứ tự ID bằng `FOR UPDATE`, đọc lại quyền/trạng thái/dấu mật khẩu của admin thao tác rồi khóa tài khoản đích trước khi cập nhật; tránh hai admin đồng thời vô hiệu hóa nhau.
- Khóa hoặc hạ quyền admin/nhân viên làm phiên quản trị mất quyền ở request tiếp theo theo kiểm tra sẵn có. Khóa chặn các lần đăng nhập mới ở cả hai lối vào. Phiên khách hàng đang mở chưa có cơ chế thu hồi toàn website.
- Danh sách chỉ truy vấn các trường cần hiển thị, không lấy hash mật khẩu. Tìm/lọc/phân trang xử lý phía máy chủ; URL giữ bộ lọc khi chuyển trang/sửa/khóa. Chỉ thêm các khối `.admin-accounts*`, `.admin-managed-*` và handler liên quan trong CSS/JS dùng chung, giữ nguyên các khối module khác.
- Hai trang Quản lý tài khoản/khách hàng cùng dùng lớp body `admin-accounts-page`; màn hình ≤700px dùng sidebar dạng trượt, mặc định thu gọn, đóng bằng nền phủ hoặc Escape. Các trang quản trị khác giữ hành vi sidebar hiện có.
- Xóa: nút thùng rác mở vùng xác nhận tên/email; cần tích checkbox `confirm_delete=1` và gửi POST. GET không xóa dữ liệu, xác nhận vẫn dùng được khi tắt JavaScript. `TaiKhoanModel::deleteManagedAccount()` dùng cùng khóa admin/transaction để chặn xóa chính mình, giữ admin hoạt động và đọc lại quyền của người thao tác. Kiểm tra bản ghi liên quan qua các khóa ngoại đã khai báo đến `tai_khoan.id_tai_khoan`, kể cả CASCADE/SET NULL; nếu có dữ liệu thì yêu cầu khóa thay vì xóa. Khi thêm module đơn đặt phải khai báo khóa ngoại tương ứng, không dựa vào quan hệ ngầm. Controller cũng xử lý lỗi FK 1451. Chỉ sau commit mới dọn ảnh đại diện do ứng dụng quản lý và không còn được tài khoản khác dùng. Không đổi schema/seed.

### Đặt tour, thanh toán mô phỏng và xử lý đơn — bổ sung 31/10/2026

- URL khách hàng: `page=booking&type=...&id=<mã tour>` (GET hiển thị form, POST `operation` mặc định tạo đơn); `page=orders` (danh sách, POST `operation=pay|cancel`); `page=orders&id=<ma_don>` (chi tiết). URL quản trị: `admin/index.php?action=orders`, chi tiết `&id=<ma_don>`, POST chỉ nhận trạng thái đích hợp lệ cho đơn đang mở. Tất cả form POST đều có CSRF; sai token trả 403. Thành công redirect 303 để refresh không gửi lại.
- Đơn được nhận diện bằng `ma_don` dạng `OD` + ngày + chuỗi ngẫu nhiên, không lộ `id_don_dat` tuần tự trên URL. Truy vấn chi tiết/hủy/thanh toán luôn lọc thêm `id_tai_khoan` của phiên, nên khách không đọc hay sửa đơn người khác dù đoán được mã. Không có `id_tai_khoan` nào đến từ form.
- Chỉ nhận lịch khởi hành thuộc đúng tour trên URL, đang mở, chưa qua ngày đi và chưa hết hạn đặt. Số người lớn ≥ 1, tổng khách ≤ 99 và ≤ chỗ còn; người tham gia phải có họ tên và ngày sinh hợp lệ (nếu nhập). Thông tin liên hệ kiểm tra họ tên, email, điện thoại; mọi lỗi hiển thị lại form kèm dữ liệu đã nhập.
- Tiền luôn tính lại ở máy chủ từ giá của lịch; trường `tong_goc`/`tien_giam`/`tong_thanh_toan` gửi kèm từ trình duyệt bị bỏ qua. Quy tắc giảm giá dùng chung `DonDatModel::calculateTotals()` với tìm kiếm và trang chi tiết.
- Tạo đơn chạy trong transaction, khóa dòng lịch bằng `SELECT ... FOR UPDATE`, kiểm tra lại chỗ còn rồi mới ghi đơn và danh sách người tham gia; lỗi bất kỳ thì rollback. Nhờ vậy hai khách đặt cùng lúc không vượt sức chứa.
- Đơn lưu **giá chụp tại thời điểm đặt** (`gia_nguoi_lon`, `gia_tre_em`, `tong_goc`, `muc_giam`, `tien_giam`, `tong_thanh_toan`) cùng `ten_tour`, `ngay_khoi_hanh`, `diem_khoi_hanh`. Đổi giá/chính sách sau này không làm thay đổi đơn cũ; không tính lại khi xem.
- Trạng thái: `cho_xac_nhan` → `da_xac_nhan` → `hoan_thanh`; `da_huy` từ `cho_xac_nhan` hoặc `da_xac_nhan`. `hoan_thanh` và `da_huy` là trạng thái cuối. `DonDatModel::allowedTransitions()` là nguồn duy nhất; View chỉ hiện nút hợp lệ, Controller và Model kiểm tra lại. Chuyển sang chính trạng thái hiện tại trả về "không đổi" thay vì ghi log sai.
- Khách chỉ tự hủy đơn đang `cho_xac_nhan`. Admin/nhân viên xử lý mọi bước còn lại tại `action=orders`; thao tác ghi đọc lại đơn đã khóa để không dựa vào trạng thái trên form.
- Chỗ còn = `so_cho` − tổng khách của các đơn có `trang_thai <> 'da_huy'`, tính trong SQL. Hủy đơn tự trả chỗ vì không cần cập nhật cột đếm; không có cột `so_cho_con` để lệch.
- Thanh toán mô phỏng: chọn `chuyen_khoan|the|vi_dien_tu`, ghi một dòng `thanh_toan` với `trang_thai = 'thanh_cong'` và `ma_giao_dich` do máy chủ sinh. Đơn đã có thanh toán, đã hủy hoặc đã hoàn thành thì không thanh toán lại; ràng buộc UNIQUE trên `thanh_toan.id_don_dat` chặn ở tầng dữ liệu. Không nhận số tiền từ form và **không phải giao dịch thật**.
- Quản trị `action=orders` dùng được cho `admin` và `nhan_vien` (khác `accounts`/`customers` chỉ admin): thống kê trạng thái kèm doanh thu từ các đơn đã thanh toán, lọc theo trạng thái, tìm theo mã đơn/tên/email/điện thoại, phân trang 10 đơn/trang. URL giữ bộ lọc khi chuyển trang.
- Đơn không xóa được tour/lịch (khóa ngoại `RESTRICT`); xóa đơn xóa kèm người tham gia và thanh toán (`CASCADE`). Nhờ vậy `TaiKhoanModel::deleteManagedAccount()` từ chối xóa khách hàng còn đơn thay vì làm mất dữ liệu.
- Chỉ thêm các khối `.booking-*`, `.order-*`, `.admin-orders*` và handler liên quan trong CSS/JS dùng chung; không sửa khối của module khác.

## 6. Database và dữ liệu

Có tám bảng nghiệp vụ hiện tại:

| Bảng | Ý nghĩa / lưu ý |
| --- | --- |
| Xử lý đặt tour, thanh toán và trạng thái đơn | `app/models/don_dat.php` (lớp `DonDatModel`); ba bảng `don_dat_tour`, `chi_tiet_don_dat`, `thanh_toan` được tạo trong khối `CAP_NHAT_2026_10_31_DON_DAT_TOUR` của `database/ontrip.sql` |
| Điểm vào và chọn trang | `public/index.php` |
| Trang chủ | `app/views/home/index.php` |
| Chuẩn bị tour nổi bật từ database cho trang chủ | `app/controllers/HomeController.php` |
| Danh sách tour trong nước/quốc tế | `app/views/tour/index.php` |
| Chi tiết tour | `app/views/tour/detail.php` |
| Tra cứu và đề xuất tour thông minh chuyên sâu | `app/views/tour/de_xuat_thong_minh.php` |
| Thuật toán xếp hạng, mùa vụ, thời tiết và gợi ý ±3 ngày | `app/models/de_xuat_thong_minh.php` (lớp `DeXuatThongMinhModel`) |
| Dịch vụ API thời tiết Open-Meteo & đồng bộ lịch trình từng ngày | `app/models/thoi_tiet.php` (lớp `ThoiTietModel`) |
| Đọc tour, nhu cầu, lịch khởi hành và lịch trình | `app/models/tour.php` (lớp `TourModel`) |
| Nhận yêu cầu danh sách/chi tiết, chuẩn bị dữ liệu cho View | `app/controllers/TourController.php` |
| Cấu trúc database và danh mục ban đầu | `database/ontrip.sql` |
| Kết nối PDO dùng chung cho Model | `config/database.php` — gọi `getDatabaseConnection()` |
| Navbar và footer dùng chung | `app/views/partials/header.php`, `footer.php` |
| Toàn bộ CSS | `public/assets/css/style.css` |
| Toàn bộ JavaScript | `public/assets/js/script.js` |

CSS và JavaScript được chia bằng chú thích theo phần dùng chung, trang chủ, danh sách và chi tiết tour. Khi sửa một phần, tìm chú thích tương ứng trước. Bảng/cột trong `ontrip.sql` dùng tiếng Việt không dấu, dạng `snake_case` (ví dụ `lich_khoi_hanh.ngay_khoi_hanh`). TourModel chỉ đọc tour đã công bố và điểm đến hoạt động; lịch đi phải đang mở, chưa qua ngày khởi hành và chưa hết hạn đặt. Không suy ra giá trẻ em bằng tỷ lệ trong View: lấy trực tiếp từ lịch được chọn. Ảnh chưa có dùng placeholder, không tự tạo ảnh hay đổi đường dẫn trong database.

Luồng tour: trình duyệt → `public/index.php` → TourController → TourModel/PDO → View → header/nội dung/footer. Trang chủ đi qua HomeController, đọc tour nổi bật, điểm đến khả dụng và danh mục nhu cầu từ database. Mỗi nhóm hiển thị tối đa năm điểm đến; đếm DISTINCT tour đã công bố, có lịch mở, chưa quá ngày đi hoặc hạn đặt. Banner và tin tức vẫn là dữ liệu mẫu riêng. Form tìm kiếm gửi GET sang `page=tours&type=all`, giữ từ khóa/ngày/số khách/ngân sách/nhu cầu. Ngân sách so với tổng nhóm sau giảm 5% hoặc 8% theo giá của cùng một lịch; sức chứa chỉ là giới hạn tối đa, chưa phải chỗ còn. Controller kiểm tra đầu vào; Model lọc kết quả ban đầu và tạm tính nhóm. JavaScript tiếp tục lọc/sắp xếp/phân trang trên dữ liệu đã tải; trạng thái được giữ trên URL và xóa hết khi reset. Không đưa truy vấn SQL hoặc đọc $_GET vào View.

## Quy tắc nghiệp vụ đã thống nhất

Phần dưới đây là đặc tả cho bước triển khai sau. Những dòng về kiểm thử là **ca cần kiểm tra**, không phải kết quả tính năng đã chạy.
### Quy tắc khuyến mãi theo nhóm của OnTrip

Ngày thống nhất: 19/10/2026. Đây là phần bổ sung phạm vi cho nghiệp vụ tour, tìm kiếm/đề xuất và đặt tour.

#### 1. Trạng thái triển khai

Đã hiển thị ưu đãi trên trang chủ; danh sách/chi tiết đọc database. Máy chủ tạm tính nhóm cho tìm kiếm và so ngân sách; trang chi tiết tạm tính trên trình duyệt theo lịch được chọn. Từ 31/10/2026 đã có bảng đơn đặt và luồng tạo đơn chính thức ở máy chủ: giá và mức giảm được tính lại và chụp vào đơn. Các yêu cầu bên dưới là căn cứ đã dùng khi triển khai.

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

Các ca dưới đây là yêu cầu kiểm thử, chưa phải kết quả chạy chức năng. Giả sử mọi khách đều là người lớn, giá 6.000.000đ/người:

Từ 31/10/2026, `DonDatModel::calculateTotals()` đã được kiểm tra bằng bộ test mô hình và bộ test HTTP end-to-end cho đúng các mốc 4→5 và 9→10, tổng có cả người lớn/trẻ em, tiền giả gửi từ trình duyệt bị bỏ qua và giá đơn cũ không đổi khi giá lịch thay đổi.


| Số khách | Tổng gốc | Mức giảm | Tiền giảm | Thanh toán |
| --- | ---: | ---: | ---: | ---: |
| 1 | 6.000.000đ | 0% | 0đ | 6.000.000đ |
| 4 | 24.000.000đ | 0% | 0đ | 24.000.000đ |
| 5 | 30.000.000đ | 5% | 1.500.000đ | 28.500.000đ |
| 9 | 54.000.000đ | 5% | 2.700.000đ | 51.300.000đ |
| 10 | 60.000.000đ | 8% | 4.800.000đ | 55.200.000đ |
| 11 | 66.000.000đ | 8% | 5.280.000đ | 60.720.000đ |

Cần kiểm tra thêm: nhóm người lớn/trẻ em như ví dụ mục 3; số khách âm, lẻ hoặc bằng 0; lịch hết chỗ/quá hạn; nhập tiền giả từ trình duyệt; quy tắc làm tròn; thay chính sách không đổi giá đơn cũ; thay số khách qua các mốc 4→5 và 9→10; bộ đề xuất và tạo đơn tính cùng một kết quả.
