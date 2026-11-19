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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=20261113-booking">
    <script src="assets/js/script.js?v=20261113-booking" defer></script>
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

            // Trạng thái cho 4 bước trong Progress Stepper:
            // 1. Đã gửi yêu cầu (Hoàn thành - Xanh lá)
            // 2. Chờ xác nhận chỗ (Đang xử lý - Cam nhấp nháy khi cho_xac_nhan)
            // 3. Thanh toán (Chưa thực hiện - Xám)
            // 4. Hoàn tất & Nhận vé (Chưa thực hiện - Xám)
            $step1Class = 'is-completed';
            $step2Class = 'is-upcoming';
            $step3Class = 'is-upcoming';
            $step4Class = 'is-upcoming';

            $step2Desc = 'Đang kiểm tra tình trạng chỗ';
            $step3Desc = 'Chưa thực hiện';
            $step4Desc = 'Chưa thực hiện';

            if ($status === 'cho_xac_nhan') {
                $step2Class = 'is-active';
                $step2Desc = 'Đang kiểm tra chỗ trống';
            } elseif ($status === 'da_xac_nhan') {
                $step2Class = 'is-completed';
                $step2Desc = 'Chỗ đã được giữ';
                if ($paid) {
                    $step3Class = 'is-completed';
                    $step3Desc = 'Đã thanh toán';
                    $step4Class = 'is-active';
                    $step4Desc = 'Sẵn sàng khởi hành';
                } else {
                    $step3Class = 'is-active';
                    $step3Desc = 'Chờ bạn thanh toán';
                }
            } elseif ($status === 'hoan_thanh') {
                $step2Class = 'is-completed';
                $step2Desc = 'Chỗ đã được giữ';
                $step3Class = 'is-completed';
                $step3Desc = 'Đã thanh toán';
                $step4Class = 'is-completed';
                $step4Desc = 'Hoàn tất chuyến đi';
            } elseif ($status === 'da_huy') {
                $step2Class = 'is-cancelled';
                $step2Desc = 'Đã hủy đơn';
                $step3Desc = 'Không áp dụng';
                $step4Desc = 'Không áp dụng';
            }
        ?>

        <header class="order-detail-header">
            <!-- 1. Tiêu đề chính & Badge trạng thái màu cam nổi bật -->
            <div class="order-header-main">
                <div class="order-header-title-wrap">
                    <h1 class="order-header-title"><?= $escape($order['ten_tour']) ?></h1>
                    <span class="order-status-badge status-<?= $escape($status) ?>">
                        <span class="status-dot-pulse" aria-hidden="true"></span>
                        <?= $escape($order['trang_thai_nhan']) ?>
                    </span>
                </div>

                <!-- 2. Mã đơn & Nút sao chép nhanh, Thời gian tạo đơn -->
                <div class="order-meta-row">
                    <div class="order-code-group">
                        <span class="order-meta-label">Mã đơn:</span>
                        <strong class="order-code-text" id="order-code-display"><?= $escape($order['ma_don']) ?></strong>
                        <button type="button" class="order-copy-btn" data-copy-target="#order-code-display" title="Sao chép mã đơn" aria-label="Sao chép mã đơn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span class="copy-label">Sao chép</span>
                        </button>
                    </div>
                    <span class="order-meta-sep" aria-hidden="true">&bull;</span>
                    <div class="order-created-time">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Thời gian tạo đơn: <strong><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></strong></span>
                    </div>
                </div>
            </div>

            <!-- 3. Thanh tiến trình đơn hàng (Order Progress Stepper - 4 bước) -->
            <div class="order-stepper-card" aria-label="Tiến trình xử lý đơn hàng">
                <ol class="order-stepper">
                    <!-- Bước 1: Đã gửi yêu cầu (Hoàn thành - Xanh lá) -->
                    <li class="order-step <?= $step1Class ?>">
                        <div class="order-step-indicator">
                            <span class="order-step-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </span>
                        </div>
                        <div class="order-step-content">
                            <span class="order-step-badge">Bước 1</span>
                            <strong class="order-step-title">Đã gửi yêu cầu</strong>
                            <span class="order-step-desc">Ghi nhận vào hệ thống</span>
                        </div>
                    </li>

                    <!-- Bước 2: Chờ xác nhận chỗ (Đang xử lý - Cam nhấp nháy khi cho_xac_nhan) -->
                    <li class="order-step <?= $step2Class ?>">
                        <div class="order-step-track" aria-hidden="true"></div>
                        <div class="order-step-indicator">
                            <span class="order-step-icon">
                                <?php if ($step2Class === 'is-completed'): ?>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php elseif ($step2Class === 'is-cancelled'): ?>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                <?php else: ?>
                                    <span class="stepper-dot-pulse" aria-hidden="true"></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="order-step-content">
                            <span class="order-step-badge">Bước 2</span>
                            <strong class="order-step-title">Chờ xác nhận chỗ</strong>
                            <span class="order-step-desc"><?= $step2Desc ?></span>
                        </div>
                    </li>

                    <!-- Bước 3: Thanh toán (Chưa thực hiện - Xám) -->
                    <li class="order-step <?= $step3Class ?>">
                        <div class="order-step-track" aria-hidden="true"></div>
                        <div class="order-step-indicator">
                            <span class="order-step-icon">
                                <?php if ($step3Class === 'is-completed'): ?>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php elseif ($step3Class === 'is-active'): ?>
                                    <span class="stepper-dot-pulse" aria-hidden="true"></span>
                                <?php else: ?>
                                    <span>3</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="order-step-content">
                            <span class="order-step-badge">Bước 3</span>
                            <strong class="order-step-title">Thanh toán</strong>
                            <span class="order-step-desc"><?= $step3Desc ?></span>
                        </div>
                    </li>

                    <!-- Bước 4: Hoàn tất & Nhận vé (Chưa thực hiện - Xám) -->
                    <li class="order-step <?= $step4Class ?>">
                        <div class="order-step-track" aria-hidden="true"></div>
                        <div class="order-step-indicator">
                            <span class="order-step-icon">
                                <?php if ($step4Class === 'is-completed'): ?>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php elseif ($step4Class === 'is-active'): ?>
                                    <span class="stepper-dot-pulse" aria-hidden="true"></span>
                                <?php else: ?>
                                    <span>4</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="order-step-content">
                            <span class="order-step-badge">Bước 4</span>
                            <strong class="order-step-title">Hoàn tất &amp; Nhận vé</strong>
                            <span class="order-step-desc"><?= $step4Desc ?></span>
                        </div>
                    </li>
                </ol>
            </div>

            <!-- 4. Banner thông báo hành động -->
            <div class="order-action-banner <?= $status === 'cho_xac_nhan' ? 'is-warning' : ($status === 'da_huy' ? 'is-muted' : 'is-info') ?>">
                <div class="banner-icon-col" aria-hidden="true">
                    <?php if ($status === 'cho_xac_nhan'): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php elseif ($status === 'da_huy'): ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                    <?php else: ?>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php endif; ?>
                </div>
                <div class="banner-body">
                    <strong class="banner-heading">
                        <?php if ($status === 'cho_xac_nhan'): ?>
                            Đang xử lý xác nhận chỗ
                        <?php elseif ($status === 'da_xac_nhan'): ?>
                            <?= $paid ? 'Đơn đã xác nhận &amp; hoàn tất thanh toán' : 'Chỗ đã được xác nhận! Vui lòng thanh toán' ?>
                        <?php elseif ($status === 'hoan_thanh'): ?>
                            Chuyến đi đã hoàn thành
                        <?php elseif ($status === 'da_huy'): ?>
                            Đơn đặt tour đã được hủy
                        <?php endif; ?>
                    </strong>
                    <p class="banner-text">
                        <?php if ($status === 'cho_xac_nhan'): ?>
                            OnTrip đang kiểm tra tình trạng chỗ. Bạn có thể chọn trước phương thức thanh toán hoặc hủy đơn miễn phí trong giai đoạn này.
                        <?php else: ?>
                            <?= $escape($statusNotes[$status] ?? '') ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </header>

        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
        <div class="auth-message<?= !empty($flash['success']) ? ' is-preview' : '' ?>" role="status"><?= $escape($flash['message']) ?></div>
        <?php endif; ?>

        <div class="order-detail-layout">
            <div class="order-detail-main">
                <section class="booking-card">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">1</span><div><h2>Thông tin chuyến đi</h2><p>Đơn giá được lưu tại thời điểm đặt nên không đổi khi OnTrip cập nhật giá tour.</p></div></div>
                    <dl class="order-facts order-facts-inline">
                        <div><dt>Mã tour</dt><dd><?= $escape($order['ma_tour']) ?></dd></div>
                        <div><dt>Khởi hành</dt><dd><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></dd></div>
                        <div><dt>Điểm khởi hành</dt><dd><?= $escape($order['diem_khoi_hanh']) ?></dd></div>
                        <div><dt>Ngày đặt</dt><dd><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></dd></div>
                    </dl>
                </section>

                <section class="booking-card">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">2</span><div><h2>Người tham gia</h2><p>Danh sách khách đi cùng đơn đặt này.</p></div></div>
                    <ul class="order-participants">
                        <?php foreach ($order['participants'] as $participant): ?>
                        <li>
                            <span class="order-participant-type"><?= $participant['loai_khach'] === 'tre_em' ? 'Trẻ em' : 'Người lớn' ?></span>
                            <strong><?= $escape($participant['ho_ten']) ?></strong>
                            <span><?= ($participant['gioi_tinh'] ?? 'nam') === 'nu' ? 'Nữ' : 'Nam' ?></span>
                            <span><?= $participant['ngay_sinh'] ? 'Sinh ' . date('d/m/Y', strtotime($participant['ngay_sinh'])) : 'Chưa cung cấp ngày sinh' ?></span>
                            <?php if (!empty($participant['cccd'])): ?><span>CCCD: <?= $escape($participant['cccd']) ?></span><?php endif; ?>
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

                    <?php if (in_array($order['trang_thai'], ['da_xac_nhan', 'hoan_thanh'], true)): ?>
                    <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--line);">
                        <a class="button button-primary" style="width: 100%; text-align: center; display: block;" href="index.php?page=tour-detail&amp;id=<?= $escape(rawurlencode($order['ma_tour'])) ?>#danh-gia">⭐ Đánh giá tour này</a>
                    </div>
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
