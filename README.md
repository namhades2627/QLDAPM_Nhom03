# OnTrip

OnTrip là dự án website quản lý tour du lịch, xây dựng bằng PHP thuần theo kiến trúc MVC và phát triển từng giai đoạn. Trang chủ đọc tour nổi bật, điểm đến và nhu cầu từ database; tìm kiếm dẫn sang danh sách tour với các điều kiện đã chọn. Danh sách, chi tiết, đăng nhập khách hàng và luồng đặt tour đều đọc/ghi database qua Model/Controller. Banner và tin tức vẫn dùng dữ liệu mẫu. Đơn đặt tour được tạo thật trong database nhưng chưa có cổng thanh toán trực tuyến.

## Chạy trên máy cá nhân

Yêu cầu: XAMPP có Apache, PHP 8.2 trở lên và MariaDB 10.4 trở lên. Không cần Composer, Node.js hay lệnh cài gói để xem website.

1. Chép hoặc clone thư mục dự án vào `htdocs/QLDAPM_Nhom03` của XAMPP.
2. Khởi động **Apache** và **MySQL** trong XAMPP.
3. Import `database/ontrip.sql` qua phpMyAdmin: một tệp duy nhất đã gồm cấu trúc 9 bảng, danh mục, 48 tour demo và 2 đơn mẫu. Chỉ có bảng mà chưa nhập dữ liệu thì danh sách tour sẽ trống.
4. Xem [database/NHAT_KY.md](database/NHAT_KY.md) để biết các thay đổi database gần đây. Tệp an toàn khi chạy lại nên import lại trên database cũ chỉ bổ sung phần còn thiếu.
5. Mở <http://localhost/QLDAPM_Nhom03/public/>.

Nếu đặt thư mục bằng tên khác, thay `QLDAPM_Nhom03` trong URL bằng tên thư mục đó. Truy cập qua Apache để PHP xử lý các View; mở trực tiếp tệp `.php` trong trình duyệt sẽ không chạy ứng dụng.

| Trang | URL mẫu |
| --- | --- |
| Trang chủ | <http://localhost/QLDAPM_Nhom03/public/> |
| Tour trong nước | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=domestic> |
| Tour quốc tế | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=international> |
| Chi tiết tour | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tour-detail&type=domestic&id=mb01> |
| Đề xuất tour thông minh | <http://localhost/QLDAPM_Nhom03/public/index.php?page=smart-search> |
| Đăng nhập khách hàng | <http://localhost/QLDAPM_Nhom03/public/index.php?page=login> |
| Đơn của tôi | <http://localhost/QLDAPM_Nhom03/public/index.php?page=my-bookings> |
| Quản trị | <http://localhost/QLDAPM_Nhom03/admin/> |
| Lịch khởi hành (quản trị) | <http://localhost/QLDAPM_Nhom03/admin/index.php?action=schedules> |

Có thể chọn vùng từ menu **Tour** trên navbar. Ví dụ URL danh sách tour miền Bắc thêm `&region=north`. Trên trang danh sách, nhấn **Xem chi tiết** để mở đúng tour.

## Những gì đã có

- Trang chủ với navbar, ba banner tự chuyển mỗi 7 giây, thanh tìm tour, bốn tour nổi bật, điểm đến trong nước/quốc tế, tin tức và footer.
- Khám phá điểm đến hiển thị tối đa năm điểm mỗi nhóm và số tour đang có lịch mở nhận đặt; nhấp ảnh để xem đúng các tour của điểm đến đó. Danh mục nhu cầu lấy chung bảng `nhu_cau` với trang tour.
- Thanh tìm tour gửi từ khóa, ngày đi, người lớn/trẻ em, ngân sách và nhu cầu sang kết quả trong nước lẫn quốc tế. Ngân sách là tổng nhóm sau ưu đãi; ngày, giá và ngân sách phải khớp cùng một lịch. Điều kiện giữ khi tải lại trang; xóa bộ lọc sẽ xóa cả ngân sách và số khách. Đây là tạm tính, chưa xác nhận chỗ còn hay nhận đặt tour.
- Danh sách tour trong nước và quốc tế dùng chung một View. Mỗi loại có **24 tour mẫu**, hiển thị **12 tour/trang** theo lưới 3 cột × 4 hàng trên desktop; bộ lọc ở bên trái, phân trang ở giữa bên dưới danh sách.
- Bộ lọc theo điểm đến, nơi khởi hành, ngày, mức giá và nhu cầu; tìm theo tên/điểm đến có hỗ trợ không dấu, sắp xếp giá và thời lượng. Bộ lọc và phân trang xử lý bằng JavaScript trên dữ liệu database đã tải. Ngày, nơi khởi hành và mức giá phải khớp cùng một lịch đi.
- Trang chi tiết đọc giới thiệu, lịch trình, lịch khởi hành và giá người lớn/trẻ em từ database. Mỗi lịch hiển thị **chỗ còn** và **hạn đặt chỗ**; lịch đã kín chỗ bị khóa và ghi “Hết chỗ”. Cột phải là form đặt tour: chọn lịch, chọn số người lớn/trẻ em, xem tạm tính theo lịch đó. Có một ảnh lớn, khu vực đánh giá chưa có dữ liệu, cùng ba tour liên quan với hai nút chuyển. Tour không có lịch đang mở sẽ hiện thông báo và không có giá giả định.
- Giao diện hiển thị **chính sách ưu đãi nhóm**: 5–9 khách giảm 5%, từ 10 khách giảm 8% cho một đơn cùng lịch khởi hành. Quy tắc đầy đủ nằm trong [AGENT.md](AGENT.md).
- **Đặt tour**: khách đăng nhập, chọn lịch và số người, sang bước xác nhận (ngày đi/ngày về, hạn đặt, chỗ còn, bảng giá, tổng thanh toán), rồi tạo đơn. Máy chủ tính lại giá và ưu đãi, kiểm tra hạn đặt và chỗ còn trong transaction nên không thể đặt quá chỗ hoặc đặt sau hạn. Đơn lưu đơn giá tại thời điểm đặt.
- **Đơn của tôi**: danh sách đơn của chính tài khoản đang đăng nhập, kèm nút hủy đơn còn hiệu lực; hủy xong trả chỗ về cho chuyến.
- **Quản trị lịch khởi hành**: danh sách toàn bộ chuyến có thống kê, lọc theo tour/khoảng ngày/trạng thái, cột chỗ còn dạng thanh đo; trang chi tiết chuyến có thông tin giá – ngày – hạn đặt, danh sách khách của chuyến và đổi trạng thái từng đơn.

**Chưa triển khai:** thanh toán trực tuyến, quản lý tour/lịch/tài khoản bằng thêm–sửa–xóa, đánh giá tour, quên mật khẩu/OTP và đăng nhập Google. Giá và lịch trình trong SQL là dữ liệu demo; chỗ còn được tính từ các đơn đặt thật trong bảng `don_dat_tour`.

## Cấu trúc mã

```text
QLDAPM_Nhom03/
├── app/
│   ├── controllers/
│   │   ├── HomeController.php      # Chuẩn bị tour nổi bật cho trang chủ
│   │   ├── TourController.php      # Chuẩn bị danh sách và chi tiết tour
│   │   ├── AuthController.php      # Đăng ký/đăng nhập/đăng xuất khách hàng
│   │   ├── BookingController.php   # Xác nhận, tạo, xem và hủy đơn đặt tour
│   │   └── AdminController.php     # Đăng nhập quản trị, bảng điều khiển, lịch khởi hành
│   ├── models/
│   │   ├── tour.php                # Lớp TourModel truy vấn dữ liệu bằng PDO
│   │   ├── booking.php            # Lớp ScheduleModel: chỗ còn, hạn đặt, đơn đặt, khách theo chuyến
│   │   ├── GroupPricing.php        # Lớp GroupPricing: ưu đãi nhóm và tính tiền
│   │   ├── tai_khoan.php           # Lớp TaiKhoanModel: tài khoản khách hàng và quản trị
│   │   ├── admin.php               # Lớp AdminModel: số liệu tổng quan
│   │   ├── SmartRecommendation.php # Lớp SmartRecommendationModel xếp hạng, mùa vụ, thời tiết, ±3 ngày
│   │   └── WeatherService.php      # Lớp WeatherService gọi Open-Meteo API & dự báo thời tiết theo lịch trình
│   └── views/
│       ├── home/index.php          # Trang chủ và dữ liệu mẫu của trang chủ
│       ├── tours/
│       │   ├── index.php           # Danh sách tour trong nước/quốc tế
│       │   ├── detail.php          # Chi tiết một tour và form đặt tour
│       │   └── smart_search.php    # Đề xuất tour thông minh
│       ├── bookings/               # confirm.php, success.php, my_bookings.php
│       ├── admin/                  # index.php, schedules.php, schedule_detail.php, partials/
│       ├── auth/                   # layout.php dùng chung cho form đăng nhập/đăng ký
│       ├── errors/database.php     # Thông báo khi không tải được dữ liệu
│       └── partials/
│           ├── header.php          # Navbar và biểu tượng dùng chung
│           ├── footer.php          # Footer dùng chung
│           └── booking-format.php  # Định dạng tiền/ngày/nhãn trạng thái dùng chung
├── admin/
│   └── index.php                   # Điểm vào riêng của khu vực quản trị
├── database/
│   ├── ontrip.sql                  # Nguồn duy nhất: cấu trúc 9 bảng và dữ liệu demo/đơn mẫu
│   └── NHAT_KY.md                  # Nhật ký thay đổi database
├── config/
│   └── database.php                # Hàm lấy kết nối PDO dùng chung
├── public/
│   ├── index.php                   # Điểm vào, điều hướng sang Controller hoặc trang chủ
│   └── assets/
│       ├── css/style.css           # CSS trang khách hàng
│       ├── css/admin.css           # CSS khu vực quản trị
│       ├── js/script.js            # JavaScript trang khách hàng
│       └── images/                 # Ảnh và placeholder cục bộ
├── .gitignore                      # Bỏ qua tệp riêng của máy, log và tệp tạm
├── AGENT.md                        # Hướng dẫn sửa mã và quy tắc nghiệp vụ
└── README.md
```

Luồng tour: **trình duyệt → `public/index.php` → TourController → TourModel → database → View**. Luồng đặt tour: **chi tiết tour → `BookingController::form()` → `confirm.php` → `create()` → `ScheduleModel::createBooking()` (transaction) → `success.php`**. Luồng quản trị: **`admin/index.php` → `AdminController` → `ScheduleModel`/`AdminModel` → `app/views/admin/*`**. Controller nhận tham số URL; Model truy vấn PDO; View hiển thị kết quả. Trang chủ đi qua HomeController để lấy tối đa bốn tour, ưu tiên tour được đánh dấu `noi_bat = 1`; nếu chưa đủ thì bổ sung tour đã công bố. Mã tour không tồn tại trả HTTP 404; lỗi database trả HTTP 503 với thông báo chung, chi tiết lỗi được ghi vào log PHP.

## Database `ontrip`

Kết nối PDO được chuẩn bị trong `config/database.php`. Model sẽ nạp tệp này bằng `require_once` và gọi `getDatabaseConnection()` để nhận đối tượng PDO. Mặc định kết nối tới `127.0.0.1:3306`, database `ontrip`, tài khoản `root`, mật khẩu trống của XAMPP. Có thể ghi đè bằng biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`; tệp không tự đọc `.env`. Bật MySQL trong XAMPP khi dùng kết nối này. HomeController và TourController sử dụng TourModel để đọc dữ liệu qua kết nối PDO.

Mở **phpMyAdmin → Import** và chọn [database/ontrip.sql](database/ontrip.sql). Tệp này là **nguồn duy nhất**: tạo database `ontrip`, chín bảng có khóa ngoại, nhập sáu vùng, sáu nhu cầu, 48 tour demo, 144 lịch khởi hành và hai đơn mẫu. Không cần import thêm tệp nào. Tệp dùng `IF NOT EXISTS`/`NOT EXISTS` nên import lại trên database đang có dữ liệu cũng an toàn, chỉ bổ sung phần còn thiếu.

Quy ước: mọi thay đổi bảng, cột và dữ liệu mẫu được gộp thẳng vào `ontrip.sql` rồi export lại; giữ tệp an toàn khi chạy lại và ghi một dòng vào `database/NHAT_KY.md` sau mỗi lần sửa.

| Bảng | Vai trò |
| --- | --- |
| `khu_vuc_tour`, `diem_den` | Phân loại trong nước/quốc tế, vùng và điểm đến; lưu ảnh điểm đến. |
| `tour` | Tên, mã tour nghiệp vụ, giới thiệu, số ngày, ảnh đại diện và trạng thái. `id_tour` là khóa nội bộ, `ma_tour` để hiển thị/tra cứu. |
| `lich_trinh_ngay` | Nội dung từng ngày của một tour. |
| `nhu_cau`, `tour_nhu_cau` | Danh mục nhu cầu và quan hệ nhiều-nhiều với tour. |
| `lich_khoi_hanh` | Ngày đi, **ngày về**, nơi xuất phát, giá người lớn/trẻ em, sức chứa, hạn nhận đặt và trạng thái theo **từng lịch**. |
| `don_dat_tour` | Đơn đặt tour: lịch đi, số người lớn/trẻ em, đơn giá đã lưu, tổng gốc, mức giảm, tiền giảm, tổng thanh toán và trạng thái đơn. |
| `tai_khoan` | Tài khoản khách hàng và admin/nhân viên; mật khẩu lưu bằng `password_hash()`. |

Tên bảng/cột dùng tiếng Việt không dấu theo dạng `snake_case`, ví dụ `ngay_khoi_hanh`, `gia_nguoi_lon`. Mã loại/vùng/nhu cầu và giá trị trạng thái trong dữ liệu giữ dạng mã ngắn để sau này đối chiếu với URL và bộ lọc hiện có.

Tệp SQL có 48 tour demo: 24 tour trong nước và 24 tour quốc tế, mỗi khu vực 8 tour, kèm nhu cầu, lịch trình theo ngày và 3 lịch khởi hành cho mỗi tour. Giá, ngày đi, sức chứa và chương trình phục vụ học tập, chưa phải dữ liệu kinh doanh đã xác nhận. Chạy lại tệp chỉ bổ sung bản ghi còn thiếu, không cập nhật các bản ghi đã có. Danh sách, chi tiết và tour nổi bật trang chủ đọc database. Chỗ còn của mỗi lịch được tính từ bảng `don_dat_tour`, không lưu sẵn trong `lich_khoi_hanh`; hai đơn mẫu `OTDEMO0001`/`OTDEMO0002` chỉ để xem trước trang danh sách khách. Chưa có bảng tin tức hay đánh giá vì các chức năng này chưa được triển khai.

## Sửa giao diện và dữ liệu mẫu

| Muốn chỉnh | Mở tệp |
| --- | --- |
| Logo, navbar, menu Tour | `app/views/partials/header.php` |
| Footer | `app/views/partials/footer.php` |
| Banner, tin tức trang chủ | Các mảng `$banners`, `$articles` ở đầu `app/views/home/index.php` |
| Điểm đến, nhu cầu và điều kiện tìm kiếm | `TourModel`, `HomeController`, `TourController`; dữ liệu ở các bảng liên quan trong database |
| Tour nổi bật trang chủ | `TourModel::getFeaturedTours()` và cột `tour.noi_bat` trong database |
| Danh sách, bộ lọc và thẻ tour | `app/views/tours/index.php` |
| Dữ liệu tour, giá, nhu cầu và lịch khởi hành | Các bảng trong database `ontrip`; `database/ontrip.sql` là nguồn duy nhất cho cấu trúc và dữ liệu mẫu |
| Nội dung trang chi tiết | `app/views/tours/detail.php` |
| Màu sắc, kích thước và bố cục | `public/assets/css/style.css` (trang khách), `public/assets/css/admin.css` (quản trị), chia bằng chú thích theo phần dùng chung và từng trang |
| Banner, menu, bộ lọc, phân trang, tour liên quan, form đặt tour | `public/assets/js/script.js`, chia bằng chú thích theo từng trang |
| Nội dung trang xác nhận, cảm ơn và Đơn của tôi | `app/views/bookings/` |
| Bảng điều khiển và trang lịch khởi hành | `app/views/admin/index.php`, `schedules.php`, `schedule_detail.php`; khung chung ở `app/views/admin/partials/` |
| Thêm bảng hoặc cột mới | Sửa trực tiếp `database/ontrip.sql`, giữ câu lệnh an toàn khi chạy lại và ghi vào `database/NHAT_KY.md` |
| Điều hướng URL sang View | `public/index.php` (khách), `admin/index.php` (quản trị) |

Để đổi ảnh, đặt tệp trong `public/assets/images/` rồi sửa giá trị `image` của mục tương ứng, ví dụ `'image' => 'assets/images/ten-anh.jpg'`. Đường dẫn tính từ `public/`. Những mục chưa có ảnh sử dụng `placeholder.svg`. Ảnh nguồn nên có độ phân giải đủ lớn cho khung hiển thị; CSS giữ tỷ lệ và cắt phần dư bằng `object-fit: cover`.

## Hướng phát triển tiếp theo

Bổ sung thanh toán trực tuyến và doanh thu theo kỳ từ `don_dat_tour`; thêm CRUD tour/lịch/tài khoản cho quản trị; làm chức năng đánh giá tour sau khi khách hoàn thành chuyến; cập nhật nội dung tour thực tế thay dữ liệu demo. Mọi thay đổi giá/ưu đãi phải tính lại ở máy chủ theo `GroupPricing` và kiểm tra chỗ còn qua `ScheduleModel`, không tin số tiền từ trình duyệt. Các trang và chức năng khác được bổ sung theo tiến độ dự án, không tạo sẵn module chưa sử dụng.

Giao diện có tham khảo các trang du lịch công khai và hình mẫu trong quá trình thiết kế; nhận diện và bố cục OnTrip được xây dựng riêng.
