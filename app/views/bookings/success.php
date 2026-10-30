<?php
/**
 * Trang xác nhận đơn đã tạo. BookingController::success() truyền $order qua require.
 * @var string $page
 * @var array<string, mixed>|null $order
 */
require_once __DIR__ . '/../partials/booking-format.php';
$page = 'booking-success';
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Kết quả đặt tour tại OnTrip.">
    <title>Đặt tour thành công | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container booking-result">
        <?php if ($order === null): ?>
            <div class="booking-result-card">
                <span class="booking-result-icon" aria-hidden="true">!</span>
                <h1>Không tìm thấy đơn</h1>
                <p>Mã đơn không tồn tại hoặc không thuộc tài khoản đang đăng nhập.</p>
                <a class="button button-primary" href="index.php?page=my-bookings">Xem đơn của tôi</a>
            </div>
        <?php else: ?>
            <div class="booking-result-card">
                <span class="booking-result-icon" aria-hidden="true">✓</span>
                <span class="detail-section-label">ĐẶT TOUR THÀNH CÔNG</span>
                <h1>Cảm ơn bạn, <?= $escape($order['ho_ten']) ?>!</h1>
                <p>Mã đơn <strong><?= $escape($order['ma_don']) ?></strong> đã được ghi nhận và giữ
                    <?= (int) $order['so_nguoi_lon'] + (int) $order['so_tre_em'] ?> chỗ cho chuyến đi.
                    Nhân viên OnTrip sẽ liên hệ qua số <?= $escape($order['so_dien_thoai']) ?> để xác nhận.</p>
                <span class="booking-status <?= booking_status_class($order['trang_thai']) ?>"><?= booking_status_label($order['trang_thai']) ?></span>
                <dl class="booking-facts">
                    <div><dt>Tour</dt><dd><?= $escape($order['ten_tour']) ?> (<?= $escape($order['ma_tour']) ?>)</dd></div>
                    <div><dt>Ngày đi</dt><dd><?= booking_date($order['ngay_khoi_hanh']) ?></dd></div>
                    <div><dt>Ngày về</dt><dd><?= booking_date($order['ngay_ve']) ?></dd></div>
                    <div><dt>Khởi hành từ</dt><dd><?= $escape($order['diem_khoi_hanh']) ?></dd></div>
                    <div><dt>Số khách</dt><dd><?= (int) $order['so_nguoi_lon'] ?> người lớn, <?= (int) $order['so_tre_em'] ?> trẻ em</dd></div>
                    <div><dt>Tổng thanh toán</dt><dd><strong><?= booking_money($order['tong_thanh_toan']) ?></strong></dd></div>
                </dl>
                <?php if ((int) $order['tien_giam'] > 0): ?>
                <p class="booking-note">Đã áp dụng ưu đãi nhóm <?= (int) $order['muc_giam'] ?>%: giảm <?= booking_money($order['tien_giam']) ?> trên tổng <?= booking_money($order['tong_goc']) ?>.</p>
                <?php endif; ?>
                <div class="booking-result-actions">
                    <a class="button button-primary" href="index.php?page=my-bookings">Xem đơn của tôi</a>
                    <a class="button" href="index.php?page=tours&amp;type=domestic">Tiếp tục tìm tour</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
