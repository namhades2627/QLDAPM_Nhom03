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

$totalOrders = count($orders);
$countsByStatus = [
    'all' => $totalOrders,
    'cho_xac_nhan' => 0,
    'da_xac_nhan' => 0,
    'hoan_thanh' => 0,
    'da_huy' => 0,
];
foreach ($orders as $o) {
    $st = $o['trang_thai'] ?? '';
    if (isset($countsByStatus[$st])) {
        $countsByStatus[$st]++;
    }
}
$activeStatus = is_string($_GET['status'] ?? null) && isset($countsByStatus[$_GET['status']]) ? $_GET['status'] : 'all';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Danh sách đơn đặt tour của bạn trên OnTrip.">
    <title>Đơn đặt của tôi | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=20261113-booking">
    <script src="assets/js/script.js?v=20261113-booking" defer></script>
</head>
<body class="booking-page order-list-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><span aria-current="page">Đơn đặt của tôi</span></nav>
        
        <!-- Header chính & Thống kê đơn đặt -->
        <header class="order-list-header">
            <div class="order-list-header-top">
                <span class="detail-kicker">QUẢN LÝ ĐƠN HÀNG</span>
                <div class="order-list-title-wrap">
                    <h1 class="order-list-title">Đơn đặt của tôi</h1>
                    <span class="order-list-total-badge"><?= $totalOrders ?> đơn</span>
                </div>
                <p class="order-list-subtitle">Theo dõi hành trình, kiểm tra tình trạng giữ chỗ và tiến độ thanh toán cho các chuyến đi của bạn.</p>
            </div>

            <!-- Thanh tìm kiếm nhanh & Thanh Tabs trạng thái -->
            <div class="order-toolbar-card">
                <!-- 1. Thanh tìm kiếm nhanh -->
                <div class="order-search-box">
                    <svg class="order-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="order-search-input" class="order-search-input" placeholder="Tìm theo mã đơn (OD...), tên tour, điểm khởi hành..." autocomplete="off">
                    <button type="button" id="order-search-clear" class="order-search-clear-btn" aria-label="Xóa từ khóa tìm kiếm" hidden>&times;</button>
                </div>

                <!-- 2. Thanh Tabs phân loại trạng thái -->
                <nav class="order-status-tabs" role="tablist" aria-label="Lọc theo trạng thái đơn">
                    <button type="button" class="order-status-tab <?= $activeStatus === 'all' ? 'is-active' : '' ?>" data-status-filter="all">
                        <span>Tất cả</span>
                        <span class="tab-count"><?= $countsByStatus['all'] ?></span>
                    </button>
                    <button type="button" class="order-status-tab tab-pending <?= $activeStatus === 'cho_xac_nhan' ? 'is-active' : '' ?>" data-status-filter="cho_xac_nhan">
                        <span class="tab-dot" aria-hidden="true"></span>
                        <span>Chờ xác nhận</span>
                        <span class="tab-count"><?= $countsByStatus['cho_xac_nhan'] ?></span>
                    </button>
                    <button type="button" class="order-status-tab tab-confirmed <?= $activeStatus === 'da_xac_nhan' ? 'is-active' : '' ?>" data-status-filter="da_xac_nhan">
                        <span class="tab-dot" aria-hidden="true"></span>
                        <span>Đã xác nhận</span>
                        <span class="tab-count"><?= $countsByStatus['da_xac_nhan'] ?></span>
                    </button>
                    <button type="button" class="order-status-tab tab-completed <?= $activeStatus === 'hoan_thanh' ? 'is-active' : '' ?>" data-status-filter="hoan_thanh">
                        <span class="tab-dot" aria-hidden="true"></span>
                        <span>Hoàn thành</span>
                        <span class="tab-count"><?= $countsByStatus['hoan_thanh'] ?></span>
                    </button>
                    <button type="button" class="order-status-tab tab-cancelled <?= $activeStatus === 'da_huy' ? 'is-active' : '' ?>" data-status-filter="da_huy">
                        <span class="tab-dot" aria-hidden="true"></span>
                        <span>Đã hủy</span>
                        <span class="tab-count"><?= $countsByStatus['da_huy'] ?></span>
                    </button>
                </nav>
            </div>
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
        <div class="order-list" id="order-cards-list">
            <?php foreach ($orders as $order): ?>
            <?php
                $days = (int) ($order['so_ngay'] ?? 0);
                if ($days > 0) {
                    $nights = max(1, $days - 1);
                    $durationText = $days . 'N' . $nights . 'Đ';
                } else {
                    $durationText = '2N1Đ';
                }
                $tourThumb = !empty($order['duong_dan_anh']) ? $order['duong_dan_anh'] : 'assets/images/tours/mb01.jpg';
                $isPaid = (bool) $order['da_thanh_toan'];
                $detailUrl = 'index.php?page=orders&amp;id=' . $escape(rawurlencode($order['ma_don']));
            ?>
            <article class="order-card order-media-card" data-order-status="<?= $escape($order['trang_thai']) ?>" data-search-text="<?= strtolower($escape($order['ma_don'] . ' ' . $order['ten_tour'] . ' ' . $order['diem_khoi_hanh'])) ?>">
                <div class="order-card-inner">
                    <!-- 1. Cột ảnh Thumbnail tour bên trái -->
                    <div class="order-card-thumb-col">
                        <a href="<?= $detailUrl ?>" class="order-card-thumb-link" tabindex="-1" aria-hidden="true">
                            <img src="<?= $escape($tourThumb) ?>" alt="<?= $escape($order['ten_tour']) ?>" class="order-card-thumb-img" loading="lazy">
                            <span class="order-card-duration-badge"><?= $escape($durationText) ?></span>
                        </a>
                    </div>

                    <!-- 2. Cột nội dung trung tâm -->
                    <div class="order-card-body-col">
                        <!-- Hàng meta đầu: Mã đơn, Sao chép & Badge trạng thái -->
                        <div class="order-card-meta-row">
                            <div class="order-card-code-wrap">
                                <span class="order-code-label">Mã đơn:</span>
                                <strong class="order-code-val" id="code-<?= $escape($order['ma_don']) ?>"><?= $escape($order['ma_don']) ?></strong>
                                <button type="button" class="order-card-copy-btn" data-copy-text="<?= $escape($order['ma_don']) ?>" title="Sao chép mã đơn" aria-label="Sao chép mã đơn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    <span class="copy-tooltip">Đã chép!</span>
                                </button>
                            </div>

                            <div class="order-card-badges-wrap">
                                <!-- Badge trạng thái đơn -->
                                <span class="order-status-badge status-<?= $escape($order['trang_thai']) ?>">
                                    <span class="status-dot-pulse" aria-hidden="true"></span>
                                    <?= $escape($order['trang_thai_nhan']) ?>
                                </span>

                                <!-- Badge thanh toán -->
                                <?php if ($isPaid): ?>
                                    <span class="order-pay-pill pay-paid" title="Đã hoàn tất thanh toán">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Đã thanh toán
                                    </span>
                                <?php else: ?>
                                    <span class="order-pay-pill pay-unpaid" title="Chưa thực hiện thanh toán">
                                        Chưa thanh toán
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tiêu đề Tour -->
                        <h2 class="order-card-tour-title">
                            <a href="<?= $detailUrl ?>"><?= $escape($order['ten_tour']) ?></a>
                        </h2>

                        <!-- Grid 4 thông số cốt lõi -->
                        <div class="order-card-facts-grid">
                            <!-- Ngày khởi hành -->
                            <div class="card-fact-item">
                                <span class="fact-item-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </span>
                                <div class="fact-item-text">
                                    <span class="fact-label">Khởi hành:</span>
                                    <strong class="fact-val"><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></strong>
                                </div>
                            </div>

                            <!-- Điểm xuất phát -->
                            <div class="card-fact-item">
                                <span class="fact-item-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </span>
                                <div class="fact-item-text">
                                    <span class="fact-label">Nơi khởi hành:</span>
                                    <strong class="fact-val"><?= $escape($order['diem_khoi_hanh']) ?></strong>
                                </div>
                            </div>

                            <!-- Số khách -->
                            <div class="card-fact-item">
                                <span class="fact-item-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </span>
                                <div class="fact-item-text">
                                    <span class="fact-label">Hành khách:</span>
                                    <strong class="fact-val"><?= (int) $order['so_nguoi_lon'] ?> người lớn<?= (int) $order['so_tre_em'] > 0 ? ', ' . (int) $order['so_tre_em'] . ' trẻ em' : '' ?></strong>
                                </div>
                            </div>

                            <!-- Thời điểm đặt -->
                            <div class="card-fact-item">
                                <span class="fact-item-icon">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </span>
                                <div class="fact-item-text">
                                    <span class="fact-label">Ngày đặt:</span>
                                    <span class="fact-val"><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hàng chân thẻ (Giá tiền & Nút thao tác) -->
                <div class="order-card-foot-row">
                    <div class="order-card-price-col">
                        <span class="price-col-label">Tổng thanh toán:</span>
                        <strong class="price-col-amount"><?= $money($order['tong_thanh_toan']) ?></strong>
                    </div>
                    <div class="order-card-actions-col">
                        <a class="button button-secondary order-action-btn" href="<?= $detailUrl ?>">Xem chi tiết</a>
                        <?php if (in_array($order['trang_thai'], ['da_xac_nhan', 'hoan_thanh'], true)): ?>
                            <a class="button button-secondary order-action-btn" href="index.php?page=tour-detail&amp;id=<?= $escape(rawurlencode($order['ma_tour'])) ?>#danh-gia">⭐ Đánh giá tour</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Khung hiển thị khi không tìm thấy kết quả theo bộ lọc -->
        <div id="order-filter-empty" class="order-filter-no-results" hidden>
            <div class="no-results-icon" aria-hidden="true">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                </svg>
            </div>
            <h3>Không tìm thấy đơn đặt phù hợp</h3>
            <p>Không có đơn hàng nào khớp với từ khóa tìm kiếm hoặc tab trạng thái bạn đang chọn.</p>
            <button type="button" id="btn-reset-order-filter" class="button button-secondary">Đặt lại bộ lọc</button>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
