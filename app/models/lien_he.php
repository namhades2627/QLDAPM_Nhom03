<?php

/**
 * Model truy vấn thông tin liên hệ và danh sách hướng dẫn viên (nhân viên) OnTrip.
 * Dữ liệu nhân sự được đồng bộ trực tiếp từ bảng `tai_khoan`.
 */
class LienHeModel
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Lấy danh sách 4 hướng dẫn viên là nhân viên của OnTrip.
     * Ảnh đại diện liên kết động với cột `hinh_anh` trong bảng `tai_khoan`
     * (được tải lên hoặc cập nhật trong phần Quản lý tài khoản nhân viên của admin).
     *
     * @return array Danh sách 4 hướng dẫn viên
     */
    public function getFeaturedGuides(): array
    {
        $query = $this->connection->prepare(
            "SELECT id_tai_khoan, ho_ten, email, so_dien_thoai, hinh_anh, vai_tro
             FROM tai_khoan
             WHERE email IN ('nam@gmail.com', 'khoa@gmail.com', 'quan@gmail.com', 'khanh@gmail.com')
               AND vai_tro = 'nhan_vien'
               AND dang_hoat_dong = 1"
        );
        $query->execute();
        $accounts = $query->fetchAll();

        // Tạo bảng ánh xạ theo email
        $map = [];
        foreach ($accounts as $account) {
            $map[$account['email']] = $account;
        }

        // Cấu hình chuyên môn và phong cách riêng biệt của từng hướng dẫn viên
        $guidesConfig = [
            'nam@gmail.com' => [
                'name' => 'Tống Nhựt Nam',
                'avatar' => 'assets/images/users/user1.jpg',
                'role' => 'Trưởng nhóm Hướng dẫn viên · Tuyến Tây Bắc & Đông Bắc',
                'badge' => 'Hướng dẫn viên',
                'experience' => '8 năm kinh nghiệm',
                'specialty' => 'Cung đường Tây Bắc, Hà Giang, Sapa & Trekking khám phá văn hóa bản địa',
                'quote' => 'Mỗi cung đường không chỉ là điểm đến, mà là một câu chuyện văn hóa đáng nhớ.',
                'color' => '#c45226',
            ],
            'khoa@gmail.com' => [
                'name' => 'Trần Đăng Khoa',
                'avatar' => 'assets/images/users/user4.jpg',
                'role' => 'Điều phối viên Tour Quốc tế · Tuyến Châu Á & Đông Nam Á',
                'badge' => 'Hướng dẫn viên',
                'experience' => '6 năm kinh nghiệm',
                'specialty' => 'Tour Thái Lan, Singapore, Hàn Quốc, Nhật Bản & hỗ trợ thủ tục xuất nhập cảnh',
                'quote' => 'Chuyến đi an tâm và trọn vẹn bắt đầu từ sự chuẩn bị chu đáo và tận tâm nhất.',
                'color' => '#1d4ed8',
            ],
            'quan@gmail.com' => [
                'name' => 'Tạ Hồng Quân',
                'avatar' => 'assets/images/users/user2.jpg',
                'role' => 'Hướng dẫn viên Di sản · Tuyến Miền Trung & Tây Nguyên',
                'badge' => 'Hướng dẫn viên',
                'experience' => '7 năm kinh nghiệm',
                'specialty' => 'Con đường di sản Huế – Hội An – Đà Nẵng, khảo cứu lịch sử & ẩm thực vùng miền',
                'quote' => 'Truyền cảm hứng qua từng di tích, đưa bạn sống lại cùng dòng chảy thời gian.',
                'color' => '#0f766e',
            ],
            'khanh@gmail.com' => [
                'name' => 'Nguyễn Quang Khanh',
                'avatar' => 'assets/images/users/user3.webp',
                'role' => 'Chuyên viên Tư vấn Lịch trình & Chăm sóc Khách hàng',
                'badge' => 'Hướng dẫn viên',
                'experience' => '5 năm kinh nghiệm',
                'specialty' => 'Thiết kế tour gia đình, tour đoàn theo yêu cầu & tư vấn tối ưu chi phí',
                'quote' => 'Sự hài lòng và nụ cười của bạn là kim chỉ nam cho mọi hành trình OnTrip.',
                'color' => '#7c3aed',
            ],
        ];

        $guides = [];
        foreach ($guidesConfig as $email => $cfg) {
            $account = $map[$email] ?? null;
            $avatarUrl = '';

            // Ưu tiên ảnh đại diện đã tải lên trong bảng tai_khoan nếu file tồn tại
            if ($account && !empty($account['hinh_anh'])) {
                $avatarPath = (string) $account['hinh_anh'];
                if (preg_match('~^assets/images/users/[a-zA-Z0-9_-]+\.(jpg|png|gif|webp)$~D', $avatarPath) === 1) {
                    $physicalFile = __DIR__ . '/../../public/' . $avatarPath;
                    if (is_file($physicalFile)) {
                        $avatarUrl = $avatarPath;
                    }
                }
            }

            // Nếu tài khoản chưa có ảnh riêng, dùng ảnh mặc định tương ứng trong public/assets/images/users/
            if ($avatarUrl === '' && !empty($cfg['avatar']) && is_file(__DIR__ . '/../../public/' . $cfg['avatar'])) {
                $avatarUrl = $cfg['avatar'];
            }

            $guides[] = [
                'id_tai_khoan' => $account ? (int) $account['id_tai_khoan'] : 0,
                'ho_ten' => $account['ho_ten'] ?? $cfg['name'],
                'email' => $account['email'] ?? $email,
                'so_dien_thoai' => $account['so_dien_thoai'] ?? '',
                'avatar_url' => $avatarUrl,
                'role_title' => $cfg['role'],
                'badge' => $cfg['badge'],
                'experience' => $cfg['experience'],
                'specialty' => $cfg['specialty'],
                'quote' => $cfg['quote'],
                'accent_color' => $cfg['color'],
            ];
        }

        return $guides;
    }
}
