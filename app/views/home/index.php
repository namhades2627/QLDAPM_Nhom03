<?php
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
$tours = [
    [
        'image' => 'assets/images/BanaHills.jpg',
        'place' => 'ĐÀ NẴNG',
        'name' => 'Đà Nẵng – Bà Nà Hills – Phố cổ Hội An',
        'duration' => '4 ngày 3 đêm',
        'departure' => 'TP. Hồ Chí Minh',
        'price' => '11.379.000 ',
    ],
    [
        'image' => 'assets/images/SongNhoQue.jpg',
        'place' => 'HÀ GIANG',
        'name' => 'Hà Giang – Đồng Văn – Sông Nho Quế',
        'duration' => '4 ngày 3 đêm',
        'departure' => 'Hà Nội',
        'price' => '4.590.000',
    ],
    [
        'image' => 'assets/images/Valais.jpg',
        'place' => 'THỤY SĨ',
        'name' => 'Thụy Sĩ – Valais – Đường sắt Gornergrat',
        'duration' => '3 ngày 2 đêm',
        'departure' => 'TP. Hồ Chí Minh',
        'price' => '99.999.000',
    ],
    [
        'image' => 'assets/images/Kiyomizudera.jpg',
        'place' => 'NHẬT BẢN',
        'name' => 'Nhật Bản – Kyoto – chùa Kiyomizudera',
        'duration' => '6 ngày 5 đêm',
        'departure' => 'TP. Hồ Chí Minh',
        'price' => '56.999.000 ',
    ],
];
$destinationGroups = [
    'domestic' => [
        ['name' => 'Đà Nẵng', 'image' => 'assets/images/KhamPha_DN.jpg'],
        ['name' => 'Hà Giang', 'image' => 'assets/images/KhamPha_HG.jpg'],
        ['name' => 'Phú Quốc', 'image' => 'assets/images/KhamPha_PQ.jpg'],
        ['name' => 'Hà Nội', 'image' => 'assets/images/KhamPha_HN.jpg'],
        ['name' => 'Đà Lạt', 'image' => 'assets/images/KhamPha_DL.jpg'],
    ],
    'international' => [
        ['name' => 'Nhật Bản', 'image' => 'assets/images/KhamPha_JP.jpg'],
        ['name' => 'Đức', 'image' => 'assets/images/KhamPha_GM.jpg'],
        ['name' => 'Mỹ', 'image' => 'assets/images/KhamPha_US.jpg'],
        ['name' => 'Australia', 'image' => 'assets/images/KhamPha_AU.jpg'],
        ['name' => 'Trung Quốc', 'image' => 'assets/images/KhamPha_CN.jpg'],
    ],
];
// Danh mục mẫu cho bộ chọn nhiều nhu cầu; sẽ lấy từ database khi triển khai.
$travelNeeds = [
    'relaxation' => 'Nghỉ dưỡng',
    'nature' => 'Khám phá',
    'culture' => 'Văn hóa – lịch sử',
    'food' => 'Ẩm thực',
    'entertainment' => 'Vui chơi – giải trí',
    'adventure' => 'Vận động – trải nghiệm',
];
$articles = [
    ['image' => 'assets/images/news/news01.jpg', 'title' => 'Đi Pháp dịp này có an toàn?', 'date' => '15.10.2026'],
    ['image' => 'assets/images/news/news02.jpg', 'title' => 'Tour Nga có thể trở lại đỉnh sau khi khách Việt được miễn visa ', 'date' => '14.10.2026'],
    ['image' => 'assets/images/news/news03.jpg', 'title' => 'Vietnam Airlines mở đường bay thẳng Hà Nội - Hàng Châu', 'date' => '12.10.2026'],
    ['image' => 'assets/images/news/news04.jpg', 'title' => 'Việt Nam trong danh sách điểm đến mùa thu đẹp nhất thế giới', 'date' => '10.10.2026'],
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
            <div class="search-heading"><h2 id="search-title">Chuyến đi tiếp theo của bạn?</h2><p>Bắt đầu từ một điểm đến yêu thích.</p></div>
            <form class="tour-search" aria-label="Thông tin tìm tour">
                <!-- Hàng 1: thông tin hành trình và số lượng từng loại khách. -->
                <div class="search-row">
                    <label class="search-field">
                        <span>ĐIỂM ĐẾN</span>
                        <span class="field-control"><svg class="icon"><use href="#i-pin"/></svg><input type="text" name="destination" placeholder="Bạn muốn đi đâu?"></span>
                    </label>
                    <label class="search-field">
                        <span>NGÀY KHỞI HÀNH</span>
                        <span class="field-control"><input type="date" name="departure" aria-label="Ngày khởi hành"></span>
                    </label>
                    <label class="search-field">
                        <span>NGƯỜI LỚN</span>
                        <input type="number" name="adults" min="1" step="1" value="1" inputmode="numeric">
                    </label>
                    <label class="search-field">
                        <span>TRẺ EM</span>
                        <input type="number" name="children" min="0" step="1" value="0" inputmode="numeric">
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
                                    <input type="checkbox" name="needs[]" value="<?= $value ?>">
                                    <span><?= $label ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <button class="button button-primary search-submit" type="button"><svg class="icon"><use href="#i-search"/></svg>Tìm tour</button>
                </div>
            </form>
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
                <?php foreach ($tours as $tour): ?>
                    <article class="tour-card">
                        <div class="tour-image-wrap">
                            <img src="<?= $tour['image'] ?>" alt="Ảnh minh họa <?= $tour['place'] ?>" width="600" height="450" loading="lazy">
                            <span class="tour-duration"><svg class="icon"><use href="#i-clock"/></svg><?= $tour['duration'] ?></span>
                        </div>
                        <div class="tour-body">
                            <p class="tour-place"><?= $tour['place'] ?></p><h3><?= $tour['name'] ?></h3>
                            <p class="tour-meta"><svg class="icon"><use href="#i-pin"/></svg>Từ <?= $tour['departure'] ?></p>
                            <div class="tour-bottom"><div><span>Giá từ / khách</span><p><strong><?= $tour['price'] ?></strong><span> ₫</span></p></div><button class="tour-detail" type="button" aria-label="Chi tiết <?= $tour['name'] ?>"><svg class="icon"><use href="#i-arrow"/></svg></button></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
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
                        <article class="destination-card<?= $index === 0 ? ' destination-large' : '' ?>">
                            <img src="<?= $destination['image'] ?>" <?= $destination['name'] ?>" width="800" height="600" loading="lazy">
                            <div class="destination-caption"><div><h3><?= $destination['name'] ?></h3></div><svg class="icon"><use href="#i-arrow"/></svg></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 5. TIN TỨC — bốn bài mẫu, chưa có trang nội dung bài viết. -->
    <section id="tin-tuc" class="section news-section" aria-labelledby="news-title">
        <div class="container">
            <div class="section-heading"><div><p class="eyebrow">CHUYỆN TRÊN NHỮNG CUNG ĐƯỜNG</p><h2 id="news-title">Tin tức & cảm hứng</h2><p>Cẩm nang, kinh nghiệm và những gợi ý cho người thích đi.</p></div><span class="section-number" aria-hidden="true">GÓC ONTRIP <span>↗</span></span></div>
            <div class="news-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="news-card">
                        <div class="news-image-wrap"><img src="<?= $article['image'] ?>" alt="Ảnh minh họa: <?= $article['title'] ?>" width="600" height="400" loading="lazy"></div>
                        <div class="news-meta"><time datetime="<?= implode('-', array_reverse(explode('.', $article['date']))) ?>"><?= $article['date'] ?></time></div>
                        <h3><?= $article['title'] ?></h3><button class="news-read" type="button">Đọc tiếp <svg class="icon"><use href="#i-arrow"/></svg></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
