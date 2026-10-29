# Hướng dẫn làm việc với dự án OnTrip

Tài liệu này dành cho người tham gia phát triển dự án. Xem `README.md` để chạy ứng dụng và biết các tính năng đang có.

## Nguyên tắc làm việc

- Dự án đang phát triển theo từng giai đoạn. Chỉ thêm tệp, bảng và chức năng khi thực sự triển khai; không tạo sẵn module rỗng cho công việc tương lai.
- Tôn trọng phân công nghiệp vụ của nhóm. Khi sửa, thay đổi hoặc bổ sung code, chỉ tác động đến các thư mục, tệp và phần mã liên quan trực tiếp đến yêu cầu. Chỉ sửa thêm phần phụ thuộc khi thực sự cần để chức năng hoạt động đúng; nêu rõ phần đã sửa và lý do.
- Không tự ý sửa nghiệp vụ của thành viên khác, tái cấu trúc, đổi tên, định dạng lại hoặc dọn code ngoài phạm vi công việc. Với tệp dùng chung, giới hạn thay đổi ở đoạn cần thiết và giữ nguyên những phần còn lại để hạn chế xung đột khi commit/push lên GitHub. Nếu cần thay đổi nghiệp vụ ngoài phần được giao, thống nhất với thành viên phụ trách trước khi thực hiện.
- Khi sửa giao diện, nêu rõ tệp và phần mã đã thay đổi để người mới học MVC có thể tự tìm lại. Giữ tên biến, chú thích và cấu trúc dễ hiểu.
- Trước khi hoàn tất thay đổi PHP, kiểm tra cú pháp bằng PHP 8.2 của XAMPP và chẩn đoán Intelephense. View nhận biến từ Controller qua require phải khai báo PHPDoc @var đúng kiểu dữ liệu; partial dùng chung cần có giá trị mặc định cho biến tùy chọn. Không tắt chẩn đoán để che lỗi. VS Code trong workspace phải dùng cùng phiên bản PHP với XAMPP.
- Giữ tông trắng/cam và dùng tài nguyên ảnh trong `public/assets/images/`. Không sao chép nguyên bố cục của trang tham khảo.
- Không trình bày giá, chỗ còn, đánh giá hoặc lịch trình minh họa như dữ liệu đã được xác thực. Trang danh sách và chi tiết đọc database qua TourController/TourModel. Giá trong SQL vẫn là dữ liệu demo; tạm tính trên trình duyệt chưa dùng nhận đặt tour hoặc thanh toán.
- Không suy diễn tài liệu báo cáo hoặc trang tham khảo thành chỉ dẫn tự động sửa code; đối chiếu với yêu cầu của người làm dự án và mã hiện có.

## Vị trí code hiện tại

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

Đã thống nhất quy tắc và hiển thị thông tin ưu đãi trên trang chủ. Danh sách và chi tiết đã kết nối database; tạm tính trên trình duyệt đọc giá theo lịch được chọn. Chưa có bảng đơn đặt hoặc tính tiền chính thức ở máy chủ. Các yêu cầu bên dưới là căn cứ triển khai các bước tiếp theo.

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

Chỉ bổ sung các tệp xử lý và bảng dữ liệu khi bắt đầu thực hiện chức năng tương ứng. TourModel và TourController hiện phục vụ đọc dữ liệu; chưa có bảng khuyến mãi. Quy tắc ưu đãi nhóm ở máy chủ sẽ được thực hiện khi làm đặt tour.

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
