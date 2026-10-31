<?php
/**
 * View: Tra cứu và đề xuất tour thông minh chuyên sâu
 * 
 * @var string $page
 * @var string $tourType
 * @var array<string, mixed> $criteria
 * @var array<int, array<string, mixed>> $recommendations
 * @var array<int, array<string, mixed>> $destinations
 * @var array<string, string> $catalogNeeds
 */

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$totalRecommendations = count($recommendations);

$groupedDestinations = [];
foreach ($destinations as $d) {
    $groupName = ($d['loai_tour'] === 'domestic' ? 'Trong nước: ' : 'Quốc tế: ') . $d['ten_khu_vuc'];
    $groupedDestinations[$groupName][] = $d;
}

$currentGuests = (int) $criteria['adults'] + (int) $criteria['children'];
$currentDiscountRate = $currentGuests >= 10 ? 8 : ($currentGuests >= 5 ? 5 : 0);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Tra cứu và đề xuất tour thông minh OnTrip — Lọc lịch hợp lệ, tính chi phí nhóm, đối chiếu sở thích, mùa vụ, dự báo thời tiết và gợi ý lịch tiết kiệm ±3 ngày.">
    <title>Đề xuất tour thông minh | OnTrip — Đi & khám phá</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="smart-search-page">
<?php require __DIR__ . '/../partials/header.php'; ?>

<main id="noi-dung">
    <!-- 1. BREADCRUMB & HERO GIỚI THIỆU TÍNH NĂNG THÔNG MINH -->
    <section class="smart-hero-section">
        <div class="container">
            <nav class="catalog-breadcrumb" aria-label="Đường dẫn trang">
                <a href="index.php">Trang chủ</a><span aria-hidden="true">/</span>
                <span aria-current="page">Tra cứu &amp; Đề xuất tour thông minh</span>
            </nav>
            <div class="smart-hero-content">
                <div class="smart-hero-badge">
                    <span class="smart-sparkle-icon" aria-hidden="true">✨</span>
                    <span>HỆ THỐNG ĐỀ XUẤT THÔNG MINH ONTRIP</span>
                </div>
                <h1>Tra cứu và Đề xuất Tour Thông minh<span aria-hidden="true">.</span></h1>
                <p class="smart-hero-desc">
                    Nhập thời điểm dự kiến, điểm đến, quy mô nhóm, ngân sách và nhu cầu chuyến đi. Hệ thống tự động lọc lịch mở hợp lệ, tính chi phí sau ưu đãi nhóm, đối chiếu sở thích và mùa vụ của điểm đến; kết hợp dự báo thời tiết để xếp hạng, giải thích lý do, đưa ra cảnh báo và <strong>tự động phát hiện lịch cùng tour tiết kiệm hơn trong khoảng ±3 ngày</strong>.
                </p>
            </div>
        </div>
    </section>

    <!-- 2. BIỂU MẪU NHẬP TIÊU CHÍ THÔNG MINH -->
    <section class="smart-form-section">
        <div class="container">
            <form id="smart-search-form" class="smart-search-card" action="index.php" method="get">
                <input type="hidden" name="page" value="smart-search">
                
                <div class="smart-form-header">
                    <h2><svg class="icon"><use href="#i-search"/></svg> Tiêu chí tìm kiếm &amp; mong muốn chuyến đi</h2>
                    <span class="smart-group-tier-badge">
                        Nhóm <strong><?= $currentGuests ?> khách</strong>:
                        <?php if ($currentDiscountRate > 0): ?>
                            Ưu đãi giảm <strong><?= $currentDiscountRate ?>%</strong>
                        <?php else: ?>
                            Đạt 5 khách để giảm 5%
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Hàng 1: Điểm đến, Thời điểm, Số lượng người lớn và trẻ em -->
                <div class="smart-form-grid">
                    <label class="smart-field">
                        <span class="smart-field-label">ĐIỂM ĐẾN MONG MUỐN</span>
                        <div class="smart-control">
                            <svg class="icon"><use href="#i-pin"/></svg>
                            <select name="destination" class="smart-select" aria-label="Chọn điểm đến mong muốn">
                                <option value="0">Tất cả điểm đến</option>
                                <?php foreach ($groupedDestinations as $groupLabel => $items): ?>
                                    <optgroup label="<?= $escape($groupLabel) ?>">
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= (int) $item['id_diem_den'] ?>" <?= (int) $criteria['destination_id'] === (int) $item['id_diem_den'] ? 'selected' : '' ?>>
                                                <?= $escape($item['ten_diem_den']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </label>

                    <label class="smart-field">
                        <span class="smart-field-label">THỜI ĐIỂM DỰ KIẾN (NGÀY ĐI)</span>
                        <div class="smart-control">
                            <input type="date" name="date" class="smart-input" value="<?= $escape($criteria['date']) ?>" aria-label="Thời điểm dự kiến khởi hành">
                        </div>
                        <small class="smart-field-hint">Hệ thống sẽ quét cả ngày lân cận ±3 ngày có giá tốt hơn.</small>
                    </label>

                    <div class="smart-field-group">
                        <label class="smart-field">
                            <span class="smart-field-label">NGƯỜI LỚN</span>
                            <input type="number" name="adults" min="1" max="99" value="<?= (int) $criteria['adults'] ?>" class="smart-input smart-number" required>
                        </label>

                        <label class="smart-field">
                            <span class="smart-field-label">TRẺ EM</span>
                            <input type="number" name="children" min="0" max="99" value="<?= (int) $criteria['children'] ?>" class="smart-input smart-number" required>
                        </label>
                    </div>

                    <label class="smart-field">
                        <span class="smart-field-label">NGÂN SÁCH CẢ NHÓM (VNĐ)</span>
                        <div class="smart-control">
                            <input type="number" name="budget" min="0" step="500000" placeholder="VD: 30000000" value="<?= $criteria['budget'] > 0 ? (int) $criteria['budget'] : '' ?>" class="smart-input">
                        </div>
                        <small class="smart-field-hint">So sánh trực tiếp với tổng thanh toán sau ưu đãi nhóm.</small>
                    </label>
                </div>

                <!-- Hàng 2: Nhu cầu & sở thích chuyến đi -->
                <div class="smart-needs-block">
                    <span class="smart-field-label">SỞ THÍCH &amp; NHU CẦU CHUYẾN ĐI (CHỌN MỘT HOẶC NHIỀU)</span>
                    <div class="smart-needs-pills" role="group" aria-label="Nhu cầu chuyến đi">
                        <?php foreach ($catalogNeeds as $code => $name): 
                            $checked = in_array($code, $criteria['needs'], true);
                        ?>
                            <label class="smart-pill <?= $checked ? 'is-active' : '' ?>">
                                <input type="checkbox" name="needs[]" value="<?= $escape($code) ?>" <?= $checked ? 'checked' : '' ?>>
                                <span><?= $escape($name) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Hàng 3: Loại tour & Nút hành động -->
                <div class="smart-form-actions">
                    <div class="smart-type-selector">
                        <label class="smart-radio">
                            <input type="radio" name="type" value="all" <?= $criteria['type'] === 'all' ? 'checked' : '' ?>>
                            <span>Tất cả tour</span>
                        </label>
                        <label class="smart-radio">
                            <input type="radio" name="type" value="domestic" <?= $criteria['type'] === 'domestic' ? 'checked' : '' ?>>
                            <span>Tour trong nước</span>
                        </label>
                        <label class="smart-radio">
                            <input type="radio" name="type" value="international" <?= $criteria['type'] === 'international' ? 'checked' : '' ?>>
                            <span>Tour quốc tế</span>
                        </label>
                    </div>

                    <div class="smart-btn-group">
                        <a href="index.php?page=smart-search" class="button button-secondary smart-reset-btn">Làm mới</a>
                        <button type="submit" class="button button-primary smart-submit-btn">
                            <span class="smart-sparkle-icon">✨</span> Phân tích &amp; Đề xuất ngay
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- 3. KẾT QUẢ XẾP HẠNG & ĐỀ XUẤT THÔNG MINH -->
    <section class="smart-results-section" aria-labelledby="smart-results-heading">
        <div class="container">
            <div class="smart-results-header">
                <div>
                    <h2 id="smart-results-heading">Bảng Xếp Hạng &amp; Đề Xuất Phù Hợp</h2>
                    <p class="smart-results-meta">
                        Tìm thấy <strong><?= $totalRecommendations ?></strong> hành trình phù hợp và đang mở lịch nhận đặt. Được xếp hạng theo thuật toán đa tiêu chí OnTrip.
                    </p>
                </div>
                <div class="smart-offer-summary-pill">
                    <span>Chính sách nhóm:</span>
                    <strong>5–9 khách: -5%</strong> · <strong>≥10 khách: -8%</strong>
                </div>
            </div>

            <?php if ($totalRecommendations === 0): ?>
                <div class="smart-empty-state">
                    <svg class="icon"><use href="#i-search"/></svg>
                    <h3>Chưa tìm thấy hành trình khớp 100% tiêu chí</h3>
                    <p>Hãy thử mở rộng khoảng ngân sách, chọn ngày khởi hành khác hoặc để trống điểm đến để hệ thống gợi ý nhiều lựa chọn hấp dẫn hơn.</p>
                    <a href="index.php?page=smart-search" class="button button-primary">Đặt lại tiêu chí tìm kiếm</a>
                </div>
            <?php else: ?>
                <div class="smart-cards-list">
                    <?php foreach ($recommendations as $rank => $item): 
                        $tour = $item['tour'];
                        $schedule = $item['selected_schedule'];
                        $cost = $item['cost'];
                        $season = $item['season'];
                        $weather = $item['weather'];
                        $flex = $item['flexible_savings'];
                        $score = $item['score'];
                        $badges = $item['badges'];
                        $isTopOne = ($rank === 0);
                    ?>
                        <article class="smart-tour-card <?= $isTopOne ? 'is-top-match' : '' ?>" aria-labelledby="tour-title-<?= $escape($tour['id']) ?>">
                            <div class="smart-card-top-bar">
                                <div class="smart-badge-tags">
                                    <span class="smart-rank-badge">Hạng #<?= $rank + 1 ?></span>
                                    <?php foreach ($badges as $b): ?>
                                        <span class="smart-tag smart-tag-<?= $escape($b['type']) ?>">
                                            <span aria-hidden="true"><?= $b['icon'] ?></span> <?= $escape($b['label']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div class="smart-score-indicator" title="Điểm phù hợp tổng hợp từ: Thời gian, Ngân sách, Sở thích, Mùa vụ & Thời tiết">
                                    <span class="smart-score-label">Độ tương thích:</span>
                                    <div class="smart-score-bar-wrap">
                                        <div class="smart-score-bar" style="width: <?= $score ?>%;"></div>
                                    </div>
                                    <strong class="smart-score-number"><?= $score ?>%</strong>
                                </div>
                            </div>

                            <div class="smart-card-main-layout">
                                <div class="smart-card-media">
                                    <img src="<?= $escape($tour['image']) ?>" alt="<?= $escape($tour['name']) ?>" width="420" height="280" loading="lazy">
                                    <span class="tour-duration"><svg class="icon"><use href="#i-clock"/></svg><?= $escape($tour['duration']) ?></span>
                                    <span class="smart-dest-pill"><svg class="icon"><use href="#i-pin"/></svg><?= $escape($tour['destination']) ?></span>
                                </div>

                                <div class="smart-card-details">
                                    <div class="smart-title-row">
                                        <span class="smart-region-text"><?= $escape($tour['region_name']) ?></span>
                                        <h3 id="tour-title-<?= $escape($tour['id']) ?>"><?= $escape($tour['name']) ?></h3>
                                    </div>

                                    <div class="smart-schedule-price-box">
                                        <div class="smart-schedule-col">
                                            <span class="smart-sub-title">LỊCH KHỞI HÀNH PHÙ HỢP</span>
                                            <p class="smart-schedule-date">
                                                <strong><?= $schedule['date_formatted'] ?></strong>
                                                <?php if ($schedule['is_exact_date']): ?>
                                                    <span class="smart-date-match-tag">✓ Đúng ngày chọn</span>
                                                <?php elseif ($criteria['date'] !== ''): ?>
                                                    <span class="smart-date-diff-tag"><?= $schedule['day_diff'] > 0 ? '+' : '' ?><?= $schedule['day_diff'] ?> ngày</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="smart-schedule-origin"><svg class="icon"><use href="#i-pin"/></svg> Xuất phát từ: <strong><?= $escape($schedule['origin']) ?></strong></p>
                                            <p class="smart-schedule-seats">Chỗ trống: <strong><?= $schedule['capacity'] ?></strong> khách</p>
                                        </div>

                                        <div class="smart-cost-col">
                                            <span class="smart-sub-title">TỔNG CHI PHÍ NHÓM (<?= $cost['total_guests'] ?> KHÁCH)</span>
                                            <div class="smart-cost-breakdown">
                                                <span class="smart-cost-unit">Người lớn: <?= number_format($cost['adult_price'], 0, ',', '.') ?> ₫/người</span>
                                                <?php if ($cost['children'] > 0): ?>
                                                    <span class="smart-cost-unit">Trẻ em: <?= number_format($cost['child_price'], 0, ',', '.') ?> ₫/người</span>
                                                <?php endif; ?>
                                                <div class="smart-cost-total-row">
                                                    <?php if ($cost['discount_percent'] > 0): ?>
                                                        <span class="smart-cost-original"><?= number_format($cost['original_total'], 0, ',', '.') ?> ₫</span>
                                                        <span class="smart-discount-tag">-<?= $cost['discount_percent'] ?>% nhóm</span>
                                                    <?php endif; ?>
                                                    <p class="smart-cost-final">
                                                        <strong><?= number_format($cost['final_total'], 0, ',', '.') ?></strong><span> ₫</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="smart-insight-columns">
                                        <div class="smart-insight-item smart-season-box">
                                            <div class="smart-insight-title">
                                                <span class="smart-insight-icon">🌸</span>
                                                <strong>Mùa vụ điểm đến (Tháng <?= (int) date('n', strtotime($schedule['date'])) ?>)</strong>
                                            </div>
                                            <span class="smart-season-status smart-season-<?= $escape($season['status']) ?>">
                                                <?= $escape($season['label']) ?>
                                            </span>
                                            <p class="smart-insight-desc"><?= $escape($season['summary']) ?></p>
                                        </div>

                                        <div class="smart-insight-item smart-weather-box">
                                            <div class="smart-insight-title">
                                                <span class="smart-insight-icon">🌤️</span>
                                                <strong>Dự báo thời tiết tại <?= $escape($tour['destination']) ?></strong>
                                            </div>
                                            <p class="smart-weather-temp">
                                                <strong><?= $escape($weather['temperature']) ?></strong> · <?= $escape($weather['condition']) ?>
                                            </p>
                                            <p class="smart-insight-desc"><?= $escape($weather['note']) ?></p>
                                        </div>
                                    </div>

                                    <div class="smart-explanations-wrap">
                                        <h4 class="smart-explain-title"><svg class="icon"><use href="#i-check"/></svg> Vì sao OnTrip đề xuất tour này cho bạn?</h4>
                                        <ul class="smart-explain-list">
                                            <?php foreach ($item['explanations'] as $exp): ?>
                                                <li><span>✓</span> <?= $escape($exp) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <?php if (!empty($item['alerts'])): ?>
                                        <div class="smart-alerts-wrap" role="alert">
                                            <strong>Lưu ý cho chuyến đi:</strong>
                                            <ul>
                                                <?php foreach ($item['alerts'] as $alert): ?>
                                                    <li><?= $escape($alert) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($flex !== null): ?>
                                        <div class="smart-flexible-savings-card" role="region" aria-label="Gợi ý lịch cùng tour tiết kiệm hơn">
                                            <div class="smart-flex-header">
                                                <div class="smart-flex-badge">
                                                    <span aria-hidden="true">💡</span> GỢI Ý TIẾT KIỆM TRONG ±3 NGÀY
                                                </div>
                                                <span class="smart-flex-savings-amount">
                                                    Tiết kiệm thêm <strong><?= number_format($flex['savings_amount'], 0, ',', '.') ?> ₫</strong> cả nhóm
                                                </span>
                                            </div>
                                            <p class="smart-flex-message">
                                                Nếu bạn có thể linh hoạt lịch trình, tour này có lịch khởi hành ngày <strong><?= $escape($flex['date_formatted']) ?></strong> (<em><?= $escape($flex['diff_text']) ?></em>):
                                            </p>
                                            <div class="smart-flex-footer">
                                                <div class="smart-flex-price-compare">
                                                    <span>Giá chỉ: <strong><?= number_format($flex['adult_price'], 0, ',', '.') ?> ₫</strong>/người lớn</span>
                                                    <small>(giảm <?= number_format($flex['savings_per_adult'], 0, ',', '.') ?> ₫/khách so với ngày <?= $schedule['date_formatted'] ?>)</small>
                                                </div>
                                                <a href="index.php?page=tour-detail&amp;type=<?= $escape($tour['type']) ?>&amp;id=<?= $escape($tour['id']) ?>&amp;date=<?= $escape($flex['date']) ?>" class="button button-outline smart-flex-action">
                                                    Xem lịch tiết kiệm này <svg class="icon"><use href="#i-arrow"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="smart-card-actions">
                                        <a href="index.php?page=tour-detail&amp;type=<?= $escape($tour['type']) ?>&amp;id=<?= $escape($tour['id']) ?>&amp;date=<?= $escape($schedule['date']) ?>" class="button button-primary smart-detail-link">
                                            Xem chi tiết hành trình <svg class="icon"><use href="#i-arrow"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>

