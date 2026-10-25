<?php

/**
 * Lấy kết nối PDO dùng chung cho các Model.
 * Mặc định dành cho XAMPP trên máy phát triển: root, mật khẩu trống.
 * Có thể ghi đè bằng biến môi trường DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.
 * Tệp này không tự đọc .env và không in nội dung ra trình duyệt.
 */
function getDatabaseConnection(): PDO
{
    // Tái sử dụng kết nối khi nhiều Model cùng gọi trong một lần chạy PHP.
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'ontrip';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS');
    $password = $password === false ? '' : $password;

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    // Nếu kết nối lỗi, PDO ném exception để nơi gọi xử lý thông báo phù hợp.
    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
