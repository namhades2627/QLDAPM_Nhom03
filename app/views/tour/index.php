<?php
// Dữ liệu đã được TourController chuẩn bị; View chỉ hiển thị.
/**
 * Các biến được TourController::index() truyền qua require.
 * @var string $page
 * @var string $tourType
 * @var string $typeTitle
 * @var string $tourRegion
 * @var string $keyword
 * @var array<int, array<string, mixed>> $tours
 * @var array<string, string> $destinationRegions
 * @var array<string, string> $destinationRegionTypes
 * @var array<string, string> $origins
 * @var array<string, string> $catalogNeeds
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $matchedTours
 * @var bool $hasGroupSearch
 * @var array<int, string> $searchSummary
 */
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$initialCount = count($matchedTours);
$pageSize = 12;
$visibleCodes = array_column(array_slice($matchedTours, 0, $pageSize), 'id');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $typeTitle ?> OnTrip — Khám phá hành trình theo điểm đến, ngày khởi hành, mức giá và nhu cầu của bạn.">
    <title><?= $typeTitle ?> | OnTrip — Đi & khám phá</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="catalog-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" data-catalog-type="<?= $tourType ?>" data-region="<?= $escape($tourRegion) ?>">
    <!-- 1. TIÊU ĐỀ — breadcrumb và tìm nhanh; chọn loại/vùng trên navbar. -->
    <section class="catalog-intro">
        <div class="container">
            <nav class="catalog-breadcrumb" aria-label="Đường dẫn trang">
                <a href="index.php">Trang chủ</a><span aria-hidden="true">/</span>
                <span aria-current="page"><?= $typeTitle ?></span>
            </nav>
            <div class="catalog-intro-row">
                <div>
                    <h1><?= $typeTitle ?><span aria-hidden="true">.</span></h1>
                    <p class="catalog-intro-text"><?= $tourType === 'domestic' ? 'Những miền đất thân quen, những trải nghiệm mới.' : 'Khám phá văn hóa và cảnh sắc qua những hành trình mới.' ?></p>
                    <p class="catalog-search-summary" <?= $searchSummary ? '' : 'hidden' ?>><?= $escape(implode(' · ', $searchSummary)) ?></p>
                </div>
                <form class="catalog-quick-search" role="search" aria-label="Tìm nhanh trong danh sách tour">
                    <label class="catalog-sr-only" for="catalog-keyword">Tên tour hoặc điểm đến</label>
                    <input id="catalog-keyword" type="search" placeholder="Tìm theo tên tour hoặc điểm đến..." value="<?= $escape($keyword) ?>" autocomplete="off">
                    <button type="submit" aria-label="Tìm nhanh tour"><svg class="icon"><use href="#i-search"/></svg></button>
                </form>
            </div>
        </div>
    </section>

    <div class="container catalog-content">
        <div class="catalog-layout">
        <!-- 2. BỘ LỌC — không có người lớn/trẻ em, mức giá là giá từ/người lớn. -->
        <aside class="catalog-filter-section" aria-labelledby="catalog-filter-title">
            <div class="catalog-filter-heading"><h2 id="catalog-filter-title">Bộ lọc</h2><button type="button" class="catalog-reset" data-catalog-reset>Xóa bộ lọc <span aria-hidden="true">↺</span></button></div>
            <form id="catalog-filters" class="catalog-filter-form">
                <input type="hidden" name="destination" value="<?= (int) $filters['destination'] ?>">
                <?php if ($hasGroupSearch): ?>
                <input type="hidden" name="adults" value="<?= (int) $filters['adults'] ?>">
                <input type="hidden" name="children" value="<?= (int) $filters['children'] ?>">
                <input type="hidden" name="budget" value="<?= (int) $filters['budget'] ?>">
                <?php endif; ?>
                <label class="catalog-field"><span>ĐIỂM ĐẾN</span>
                    <select name="region"><option value="">Tất cả điểm đến</option>
                        <?php foreach ($destinationRegions as $region => $label): ?>
                            <option value="<?= $escape($region) ?>" data-region-type="<?= $escape($destinationRegionTypes[$region]) ?>" <?= $tourRegion === $region ? 'selected' : '' ?>><?= $escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="catalog-field"><span>KHỞI HÀNH TỪ</span><select name="origin"><option value="">Tất cả nơi khởi hành</option><?php foreach ($origins as $origin): ?><option <?= $filters['origin'] === $origin ? 'selected' : '' ?>><?= $escape($origin) ?></option><?php endforeach; ?></select></label>
                <label class="catalog-field"><span>NGÀY KHỞI HÀNH</span><input type="date" name="date" value="<?= $escape($filters['date']) ?>"></label>
                <label class="catalog-field"><span>MỨC GIÁ</span><select name="price">
                    <?php foreach (['' => 'Tất cả mức giá', '0-5000000' => 'Dưới 5 triệu', '5000000-10000000' => '5 – dưới 10 triệu', '10000000-20000000' => '10 – dưới 20 triệu', '20000000-50000000' => '20 – dưới 50 triệu', '50000000-' => 'Từ 50 triệu'] as $value => $label): ?>
                    <option value="<?= $escape($value) ?>" <?= $filters['price'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <details class="catalog-needs">
                    <summary><span>NHU CẦU</span><span id="catalog-needs-value">Tất cả nhu cầu</span><svg class="icon chevron-down"><use href="#i-chevron"/></svg></summary>
                    <div class="catalog-needs-options" role="group" aria-label="Nhu cầu chuyến đi">
                        <?php foreach ($catalogNeeds as $value => $label): ?>
                            <label><input type="checkbox" name="needs[]" value="<?= $escape($value) ?>" <?= in_array($value, $filters['needs'], true) ? 'checked' : '' ?>><span><?= $escape($label) ?></span></label>
                        <?php endforeach; ?>
                        <p>Có thể chọn nhiều nhu cầu.</p>
                    </div>
                </details>
                <button class="button button-primary catalog-apply" type="submit">Áp dụng</button>
            </form>
            <p class="catalog-price-note">Mức giá là giá từ cho một người lớn, chưa áp dụng ưu đãi nhóm. Giá cụ thể phụ thuộc lịch khởi hành.</p>
        </aside>

        <div class="catalog-main">
        <aside class="catalog-group-offer" aria-label="Ưu đãi nhóm">
            <span class="catalog-offer-heading">ĐI CÙNG NHAU, ƯU ĐÃI HƠN</span>
            <p>Nhóm 5–9 khách <strong>giảm 5%</strong><span aria-hidden="true">·</span> Từ 10 khách <strong>giảm 8%</strong></p>
            <span class="catalog-offer-condition">Trong cùng một đơn và lịch đi</span>
        </aside>

        <!-- 3. KẾT QUẢ — mỗi loại 24 tour, 12 tour/trang, 3 cột × 4 hàng. -->
        <section id="catalog-results" aria-labelledby="catalog-results-title">
            <div class="catalog-toolbar">
                <div><h2 id="catalog-results-title">Danh sách tour</h2><p id="catalog-result-description" aria-live="polite"><strong><?= $initialCount ?></strong> tour · Hiển thị <?= min($pageSize, $initialCount) ?> tour</p></div>
                <label class="catalog-sort"><span>Sắp xếp theo</span><select id="catalog-sort"><option value="default">Mặc định</option><option value="price-asc">Giá tăng dần</option><option value="price-desc">Giá giảm dần</option><option value="duration">Thời lượng ngắn nhất</option></select></label>
            </div>
            <div id="catalog-grid" class="tour-grid catalog-grid">
                <?php foreach ($tours as $index => $tour):
                    $visible = in_array($tour['id'], $visibleCodes, true);
                ?>
                    <article class="tour-card catalog-card" data-name="<?= $escape($tour['name']) ?>" data-destination="<?= $escape($tour['destination']) ?>" data-destination-id="<?= (int) $tour['destination_id'] ?>" data-region="<?= $escape($tour['region']) ?>" data-price="<?= $tour['price'] ?? '' ?>" data-days="<?= $tour['days'] ?>" data-needs="<?= $escape(implode(',', $tour['needs'])) ?>" data-schedules="<?= $escape(json_encode($tour['schedules'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) ?>" data-order="<?= $index ?>" <?= $visible ? '' : 'hidden' ?>>
                        <div class="tour-image-wrap">
                            <img src="<?= $escape($tour['image']) ?>" alt="<?= $escape($tour['destination']) ?>" width="600" height="450" loading="lazy">
                            <span class="tour-duration"><svg class="icon"><use href="#i-clock"/></svg><?= $tour['duration'] ?></span>
                        </div>
                        <div class="tour-body">
                            <div class="catalog-card-top"><p class="tour-place"><?= $escape($tour['destination']) ?></p></div>
                            <h3><?= $escape($tour['name']) ?></h3>
                            <p class="tour-meta"><svg class="icon"><use href="#i-pin"/></svg><span data-card-origin><?= $tour['origin'] !== '' ? 'Khởi hành từ ' . $escape($tour['origin']) : 'Chưa có lịch khởi hành' ?></span></p>
                            <div class="catalog-card-bottom"><div><span>Giá từ / người lớn</span><p data-card-price><?= $tour['price'] !== null ? '<strong>' . number_format($tour['price'], 0, ',', '.') . '</strong> <span>₫</span>' : 'Đang cập nhật' ?></p></div><a class="catalog-detail" href="index.php?page=tour-detail&amp;type=<?= $escape($tour['type']) ?>&amp;id=<?= $escape($tour['id']) ?>">Xem chi tiết <svg class="icon"><use href="#i-arrow"/></svg></a></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="catalog-empty" class="catalog-empty" <?= $initialCount ? 'hidden' : '' ?>>
                <svg class="icon"><use href="#i-search"/></svg><h3>Chưa tìm thấy tour phù hợp</h3><p>Thử thay đổi điểm đến, ngày đi hoặc mức giá để có thêm lựa chọn.</p><button class="button button-primary" type="button" data-catalog-reset>Xóa bộ lọc</button>
            </div>
            <div class="catalog-list-footer">
                <nav id="catalog-pagination" class="catalog-pagination" aria-label="Phân trang danh sách tour"></nav>
            </div>
        </section>
        </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
