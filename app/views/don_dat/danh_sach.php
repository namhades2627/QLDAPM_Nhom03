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
            <article class="order-card" data-order-status="<?= $escape($order['trang_thai']) ?>" data-search-text="<?= strtolower($escape($order['ma_don'] . ' ' . $order['ten_tour'] . ' ' . $order['diem_khoi_hanh'])) ?>">
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
                    <?php if (in_array($order['trang_thai'], ['da_xac_nhan', 'hoan_thanh'], true)): ?>
                        <a class="button booking-secondary-button" href="index.php?page=tour-detail&amp;id=<?= $escape(rawurlencode($order['ma_tour'])) ?>#danh-gia">⭐ Đánh giá tour</a>
                    <?php endif; ?>
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
