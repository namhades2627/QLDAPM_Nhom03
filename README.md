# OnTrip

OnTrip là dự án website quản lý tour du lịch, xây dựng bằng PHP thuần theo kiến trúc MVC và phát triển từng giai đoạn. Trang chủ đọc tour nổi bật, điểm đến và nhu cầu từ database; tìm kiếm dẫn sang danh sách tour với các điều kiện đã chọn. Danh sách và chi tiết cũng đọc database qua Model/Controller. Khách hàng đã đăng ký/đăng nhập, đặt tour theo lịch khởi hành, xem và hủy đơn của mình; quản trị viên xử lý trạng thái đơn. Thanh toán được **xác nhận mô phỏng**, chưa nối cổng thanh toán thật. Banner và tin tức vẫn dùng dữ liệu mẫu.

## Chạy trên máy cá nhân

Yêu cầu: XAMPP có Apache, PHP 8.2 trở lên và MariaDB 10.4 trở lên. Không cần Composer, Node.js hay lệnh cài gói để xem website.

1. Chép hoặc clone thư mục dự án vào `htdocs/QLDAPM_Nhom03` của XAMPP.
2. Khởi động **Apache** và **MySQL** trong XAMPP.
3. Import `database/ontrip.sql` qua phpMyAdmin để có bảng và 48 tour demo. Chỉ có bảng mà chưa nhập dữ liệu thì danh sách tour sẽ trống.
4. Mở <http://localhost/QLDAPM_Nhom03/public/>.

Nếu đặt thư mục bằng tên khác, thay `QLDAPM_Nhom03` trong URL bằng tên thư mục đó. Truy cập qua Apache để PHP xử lý các View; mở trực tiếp tệp `.php` trong trình duyệt sẽ không chạy ứng dụng.

| Trang | URL mẫu |
| --- | --- |
| Trang chủ | <http://localhost/QLDAPM_Nhom03/public/> |
| Tour trong nước | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=domestic> |
| Tour quốc tế | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=international> |
| Chi tiết tour | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tour-detail&type=domestic&id=mb01> |
| Đặt tour | <http://localhost/QLDAPM_Nhom03/public/index.php?page=booking&type=domestic&id=mb01> |
| Đơn đặt của tôi | <http://localhost/QLDAPM_Nhom03/public/index.php?page=orders> |
| Chi tiết một đơn | <http://localhost/QLDAPM_Nhom03/public/index.php?page=orders&id=OD261031730A61> |
| Quản lý đơn đặt (quản trị) | <http://localhost/QLDAPM_Nhom03/admin/index.php?action=orders> |
| Đề xuất tour thông minh | <http://localhost/QLDAPM_Nhom03/public/index.php?page=smart-search> |

Có thể chọn vùng từ menu **Tour** trên navbar. Ví dụ URL danh sách tour miền Bắc thêm `&region=north`. Trên trang danh sách, nhấn **Xem chi tiết** để mở đúng tour.

## Những gì đã có

- Trang chủ với navbar, ba banner tự chuyển mỗi 7 giây, thanh tìm tour, bốn tour nổi bật, điểm đến trong nước/quốc tế, tin tức và footer.
- Khám phá điểm đến hiển thị tối đa năm điểm mỗi nhóm và số tour đang có lịch mở nhận đặt; nhấp ảnh để xem đúng các tour của điểm đến đó. Danh mục nhu cầu lấy chung bảng `nhu_cau` với trang tour.
- Thanh tìm tour gửi từ khóa, ngày đi, người lớn/trẻ em, ngân sách và nhu cầu sang kết quả trong nước lẫn quốc tế. Ngân sách là tổng nhóm sau ưu đãi; ngày, giá và ngân sách phải khớp cùng một lịch. Điều kiện giữ khi tải lại trang; xóa bộ lọc sẽ xóa cả ngân sách và số khách. Đây là tạm tính, chưa xác nhận chỗ còn hay nhận đặt tour.
- Danh sách tour trong nước và quốc tế dùng chung một View. Mỗi loại có **24 tour mẫu**, hiển thị **12 tour/trang** theo lưới 3 cột × 4 hàng trên desktop; bộ lọc ở bên trái, phân trang ở giữa bên dưới danh sách.
- Bộ lọc theo điểm đến, nơi khởi hành, ngày, mức giá và nhu cầu; tìm theo tên/điểm đến có hỗ trợ không dấu, sắp xếp giá và thời lượng. Bộ lọc và phân trang xử lý bằng JavaScript trên dữ liệu database đã tải. Ngày, nơi khởi hành và mức giá phải khớp cùng một lịch đi.
- Trang chi tiết đọc giới thiệu, lịch trình, lịch khởi hành và giá người lớn/trẻ em từ database. Chọn lịch khác sẽ đổi giá tạm tính theo lịch đó. Có một ảnh lớn, khu vực đánh giá chưa có dữ liệu, cùng ba tour liên quan với hai nút chuyển. Tour không có lịch đang mở sẽ hiện thông báo và không có giá giả định.
- Giao diện hiển thị **chính sách ưu đãi nhóm**: 5–9 khách giảm 5%, từ 10 khách giảm 8% cho một đơn cùng lịch khởi hành. Quy tắc đầy đủ nằm trong [AGENT.md](AGENT.md).
- **Đặt tour:** khách chọn lịch khởi hành, nhập thông tin liên hệ và từng người tham gia (người lớn/trẻ em, ngày sinh, ghi chú), xem tổng tiền tạm tính rồi gửi yêu cầu. Máy chủ kiểm tra lại chỗ còn, số khách và tính tiền; giá của lịch được **chụp lại vào đơn** nên đổi giá sau này không làm sai đơn cũ. Đơn ở trạng thái chờ xác nhận sau khi gửi.
- **Đơn đặt của tôi:** danh sách đơn kèm trạng thái, xem chi tiết người tham gia và thanh toán, tự hủy khi đơn còn chờ xác nhận.
- **Thanh toán mô phỏng:** chọn phương thức (chuyển khoản/thẻ/ví điện tử), hệ thống ghi nhận một giao dịch thành công kèm mã giao dịch. Mỗi đơn chỉ thanh toán một lần; đơn đã hủy hoặc đã hoàn thành không thanh toán được. Đây không phải giao dịch thật.
- **Quản lý đơn (quản trị):** `action=orders` cho admin và nhân viên — thống kê nhanh, lọc theo trạng thái, tìm theo mã đơn/tên/email/điện thoại, phân trang, xem chi tiết và chuyển trạng thái theo đúng luồng chờ xác nhận → đã xác nhận → hoàn thành, hoặc hủy.

**Chưa triển khai:** cổng thanh toán thật, hoàn tiền, đăng đánh giá, và gửi email xác nhận đơn. (API thời tiết theo từng ngày lịch trình đã hoàn thành tích hợp). Giá và lịch trình trong SQL là dữ liệu demo. Tạm tính trên trang chi tiết chỉ để tham khảo; tổng tiền chính thức do máy chủ tính lại khi tạo đơn.

## Cấu trúc mã

```text
QLDAPM_Nhom03/
├── app/
│   ├── controllers/
│   │   ├── HomeController.php  # Chuẩn bị tour nổi bật cho trang chủ
│   │   ├── TaiKhoanController.php  # Đăng ký, đăng nhập khách hàng
│   │   ├── DonDatController.php  # Đặt tour, đơn của tôi, thanh toán, hủy đơn
│   │   ├── AdminController.php   # Khu vực quản trị: tài khoản/khách hàng/đơn đặt
│   │   └── TourController.php      # Chuẩn bị danh sách và chi tiết tour
│   ├── models/
│   │   ├── tour.php                # Lớp TourModel truy vấn dữ liệu bằng PDO
│   │   ├── tai_khoan.php          # Lớp TaiKhoanModel
│   │   ├── don_dat.php             # Lớp DonDatModel: giá, chỗ còn, đơn, thanh toán, trạng thái
│   │   ├── admin.php           # Lớp AdminModel: số liệu tổng quan
│   │   ├── de_xuat_thong_minh.php  # Tệp dự kiến cho đề xuất thông minh, hiện trống
│   │   └── thoi_tiet.php           # Lớp ThoiTietModel (dịch vụ thời tiết) gọi Open-Meteo API & dự báo thời tiết theo lịch trình
│   └── views/
│       ├── home/index.php     # Trang chủ
│       ├── tai_khoan/             # layout.php, login.php, register.php
│       ├── don_dat/            # dat_tour.php, danh_sach.php, chi_tiet.php
│       ├── admin/              # index.php, tai_khoan.php, don_dat.php, profile.php, password.php
│       ├── tour/
│       │   ├── index.php           # Danh sách tour trong nước/quốc tế
│       │   └── detail.php        # Chi tiết một tour
│       ├── errors/database.php     # Thông báo khi không tải được dữ liệu
│       └── partials/
│           ├── header.php          # Navbar và biểu tượng dùng chung
│           └── footer.php          # Footer dùng chung
├── database/
│   └── ontrip.sql                  # Cấu trúc bảng và danh mục vùng/nhu cầu
├── config/
│   └── database.php                # Hàm lấy kết nối PDO dùng chung
├── public/
│   ├── index.php                   # Điểm vào, điều hướng sang Controller hoặc trang chủ
│   └── assets/
│       ├── css/style.css           # Toàn bộ CSS
│       ├── js/script.js            # Toàn bộ JavaScript
│       └── images/                 # Ảnh và placeholder cục bộ
├── .gitignore                      # Bỏ qua tệp riêng của máy, log và tệp tạm
├── AGENT.md                        # Hướng dẫn sửa mã và quy tắc nghiệp vụ
└── README.md
```

Luồng tour: **trình duyệt → `public/index.php` → TourController → TourModel → database → View**. Controller nhận tham số URL; Model truy vấn PDO; View hiển thị kết quả. Trang chủ đi qua HomeController để lấy tối đa bốn tour, ưu tiên tour được đánh dấu `noi_bat = 1`; nếu chưa đủ thì bổ sung tour đã công bố. Mã tour không tồn tại trả HTTP 404; lỗi database trả HTTP 503 với thông báo chung, chi tiết lỗi được ghi vào log PHP.

## Database `ontrip`

Kết nối PDO được chuẩn bị trong `config/database.php`. Model sẽ nạp tệp này bằng `require_once` và gọi `getDatabaseConnection()` để nhận đối tượng PDO. Mặc định kết nối tới `127.0.0.1:3306`, database `ontrip`, tài khoản `root`, mật khẩu trống của XAMPP. Có thể ghi đè bằng biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`; tệp không tự đọc `.env`. Bật MySQL trong XAMPP khi dùng kết nối này. HomeController và TourController sử dụng TourModel để đọc dữ liệu qua kết nối PDO.

Mở **phpMyAdmin → Import** và chọn [database/ontrip.sql](database/ontrip.sql). Tệp tạo database `ontrip`, mười một bảng có khóa ngoại và nhập sáu vùng cùng sáu nhu cầu. Tệp không xóa bảng hay dữ liệu khi nhập lại. Trên máy phát triển hiện tại, SQL đã được nhập vào MariaDB 10.4 để kiểm tra; khi clone trên máy khác vẫn cần nhập tệp này.

| Bảng | Vai trò |
| --- | --- |
| `khu_vuc_tour`, `diem_den` | Phân loại trong nước/quốc tế, vùng và điểm đến; lưu ảnh điểm đến. |
| `tour` | Tên, mã tour nghiệp vụ, giới thiệu, số ngày, ảnh đại diện và trạng thái. `id_tour` là khóa nội bộ, `ma_tour` để hiển thị/tra cứu. |
| `lich_trinh_ngay` | Nội dung từng ngày của một tour. |
| `nhu_cau`, `tour_nhu_cau` | Danh mục nhu cầu và quan hệ nhiều-nhiều với tour. |
| `lich_khoi_hanh` | Ngày đi, nơi xuất phát, giá người lớn/trẻ em, sức chứa, hạn nhận đặt và trạng thái theo **từng lịch**. |
| `tai_khoan` | Tài khoản khách hàng và quản trị/nhân viên; mật khẩu lưu bằng `password_hash()`. |
| `don_dat_tour` | Đơn đặt tour: mã đơn, tài khoản, lịch khởi hành, **giá chụp lại tại thời điểm đặt**, tổng gốc/giảm giá/thành tiền, số người lớn-trẻ em, thông tin liên hệ và `trang_thai` (`cho_xac_nhan`, `da_xac_nhan`, `hoan_thanh`, `da_huy`). |
| `chi_tiet_don_dat` | Danh sách người tham gia của một đơn: họ tên, loại khách, ngày sinh, ghi chú. |
| `thanh_toan` | Giao dịch **mô phỏng** của một đơn: phương thức, số tiền, mã giao dịch, trạng thái thành công/thất bại. |

Tên bảng/cột dùng tiếng Việt không dấu theo dạng `snake_case`, ví dụ `ngay_khoi_hanh`, `gia_nguoi_lon`. Mã loại/vùng/nhu cầu và giá trị trạng thái trong dữ liệu giữ dạng mã ngắn để sau này đối chiếu với URL và bộ lọc hiện có.

Tệp SQL có 48 tour demo: 24 tour trong nước và 24 tour quốc tế, mỗi khu vực 8 tour, kèm nhu cầu, lịch trình theo ngày và 3 lịch khởi hành cho mỗi tour. Giá, ngày đi, sức chứa và chương trình phục vụ học tập, chưa phải dữ liệu kinh doanh đã xác nhận. Chạy lại tệp chỉ bổ sung bản ghi còn thiếu, không cập nhật các bản ghi đã có. Danh sách, chi tiết và tour nổi bật trang chủ đọc database. Chỗ còn của một lịch = `so_cho` trừ tổng số khách của các đơn **chưa hủy**; phép tính này chạy trong SQL mỗi lần hiển thị nên không có cột đếm dễ lệch. Đơn đặt **không** xóa tour/lịch (khóa ngoại `RESTRICT`); xóa đơn sẽ xóa kèm người tham gia và thanh toán (`CASCADE`).

## Sửa giao diện và dữ liệu mẫu

| Muốn chỉnh | Mở tệp |
| --- | --- |
| Logo, navbar, menu Tour | `app/views/partials/header.php` |
| Footer | `app/views/partials/footer.php` |
| Banner, tin tức trang chủ | Các mảng `$banners`, `$articles` ở đầu `app/views/home/index.php` |
| Điểm đến, nhu cầu và điều kiện tìm kiếm | `TourModel`, `HomeController`, `TourController`; dữ liệu ở các bảng liên quan trong database |
| Tour nổi bật trang chủ | `TourModel::getFeaturedTours()` và cột `tour.noi_bat` trong database |
| Danh sách, bộ lọc và thẻ tour | `app/views/tour/index.php` |
| Dữ liệu tour, giá, nhu cầu và lịch khởi hành | Các bảng trong database `ontrip`; `database/ontrip.sql` là dữ liệu cài đặt ban đầu |
| Nội dung trang chi tiết | `app/views/tour/detail.php` |
| Form đặt tour, danh sách đơn, chi tiết đơn | `app/views/don_dat/` |
| Quản lý đơn trong quản trị | `app/views/admin/don_dat.php` |
| Màu sắc, kích thước và bố cục | `public/assets/css/style.css`, chia bằng chú thích theo phần dùng chung và từng trang |
| Banner, menu, bộ lọc, phân trang, tour liên quan | `public/assets/js/script.js`, chia bằng chú thích theo từng trang |
| Điều hướng URL sang View | `public/index.php` |

Để đổi ảnh, đặt tệp trong `public/assets/images/` rồi sửa giá trị `image` của mục tương ứng, ví dụ `'image' => 'assets/images/ten-anh.jpg'`. Đường dẫn tính từ `public/`. Những mục chưa có ảnh sử dụng `placeholder.svg`. Ảnh nguồn nên có độ phân giải đủ lớn cho khung hiển thị; CSS giữ tỷ lệ và cắt phần dư bằng `object-fit: cover`.

## Hướng phát triển tiếp theo

Triển khai cổng thanh toán thật và hoàn tiền khi hủy đơn đã thanh toán, gửi email xác nhận đơn, đăng đánh giá sau khi hoàn thành; cập nhật nội dung tour thực tế. Mọi thay đổi giá/ưu đãi phải tính lại ở máy chủ theo [AGENT.md](AGENT.md) và giữ nguyên giá đã chụp trong đơn cũ. Các trang và chức năng khác được bổ sung theo tiến độ dự án, không tạo sẵn module chưa sử dụng.

Giao diện có tham khảo các trang du lịch công khai và hình mẫu trong quá trình thiết kế; nhận diện và bố cục OnTrip được xây dựng riêng.
