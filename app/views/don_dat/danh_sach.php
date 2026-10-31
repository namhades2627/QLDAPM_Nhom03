<?php
/**
 * DonDatController::orders() truyền danh sách đơn của tài khoản đang đăng nhập.
 * @var array<int, array<string, mixed>> $orders
 * @var array<string, string> $statusLabels
 * @var array<string, string>|null $flash
 */
$page = 'orders';
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' ₫';
$statusClasses = [
    'cho_xac_nhan' => 'is-pending',
    'da_xac_nhan' => 'is-confirmed',
    'hoan_thanh' => 'is-completed',
    'da_huy' => 'is-cancelled',
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Danh sách đơn đặt tour của bạn trên OnTrip.">
    <title>Đơn đặt của tôi | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><span aria-current="page">Đơn đặt của tôi</span></nav>
        <header class="booking-heading">
            <span class="detail-kicker">TÀI KHOẢN</span>
            <h1>Đơn đặt của tôi</h1>
            <p>Theo dõi trạng thái đơn, thanh toán mô phỏng và hủy đơn đang chờ xác nhận.</p>
        </header>

        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <div class="auth-message<?= !empty($flash['success']) ? ' is-preview' : '' ?>" role="status"><?= $escape($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (!$orders): ?>
        <div class="booking-empty">
            <h2>Bạn chưa có đơn đặt tour nào</h2>
            <p>Các tour đang mở nhận đặt sẵn sàng cho chuyến đi tiếp theo của bạn.</p>
            <a class="button button-primary" href="index.php?page=tours&amp;type=domestic">Khám phá tour</a>
        </div>
        <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
            <article class="order-card">
                <div class="order-card-head">
                    <div>
                        <span class="order-code">Mã đơn <?= $escape($order['ma_don']) ?></span>
                        <h2><?= $escape($order['ten_tour']) ?></h2>
                        <p>Khởi hành <?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?> · <?= $escape($order['diem_khoi_hanh']) ?></p>
                    </div>
                    <span class="order-status <?= $statusClasses[$order['trang_thai']] ?? '' ?>"><?= $escape($order['trang_thai_nhan']) ?></span>
                </div>
                <dl class="order-card-facts">
                    <div><dt>Số khách</dt><dd><?= (int) $order['so_nguoi_lon'] ?> người lớn<?= (int) $order['so_tre_em'] > 0 ? ', ' . (int) $order['so_tre_em'] . ' trẻ em' : '' ?></dd></div>
                    <div><dt>Tổng thanh toán</dt><dd><strong><?= $money($order['tong_thanh_toan']) ?></strong></dd></div>
                    <div><dt>Thanh toán</dt><dd><?= $order['da_thanh_toan'] ? 'Đã thanh toán (mô phỏng)' : 'Chưa thanh toán' ?></dd></div>
                    <div><dt>Ngày đặt</dt><dd><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></dd></div>
                </dl>
                <div class="order-card-actions">
                    <a class="button booking-secondary-button" href="index.php?page=orders&amp;id=<?= $escape(rawurlencode($order['ma_don'])) ?>">Xem chi tiết</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
