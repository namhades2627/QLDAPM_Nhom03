<?php
/**
 * Dữ liệu tour nổi bật do HomeController truyền vào.
 * @var string $page
 * @var array<int, array<string, mixed>> $featuredTours
 * @var array<string, array<int, array<string, mixed>>> $destinationGroups
 * @var array<int, array<string, mixed>> $availableDestinations
 * @var array<string, string> $travelNeeds
 */
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$banners = [
    [
        'image' => 'assets/images/banner2.jpg',
        'alt' => '',
        'title' => 'Đi một chuyến.<br>Thêm một câu chuyện.',
        'text' => 'Từ những miền biển đầy nắng đến phố cổ bình yên,<br>một hành trình mới đang chờ bạn.',
    ],
    [
        'image' => 'assets/images/banner3.jpg',
        'alt' => '',
        'title' => 'Chạm vào thiên nhiên.<br>Gần hơn với chính mình.',
        'text' => 'Đón bình minh giữa núi rừng, đi qua những bản làng<br>và mang về những kỷ niệm rất riêng.',
    ],
    [
        'image' => 'assets/images/banner1.jpg',
        'alt' => '',
        'title' => 'Một điểm đến mới.<br>Muôn điều để nhớ.',
        'text' => 'Bước ra khỏi những điều quen thuộc.<br>Khám phá văn hóa, hương vị và nhịp sống mới.',
    ],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OnTrip — Cảm hứng cho hành trình tiếp theo. Khám phá tour trong nước, quốc tế và những điểm đến đáng nhớ.">
    <meta name="theme-color" content="#df6024">
    <title>OnTrip — Đi một chuyến, thêm một câu chuyện</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body>
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung">
    <!-- 1. BANNER — 3 ảnh, nút trước/sau và bộ chọn slide. -->
    <section class="hero" aria-label="Cảm hứng hành trình" aria-roledescription="trình chiếu">
        <div class="hero-slides">
            <?php foreach ($banners as $index => $banner): ?>
                <article class="hero-slide<?= $index === 0 ? ' is-active' : '' ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>" aria-label="<?= $index + 1 ?> / 3">
                    <img class="hero-image" src="<?= $banner['image'] ?>" alt="<?= $banner['alt'] ?>" width="1600" height="900">
                    <div class="container hero-inner">
                        <div class="hero-copy">
                            <?php if ($index === 0): ?><h1><?= $banner['title'] ?></h1><?php else: ?><h2><?= $banner['title'] ?></h2><?php endif; ?>
                            <p class="hero-description"><?= $banner['text'] ?></p>
                            <a class="button button-primary" href="#tour-noi-bat">Khám phá hành trình <svg class="icon"><use href="#i-arrow"/></svg></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <button class="hero-arrow hero-prev" type="button" aria-label="Banner trước"><svg class="icon"><use href="#i-chevron"/></svg></button>
        <button class="hero-arrow hero-next" type="button" aria-label="Banner tiếp theo"><svg class="icon"><use href="#i-chevron"/></svg></button>
        <div class="hero-pagination">
            <button class="hero-dot is-active" type="button" aria-label="Đến banner 1" aria-current="true"></button>
            <button class="hero-dot" type="button" aria-label="Đến banner 2" aria-current="false"></button>
            <button class="hero-dot" type="button" aria-label="Đến banner 3" aria-current="false"></button>
        </div>
    </section>

    <!-- 2. TÌM TOUR — nằm bên dưới, không đè lên banner. -->
    <section id="tim-tour" class="search-section" aria-labelledby="search-title">
        <div class="container">
            <div class="search-heading"><h2 id="search-title">Chuyến đi tiếp theo của bạn?</h2></div>
            <form class="tour-search" action="index.php" method="get" aria-label="Thông tin tìm tour">
                <input type="hidden" name="page" value="tours">
                <input type="hidden" name="type" value="all">
                <!-- Hàng 1: thông tin hành trình và số lượng từng loại khách. -->
                <div class="search-row">
                    <label class="search-field">
                        <span>ĐIỂM ĐẾN</span>
                        <span class="field-control"><svg class="icon"><use href="#i-pin"/></svg><input type="text" name="q" list="search-destinations" maxlength="255" placeholder="Bạn muốn đi đâu?"></span>
                        <datalist id="search-destinations"><?php foreach ($availableDestinations as $destination): ?><option value="<?= $escape($destination['name']) ?>"></option><?php endforeach; ?></datalist>
                    </label>
                    <label class="search-field">
                        <span>NGÀY KHỞI HÀNH</span>
                        <span class="field-control"><input type="date" name="date" aria-label="Ngày khởi hành"></span>
                    </label>
                    <label class="search-field">
                        <span>NGƯỜI LỚN</span>
                        <input type="number" name="adults" min="1" max="99" step="1" value="1" inputmode="numeric" required>
                    </label>
                    <label class="search-field">
                        <span>TRẺ EM</span>
                        <input type="number" name="children" min="0" max="99" step="1" value="0" inputmode="numeric" required>
                    </label>
                </div>
                <!-- Hàng 2: ngân sách, nhu cầu tùy chọn và nút tìm. -->
                <div class="search-row search-row-secondary">
                    <label class="search-field">
                        <span>NGÂN SÁCH</span>
                        <input type="number" name="budget" min="0" step="1" inputmode="numeric" placeholder="Nhập số tiền (VNĐ)">
                    </label>
                    <details class="search-needs">
                        <summary>
                            <span class="needs-label">NHU CẦU CHUYẾN ĐI</span>
                            <span class="needs-value">Tất cả nhu cầu</span>
                            <svg class="icon chevron-down"><use href="#i-chevron"/></svg>
                        </summary>
                        <div class="needs-options" role="group" aria-label="Chọn một hoặc nhiều nhu cầu chuyến đi">
                            <?php foreach ($travelNeeds as $value => $label): ?>
                                <label class="needs-option">
                                    <input type="checkbox" name="needs[]" value="<?= $escape($value) ?>">
                                    <span><?= $escape($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <div class="search-actions-group" style="display:flex;gap:8px;align-items:center;">
                        <button class="button button-primary search-submit" type="submit"><svg class="icon"><use href="#i-search"/></svg>Tìm tour</button>
                        <a href="index.php?page=smart-search" class="button button-outline" style="border-color:var(--orange);color:var(--orange-dark);font-weight:600;display:inline-flex;align-items:center;gap:6px;height:45px;padding:0 16px;border-radius:4px;"><span aria-hidden="true">✨</span> Đề xuất thông minh</a>
                    </div>
                </div>
            </form>
            <p class="search-budget-note">Ngân sách là tổng tiền cho cả nhóm sau ưu đãi (5–9 khách -5%, từ 10 khách -8%). Hệ thống đối chiếu sở thích, mùa vụ, thời tiết &amp; gợi ý lịch tiết kiệm ±3 ngày.</p>
        </div>
    </section>

    <!-- 3. TOUR NỔI BẬT — bốn thẻ trên một hàng ở máy tính. -->
    <section id="tour-noi-bat" class="section featured-section" aria-labelledby="featured-title">
        <div class="container">
            <div class="section-heading"><div><p class="eyebrow">ONTRIP GỢI Ý</p><h2 id="featured-title">Tour nổi bật</h2><p>Những hành trình đáng cân nhắc cho chuyến đi tiếp theo.</p></div><a class="text-link" href="index.php?page=tours&amp;type=domestic">Khám phá các tour <svg class="icon"><use href="#i-arrow"/></svg></a></div>
            <!-- Chính sách ưu đãi nhóm: hiện chỉ hiển thị, chưa tính giá đặt tour. -->
            <aside class="group-offer" aria-label="Ưu đãi đặt tour theo nhóm">
                <div class="group-offer-intro">
                    <h3>Ưu đãi đặt tour theo nhóm</h3>
                    <p>Cùng một đơn, cùng một hành trình.</p>
                </div>
                <p class="group-offer-tier"><span>Nhóm 5–9 khách</span><strong>Giảm 5%</strong></p>
                <p class="group-offer-tier"><span>Nhóm từ 10 khách</span><strong>Giảm 8%</strong></p>
                <p class="group-offer-note">Tính cả người lớn và trẻ em. Giảm trên tổng tiền tour của một đơn cùng lịch khởi hành, không cộng dồn ưu đãi.</p>
            </aside>
            <div class="tour-grid">
                <?php foreach ($featuredTours as $tour): ?>
                    <article class="tour-card">
                        <div class="tour-image-wrap">
                            <img src="<?= $escape($tour['image']) ?>" alt="<?= $escape($tour['destination']) ?>" width="600" height="450" loading="lazy">
                            <span class="tour-duration"><svg class="icon"><use href="#i-clock"/></svg><?= $escape($tour['duration']) ?></span>
                        </div>
                        <div class="tour-body">
                            <p class="tour-place"><?= $escape($tour['destination']) ?></p><h3><?= $escape($tour['name']) ?></h3>
                            <p class="tour-meta"><svg class="icon"><use href="#i-pin"/></svg><?= $tour['origin'] !== '' ? 'Từ ' . $escape($tour['origin']) : 'Chưa có lịch khởi hành' ?></p>
                            <div class="tour-bottom"><div><span>Giá từ / người lớn</span><p><?= $tour['price'] !== null ? '<strong>' . number_format($tour['price'], 0, ',', '.') . '</strong><span> ₫</span>' : 'Đang cập nhật' ?></p></div><a class="tour-detail" href="index.php?page=tour-detail&amp;type=<?= $escape($tour['type']) ?>&amp;id=<?= $escape($tour['id']) ?>" aria-label="Chi tiết <?= $escape($tour['name']) ?>"><svg class="icon"><use href="#i-arrow"/></svg></a></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (!$featuredTours): ?><p class="sample-note">Chưa có tour được công bố.</p><?php endif; ?>
            <p class="sample-note">Thông tin tour và giá là dữ liệu minh họa.</p>
        </div>
    </section>

    <!-- 4. ĐIỂM ĐẾN — một ảnh lớn bên trái và bốn ảnh nhỏ bên phải. -->
    <section id="kham-pha" class="section destinations-section" aria-labelledby="destinations-title">
        <div class="container">
            <div class="section-heading">
                <div><p class="eyebrow">CHỌN MỘT NƠI, BẮT ĐẦU HÀNH TRÌNH</p><h2 id="destinations-title">Khám phá điểm đến</h2><p>Từ những miền đất quen thuộc đến một chân trời mới.</p></div>
                <div class="destination-tabs" role="tablist" aria-label="Khu vực điểm đến">
                    <button id="tab-domestic" class="destination-tab is-active" role="tab" type="button" aria-selected="true" aria-controls="diem-den-trong-nuoc" data-region="domestic">Trong nước</button>
                    <button id="tab-international" class="destination-tab" role="tab" type="button" aria-selected="false" aria-controls="diem-den-quoc-te" tabindex="-1" data-region="international">Nước ngoài</button>
                </div>
            </div>
            <?php foreach ($destinationGroups as $region => $destinations): ?>
                <div id="<?= $region === 'domestic' ? 'diem-den-trong-nuoc' : 'diem-den-quoc-te' ?>" class="destination-grid" role="tabpanel" aria-labelledby="tab-<?= $region ?>" data-region-panel="<?= $region ?>" <?= $region === 'international' ? 'hidden' : '' ?>>
                    <?php foreach ($destinations as $index => $destination): ?>
                        <a class="destination-card<?= $index === 0 ? ' destination-large' : '' ?>" href="index.php?page=tours&amp;type=<?= $escape($region) ?>&amp;destination=<?= (int) $destination['id'] ?>" aria-label="Xem tour <?= $escape($destination['name']) ?>">
                            <img src="<?= htmlspecialchars($destination['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($destination['name'], ENT_QUOTES, 'UTF-8') ?>" width="800" height="600" loading="lazy">
                            <div class="destination-caption"><div><h3><?= $escape($destination['name']) ?></h3><p><?= (int) $destination['tour_count'] ?> tour khả dụng</p></div><svg class="icon"><use href="#i-arrow"/></svg></div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (!$destinations): ?><p class="destination-empty" data-region-panel="<?= $escape($region) ?>" <?= $region === 'international' ? 'hidden' : '' ?>>Chưa có điểm đến có lịch khởi hành đang mở.</p><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
