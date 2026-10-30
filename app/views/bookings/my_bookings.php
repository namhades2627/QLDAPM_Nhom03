<?php
/**
 * Danh sách đơn của khách. BookingController::myBookings() truyền dữ liệu qua require.
 * @var string $page
 * @var array<int, array<string, mixed>> $orders
 * @var string $message
 */
require_once __DIR__ . '/../partials/booking-format.php';
$page = 'my-bookings';
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Danh sách đơn đặt tour của bạn tại OnTrip.">
    <title>Đơn của tôi | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <header class="booking-heading">
            <span class="detail-section-label">TÀI KHOẢN / ĐƠN ĐẶT TOUR</span>
            <h1>Đơn của tôi</h1>
            <p>Theo dõi trạng thái đơn, xem lại lịch khởi hành và tự hủy khi còn trong thời gian cho phép.</p>
        </header>

        <?php if ($message !== ''): ?>
        <div class="booking-alert" role="status"><?= $escape($message) ?></div>
        <?php endif; ?>

        <?php if (!$orders): ?>
        <div class="booking-empty">
            <p>Bạn chưa có đơn đặt tour nào.</p>
            <a class="button button-primary" href="index.php?page=tours&amp;type=domestic">Tìm tour ngay</a>
        </div>
        <?php else: ?>
        <table class="booking-table">
            <caption class="visually-hidden">Danh sách đơn đặt tour</caption>
            <thead>
                <tr>
                    <th scope="col">Mã đơn</th>
                    <th scope="col">Tour</th>
                    <th scope="col">Khởi hành</th>
                    <th scope="col">Số khách</th>
                    <th scope="col">Tổng tiền</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <?php
                $canCancel = in_array($order['trang_thai'], ScheduleModel::HOLDING_STATUSES, true)
                    && $order['ngay_khoi_hanh'] > $today;
                ?>
                <tr>
                    <td data-label="Mã đơn"><strong><?= $escape($order['ma_don']) ?></strong><small><?= booking_datetime($order['tao_luc']) ?></small></td>
                    <td data-label="Tour"><strong><?= $escape($order['ten_tour']) ?></strong><small><?= $escape($order['ma_tour']) ?> · <?= $escape($order['diem_khoi_hanh']) ?></small></td>
                    <td data-label="Khởi hành"><?= booking_date($order['ngay_khoi_hanh']) ?><small>về <?= booking_date($order['ngay_ve']) ?></small></td>
                    <td data-label="Số khách"><?= (int) $order['so_nguoi_lon'] ?> NL<small><?= (int) $order['so_tre_em'] ?> TE</small></td>
                    <td data-label="Tổng tiền"><?= booking_money($order['tong_thanh_toan']) ?><?php if ((int) $order['tien_giam'] > 0): ?><small>giảm <?= (int) $order['muc_giam'] ?>%</small><?php endif; ?></td>
                    <td data-label="Trạng thái"><span class="booking-status <?= booking_status_class($order['trang_thai']) ?>"><?= booking_status_label($order['trang_thai']) ?></span></td>
                    <td data-label="Thao tác">
                        <?php if ($canCancel): ?>
                        <form class="booking-cancel" method="post" action="index.php?page=cancel-booking">
                            <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="id_don_dat" value="<?= (int) $order['id_don_dat'] ?>">
                            <button class="booking-cancel-button" type="submit" data-confirm="Hủy đơn <?= $escape($order['ma_don']) ?>? Chỗ sẽ được trả lại cho chuyến đi.">Hủy đơn</button>
                        </form>
                        <?php else: ?>
                        <span class="booking-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
