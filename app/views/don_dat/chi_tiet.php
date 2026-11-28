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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?>">
    <script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/script.js') ?>" defer></script>
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
            $currentMethod = !empty($order['phuong_thuc']) ? $order['phuong_thuc'] : (!empty($order['phuong_thuc_thanh_toan']) ? $order['phuong_thuc_thanh_toan'] : 'chuyen_khoan');
            if ($currentMethod === 'thanh_toan_sau' || $currentMethod === 'vi_dien_tu') {
                $currentMethod = 'chuyen_khoan';
            }

            $days = (int) ($order['so_ngay'] ?? 0);
            if ($days > 0) {
                $nights = max(1, $days - 1);
                $durationText = $days . ' Ngày ' . $nights . ' Đêm';
            } else {
                $durationText = '2 Ngày 1 Đêm';
            }
            $tourImage = !empty($order['duong_dan_anh']) ? $order['duong_dan_anh'] : 'assets/images/tours/mb01.jpg';

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
                $step3Class = $paid ? 'is-completed' : 'is-upcoming';
                $step3Desc = $paid ? 'Đã thanh toán' : 'Chưa ghi nhận trực tuyến';
                $step4Class = 'is-completed';
                $step4Desc = 'Hoàn tất chuyến đi';
            } elseif ($status === 'da_huy') {
                $step2Class = 'is-cancelled';
                $step2Desc = 'Đã hủy đơn';
                $step3Class = $paid ? 'is-completed' : 'is-upcoming';
                $step3Desc = $paid ? 'Đã thanh toán (cần đối soát)' : 'Không áp dụng';
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
                <!-- Card 1: Tóm tắt Tour & Lịch trình (Tour Summary Card) -->
                <section class="booking-card order-tour-summary-card">
                    <div class="booking-card-heading">
                        <span class="booking-step" aria-hidden="true">1</span>
                        <div>
                            <h2>Tóm tắt Tour &amp; Lịch trình</h2>
                            <p>Thông tin cốt lõi về chuyến đi và thời gian khởi hành.</p>
                        </div>
                    </div>

                    <div class="order-tour-media-row">
                        <div class="order-tour-thumb-col">
                            <img class="order-tour-thumb-img" src="<?= $escape($tourImage) ?>" alt="<?= $escape($order['ten_tour']) ?>" loading="lazy" onerror="this.onerror=null;this.src='assets/images/favicon.svg';">
                            <span class="order-tour-badge-overlay"><?= $escape($durationText) ?></span>
                        </div>
                        <div class="order-tour-info-col">
                            <h3 class="order-tour-name-heading"><?= $escape($order['ten_tour']) ?></h3>
                            
                            <!-- Grid 2x2 thông tin cốt lõi -->
                            <div class="order-tour-grid-2x2">
                                <div class="tour-grid-cell">
                                    <span class="grid-cell-icon" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16"></path><path d="M4 15h16"></path><path d="M10 3L8 21"></path><path d="M16 3l-2 18"></path></svg>
                                    </span>
                                    <div class="grid-cell-meta">
                                        <span class="grid-cell-label">Mã tour</span>
                                        <strong class="grid-cell-val grid-cell-code"><?= $escape($order['ma_tour']) ?></strong>
                                    </div>
                                </div>

                                <div class="tour-grid-cell">
                                    <span class="grid-cell-icon" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    </span>
                                    <div class="grid-cell-meta">
                                        <span class="grid-cell-label">Ngày khởi hành</span>
                                        <strong class="grid-cell-val"><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></strong>
                                    </div>
                                </div>

                                <div class="tour-grid-cell">
                                    <span class="grid-cell-icon" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    </span>
                                    <div class="grid-cell-meta">
                                        <span class="grid-cell-label">Điểm khởi hành</span>
                                        <strong class="grid-cell-val"><?= $escape($order['diem_khoi_hanh']) ?></strong>
                                    </div>
                                </div>

                                <div class="tour-grid-cell">
                                    <span class="grid-cell-icon" aria-hidden="true">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    </span>
                                    <div class="grid-cell-meta">
                                        <span class="grid-cell-label">Thời lượng</span>
                                        <strong class="grid-cell-val"><?= $escape($durationText) ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Đường link mở Modal lịch trình & chính sách hoàn hủy -->
                            <div class="order-tour-itinerary-link-row">
                                <button type="button" class="order-itinerary-popup-btn" data-modal-open="tour-itinerary-modal">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <span>Xem lại lịch trình &amp; chính sách hoàn hủy</span>
                                    <span class="popup-btn-arrow" aria-hidden="true">&rarr;</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Card 2: Danh sách hành khách (Passengers List - Compact Table) -->
                <section class="booking-card order-passengers-card">
                    <div class="booking-card-heading">
                        <span class="booking-step" aria-hidden="true">2</span>
                        <div>
                            <h2>Danh sách hành khách (<?= count($order['participants']) ?> khách)</h2>
                            <p>Danh sách chi tiết thành viên tham gia chuyến đi.</p>
                        </div>
                    </div>

                    <div class="order-passengers-table-wrapper">
                        <table class="order-passengers-table">
                            <thead>
                                <tr>
                                    <th class="th-stt">#</th>
                                    <th class="th-type">Phân loại</th>
                                    <th class="th-name">Họ và tên</th>
                                    <th class="th-gender">Giới tính</th>
                                    <th class="th-dob">Ngày sinh</th>
                                    <th class="th-id">CCCD / Hộ chiếu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['participants'] as $index => $participant): ?>
                                <?php
                                    $isChild = ($participant['loai_khach'] === 'tre_em');
                                    $gender = ($participant['gioi_tinh'] ?? 'nam') === 'nu' ? 'Nữ' : 'Nam';
                                    $dobFormatted = !empty($participant['ngay_sinh']) ? date('d/m/Y', strtotime($participant['ngay_sinh'])) : '—';
                                ?>
                                <tr>
                                    <td class="td-stt"><?= $index + 1 ?></td>
                                    <td class="td-type">
                                        <span class="passenger-type-badge <?= $isChild ? 'is-child' : 'is-adult' ?>">
                                            <?php if ($isChild): ?>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M6 20v-2a6 6 0 0 1 12 0v2"></path></svg>
                                                Trẻ em
                                            <?php else: ?>
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                                Người lớn
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="td-name">
                                        <div class="passenger-name-cell">
                                            <strong class="passenger-fullname"><?= $escape($participant['ho_ten']) ?></strong>
                                            <?php if (!empty($participant['ghi_chu'])): ?>
                                                <span class="passenger-note-bubble" title="Ghi chú">
                                                    Ghi chú: <?= $escape($participant['ghi_chu']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="td-gender">
                                        <span class="passenger-gender-pill"><?= $gender ?></span>
                                    </td>
                                    <td class="td-dob"><?= $dobFormatted ?></td>
                                    <td class="td-id">
                                        <?php if (!empty($participant['cccd'])): ?>
                                            <code class="passenger-id-code"><?= $escape($participant['cccd']) ?></code>
                                        <?php else: ?>
                                            <span class="passenger-id-empty"><?= $isChild ? 'Theo người bảo hộ' : '—' ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Card 3: Thông tin liên hệ & Ghi chú (Contact & Special Requests) -->
                <section class="booking-card order-contact-card">
                    <div class="booking-card-heading">
                        <span class="booking-step" aria-hidden="true">3</span>
                        <div>
                            <h2>Thông tin liên hệ &amp; Ghi chú</h2>
                            <p>Thông tin người đại diện nhận vé và các lưu ý đặc biệt cho chuyến đi.</p>
                        </div>
                    </div>

                    <!-- Chia 2 cột rõ ràng cho thông tin người đặt -->
                    <div class="order-contact-grid">
                        <div class="order-contact-item">
                            <span class="contact-item-label">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                Họ và tên
                            </span>
                            <strong class="contact-item-value"><?= $escape($order['ho_ten']) ?></strong>
                        </div>

                        <div class="order-contact-item">
                            <span class="contact-item-label">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                Số điện thoại
                            </span>
                            <?php if (!empty($order['so_dien_thoai'])): ?>
                                <strong class="contact-item-value"><a href="tel:<?= $escape($order['so_dien_thoai']) ?>"><?= $escape($order['so_dien_thoai']) ?></a></strong>
                            <?php else: ?>
                                <span class="contact-item-empty">Chưa cung cấp</span>
                            <?php endif; ?>
                        </div>

                        <div class="order-contact-item">
                            <span class="contact-item-label">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                Email nhận xác nhận
                            </span>
                            <?php if (!empty($order['email'])): ?>
                                <strong class="contact-item-value"><a href="mailto:<?= $escape($order['email']) ?>"><?= $escape($order['email']) ?></a></strong>
                            <?php else: ?>
                                <span class="contact-item-empty">Chưa cung cấp</span>
                            <?php endif; ?>
                        </div>

                        <div class="order-contact-item">
                            <span class="contact-item-label">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                Địa chỉ liên hệ
                            </span>
                            <?php if (!empty($order['dia_chi'])): ?>
                                <span class="contact-item-value"><?= $escape($order['dia_chi']) ?></span>
                            <?php else: ?>
                                <span class="contact-item-empty">Chưa cung cấp</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Khung Ghi chú đặc biệt -->
                    <div class="order-special-note-box">
                        <div class="special-note-header">
                            <div class="special-note-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <span>Ghi chú &amp; Yêu cầu đặc biệt cho đoàn</span>
                            </div>
                            <?php if ($status === 'cho_xac_nhan'): ?>
                            <button type="button" class="btn-edit-note" data-modal-open="edit-note-modal">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                <?= !empty($order['ghi_chu']) ? 'Chỉnh sửa ghi chú' : 'Thêm ghi chú' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="special-note-body">
                            <?php if (!empty($order['ghi_chu'])): ?>
                                <p class="special-note-content"><?= nl2br($escape($order['ghi_chu'])) ?></p>
                            <?php else: ?>
                                <p class="special-note-placeholder">Không có ghi chú hay yêu cầu đặc biệt nào (ăn chay, dị ứng, phòng gần nhau...).</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- PHẦN 3: KHU VỰC THANH TOÁN & TÁC VỤ – CỘT PHẢI 35% (STICKY PAYMENT & ACTION SIDEBAR) -->
            <aside class="booking-sidebar order-sticky-sidebar" aria-label="Tổng kết đơn đặt &amp; Thanh toán">
                <div class="order-sidebar-card">
                    <!-- KHỐI A: CHI TIẾT HÓA ĐƠN (PRICE BREAKDOWN) -->
                    <div class="order-sidebar-section price-breakdown-section">
                        <h3 class="sidebar-section-title">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            Chi tiết hóa đơn
                        </h3>

                        <div class="billing-items-list">
                            <!-- Người lớn: Liệt kê rõ đơn giá nhân với số lượng -->
                            <div class="billing-item-row">
                                <div class="billing-item-info">
                                    <span class="billing-item-name">Người lớn</span>
                                    <span class="billing-item-calc">(<?= (int) $order['so_nguoi_lon'] ?> × <?= $money($order['gia_nguoi_lon']) ?>)</span>
                                </div>
                                <strong class="billing-item-amount"><?= $money($order['so_nguoi_lon'] * $order['gia_nguoi_lon']) ?></strong>
                            </div>

                            <!-- Trẻ em (nếu có): Liệt kê rõ đơn giá nhân với số lượng -->
                            <?php if ((int) $order['so_tre_em'] > 0): ?>
                            <div class="billing-item-row">
                                <div class="billing-item-info">
                                    <span class="billing-item-name">Trẻ em</span>
                                    <span class="billing-item-calc">(<?= (int) $order['so_tre_em'] ?> × <?= $money($order['gia_tre_em']) ?>)</span>
                                </div>
                                <strong class="billing-item-amount"><?= $money($order['so_tre_em'] * $order['gia_tre_em']) ?></strong>
                            </div>
                            <?php endif; ?>

                            <!-- Tổng tiền gốc (nếu có giảm giá nhóm hoặc voucher) -->
                            <?php if ((int) $order['muc_giam'] > 0 || !empty($order['ma_giam_gia'])): ?>
                            <div class="billing-item-row billing-subtotal-row">
                                <span class="billing-item-name">Tổng tiền gốc</span>
                                <span class="billing-item-subtotal"><?= $money($order['tong_goc']) ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Giảm giá nhóm (nếu có) -->
                            <?php if ((int) $order['muc_giam'] > 0): ?>
                            <div class="billing-item-row billing-discount-row">
                                <span class="billing-item-name">
                                    <span class="discount-badge">Giảm giá nhóm -<?= (int) $order['muc_giam'] ?>%</span>
                                </span>
                                <strong class="billing-item-discount">−<?= $money($order['tien_giam']) ?></strong>
                            </div>
                            <?php endif; ?>

                            <!-- Voucher ưu đãi (nếu có) -->
                            <?php if (!empty($order['ma_giam_gia']) || (int) $order['tien_giam_voucher'] > 0): ?>
                            <div class="billing-item-row billing-discount-row">
                                <span class="billing-item-name">
                                    <span class="voucher-code-pill">Voucher: <?= $escape($order['ma_giam_gia']) ?></span>
                                </span>
                                <strong class="billing-item-discount">−<?= $money($order['tien_giam_voucher']) ?></strong>
                            </div>
                            <?php endif; ?>

                            <!-- Thuế & Phí dịch vụ: Đã bao gồm -->
                            <div class="billing-item-row billing-vat-row">
                                <span class="billing-item-name">Thuế &amp; Phí dịch vụ</span>
                                <span class="vat-included-tag">Đã bao gồm</span>
                            </div>
                        </div>

                        <!-- Tổng thanh toán: Nổi bật với cỡ chữ lớn 22px-24px màu đỏ/cam thương hiệu -->
                        <div class="order-total-highlight-card">
                            <div class="order-total-label-group">
                                <span class="total-label-main">Tổng thanh toán</span>
                                <span class="total-label-sub">Đã bao gồm toàn bộ thuế phí</span>
                            </div>
                            <div class="order-total-price-display"><?= $money($order['tong_thanh_toan']) ?></div>
                        </div>
                    </div>

                    <!-- KHỐI B: PHƯƠNG THỨC THANH TOÁN (PAYMENT METHOD) -->
                    <?php if ($status === 'da_huy'): ?>
                        <div class="order-sidebar-section payment-cancelled-section">
                            <div class="cancelled-notice-badge">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                <span>Đơn đặt đã bị hủy</span>
                            </div>
                            <p class="cancelled-notice-desc"><?= $paid ? 'Đơn tour này đã hủy. Vui lòng liên hệ OnTrip để đối soát giao dịch đã thanh toán.' : 'Đơn tour này đã hủy nên không thể thực hiện giao dịch thanh toán.' ?></p>
                            <?php if ($paid): ?>
                            <div class="payment-receipt-details" style="margin-top:12px;">
                                <div class="receipt-row">
                                    <span>Phương thức:</span>
                                    <strong><?= $escape(DonDatModel::paymentMethodLabel((string) $order['phuong_thuc'])) ?></strong>
                                </div>
                                <div class="receipt-row">
                                    <span>Mã giao dịch:</span>
                                    <code><?= $escape($order['ma_giao_dich']) ?></code>
                                </div>
                                <div class="receipt-row">
                                    <span>Số tiền đã trả:</span>
                                    <strong class="receipt-paid-amount"><?= $money($order['so_tien_da_tra']) ?></strong>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($paid): ?>
                        <!-- ĐÃ THANH TOÁN: BIÊN LAI XÁC NHẬN -->
                        <div class="order-sidebar-section payment-receipt-section">
                            <div class="payment-receipt-badge">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                <span>Thanh toán thành công</span>
                            </div>
                            <div class="payment-receipt-details">
                                <div class="receipt-row">
                                    <span>Phương thức:</span>
                                    <strong><?= $escape(DonDatModel::paymentMethodLabel((string) $order['phuong_thuc'])) ?></strong>
                                </div>
                                <div class="receipt-row">
                                    <span>Mã giao dịch:</span>
                                    <code><?= $escape($order['ma_giao_dich']) ?></code>
                                </div>
                                <div class="receipt-row">
                                    <span>Số tiền đã trả:</span>
                                    <strong class="receipt-paid-amount"><?= $money($order['so_tien_da_tra']) ?></strong>
                                </div>
                                <?php if (!empty($order['thanh_toan_luc'])): ?>
                                <div class="receipt-row">
                                    <span>Thời điểm:</span>
                                    <span><?= date('d/m/Y H:i', strtotime($order['thanh_toan_luc'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($status === 'hoan_thanh'): ?>
                        <div class="order-sidebar-section payment-cancelled-section">
                            <p class="cancelled-notice-desc">Chuyến đi đã hoàn thành. Chưa ghi nhận giao dịch thanh toán trực tuyến cho đơn này trên hệ thống.</p>
                        </div>
                    <?php else: ?>
                        <!-- FORM CHỌN PHƯƠNG THỨC & SUBMIT -->
                        <form id="order-payment-sidebar-form" method="post" action="index.php?page=orders" class="order-payment-sidebar-form">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="ma_don" value="<?= $escape($order['ma_don']) ?>">
                            <input type="hidden" name="operation" value="<?= $status === 'da_xac_nhan' ? 'pay' : 'update_payment_method' ?>">

                            <div class="order-sidebar-section payment-methods-section">
                                <h3 class="sidebar-section-title">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                                    Phương thức thanh toán
                                </h3>
                                <p class="payment-section-desc">
                                    <?php if (!empty($order['phuong_thuc_thanh_toan'])): ?>
                                        Đã chọn lúc đặt: <strong><?= $escape(DonDatModel::paymentMethodLabel($order['phuong_thuc_thanh_toan'])) ?></strong>.
                                    <?php else: ?>
                                        Chọn hình thức thanh toán thuận tiện nhất cho bạn:
                                    <?php endif; ?>
                                </p>

                                <!-- Radio Cards trực quan -->
                                <div class="payment-radio-cards" role="radiogroup" aria-label="Chọn phương thức thanh toán">
                                    <!-- 1. Chuyển khoản ngân hàng VietQR -->
                                    <label class="payment-radio-card <?= $currentMethod === 'chuyen_khoan' ? 'is-selected' : '' ?>">
                                        <input type="radio" name="phuong_thuc" value="chuyen_khoan" <?= $currentMethod === 'chuyen_khoan' ? 'checked' : '' ?> class="payment-radio-input">
                                        <div class="payment-radio-custom" aria-hidden="true"></div>
                                        <div class="payment-method-icon icon-vietqr">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                        </div>
                                        <div class="payment-method-details">
                                            <span class="method-title">Chuyển khoản ngân hàng</span>
                                            <span class="method-subtitle">VietQR (Vietcombank) - Tự động xác nhận 24/7</span>
                                        </div>
                                    </label>

                                    <!-- 2. Thẻ quốc tế (Visa / Mastercard) -->
                                    <label class="payment-radio-card <?= $currentMethod === 'the' ? 'is-selected' : '' ?>">
                                        <input type="radio" name="phuong_thuc" value="the" <?= $currentMethod === 'the' ? 'checked' : '' ?> class="payment-radio-input">
                                        <div class="payment-radio-custom" aria-hidden="true"></div>
                                        <div class="payment-method-icon icon-card">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                        </div>
                                        <div class="payment-method-details">
                                            <span class="method-title">Thẻ quốc tế</span>
                                            <span class="method-subtitle">Visa / Mastercard (kèm VietQR mô phỏng)</span>
                                        </div>
                                    </label>

                                    <!-- 3. Thanh toán tại văn phòng OnTrip -->
                                    <label class="payment-radio-card <?= $currentMethod === 'tai_van_phong' ? 'is-selected' : '' ?>">
                                        <input type="radio" name="phuong_thuc" value="tai_van_phong" <?= $currentMethod === 'tai_van_phong' ? 'checked' : '' ?> class="payment-radio-input">
                                        <div class="payment-radio-custom" aria-hidden="true"></div>
                                        <div class="payment-method-icon icon-office">
                                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><line x1="9" y1="13" x2="9.01" y2="13"></line><line x1="15" y1="13" x2="15.01" y2="13"></line></svg>
                                        </div>
                                        <div class="payment-method-details">
                                            <span class="method-title">Tại văn phòng OnTrip</span>
                                            <span class="method-subtitle">Tiền mặt hoặc quẹt thẻ tại quầy</span>
                                        </div>
                                    </label>
                                </div>

                                <?php
                                    $vietqrMethod = $currentMethod;
                                    $vietqrVisible = in_array($currentMethod, ['chuyen_khoan', 'the'], true);
                                    $vietqrAmountText = number_format((float) $order['tong_thanh_toan'], 0, ',', '.') . ' VND';
                                    $vietqrTransferNote = 'ONTRIP ' . $order['ma_don'];
                                    $vietqrRecipientName = (string) ($adminRecipientName ?? 'QUẢN TRỊ VIÊN ONTRIP');
                                    $vietqrSeed = 'VCB|ONTRIP|' . $order['ma_don'] . '|' . (int) $order['tong_thanh_toan'];
                                    $vietqrCompact = true;
                                    require __DIR__ . '/../partials/vietqr_box.php';
                                ?>
                            </div>

                            <!-- KHỐI C: CỤM NÚT HÀNH ĐỘNG (ACTION BUTTONS) -->
                            <div class="order-sidebar-section action-buttons-section">
                                <?php if ($status === 'da_xac_nhan'): ?>
                                    <!-- ĐÃ XÁC NHẬN CHỖ: CHO PHÉP XÁC NHẬN & THANH TOÁN NGAY -->
                                    <button type="submit" class="btn-primary-action btn-pay-now">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                        <span>Xác nhận &amp; Thanh toán ngay</span>
                                    </button>
                                    <p class="action-note-sub">Thanh toán trong bản demo là mô phỏng an toàn, không trừ tiền thật.</p>
                                <?php elseif ($status === 'cho_xac_nhan'): ?>
                                    <!-- ĐANG CHỜ XÁC NHẬN CHỖ: LƯU PHƯƠNG THỨC -->
                                    <button type="submit" class="btn-primary-action btn-save-payment-method">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                        <span>Lưu phương thức thanh toán</span>
                                    </button>
                                    <p class="action-note-sub">OnTrip đang kiểm tra tình trạng chỗ. Cổng thanh toán sẽ mở ngay khi tư vấn viên chốt giữ chỗ thành công.</p>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- NÚT PHỤ (SECONDARY CTA): HỦY ĐƠN ĐẶT -->
                    <?php if ($canCancel): ?>
                    <div class="order-cancel-trigger-wrap">
                        <button type="button" class="btn-cancel-order-trigger" data-modal-open="cancel-order-modal">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                            <span>Hủy đơn đặt tour</span>
                        </button>
                        <span class="cancel-help-text">Miễn phí hủy trong giai đoạn chờ xác nhận chỗ</span>
                    </div>
                    <?php endif; ?>

                    <?php if (in_array($order['trang_thai'], ['da_xac_nhan', 'hoan_thanh'], true)): ?>
                    <div class="order-rate-tour-wrap">
                        <a class="btn-rate-tour-link" href="index.php?page=tour-detail&amp;id=<?= $escape(rawurlencode($order['ma_tour'])) ?>#danh-gia">⭐ Đánh giá tour này</a>
                    </div>
                    <?php endif; ?>

                    <!-- BOX HỖ TRỢ NHANH (FOOTER CỦA SIDEBAR) -->
                    <div class="order-quick-support-box">
                        <div class="support-box-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        </div>
                        <div class="support-box-content">
                            <strong class="support-title">Cần hỗ trợ gấp về đơn hàng?</strong>
                            <p class="support-desc">Gọi ngay Hotline <a href="tel:19002345" class="support-hotline-link"><strong>1900 2345</strong></a> (24/7) hoặc Chat với tư vấn viên OnTrip.</p>
                        </div>
                    </div>

                    <div class="order-back-list-wrap">
                        <a class="booking-back-link" href="index.php?page=orders">← Về danh sách đơn</a>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Modal Popup: Xem lại lịch trình & chính sách hoàn hủy -->
        <div class="order-modal-backdrop" id="tour-itinerary-modal" aria-hidden="true" role="dialog" aria-labelledby="modal-itinerary-title">
            <div class="order-modal-card">
                <div class="order-modal-header">
                    <div>
                        <span class="order-modal-kicker">THÔNG TIN TOUR &amp; CHÍNH SÁCH</span>
                        <h3 id="modal-itinerary-title" class="order-modal-title"><?= $escape($order['ten_tour']) ?></h3>
                        <p class="order-modal-subtitle">
                            <span>Khởi hành: <strong><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></strong></span> &bull; 
                            <span>Từ: <strong><?= $escape($order['diem_khoi_hanh']) ?></strong></span> &bull; 
                            <span>Thời lượng: <strong><?= $escape($durationText) ?></strong></span>
                        </p>
                    </div>
                    <button type="button" class="order-modal-close-btn" data-modal-close aria-label="Đóng popup">&times;</button>
                </div>

                <div class="order-modal-body">
                    <!-- Mục 1: Lịch trình chi tiết -->
                    <div class="order-modal-section">
                        <div class="modal-section-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <span>Lịch trình tour chi tiết</span>
                        </div>

                        <?php if (!empty($order['itinerary'])): ?>
                            <div class="modal-itinerary-timeline">
                                <?php foreach ($order['itinerary'] as $day): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-day-pill">Ngày <?= (int) $day['ngay_thu'] ?></div>
                                        <div class="timeline-content">
                                            <h4 class="timeline-day-title"><?= $escape($day['tieu_de']) ?></h4>
                                            <p class="timeline-day-desc"><?= nl2br($escape($day['noi_dung'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="modal-itinerary-empty">
                                <p>Chương trình tour trọn gói <strong><?= $escape($durationText) ?></strong> khởi hành từ <strong><?= $escape($order['diem_khoi_hanh']) ?></strong>.</p>
                                <p>Lịch trình chi tiết theo giờ, xe đón và hướng dẫn viên sẽ được OnTrip gửi qua Zalo/Email và thông báo trực tiếp tới quý khách trước ngày khởi hành.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mục 2: Chính sách hoàn hủy -->
                    <div class="order-modal-section">
                        <div class="modal-section-badge">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span>Chính sách hoàn hủy chuẩn OnTrip</span>
                        </div>

                        <div class="modal-policy-container">
                            <table class="modal-policy-table">
                                <thead>
                                    <tr>
                                        <th>Thời gian báo hủy</th>
                                        <th>Mức phí áp dụng</th>
                                        <th>Chi phí hoàn lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Báo hủy <strong>trước ngày đi từ 7 ngày trở lên</strong></td>
                                        <td><span class="policy-tag tag-free">Miễn phí (0%)</span></td>
                                        <td><strong class="policy-refund refund-full">Hoàn 100%</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Báo hủy <strong>từ 3 đến dưới 7 ngày</strong> trước ngày đi</td>
                                        <td><span class="policy-tag tag-warn">30% giá trị tour</span></td>
                                        <td><strong class="policy-refund">Hoàn 70%</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Báo hủy <strong>dưới 3 ngày</strong> hoặc không đến điểm tập kết</td>
                                        <td><span class="policy-tag tag-danger">100% giá trị tour</span></td>
                                        <td><strong class="policy-refund refund-none">Không hoàn lại</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="policy-extra-notes">
                                <p><strong>Lưu ý quan trọng:</strong></p>
                                <ul>
                                    <li>Trường hợp hủy do thời tiết xấu hoặc thiên tai bất khả kháng, OnTrip bảo lưu 100% tiền tour hoặc dời ngày khởi hành miễn phí cho khách.</li>
                                    <li>Người lớn khi tham gia tour bắt buộc xuất trình CCCD / Hộ chiếu bản gốc còn hạn sử dụng tại điểm đón.</li>
                                    <li>Mọi yêu cầu hoàn hủy vui lòng liên hệ hotline hỗ trợ 24/7 của OnTrip hoặc nhấn nút "Hủy đơn" trên hệ thống trước thời hạn quy định.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-modal-footer">
                    <button type="button" class="button button-primary modal-close-action-btn" data-modal-close>Đã hiểu và Đóng</button>
                </div>
            </div>
        </div>

        <!-- Modal Popup 2: Chỉnh sửa ghi chú đặc biệt -->
        <div class="order-modal-backdrop" id="edit-note-modal" aria-hidden="true" role="dialog" aria-labelledby="modal-note-title">
            <div class="order-modal-card order-modal-sm">
                <div class="order-modal-header">
                    <div>
                        <span class="order-modal-kicker">CẬP NHẬT ĐƠN ĐẶT</span>
                        <h3 id="modal-note-title" class="order-modal-title">Ghi chú &amp; Yêu cầu đặc biệt</h3>
                    </div>
                    <button type="button" class="order-modal-close-btn" data-modal-close aria-label="Đóng popup">&times;</button>
                </div>
                <form method="post" action="index.php?page=orders" class="order-modal-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="ma_don" value="<?= $escape($order['ma_don']) ?>">
                    <input type="hidden" name="operation" value="update_note">
                    <div class="order-modal-body">
                        <p class="modal-form-intro">Nhập các yêu cầu hoặc lưu ý riêng cho đoàn của bạn (ví dụ: ăn chay trường, dị ứng thức ăn, xếp phòng gần nhau, đón tại điểm hẹn...).</p>
                        <div class="form-group-note">
                            <label for="order-edit-note-textarea" class="modal-input-label">Nội dung ghi chú:</label>
                            <textarea id="order-edit-note-textarea" name="ghi_chu" rows="4" maxlength="500" class="modal-textarea" placeholder="Ví dụ: Có 1 khách ăn chay; xin vui lòng sắp xếp phòng giường đôi liền kề..."><?= $escape($order['ghi_chu']) ?></textarea>
                        </div>
                    </div>
                    <div class="order-modal-footer">
                        <button type="button" class="button button-secondary" data-modal-close>Hủy bỏ</button>
                        <button type="submit" class="button button-primary">Lưu ghi chú</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Popup 3: Xác nhận lý do hủy đơn -->
        <div class="order-modal-backdrop" id="cancel-order-modal" aria-hidden="true" role="dialog" aria-labelledby="modal-cancel-title">
            <div class="order-modal-card order-modal-sm">
                <div class="order-modal-header modal-header-danger">
                    <div>
                        <span class="order-modal-kicker kicker-danger">HỦY YÊU CẦU ĐẶT TOUR</span>
                        <h3 id="modal-cancel-title" class="order-modal-title">Xác nhận hủy đơn đặt</h3>
                    </div>
                    <button type="button" class="order-modal-close-btn" data-modal-close aria-label="Đóng popup">&times;</button>
                </div>
                <form method="post" action="index.php?page=orders" class="order-modal-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="ma_don" value="<?= $escape($order['ma_don']) ?>">
                    <input type="hidden" name="operation" value="cancel">
                    <div class="order-modal-body">
                        <div class="cancel-warning-banner">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <div>
                                <strong>Bạn có chắc chắn muốn hủy đơn này?</strong>
                                <p>Số chỗ đang được giữ sẽ được giải phóng cho hành khách khác trên hệ thống.</p>
                            </div>
                        </div>

                        <div class="form-group-cancel-reason">
                            <label for="cancel-reason-select" class="modal-input-label">Vui lòng chọn lý do hủy:</label>
                            <select id="cancel-reason-select" name="ly_do_huy" class="modal-select">
                                <option value="Thay đổi kế hoạch / Chọn điểm đến khác">Thay đổi kế hoạch / Chọn điểm đến khác</option>
                                <option value="Có việc bận đột xuất / Trùng lịch trình">Có việc bận đột xuất / Trùng lịch trình</option>
                                <option value="Muốn đổi ngày khởi hành hoặc số lượng khách">Muốn đổi ngày khởi hành hoặc số lượng khách</option>
                                <option value="Đặt nhầm thông tin / Thử nghiệm tính năng">Đặt nhầm thông tin / Thử nghiệm tính năng</option>
                                <option value="Lý do cá nhân khác">Lý do cá nhân khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="order-modal-footer">
                        <button type="button" class="button button-secondary" data-modal-close>Giữ lại đơn này</button>
                        <button type="submit" class="button btn-confirm-cancel">Xác nhận hủy đơn</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
