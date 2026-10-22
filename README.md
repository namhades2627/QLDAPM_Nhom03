# OnTrip

OnTrip là dự án website quản lý tour du lịch, xây dựng bằng PHP thuần theo hướng MVC để phát triển từng giai đoạn. Phiên bản hiện tại là **giao diện trình diễn** cho trang chủ, danh sách tour và chi tiết tour. Dữ liệu tour đang nằm trong các mảng PHP; dự án **chưa kết nối cơ sở dữ liệu**.

## Chạy trên máy cá nhân

Yêu cầu: XAMPP có Apache và PHP 8.2 trở lên. Không cần Composer, Node.js hay lệnh cài gói để chạy website.

1. Chép hoặc clone thư mục dự án vào `htdocs/QLDAPM_Nhom03` của XAMPP.
2. Khởi động **Apache** trong XAMPP.
3. Mở <http://localhost/QLDAPM_Nhom03/public/>.

Nếu đặt thư mục bằng tên khác, thay `QLDAPM_Nhom03` trong URL bằng tên thư mục đó. Truy cập qua Apache để PHP xử lý các View; mở trực tiếp tệp `.php` trong trình duyệt sẽ không chạy ứng dụng.

| Trang | URL mẫu |
| --- | --- |
| Trang chủ | <http://localhost/QLDAPM_Nhom03/public/> |
| Tour trong nước | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=domestic> |
| Tour quốc tế | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tours&type=international> |
| Chi tiết tour mẫu | <http://localhost/QLDAPM_Nhom03/public/index.php?page=tour-detail&type=domestic&id=domestic-1> |

Có thể chọn vùng từ menu **Tour** trên navbar. Ví dụ URL danh sách tour miền Bắc thêm `&region=north`. Trên trang danh sách, nhấn **Xem chi tiết** để mở đúng tour.

## Những gì đã có

- Trang chủ với navbar, ba banner tự chuyển mỗi 7 giây, thanh tìm tour, bốn tour nổi bật, điểm đến trong nước/quốc tế, tin tức và footer.
- Danh sách tour trong nước và quốc tế dùng chung một View. Mỗi loại có **24 tour mẫu**, hiển thị **12 tour/trang** theo lưới 3 cột × 4 hàng trên desktop; bộ lọc ở bên trái, phân trang ở giữa bên dưới danh sách.
- Bộ lọc mẫu theo điểm đến, nơi khởi hành, ngày, mức giá và nhu cầu; tìm theo tên/điểm đến có hỗ trợ không dấu, sắp xếp giá và thời lượng. Bộ lọc và phân trang hiện xử lý trên các thẻ đã tải bằng JavaScript.
- Trang chi tiết có một ảnh lớn, thông tin tour, lịch trình theo ngày, giá và lịch đi minh họa, khu vực đánh giá chưa có dữ liệu, cùng ba tour liên quan với hai nút chuyển.
- Giao diện hiển thị **chính sách ưu đãi nhóm**: 5–9 khách giảm 5%, từ 10 khách giảm 8% cho một đơn cùng lịch khởi hành. Quy tắc đầy đủ nằm trong [AGENT.md](AGENT.md).

**Chưa triển khai:** database, Model/Controller nghiệp vụ, đặt tour, tính giá/giảm giá thật, đăng nhập/đăng ký, đăng đánh giá, tìm tour từ trang chủ và API thời tiết. Một số nút chỉ minh họa giao diện. Các ngày khởi hành, giá, số lượng tour và lịch trình trong trang chi tiết là dữ liệu mẫu; không dùng để nhận đặt tour thực tế.

## Cấu trúc mã

```text
QLDAPM_Nhom03/
├── app/
│   ├── controllers/                 # Chưa có nghiệp vụ; giữ thư mục bằng .gitkeep
│   ├── models/                      # Chưa kết nối dữ liệu; giữ thư mục bằng .gitkeep
│   └── views/
│       ├── home/index.php          # Trang chủ và dữ liệu mẫu của trang chủ
│       ├── tours/
│       │   ├── index.php           # Danh sách tour trong nước/quốc tế
│       │   ├── detail.php          # Chi tiết một tour
│       │   └── sample-data.php     # Dữ liệu mẫu của danh sách và chi tiết
│       └── partials/
│           ├── header.php          # Navbar và biểu tượng dùng chung
│           └── footer.php          # Footer dùng chung
├── public/
│   ├── index.php                   # Điểm vào, chọn View theo URL
│   └── assets/
│       ├── css/style.css           # Toàn bộ CSS
│       ├── js/script.js            # Toàn bộ JavaScript
│       └── images/                 # Ảnh và placeholder cục bộ
├── .gitignore                      # Bỏ qua tệp riêng của máy, log và tệp tạm
├── AGENT.md                        # Hướng dẫn sửa mã và quy tắc nghiệp vụ
└── README.md
```

Luồng hiện tại: **trình duyệt → `public/index.php` → View → `header.php` + nội dung + `footer.php`**. `public/index.php` chỉ chọn trong các View cố định; chưa có Controller/Model xử lý dữ liệu. Khi triển khai database, Controller sẽ nhận yêu cầu, Model lấy dữ liệu, còn View hiển thị kết quả.

## Sửa giao diện và dữ liệu mẫu

| Muốn chỉnh | Mở tệp |
| --- | --- |
| Logo, navbar, menu Tour | `app/views/partials/header.php` |
| Footer | `app/views/partials/footer.php` |
| Banner, tour nổi bật, điểm đến, tin tức trang chủ | Các mảng `$banners`, `$tours`, `$destinationGroups`, `$articles` ở đầu `app/views/home/index.php` |
| Danh sách, bộ lọc và thẻ tour | `app/views/tours/index.php` |
| Ảnh, tên, giá, nhu cầu và lịch mẫu của tour | `app/views/tours/sample-data.php` |
| Nội dung trang chi tiết | `app/views/tours/detail.php` |
| Màu sắc, kích thước và bố cục | `public/assets/css/style.css`, chia bằng chú thích theo phần dùng chung và từng trang |
| Banner, menu, bộ lọc, phân trang, tour liên quan | `public/assets/js/script.js`, chia bằng chú thích theo từng trang |
| Điều hướng URL sang View | `public/index.php` |

Để đổi ảnh, đặt tệp trong `public/assets/images/` rồi sửa giá trị `image` của mục tương ứng, ví dụ `'image' => 'assets/images/ten-anh.jpg'`. Đường dẫn tính từ `public/`. Những mục chưa có ảnh sử dụng `placeholder.svg`. Ảnh nguồn nên có độ phân giải đủ lớn cho khung hiển thị; CSS giữ tỷ lệ và cắt phần dư bằng `object-fit: cover`.

## Hướng phát triển tiếp theo

Hoàn thiện dữ liệu/lịch trình của trang chi tiết, sau đó thiết kế cơ sở dữ liệu và đưa dữ liệu tour, lịch khởi hành, nhu cầu, giá và chỗ còn vào Model/Controller. Khi có luồng đặt tour, áp dụng quy tắc ưu đãi nhóm trong [AGENT.md](AGENT.md) ở phía máy chủ. Các trang và chức năng khác được bổ sung theo tiến độ dự án, không tạo sẵn module chưa sử dụng.

Giao diện có tham khảo các trang du lịch công khai và hình mẫu trong quá trình thiết kế; nhận diện và bố cục OnTrip được xây dựng riêng.
