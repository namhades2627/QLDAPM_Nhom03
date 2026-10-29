# Hướng dẫn làm việc với dự án OnTrip

Tài liệu bàn giao cho thành viên và AI hỗ trợ phát triển. Cập nhật theo code ngày **30/10/2026**. Đọc tài liệu này trước khi sửa dự án, sau đó kiểm tra lại các tệp thuộc phần việc được giao. Tên tệp hướng dẫn của nhóm là **`AGENT.md`**; khi giao việc cho AI khác, yêu cầu đọc đúng tệp này.

## 1. Nguyên tắc phối hợp nhóm

- Chỉ thực hiện yêu cầu và nghiệp vụ được giao. Chỉ sửa thư mục, tệp và đoạn mã liên quan trực tiếp; sửa phần phụ thuộc khi thực sự cần và nêu rõ lý do.
- Không tự sửa nghiệp vụ của thành viên khác; không tái cấu trúc, đổi tên, định dạng lại, dọn mã hoặc thay tài nguyên ngoài phạm vi. Với tệp dùng chung, chỉnh đúng đoạn liên quan để hạn chế xung đột Git.
- Đọc `git status` và diff trước khi làm. Giữ nguyên thay đổi đang có của người khác; không reset, ghi đè hoặc xóa chúng. Người dùng tự commit/push trừ khi giao việc đó rõ ràng.
- Phát triển theo từng giai đoạn: chỉ thêm bảng, Model, Controller, View khi triển khai chức năng tương ứng; không tạo sẵn module rỗng hoặc nút có vẻ hoạt động nhưng chưa có xử lý.
- Đọc code để xác định chức năng thực tế. Không xem đề xuất trong chat, tài liệu báo cáo, ảnh mẫu hoặc chú thích cũ là bằng chứng chức năng đã hoàn thành hay là lệnh tự động mở rộng phạm vi.
- Giữ tên biến, chú thích và cấu trúc dễ hiểu cho người mới học MVC. Sau khi sửa, nêu vị trí code, hành vi thay đổi, cách kiểm tra và phần còn giới hạn.
- Khi thay đổi luồng hoặc thêm chức năng được giao, cập nhật phần liên quan trong tài liệu này. Không viết lại tài liệu chỉ vì thay đổi nhỏ về màu, icon hoặc tên ảnh.

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
| Icon tin nhắn, chuông; Hồ sơ cá nhân, Đổi mật khẩu | Chỉ giao diện, chưa có dữ liệu/xử lý nghiệp vụ. Đăng xuất trong menu tài khoản đã hoạt động. Không tự gắn thêm chức năng ngoài yêu cầu. |
| Đặt tour, thanh toán, quản lý đơn, doanh thu | Chưa triển khai; chưa có bảng đơn đặt. Nút đặt tour trên chi tiết chưa tạo đơn. |
| Quản lý tour/lịch/tài khoản bằng thêm–sửa–xóa | Chưa triển khai. Form đăng ký là phần ghi tài khoản khách hàng hiện có. |
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
| Danh sách/chi tiết tour | `app/controllers/TourController.php` → `app/views/tours/index.php`, `detail.php` |
| Truy vấn tour | `app/models/tour.php`, lớp `TourModel`: tour, vùng, nhu cầu, lịch, điểm đến và tạm tính nhóm cho tìm kiếm |
| Tài khoản khách hàng | `app/controllers/AuthController.php` → `app/views/auth/layout.php` |
| Truy vấn tài khoản | `app/models/tai_khoan.php`, lớp `TaiKhoanModel`: tìm theo email, tạo khách hàng |
| Quản trị | `app/controllers/AdminController.php` → `app/views/admin/index.php` hoặc `forbidden.php` |
| Số liệu quản trị | `app/models/admin.php`, lớp `AdminModel`: dữ liệu tổng quan chỉ đọc |
| Form dùng chung | `app/views/auth/layout.php`: hỗ trợ đăng ký/đăng nhập khách hàng và đăng nhập quản trị qua biến cấu hình |
| Tệp View cũ | `app/views/auth/login.php`, `register.php` còn trong thư mục nhưng router hiện gọi AuthController rồi nạp `layout.php`; không thêm xử lý xác thực vào các wrapper này |
| Navbar/footer khách hàng | `app/views/partials/header.php`, `footer.php`; không dùng làm khung quản trị |
| Lỗi database khách hàng | `app/views/errors/database.php`; ghi chi tiết lỗi vào log, không đưa exception/SQL ra giao diện |
| Database/kết nối | `database/ontrip.sql`, `config/database.php` |
| Toàn bộ CSS | **`public/assets/css/style.css`**, chia theo chú thích từng phần |
| Toàn bộ JavaScript | **`public/assets/js/script.js`**, chia theo chú thích từng phần |
| Ảnh | `public/assets/images/`, tour tại `images/tours/`, placeholder cục bộ |

Không tách thêm CSS/JS riêng từng trang. CSS quản trị dùng lớp `.admin-*`; JS kiểm tra phần tử tồn tại trước khi gắn sự kiện vì một file dùng cho cả trang chủ, tour, tài khoản và quản trị. Không đổi tên các Model chỉ để đồng nhất phong cách.

## 5. Route, session và phân quyền

### Khách hàng

- `public/index.php` mặc định trang chủ; `page=tours&type=domestic|international|all`; chi tiết `page=tour-detail&type=domestic|international&id=mb01` (id trên URL này là **mã tour nghiệp vụ**, không phải `id_tour`).
- Tài khoản: `page=login`, `page=register`, `page=logout` (đăng xuất bằng POST có CSRF).
- AuthController kiểm tra form, gọi TaiKhoanModel. Đăng ký thành công chuyển sang đăng nhập; đăng nhập thành công về trang chủ. Navbar hiển thị tên và Đăng xuất, **không có mục Quản lý**.
- Session PHP mặc định dùng cookie `PHPSESSID`; thông tin đăng nhập ở `$_SESSION['tai_khoan']`, gồm `id_tai_khoan`, `ho_ten`, `email`, `vai_tro`; không đưa hash/mật khẩu vào session.
- Form công khai luôn tạo vai trò `khach_hang`, hoạt động. Không nhận vai trò hoặc trạng thái đặc quyền từ POST. Public login hiện kiểm tra tài khoản hoạt động mà không giới hạn vai trò; đăng nhập ở đây không tạo phiên quản trị.

### Quản trị

- Vào `/admin/` khi chưa có phiên quản trị sẽ hiển thị form đăng nhập tại lối vào đó. `admin/index.php?action=login` là action đăng nhập; thành công chuyển về `admin/index.php`.
- Dùng tên session **`ONTRIP_ADMIN`**, cookie giới hạn trong đường dẫn `/admin/` của dự án. Dùng cùng bảng `tai_khoan` và Model tài khoản, nhưng phiên độc lập với khách hàng. Không chuyển lại thành đăng nhập qua navbar khách hàng.
- AdminController kiểm tra mật khẩu, `dang_hoat_dong = 1` và vai trò `admin`/`nhan_vien`. Mỗi lần mở bảng điều khiển đọc lại tài khoản từ database, đối chiếu ID, trạng thái và vai trò. Tài khoản mất quyền trả HTTP 403 qua `forbidden.php`.
- `action=logout` chỉ POST có CSRF; hủy phiên quản trị rồi về form quản trị. Không ảnh hưởng phiên khách hàng. URL cũ `public/index.php?page=admin` chỉ chuyển sang `../admin/`.
- Mở menu user để thấy Hồ sơ cá nhân, Đổi mật khẩu, Đăng xuất. Hai mục đầu chỉ giao diện; tin nhắn và thông báo cũng chỉ giao diện. Không thêm số thông báo, nội dung tin nhắn hoặc trạng thái hệ thống giả.

### Quyền đã có và quyền sẽ triển khai

Hiện admin/nhân viên đều xem được bảng điều khiển; khách hàng bị từ chối đăng nhập quản trị. Chưa có CRUD để áp dụng các quyền chi tiết sau:

- Admin quản lý toàn hệ thống, gồm tài khoản nhân viên, vai trò và khóa/mở tài khoản.
- Nhân viên vận hành tour, lịch và xử lý đơn theo chức năng được giao; không được cấp quyền hoặc quản lý tài khoản nhân sự.
- Khách hàng chỉ thao tác dữ liệu của chính mình. Khi làm đơn đặt phải kiểm tra chủ sở hữu đơn, không chỉ kiểm tra đã đăng nhập.
- Chặn quyền tại Controller cho **mọi trang và thao tác**, không chỉ ẩn menu. Không cần tự tạo bảng phân quyền khi ba vai trò hiện tại đã đủ.

Mật khẩu dùng `password_hash()`/`password_verify()`, SQL dùng prepared statements, đầu ra HTML có escape. Cả hai luồng đổi session ID sau đăng nhập; form POST dùng `$_SESSION['csrf_token']`. Không bỏ các kiểm tra này khi sửa giao diện.

## 6. Database và dữ liệu

Có tám bảng nghiệp vụ hiện tại:

| Bảng | Ý nghĩa / lưu ý |
| --- | --- |
| `khu_vuc_tour` | Sáu vùng, có loại tour `domestic`/`international` |
| `diem_den` | Điểm đến, vùng, trạng thái hoạt động |
| `nhu_cau`, `tour_nhu_cau` | Danh mục nhu cầu và quan hệ nhiều–nhiều với tour |
| `tour` | ID nội bộ, mã nghiệp vụ, tên, điểm đến chính, giới thiệu, số ngày, ảnh, trạng thái và nổi bật; **không lưu giá chung** |
| `lich_trinh_ngay` | Nội dung từng ngày của tour |
| `lich_khoi_hanh` | Ngày đi, điểm khởi hành (hiện là cột văn bản), giá người lớn/trẻ em, sức chứa, hạn đặt, trạng thái của từng lịch |
| `tai_khoan` | ID, họ tên, email duy nhất, điện thoại, hash mật khẩu, vai trò, trạng thái, thời điểm tạo/cập nhật |

- Tên bảng/cột tiếng Việt không dấu dạng `snake_case`. Giữ mã vùng, loại và trạng thái hiện có để tương thích URL/bộ lọc.
- Vùng trong nước: `north`, `central`, `south`; quốc tế: `asia`, `europe`, `americas-oceania`.
- Mã tour demo: `mb01`–`mb08`, `mt01`–`mt08`, `mn01`–`mn08`, `as01`–`as08`, `eu01`–`eu08`, `us01`–`us08`. Tổng 48 tour, mỗi loại 24; SQL có ba lịch demo cho mỗi tour. Không tự đánh lại mã hoặc đổi tên/ảnh mà người dùng đã chỉnh.
- Trạng thái tour: `draft`, `published`, `archived`; lịch: `open`, `closed`, `cancelled`. Tài khoản dùng `vai_tro = admin|nhan_vien|khach_hang`, `dang_hoat_dong = 1|0`.
- Email là duy nhất. `tai_khoan.mat_khau` chứa hash; không lưu cột xác nhận mật khẩu. `tao_luc`, `cap_nhat_luc` hiện có trong schema.
- `ontrip.sql` là cấu trúc và seed cài đặt; import lại chủ yếu bổ sung bản ghi thiếu, **không tự cập nhật nội dung hoặc mật khẩu của bản ghi đã tồn tại**. Sửa SQL trong repo không tự sửa database phpMyAdmin của thành viên khác. Không mặc định người dùng đã import bản mới.
- Khi được giao thay đổi schema/dữ liệu, nêu rõ lệnh cập nhật dành cho database đã import và kiểm tra với schema hiện có; không xóa dữ liệu hoặc chạy lại seed để thay thế migration tùy tiện.
- Ba tài khoản demo trong seed: `admin@gmail.com` / `adminontrip123`; `nhanvien@gmail.com` / `nhanvienontrip123`; `khachhang@gmail.com` / `khachhangontrip123`. SQL chỉ lưu hash, các mật khẩu này phục vụ chạy thử. Database đã import trước lần đổi mật khẩu có thể vẫn còn hash cũ.

## 7. Luồng tour và quy ước dữ liệu hiển thị

- Luồng: trình duyệt → entry point → Controller → Model/PDO → View. Controller nhận và kiểm tra GET/POST; Model truy vấn; View hiển thị. Không đưa SQL hoặc đọc trực tiếp GET/POST vào View để thực hiện nghiệp vụ.
- TourModel cho khách hàng đọc tour công bố và điểm đến hoạt động; lịch phải `open`, chưa qua ngày đi, chưa hết `han_dat`. Không có lịch hợp lệ thì không hiển thị giá giả định.
- “Giá từ” là giá người lớn thấp nhất của lịch hợp lệ. Giá trẻ em đọc trực tiếp từ lịch được chọn, không suy ra theo tỷ lệ tại View.
- HomeController chuẩn bị tour nổi bật, nhu cầu, nhóm điểm đến và ảnh khám phá riêng. Banner/tin tức còn trong mảng View; không phục hồi `sample-data.php` (đã bỏ).
- Form tìm tour trang chủ gửi GET sang `page=tours&type=all`: `q`, `date`, `adults`, `children`, `budget`, `needs[]`. Danh sách còn dùng `region`, `destination`, `origin`, `price`. Giá/ngân sách, ngày, nơi đi phải khớp **cùng một lịch**.
- Ngân sách trang chủ là tổng nhóm sau ưu đãi; mức giá trang danh sách là giá từ/người lớn trước giảm nhóm. Không gộp hai ý nghĩa. Số khách và ngân sách được giữ khi lọc, tải lại URL và xóa khi reset.
- Controller/Model lọc kết quả ban đầu, JS tiếp tục lọc/sắp xếp/phân trang trên dữ liệu đã tải; thay đổi phải giữ hai phía nhất quán. Lưới desktop 3 cột, 12 tour/trang, phân trang giữa phía dưới; không tự thêm người lớn/trẻ em vào bộ lọc bên trái.
- Tạm tính chi tiết hiện ở trình duyệt. Máy chủ đã tạm tính nhóm cho **tìm kiếm**, chưa tạo đơn hoặc chốt thanh toán. `so_cho` là sức chứa, chưa trừ đơn đặt; không gọi là chỗ còn.
- AdminModel đọc số liệu quản trị riêng: đếm toàn bộ tour, tour công bố, điểm đến hoạt động, lịch `open` từ hôm nay và năm lịch gần nhất. Đây không phải điều kiện khả dụng dành cho khách hàng: thống kê lịch chưa xét hạn đặt, trạng thái tour hoặc chỗ còn. Không đồng nhất hai truy vấn khi chưa chốt nghiệp vụ.
- Ảnh tour dùng chung giữa trang chủ/danh sách/chi tiết theo database. Ảnh khám phá là bộ ảnh riêng `KhamPha_*.jpg` được ánh xạ trong HomeController. Ảnh chưa có dùng `placeholder.svg`; không tự tạo ảnh hoặc sửa đường dẫn database ngoài phần được giao.
- Đường dẫn tài nguyên View khách hàng tính từ `public/`; View quản trị dùng `../public/assets/...`. Không tạo bản CSS/JS/ảnh trùng trong thư mục `admin/`.

## 8. Kiểm tra trước khi bàn giao

- Kiểm tra cú pháp các file PHP đã sửa bằng **PHP 8.2 của XAMPP** (`php -l <tệp>`). Đảm bảo VS Code và PHP chạy thực tế cùng phiên bản; không sử dụng PHP cũ trên PATH rồi tắt chẩn đoán để che lỗi.
- Chạy chẩn đoán Intelephense với PHP 8.2, sửa lỗi thực sự; View nhận biến từ Controller/include khai báo PHPDoc `@var` đúng kiểu; partial có giá trị mặc định cho biến tùy chọn. Không giả định máy khác có script chẩn đoán tạm của người phát triển trước.
- Sửa JavaScript thì kiểm tra cú pháp (`node --check public/assets/js/script.js` nếu có Node) và console trình duyệt. PHP/CSS/JS cùng chia sẻ nhiều trang nên kiểm tra trang bị tác động và phần phụ thuộc liên quan.
- Tài khoản: đăng ký hợp lệ, email trùng, lỗi từng trường, sai mật khẩu, tài khoản bị khóa, navbar sau đăng nhập và sau đăng xuất; không hiện lại mật khẩu trong HTML khi lỗi.
- Quản trị: mở `/admin/` chưa đăng nhập, từ chối tài khoản khách hàng, nhận admin/nhân viên, kiểm tra phiên riêng và đăng xuất về form quản trị. Tài khoản mất quyền/khóa không còn mở bảng điều khiển được.
- Giao diện quản trị: menu user mở/đóng bằng click, đóng khi nhấn ngoài/Esc; sidebar trượt và nội dung giãn mượt, không để phần ẩn nhận focus. Không gắn chức năng giả cho mục chỉ giao diện.
- Tour: kiểm tra lọc và chuyển trang giữ điều kiện; tour không tồn tại trả 404; không có lịch thì không giả giá; lỗi database thông báo chung 503. Không import lại hoặc sửa dữ liệu của thành viên khác chỉ để kiểm tra UI.
- Dự án chưa có bộ test tự động được commit vào repo. Các kiểm tra trước đây không thay thế kiểm tra phần vừa sửa trên máy nhận việc; không báo đã chạy kiểm tra nếu chưa thực hiện. Không tạo test chỉ để sao chép implementation cho sửa giao diện nhỏ.
- Kiểm tra `git diff --check` và diff cuối; không để log, ảnh chụp thử, script tạm hoặc cấu hình cá nhân lọt vào commit. Báo rõ file đã thay đổi và phần chưa kiểm tra nếu môi trường thiếu công cụ.

## Quy tắc nghiệp vụ đã thống nhất

Phần dưới đây là đặc tả cho bước triển khai sau. Những dòng về kiểm thử là **ca cần kiểm tra**, không phải kết quả tính năng đã chạy.
### Quy tắc khuyến mãi theo nhóm của OnTrip

Ngày thống nhất: 19/10/2026. Đây là phần bổ sung phạm vi cho nghiệp vụ tour, tìm kiếm/đề xuất và đặt tour.

#### 1. Trạng thái triển khai

Đã hiển thị ưu đãi trên trang chủ; danh sách/chi tiết đọc database. Máy chủ tạm tính nhóm cho tìm kiếm và so ngân sách; trang chi tiết tạm tính trên trình duyệt theo lịch được chọn. Chưa có bảng đơn đặt hoặc tính tiền chính thức để tạo đơn ở máy chủ. Các yêu cầu bên dưới là căn cứ triển khai các bước tiếp theo.

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

| Số khách | Tổng gốc | Mức giảm | Tiền giảm | Thanh toán |
| --- | ---: | ---: | ---: | ---: |
| 1 | 6.000.000đ | 0% | 0đ | 6.000.000đ |
| 4 | 24.000.000đ | 0% | 0đ | 24.000.000đ |
| 5 | 30.000.000đ | 5% | 1.500.000đ | 28.500.000đ |
| 9 | 54.000.000đ | 5% | 2.700.000đ | 51.300.000đ |
| 10 | 60.000.000đ | 8% | 4.800.000đ | 55.200.000đ |
| 11 | 66.000.000đ | 8% | 5.280.000đ | 60.720.000đ |

Cần kiểm tra thêm: nhóm người lớn/trẻ em như ví dụ mục 3; số khách âm, lẻ hoặc bằng 0; lịch hết chỗ/quá hạn; nhập tiền giả từ trình duyệt; quy tắc làm tròn; thay chính sách không đổi giá đơn cũ; thay số khách qua các mốc 4→5 và 9→10; bộ đề xuất và tạo đơn tính cùng một kết quả.
