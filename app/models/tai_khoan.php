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
