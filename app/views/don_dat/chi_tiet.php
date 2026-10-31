<?php
/**
 * DonDatController::orders() truyền một đơn kèm danh sách người tham gia.
 * @var array<string, mixed>|null $order
 * @var array<string, string> $statusLabels
 * @var array<string, string> $paymentMethods
 * @var array<string, string>|null $flash
 * @var string $csrfToken
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
$statusNotes = [
    'cho_xac_nhan' => 'OnTrip đã nhận yêu cầu và đang kiểm tra chỗ. Bạn có thể hủy đơn trong lúc chờ xác nhận.',
    'da_xac_nhan' => 'Đơn đã được xác nhận. Vui lòng thanh toán để hoàn tất giữ chỗ.',
    'hoan_thanh' => 'Chuyến đi đã hoàn thành. Cảm ơn bạn đã đồng hành cùng OnTrip.',
    'da_huy' => 'Đơn đã hủy nên không thể thanh toán hay khôi phục.',
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Chi tiết đơn đặt tour trên OnTrip.">
    <title>Chi tiết đơn đặt | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><a href="index.php?page=orders">Đơn đặt của tôi</a><span>/</span><span aria-current="page">Chi tiết đơn</span></nav>

        <?php if ($order === null): ?>
        <div class="booking-empty">
            <h1>Không tìm thấy đơn đặt</h1>
            <p>Đơn không tồn tại hoặc không thuộc tài khoản của bạn.</p>
            <a class="button button-primary" href="index.php?page=orders">Về danh sách đơn</a>
        </div>
        <?php else: ?>
        <?php
            $status = $order['trang_thai'];
            $paid = $order['da_thanh_toan'];
            $canPay = !$paid && in_array($status, ['cho_xac_nhan', 'da_xac_nhan'], true);
            $canCancel = $status === 'cho_xac_nhan';
        ?>
        <header class="booking-heading order-detail-heading">
            <span class="detail-kicker">MÃ ĐƠN <?= $escape($order['ma_don']) ?></span>
            <h1><?= $escape($order['ten_tour']) ?></h1>
            <p><span class="order-status <?= $statusClasses[$status] ?? '' ?>"><?= $escape($order['trang_thai_nhan']) ?></span> <?= $escape($statusNotes[$status] ?? '') ?></p>
        </header>

        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <div class="auth-message<?= !empty($flash['success']) ? ' is-preview' : '' ?>" role="status"><?= $escape($flash['message']) ?></div>
        <?php endif; ?>

        <div class="order-detail-layout">
            <div class="order-detail-main">
                <section class="booking-card">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">1</span><div><h2>Thông tin chuyến đi</h2><p>Đơn giá được lưu tại thời điểm đặt nên không đổi khi OnTrip cập nhật giá tour.</p></div></div>
                    <dl class="order-facts">
                        <div><dt>Mã tour</dt><dd><?= $escape($order['ma_tour']) ?></dd></div>
                        <div><dt>Khởi hành</dt><dd><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></dd></div>
                        <div><dt>Điểm khởi hành</dt><dd><?= $escape($order['diem_khoi_hanh']) ?></dd></div>
                        <div><dt>Ngày đặt</dt><dd><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></dd></div>
                        <div><dt>Số khách</dt><dd><?= (int) $order['so_nguoi_lon'] ?> người lớn, <?= (int) $order['so_tre_em'] ?> trẻ em</dd></div>
                        <div><dt>Đơn giá người lớn</dt><dd><?= $money($order['gia_nguoi_lon']) ?></dd></div>
                        <div><dt>Đơn giá trẻ em</dt><dd><?= $money($order['gia_tre_em']) ?></dd></div>
                        <div><dt>Thanh toán</dt><dd><?= $paid ? 'Đã thanh toán (mô phỏng)' : 'Chưa thanh toán' ?></dd></div>
                    </dl>
                </section>

                <section class="booking-card">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">2</span><div><h2>Người tham gia</h2><p>Danh sách khách đi cùng đơn đặt này.</p></div></div>
                    <ul class="order-participants">
                        <?php foreach ($order['participants'] as $participant): ?>
                        <li>
                            <span class="order-participant-type"><?= $participant['loai_khach'] === 'tre_em' ? 'Trẻ em' : 'Người lớn' ?></span>
                            <strong><?= $escape($participant['ho_ten']) ?></strong>
                            <span><?= $participant['ngay_sinh'] ? 'Sinh ' . date('d/m/Y', strtotime($participant['ngay_sinh'])) : 'Chưa cung cấp ngày sinh' ?></span>
                            <?php if ($participant['ghi_chu'] !== ''): ?><em><?= $escape($participant['ghi_chu']) ?></em><?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="booking-card">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">3</span><div><h2>Thông tin liên hệ</h2><p>OnTrip liên hệ theo thông tin bạn đã cung cấp.</p></div></div>
                    <dl class="order-facts">
                        <div><dt>Họ và tên</dt><dd><?= $escape($order['ho_ten']) ?></dd></div>
                        <div><dt>Email</dt><dd><?= $escape($order['email']) ?></dd></div>
                        <div><dt>Số điện thoại</dt><dd><?= $escape($order['so_dien_thoai']) ?></dd></div>
                        <div><dt>Địa chỉ</dt><dd><?= $order['dia_chi'] !== '' ? $escape($order['dia_chi']) : '—' ?></dd></div>
                    </dl>
                    <?php if ($order['ghi_chu'] !== ''): ?>
                    <p class="order-note"><strong>Ghi chú:</strong> <?= $escape($order['ghi_chu']) ?></p>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="booking-sidebar" aria-label="Tổng kết đơn đặt">
                <div class="booking-summary">
                    <span class="detail-booking-label">TỔNG KẾT ĐƠN ĐẶT</span>
                    <div class="booking-summary-row"><span>Người lớn × <?= (int) $order['so_nguoi_lon'] ?></span><strong><?= $money($order['so_nguoi_lon'] * $order['gia_nguoi_lon']) ?></strong></div>
                    <div class="booking-summary-row"><span>Trẻ em × <?= (int) $order['so_tre_em'] ?></span><strong><?= $money($order['so_tre_em'] * $order['gia_tre_em']) ?></strong></div>
                    <div class="booking-summary-row booking-summary-subtotal"><span>Tổng trước ưu đãi</span><strong><?= $money($order['tong_goc']) ?></strong></div>
                    <?php if ((int) $order['muc_giam'] > 0): ?>
                    <div class="booking-summary-row"><span>Ưu đãi nhóm <?= (int) $order['muc_giam'] ?>%</span><strong>−<?= $money($order['tien_giam']) ?></strong></div>
                    <?php endif; ?>
                    <div class="booking-summary-total"><span>Tổng thanh toán</span><strong><?= $money($order['tong_thanh_toan']) ?></strong></div>

                    <?php if ($paid): ?>
                    <div class="order-payment-done">
                        <strong>Đã thanh toán mô phỏng</strong>
                        <span>Phương thức: <?= $escape($paymentMethods[$order['phuong_thuc']] ?? $order['phuong_thuc']) ?></span>
                        <span>Mã giao dịch: <?= $escape($order['ma_giao_dich']) ?></span>
                        <span>Số tiền: <?= $money($order['so_tien_da_tra']) ?></span>
                        <?php if (!empty($order['thanh_toan_luc'])): ?><span>Thời điểm: <?= date('d/m/Y H:i', strtotime($order['thanh_toan_luc'])) ?></span><?php endif; ?>
                    </div>
                    <?php elseif ($canPay): ?>
                    <form class="order-payment-form" method="post" action="index.php?page=orders">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="ma_don" value="<?= $escape($order['ma_don']) ?>">
                        <input type="hidden" name="operation" value="pay">
                        <label class="booking-field"><span>Phương thức thanh toán</span>
                            <select name="phuong_thuc" required>
                                <?php foreach ($paymentMethods as $value => $label): ?>
                                <option value="<?= $escape($value) ?>"><?= $escape($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <p class="booking-summary-note">Thanh toán trong bản demo chỉ là mô phỏng, không phát sinh giao dịch thật.</p>
                        <button class="button button-primary booking-submit" type="submit">Xác nhận thanh toán</button>
                    </form>
                    <?php endif; ?>

                    <?php if ($canCancel): ?>
                    <form class="order-cancel-form" method="post" action="index.php?page=orders" data-confirm="Hủy đơn đặt tour này?">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="ma_don" value="<?= $escape($order['ma_don']) ?>">
                        <input type="hidden" name="operation" value="cancel">
                        <button class="booking-cancel" type="submit">Hủy đơn</button>
                    </form>
                    <?php endif; ?>

                    <a class="booking-back-link" href="index.php?page=orders">← Về danh sách đơn</a>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
