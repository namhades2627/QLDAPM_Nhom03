<?php
// Hai loại tour dùng chung View. Chỉ có dữ liệu mẫu, chưa truy vấn database.
require __DIR__ . '/sample-data.php';

$tourType = ($_GET['type'] ?? '') === 'international' ? 'international' : 'domestic';
$typeTitle = $tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế';
$regionNames = $tourType === 'domestic'
    ? ['north' => 'Miền Bắc', 'central' => 'Miền Trung', 'south' => 'Miền Nam']
    : ['asia' => 'Châu Á', 'europe' => 'Châu Âu', 'americas-oceania' => 'Châu Mỹ – Châu Úc'];
$requestedRegion = $_GET['region'] ?? '';
$tourRegion = is_string($requestedRegion) && isset($regionNames[$requestedRegion]) ? $requestedRegion : '';
$keyword = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$tours = $catalogTours[$tourType];
$initialCount = count(array_filter($tours, static fn($tour) => $tourRegion === '' || $tour['region'] === $tourRegion));
$initialShown = 0;
$pageSize = 12;
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
                <label class="catalog-field"><span>ĐIỂM ĐẾN</span>
                    <select name="destination"><option value="">Tất cả điểm đến</option>
                        <?php foreach ($catalogDestinations[$tourType] as $destination): ?>
                            <option value="<?= $escape($destination['name']) ?>"><?= $escape($destination['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="catalog-field"><span>KHỞI HÀNH TỪ</span><select name="origin"><option value="">Tất cả nơi khởi hành</option><option>TP. Hồ Chí Minh</option><option>Hà Nội</option></select></label>
                <label class="catalog-field"><span>NGÀY KHỞI HÀNH</span><input type="date" name="date"></label>
                <label class="catalog-field"><span>MỨC GIÁ</span><select name="price"><option value="">Tất cả mức giá</option><option value="0-5000000">Dưới 5 triệu</option><option value="5000000-10000000">5 – dưới 10 triệu</option><option value="10000000-20000000">10 – dưới 20 triệu</option><option value="20000000-50000000">20 – dưới 50 triệu</option><option value="50000000-">Từ 50 triệu</option></select></label>
                <details class="catalog-needs">
                    <summary><span>NHU CẦU</span><span id="catalog-needs-value">Tất cả nhu cầu</span><svg class="icon chevron-down"><use href="#i-chevron"/></svg></summary>
                    <div class="catalog-needs-options" role="group" aria-label="Nhu cầu chuyến đi">
                        <?php foreach ($catalogNeeds as $value => $label): ?>
                            <label><input type="checkbox" name="needs[]" value="<?= $value ?>"><span><?= $label ?></span></label>
                        <?php endforeach; ?>
                        <p>Có thể chọn nhiều nhu cầu.</p>
                    </div>
                </details>
                <button class="button button-primary catalog-apply" type="submit"><svg class="icon"><use href="#i-search"/></svg>Tìm tour</button>
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
                <div><h2 id="catalog-results-title">Danh sách hành trình</h2><p id="catalog-result-description" aria-live="polite"><strong><?= $initialCount ?></strong> tour · Hiển thị <?= min($pageSize, $initialCount) ?> tour</p></div>
                <label class="catalog-sort"><span>Sắp xếp theo</span><select id="catalog-sort"><option value="default">Mặc định</option><option value="price-asc">Giá tăng dần</option><option value="price-desc">Giá giảm dần</option><option value="duration">Thời lượng ngắn nhất</option></select></label>
            </div>
            <div id="catalog-grid" class="tour-grid catalog-grid">
                <?php foreach ($tours as $index => $tour):
                    $matchesRegion = $tourRegion === '' || $tour['region'] === $tourRegion;
                    $visible = $matchesRegion && $initialShown < $pageSize;
                    if ($visible) $initialShown++;
                ?>
                    <article class="tour-card catalog-card" data-name="<?= $escape($tour['name']) ?>" data-destination="<?= $escape($tour['destination']) ?>" data-region="<?= $tour['region'] ?>" data-origin="<?= $escape($tour['origin']) ?>" data-price="<?= $tour['price'] ?>" data-days="<?= $tour['days'] ?>" data-needs="<?= implode(',', $tour['needs']) ?>" data-dates="<?= implode(',', $tour['dates']) ?>" data-order="<?= $index ?>" <?= $visible ? '' : 'hidden' ?>>
                        <div class="tour-image-wrap">
                            <img src="<?= $tour['image'] ?>" alt="<?= $escape($tour['destination']) ?>" width="600" height="450" loading="lazy">
                            <span class="tour-duration"><svg class="icon"><use href="#i-clock"/></svg><?= $tour['duration'] ?></span>
                        </div>
                        <div class="tour-body">
                            <div class="catalog-card-top"><p class="tour-place"><?= $escape($tour['destination']) ?></p><span>OT-<?= $tourType === 'domestic' ? 'TN' : 'QT' ?><?= str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) ?></span></div>
                            <h3><?= $escape($tour['name']) ?></h3>
                            <p class="tour-meta"><svg class="icon"><use href="#i-pin"/></svg>Khởi hành từ <?= $escape($tour['origin']) ?></p>
                            <div class="catalog-card-bottom"><div><span>Giá từ / người lớn</span><p><strong><?= number_format($tour['price'], 0, ',', '.') ?></strong> <span>₫</span></p></div><a class="catalog-detail" href="index.php?page=tour-detail&amp;type=<?= $tourType ?>&amp;id=<?= $escape($tour['id']) ?>">Xem chi tiết <svg class="icon"><use href="#i-arrow"/></svg></a></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="catalog-empty" class="catalog-empty" hidden>
                <svg class="icon"><use href="#i-search"/></svg><h3>Chưa tìm thấy tour phù hợp</h3><p>Thử thay đổi điểm đến, ngày đi hoặc mức giá để có thêm lựa chọn.</p><button class="button button-primary" type="button" data-catalog-reset>Xóa bộ lọc</button>
            </div>
            <div class="catalog-list-footer">
                <nav id="catalog-pagination" class="catalog-pagination" aria-label="Phân trang danh sách tour"></nav>
                <p id="catalog-range">Hiển thị 1–<?= min($pageSize, $initialCount) ?> / <?= $initialCount ?> tour</p>
            </div>
            <p class="catalog-demo-note">Dữ liệu tour, mức giá và lịch khởi hành đang dùng để minh họa giao diện.</p>
        </section>
        </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
