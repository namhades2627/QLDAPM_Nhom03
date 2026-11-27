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

$csrfToken = $_SESSION['csrf_token'] ?? '';
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
$cssVersion = (string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css');
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cssVersion ?>">
    <script src="assets/js/script.js?v=<?= $cssVersion ?>" defer></script>

    <!-- Scoped Styling đặc trị cho Trang Danh Sách Đơn Đặt (chống cache và đảm bảo bố cục 3 cột chuẩn đẹp) -->
    <style>
        .order-list-page .booking-main { padding-bottom: 50px !important; }
        .order-list-header { margin-bottom: 22px !important; }
        .order-list-title-wrap { display: flex !important; align-items: center !important; gap: 12px !important; margin: 4px 0 6px !important; flex-wrap: wrap !important; }
        .order-list-title { font-size: 26px !important; font-weight: 800 !important; color: var(--ink, #1e293b) !important; letter-spacing: -.5px !important; margin: 0 !important; }
        .order-list-total-badge { display: inline-flex !important; align-items: center !important; background: #ffedd5 !important; color: #9a3412 !important; border: 1px solid #fed7aa !important; font-size: 12.5px !important; font-weight: 700 !important; padding: 2px 10px !important; border-radius: 999px !important; }
        .order-list-subtitle { font-size: 13.5px !important; color: #64748b !important; margin: 0 !important; line-height: 1.5 !important; }

        /* Toolbar Tìm kiếm & Tabs */
        .order-toolbar-card { background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; padding: 14px 16px !important; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04) !important; margin-top: 16px !important; display: flex !important; flex-direction: column !important; gap: 12px !important; }
        .order-search-box { position: relative !important; width: 100% !important; }
        .order-search-icon { position: absolute !important; left: 14px !important; top: 50% !important; transform: translateY(-50%) !important; color: #94a3b8 !important; pointer-events: none !important; }
        .order-search-input { width: 100% !important; height: 42px !important; padding: 8px 36px 8px 40px !important; border: 1.5px solid #e2e8f0 !important; border-radius: 8px !important; background: #f8fafc !important; font-size: 13.5px !important; font-family: inherit !important; color: #1e293b !important; transition: all .18s ease !important; box-sizing: border-box !important; }
        .order-search-input:focus { outline: none !important; background: #ffffff !important; border-color: #ea580c !important; box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12) !important; }
        .order-search-clear-btn { position: absolute !important; right: 12px !important; top: 50% !important; transform: translateY(-50%) !important; width: 20px !important; height: 20px !important; border-radius: 50% !important; background: #e2e8f0 !important; border: none !important; color: #475569 !important; font-size: 14px !important; line-height: 1 !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important; }
        .order-status-tabs { display: flex !important; align-items: center !important; gap: 8px !important; overflow-x: auto !important; padding-bottom: 2px !important; scrollbar-width: none !important; }
        .order-status-tabs::-webkit-scrollbar { display: none !important; }
        .order-status-tab { display: inline-flex !important; align-items: center !important; gap: 6px !important; padding: 7px 14px !important; border-radius: 8px !important; border: 1px solid transparent !important; background: #f1f5f9 !important; color: #475569 !important; font-size: 12.5px !important; font-weight: 600 !important; cursor: pointer !important; white-space: nowrap !important; transition: all .15s ease !important; }
        .order-status-tab:hover { background: #e2e8f0 !important; color: #1e293b !important; }
        .order-status-tab.is-active { background: #fff7ed !important; border-color: #fed7aa !important; color: #9a3412 !important; font-weight: 700 !important; }
        .tab-dot { width: 7px !important; height: 7px !important; border-radius: 50% !important; flex-shrink: 0 !important; }
        .tab-pending .tab-dot { background: #f97316 !important; }
        .tab-confirmed .tab-dot { background: #0284c7 !important; }
        .tab-completed .tab-dot { background: #16a34a !important; }
        .tab-cancelled .tab-dot { background: #dc2626 !important; }
        .tab-count { font-size: 11px !important; padding: 1px 7px !important; border-radius: 999px !important; background: rgba(0,0,0,0.06) !important; color: inherit !important; font-weight: 700 !important; }
        .order-status-tab.is-active .tab-count { background: #fed7aa !important; color: #7c2d12 !important; }

        /* Danh sách thẻ đơn hàng - Bố cục 3 Cột Sang Trọng */
        .order-list { display: flex !important; flex-direction: column !important; gap: 16px !important; margin-top: 18px !important; }
        .order-media-card { background: #ffffff !important; border: 1.5px solid #e2e8f0 !important; border-radius: 14px !important; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05) !important; padding: 0 !important; overflow: hidden !important; transition: all .2s ease !important; margin: 0 !important; }
        .order-media-card:hover { transform: translateY(-2px) !important; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.09) !important; border-color: #cbd5e1 !important; }
        .order-card-row { display: flex !important; align-items: stretch !important; gap: 20px !important; padding: 18px 20px !important; }

        /* Cột 1: Thumbnail tour */
        .order-card-thumb-col { width: 210px !important; min-width: 210px !important; max-width: 210px !important; height: 145px !important; position: relative !important; border-radius: 10px !important; overflow: hidden !important; flex-shrink: 0 !important; background: #f1f5f9 !important; }
        .order-card-thumb-link { display: block !important; width: 100% !important; height: 100% !important; position: relative !important; }
        .order-card-thumb-img { width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; transition: transform .35s ease !important; }
        .order-media-card:hover .order-card-thumb-img { transform: scale(1.05) !important; }
        .order-card-duration-badge { position: absolute !important; bottom: 8px !important; left: 8px !important; background: rgba(15, 23, 42, 0.85) !important; color: #ffffff !important; font-size: 11px !important; font-weight: 700 !important; padding: 3px 8px !important; border-radius: 6px !important; backdrop-filter: blur(4px) !important; z-index: 2 !important; }

        /* Cột 2: Thông tin chuyến đi */
        .order-card-info-col { flex: 1 !important; min-width: 0 !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; }
        .order-card-meta-line { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 10px !important; flex-wrap: wrap !important; margin-bottom: 6px !important; }
        .order-code-box { display: inline-flex !important; align-items: center !important; gap: 5px !important; font-size: 12px !important; }
        .code-label { color: #94a3b8 !important; font-size: 11px !important; font-weight: 600 !important; text-transform: uppercase !important; }
        .code-text { font-family: monospace, monospace !important; font-size: 13px !important; font-weight: 700 !important; color: #1e293b !important; background: #f1f5f9 !important; padding: 2px 7px !important; border-radius: 5px !important; border: 1px solid #e2e8f0 !important; letter-spacing: .4px !important; }
        .order-copy-code-btn { position: relative !important; background: none !important; border: none !important; padding: 4px !important; color: #94a3b8 !important; cursor: pointer !important; border-radius: 4px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; transition: all .15s ease !important; }
        .order-copy-code-btn:hover { color: #ea580c !important; background: #fff7ed !important; }
        .copy-bubble { position: absolute !important; top: -24px !important; left: 50% !important; transform: translateX(-50%) !important; background: #1e293b !important; color: #ffffff !important; font-size: 10.5px !important; font-weight: 600 !important; padding: 2px 6px !important; border-radius: 4px !important; opacity: 0 !important; pointer-events: none !important; transition: opacity .2s ease !important; white-space: nowrap !important; z-index: 10 !important; }
        .order-copy-code-btn.is-copied .copy-bubble { opacity: 1 !important; }
        .order-badges-group { display: flex !important; align-items: center !important; gap: 8px !important; flex-wrap: wrap !important; }
        .order-pay-pill { display: inline-flex !important; align-items: center !important; gap: 4px !important; font-size: 11px !important; font-weight: 700 !important; padding: 2px 8px !important; border-radius: 999px !important; }
        .pay-paid { background: #dcfce7 !important; color: #15803d !important; border: 1px solid #bbf7d0 !important; }
        .pay-unpaid { background: #fef3c7 !important; color: #b45309 !important; border: 1px solid #fde68a !important; }

        /* Tên Tour */
        .order-tour-title { font-size: 16.5px !important; font-weight: 700 !important; color: #1e293b !important; margin: 0 0 10px 0 !important; line-height: 1.35 !important; }
        .order-tour-title a { color: inherit !important; text-decoration: none !important; transition: color .15s ease !important; }
        .order-tour-title a:hover { color: #ea580c !important; }

        /* Specs Grid 2x2 */
        .order-specs-grid { display: grid !important; grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: 8px 16px !important; padding: 10px 14px !important; background: #f8fafc !important; border-radius: 8px !important; border: 1px solid #e2e8f0 !important; }
        .spec-item { display: flex !important; align-items: center !important; gap: 7px !important; font-size: 12.5px !important; min-width: 0 !important; }
        .spec-icon { color: #ea580c !important; flex-shrink: 0 !important; display: flex !important; align-items: center !important; }
        .spec-label { font-size: 11.5px !important; color: #64748b !important; font-weight: 500 !important; flex-shrink: 0 !important; }
        .spec-val { font-size: 12px !important; font-weight: 700 !important; color: #334155 !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; }

        /* Cột 3: Giá tiền & Nút Tác vụ */
        .order-card-action-col { width: 210px !important; min-width: 210px !important; border-left: 1px solid #f1f5f9 !important; padding-left: 20px !important; display: flex !important; flex-direction: column !important; justify-content: space-between !important; align-items: stretch !important; flex-shrink: 0 !important; }
        .action-col-price-block { display: flex !important; flex-direction: column !important; gap: 3px !important; text-align: right !important; margin-bottom: 12px !important; }
        .price-title { font-size: 11.5px !important; color: #64748b !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: .3px !important; }
        .price-number { font-size: 22px !important; font-weight: 800 !important; color: #ea580c !important; letter-spacing: -.5px !important; line-height: 1.2 !important; }
        .price-status-tag { font-size: 11px !important; font-weight: 700 !important; margin-top: 2px !important; }
        .tag-paid { color: #16a34a !important; }
        .tag-waiting { color: #ea580c !important; }
        .tag-pending { color: #d97706 !important; }
        .tag-cancelled { color: #dc2626 !important; }
        .action-col-buttons-block { display: flex !important; flex-direction: column !important; gap: 7px !important; }

        /* Nút Tác vụ */
        .btn-primary-detail { width: 100% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important; padding: 10px 14px !important; font-size: 13.5px !important; font-weight: 700 !important; color: #ffffff !important; background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%) !important; border: none !important; border-radius: 8px !important; text-decoration: none !important; box-shadow: 0 2px 8px rgba(234, 88, 12, 0.28) !important; transition: all .18s ease !important; box-sizing: border-box !important; text-align: center !important; cursor: pointer !important; }
        .btn-primary-detail:hover { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%) !important; transform: translateY(-1px) !important; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.38) !important; color: #ffffff !important; }
        .btn-sub-pay { width: 100% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 6px !important; padding: 8px 12px !important; font-size: 12.5px !important; font-weight: 700 !important; color: #ffffff !important; background: #0284c7 !important; border: none !important; border-radius: 7px !important; text-decoration: none !important; transition: all .15s ease !important; box-sizing: border-box !important; }
        .btn-sub-pay:hover { background: #0369a1 !important; color: #ffffff !important; }
        .btn-sub-cancel { width: 100% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 5px !important; padding: 7px 10px !important; font-size: 12px !important; font-weight: 600 !important; color: #dc2626 !important; background: #fef2f2 !important; border: 1px solid #fee2e2 !important; border-radius: 7px !important; cursor: pointer !important; transition: all .15s ease !important; box-sizing: border-box !important; }
        .btn-sub-cancel:hover { background: #fee2e2 !important; color: #b91c1c !important; }
        .btn-sub-review { width: 100% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 5px !important; padding: 7px 10px !important; font-size: 12px !important; font-weight: 700 !important; color: #b45309 !important; background: #fffbeb !important; border: 1px solid #fde68a !important; border-radius: 7px !important; text-decoration: none !important; transition: all .15s ease !important; box-sizing: border-box !important; }
        .btn-sub-review:hover { background: #fef3c7 !important; color: #92400e !important; }

        /* Empty State */
        .order-empty-state { padding: 60px 24px !important; text-align: center !important; background: #ffffff !important; border: 1px dashed #cbd5e1 !important; border-radius: 16px !important; margin-top: 10px !important; }
        .empty-state-illustration { width: 84px !important; height: 84px !important; border-radius: 50% !important; background: #fff7ed !important; color: #ea580c !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 18px !important; border: 1.5px solid #fed7aa !important; }
        .order-empty-state h2 { font-size: 22px !important; font-weight: 800 !important; color: #1e293b !important; margin: 0 0 10px 0 !important; }
        .order-empty-state p { font-size: 14px !important; color: #64748b !important; max-width: 480px !important; margin: 0 auto 24px auto !important; line-height: 1.6 !important; }
        .empty-state-actions { display: flex !important; align-items: center !important; justify-content: center !important; gap: 12px !important; flex-wrap: wrap !important; }

        /* Responsive */
        @media (max-width: 860px) {
            .order-card-row { flex-direction: column !important; gap: 14px !important; padding: 14px 16px !important; }
            .order-card-thumb-col { width: 100% !important; min-width: 100% !important; max-width: 100% !important; height: 180px !important; }
            .order-card-action-col { width: 100% !important; min-width: 100% !important; border-left: none !important; padding-left: 0 !important; border-top: 1px solid #f1f5f9 !important; padding-top: 12px !important; flex-direction: row !important; justify-content: space-between !important; align-items: center !important; }
            .action-col-price-block { text-align: left !important; margin-bottom: 0 !important; }
            .action-col-buttons-block { flex-direction: row !important; align-items: center !important; }
        }

        @media (max-width: 600px) {
            .order-specs-grid { grid-template-columns: 1fr !important; }
            .order-card-action-col { flex-direction: column !important; align-items: stretch !important; gap: 10px !important; }
            .action-col-buttons-block { flex-direction: column !important; }
        }
    </style>
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
        <div class="booking-empty order-empty-state">
            <div class="empty-state-illustration" aria-hidden="true">
                <svg width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <h2>Bạn chưa có chuyến đi nào</h2>
            <p>Hàng trăm điểm đến hấp dẫn và những hành trình tuyệt vời đang chờ đón bạn. Hãy lên kế hoạch cho kỳ nghỉ tiếp theo ngay hôm nay!</p>
            <div class="empty-state-actions">
                <a class="button button-primary" href="index.php?page=tours&amp;type=domestic">Khám phá tour trong nước</a>
                <a class="button button-secondary" href="index.php?page=tours&amp;type=international">Tour quốc tế</a>
            </div>
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
                <div class="order-card-row">
                    <!-- CỘT 1: THUMBNAIL TOUR (Cố định 210px x 145px, bo góc, có nhãn số ngày) -->
                    <div class="order-card-thumb-col">
                        <a href="<?= $detailUrl ?>" class="order-card-thumb-link" tabindex="-1" aria-hidden="true">
                            <img src="<?= $escape($tourThumb) ?>" alt="<?= $escape($order['ten_tour']) ?>" class="order-card-thumb-img" loading="lazy">
                            <span class="order-card-duration-badge"><?= $escape($durationText) ?></span>
                        </a>
                    </div>

                    <!-- CỘT 2: THÔNG TIN CHUYẾN ĐI (Chiếm không gian chính) -->
                    <div class="order-card-info-col">
                        <!-- Hàng 1: Mã đơn & Huy hiệu trạng thái -->
                        <div class="order-card-meta-line">
                            <div class="order-code-box">
                                <span class="code-label">Mã đơn:</span>
                                <strong class="code-text" id="code-<?= $escape($order['ma_don']) ?>"><?= $escape($order['ma_don']) ?></strong>
                                <button type="button" class="order-copy-code-btn" data-copy-text="<?= $escape($order['ma_don']) ?>" title="Sao chép mã đơn" aria-label="Sao chép mã đơn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    <span class="copy-bubble">Đã chép!</span>
                                </button>
                            </div>

                            <div class="order-badges-group">
                                <span class="order-status-badge status-<?= $escape($order['trang_thai']) ?>">
                                    <span class="status-dot-pulse" aria-hidden="true"></span>
                                    <?= $escape($order['trang_thai_nhan']) ?>
                                </span>
                                <?php if ($isPaid): ?>
                                    <span class="order-pay-pill pay-paid">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        Đã thanh toán
                                    </span>
                                <?php else: ?>
                                    <span class="order-pay-pill pay-unpaid">Chưa thanh toán</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hàng 2: Tên Tour (link to chi tiết) -->
                        <h2 class="order-tour-title">
                            <a href="<?= $detailUrl ?>"><?= $escape($order['ten_tour']) ?></a>
                        </h2>

                        <!-- Hàng 3: Lưới 4 thông số chuyến đi (2 cột x 2 hàng gọn gàng) -->
                        <div class="order-specs-grid">
                            <div class="spec-item">
                                <span class="spec-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                </span>
                                <span class="spec-label">Khởi hành:</span>
                                <strong class="spec-val"><?= date('d/m/Y', strtotime($order['ngay_khoi_hanh'])) ?></strong>
                            </div>

                            <div class="spec-item">
                                <span class="spec-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                </span>
                                <span class="spec-label">Nơi đi:</span>
                                <strong class="spec-val"><?= $escape($order['diem_khoi_hanh']) ?></strong>
                            </div>

                            <div class="spec-item">
                                <span class="spec-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                </span>
                                <span class="spec-label">Hành khách:</span>
                                <strong class="spec-val"><?= (int) $order['so_nguoi_lon'] ?> lớn<?= (int) $order['so_tre_em'] > 0 ? ', ' . (int) $order['so_tre_em'] . ' trẻ' : '' ?></strong>
                            </div>

                            <div class="spec-item">
                                <span class="spec-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                </span>
                                <span class="spec-label">Ngày đặt:</span>
                                <span class="spec-val"><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- CỘT 3: TỔNG TIỀN & CỤM NÚT HÀNH ĐỘNG (Ngăn cách rõ ràng, to rõ, dễ bấm) -->
                    <div class="order-card-action-col">
                        <div class="action-col-price-block">
                            <span class="price-title">Tổng thanh toán:</span>
                            <strong class="price-number"><?= $money($order['tong_thanh_toan']) ?></strong>
                            <?php if ($isPaid): ?>
                                <span class="price-status-tag tag-paid">✓ Đã thanh toán</span>
                            <?php elseif ($order['trang_thai'] === 'da_xac_nhan'): ?>
                                <span class="price-status-tag tag-waiting">Chờ thanh toán</span>
                            <?php elseif ($order['trang_thai'] === 'cho_xac_nhan'): ?>
                                <span class="price-status-tag tag-pending">Chờ xác nhận</span>
                            <?php elseif ($order['trang_thai'] === 'da_huy'): ?>
                                <span class="price-status-tag tag-cancelled">Đã hủy đơn</span>
                            <?php endif; ?>
                        </div>

                        <div class="action-col-buttons-block">
                            <!-- Nút chính: Xem chi tiết đơn -->
                            <a class="btn-primary-detail" href="<?= $detailUrl ?>">
                                <span>Xem chi tiết</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            </a>

                            <!-- Nút phụ ngữ cảnh -->
                            <?php if ($order['trang_thai'] === 'da_xac_nhan' && !$isPaid): ?>
                                <a class="btn-sub-pay" href="<?= $detailUrl ?>">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                    <span>Thanh toán ngay</span>
                                </a>
                            <?php elseif ($order['trang_thai'] === 'cho_xac_nhan'): ?>
                                <button type="button" class="btn-sub-cancel" data-cancel-modal-trigger data-order-code="<?= $escape($order['ma_don']) ?>" data-tour-name="<?= $escape($order['ten_tour']) ?>">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                                    <span>Hủy đơn</span>
                                </button>
                            <?php elseif (in_array($order['trang_thai'], ['da_xac_nhan', 'hoan_thanh'], true)): ?>
                                <a class="btn-sub-review" href="index.php?page=tour-detail&amp;id=<?= $escape(rawurlencode($order['ma_tour'])) ?>#danh-gia">
                                    ⭐ Đánh giá tour
                                </a>
                            <?php endif; ?>
                        </div>
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

        <!-- Modal Popup Xác nhận Hủy đơn từ Danh sách -->
        <div class="order-modal-backdrop" id="list-cancel-order-modal" aria-hidden="true" role="dialog" aria-labelledby="modal-list-cancel-title">
            <div class="order-modal-card order-modal-sm">
                <div class="order-modal-header modal-header-danger">
                    <div>
                        <span class="order-modal-kicker kicker-danger">HỦY YÊU CẦU ĐẶT TOUR</span>
                        <h3 id="modal-list-cancel-title" class="order-modal-title">Xác nhận hủy đơn đặt</h3>
                    </div>
                    <button type="button" class="order-modal-close-btn" data-modal-close aria-label="Đóng popup">&times;</button>
                </div>
                <form method="post" action="index.php?page=orders" class="order-modal-form">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="ma_don" id="cancel-modal-order-code" value="">
                    <input type="hidden" name="operation" value="cancel">
                    <div class="order-modal-body">
                        <div class="cancel-warning-banner">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <div>
                                <strong id="cancel-modal-tour-display">Bạn có chắc chắn muốn hủy đơn này?</strong>
                                <p>Mã đơn: <code id="cancel-modal-code-display"></code>. Số chỗ đang được giữ sẽ được giải phóng cho hành khách khác trên hệ thống.</p>
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
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
