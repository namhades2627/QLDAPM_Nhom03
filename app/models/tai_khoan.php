<?php

/** Truy vấn tài khoản; không nhận trực tiếp dữ liệu từ form. */
class TaiKhoanModel
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE id_tai_khoan = ? LIMIT 1');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function updateStaffProfile(int $id, string $name, string $email, string $phone, ?string $avatar = null): void
    {
        // Chỉ cập nhật thông tin liên hệ, không đổi vai trò, trạng thái hay mật khẩu.
        $query = $this->connection->prepare(
            "UPDATE tai_khoan SET ho_ten = ?, email = ?, so_dien_thoai = ?, hinh_anh = COALESCE(?, hinh_anh)
             WHERE id_tai_khoan = ? AND dang_hoat_dong = 1 AND vai_tro IN ('admin', 'nhan_vien')"
        );
        $query->execute([$name, $email, $phone, $avatar, $id]);
    }

    public function updateStaffPassword(int $id, string $oldHash, string $newHash): bool
    {
        $query = $this->connection->prepare(
            "UPDATE tai_khoan SET mat_khau = ? WHERE id_tai_khoan = ? AND mat_khau = ?
             AND dang_hoat_dong = 1 AND vai_tro IN ('admin', 'nhan_vien')"
        );
        $query->execute([$newHash, $id, $oldHash]);
        return $query->rowCount() === 1;
    }

    public function createCustomer(string $name, string $email, string $phone, string $passwordHash): void
    {
        // Vai trò và trạng thái do máy chủ quyết định, không lấy từ form đăng ký.
        $query = $this->connection->prepare(
            "INSERT INTO tai_khoan (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, dang_hoat_dong)
             VALUES (?, ?, ?, ?, 'khach_hang', 1)"
        );
        $query->execute([$name, $email, $phone, $passwordHash]);
    }
}
