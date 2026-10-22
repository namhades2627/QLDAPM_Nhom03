<?php
// Giao diện chi tiết dùng chung dữ liệu minh họa với danh sách tour.
require __DIR__ . '/sample-data.php';
$tourType = ($_GET['type'] ?? '') === 'international' ? 'international' : 'domestic';
$requestedId = $_GET['id'] ?? '';
$requestedId = is_string($requestedId) ? $requestedId : '';
$tour = null;
foreach ($catalogTours[$tourType] as $item) {
    if ($item['id'] === $requestedId) { $tour = $item; break; }
}
if ($tour === null) {
    http_response_code(404);
    $tour = $catalogTours[$tourType][0];
    $notFound = true;
}
$tourRegion = '';
$typeTitle = $tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế';
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$relatedTours = array_values(array_filter($catalogTours[$tourType], static fn($item) => $item['id'] !== $tour['id']));
usort($relatedTours, static function ($a, $b) use ($tour) {
    $aScore = ($a['destination'] === $tour['destination'] ? 2 : 0) + ($a['region'] === $tour['region'] ? 1 : 0);
    $bScore = ($b['destination'] === $tour['destination'] ? 2 : 0) + ($b['region'] === $tour['region'] ? 1 : 0);
    return $bScore <=> $aScore;
});
$stops = array_values(array_filter(array_map('trim', explode('–', $tour['name']))));
$routeStops = array_slice($stops, 1);
if (!$routeStops) $routeStops = [$tour['destination']];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Thông tin hành trình <?= $escape($tour['name']) ?> tại OnTrip.">
    <title><?= !empty($notFound) ? 'Không tìm thấy tour' : $escape($tour['name']) ?> | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="detail-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="detail-main">
<?php if (!empty($notFound)): ?>
    <div class="container detail-not-found"><h1>Không tìm thấy tour</h1><p>Mã tour này chưa có trong danh sách mẫu.</p><a class="button button-primary" href="index.php?page=tours&amp;type=<?= $tourType ?>">Xem danh sách tour</a></div>
<?php else: ?>
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><a href="index.php?page=tours&amp;type=<?= $tourType ?>"><?= $typeTitle ?></a><span>/</span><span aria-current="page"><?= $escape($tour['name']) ?></span></nav>
        <header class="detail-heading"><div><span class="detail-kicker"><?= $typeTitle ?> <span>·</span> <?= $escape($tour['destination']) ?></span><h1><?= $escape($tour['name']) ?></h1><p>Hành trình <?= $escape($tour['duration']) ?> · Khởi hành từ <?= $escape($tour['origin']) ?></p></div><span class="detail-code">Mã tour: <?= $escape(strtoupper($tour['id'])) ?></span></header>
        <div class="detail-hero"><img src="<?= $escape($tour['image']) ?>" alt="<?= $escape($tour['destination']) ?>" width="1200" height="590"><span>Ảnh minh họa hành trình · <?= $escape($tour['destination']) ?></span></div>
        <div class="detail-facts" aria-label="Thông tin nhanh"><div><span>ĐIỂM ĐẾN</span><strong><?= $escape($tour['destination']) ?></strong></div><div><span>THỜI LƯỢNG</span><strong><?= $escape($tour['duration']) ?></strong></div><div><span>KHỞI HÀNH TỪ</span><strong><?= $escape($tour['origin']) ?></strong></div></div>
        <div class="detail-layout">
            <div class="detail-content">
                <section class="detail-section" id="tong-quan"><span class="detail-section-label">1 / HÀNH TRÌNH</span><h2>Một chuyến đi, nhiều trải nghiệm</h2><p>Khám phá <?= $escape($tour['destination']) ?> qua các điểm dừng trong hành trình. Thông tin dưới đây là nội dung mẫu để xem bố cục trang chi tiết; chương trình cụ thể sẽ được cập nhật khi có dữ liệu tour.</p><div class="detail-stop-list"><?php foreach ($routeStops as $stop): ?><span><?= $escape($stop) ?></span><?php endforeach; ?></div></section>
                <section class="detail-section" id="lich-trinh"><span class="detail-section-label">2 / LỊCH TRÌNH</span><h2>Lịch trình theo ngày</h2><p class="detail-section-note">Các hoạt động đang là gợi ý minh họa theo tuyến; lịch trình chính thức sẽ được nhập cho từng tour.</p><div class="detail-days"><?php for ($day = 1; $day <= $tour['days']; $day++): $stop = $routeStops[($day - 1) % count($routeStops)]; ?><article class="detail-day"><span class="detail-day-number"><?= str_pad((string)$day, 2, '0', STR_PAD_LEFT) ?></span><div><h3>Ngày <?= $day ?> <span>·</span> <?= $escape($stop) ?></h3><p><?= $day === 1 ? 'Tập trung tại điểm hẹn, khởi hành và bắt đầu khám phá điểm đến.' : ($day === $tour['days'] ? 'Tiếp tục tham quan theo chương trình và kết thúc hành trình.' : 'Tham quan các điểm dừng trong tuyến, trải nghiệm văn hóa và ẩm thực địa phương.') ?></p></div></article><?php endfor; ?></div></section>
                <section class="detail-section" id="danh-gia"><span class="detail-section-label">3 / CẢM NHẬN</span><h2>Đánh giá tour</h2><div class="detail-review-empty"><span aria-hidden="true">☆</span><div><strong>Chưa có đánh giá</strong><p>Nhận xét của khách đã hoàn thành chuyến đi sẽ xuất hiện tại đây khi chức năng đánh giá được triển khai.</p></div></div></section>
            </div>
            <aside class="detail-sidebar" aria-label="Lịch khởi hành và giá tour"><div class="detail-booking"><span class="detail-booking-label">GIÁ TỪ / NGƯỜI LỚN</span><p class="detail-price"><?= number_format($tour['price'], 0, ',', '.') ?> <small>₫</small></p><p class="detail-price-note">Giá minh họa; giá thực tế sẽ phụ thuộc lịch khởi hành.</p><div class="detail-booking-divider"></div><h2>Lịch khởi hành</h2><div class="detail-date-list"><?php foreach ($tour['dates'] as $date): ?><span><?= date('d/m/Y', strtotime($date)) ?></span><?php endforeach; ?></div><p class="detail-seat-note">Số chỗ còn lại và giá theo ngày sẽ hiển thị khi kết nối dữ liệu.</p><div class="detail-booking-divider"></div><div class="detail-discount"><strong>Ưu đãi theo nhóm</strong><p>5–9 khách: giảm 5% · Từ 10 khách: giảm 8%</p><small>Áp dụng cho cùng một đơn đặt và lịch đi.</small></div><button class="button button-primary detail-book-button" type="button" disabled>Đặt tour ngay</button><p class="detail-static-note">Đây là giao diện minh họa, chưa nhận đặt tour.</p></div></aside>
        </div>
        <section class="detail-related" aria-labelledby="related-title"><div class="detail-related-heading"><div><span class="detail-section-label">TIẾP TỤC KHÁM PHÁ</span><h2 id="related-title">Tour liên quan</h2></div><div class="detail-related-controls"><button type="button" data-related-prev aria-label="Xem các tour liên quan trước">‹</button><button type="button" data-related-next aria-label="Xem các tour liên quan tiếp theo">›</button></div></div><div class="detail-related-grid" data-related-grid><?php foreach (array_slice($relatedTours, 0, 6) as $index => $related): ?><article class="detail-related-card" data-related-card <?= $index >= 3 ? 'hidden' : '' ?>><a href="index.php?page=tour-detail&amp;type=<?= $tourType ?>&amp;id=<?= $escape($related['id']) ?>"><img src="<?= $escape($related['image']) ?>" alt="<?= $escape($related['destination']) ?>" width="380" height="250" loading="lazy"><div><span><?= $escape($related['destination']) ?> · <?= $escape($related['duration']) ?></span><h3><?= $escape($related['name']) ?></h3><p>Từ <strong><?= number_format($related['price'], 0, ',', '.') ?> ₫</strong> / người lớn</p></div></a></article><?php endforeach; ?></div></section>
    </div>
<?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
