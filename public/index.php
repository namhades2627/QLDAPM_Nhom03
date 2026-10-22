<?php
// Điều hướng các giao diện đã có; chưa xử lý nghiệp vụ hoặc kết nối database.
// Dùng tên View cố định, không lấy đường dẫn tệp trực tiếp từ tham số URL.
$requestedPage = $_GET['page'] ?? '';
$page = in_array($requestedPage, ['tours', 'tour-detail'], true) ? $requestedPage : 'home';
$view = $page === 'tour-detail' ? 'tours/detail.php' : $page . '/index.php';
require __DIR__ . '/../app/views/' . $view;
