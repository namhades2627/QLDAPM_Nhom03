<?php
// TourController truyền thông tin tour, lịch trình, thời tiết từng ngày và các lịch còn mở vào View.
/**
 * Các biến được TourController::detail() truyền qua require.
 * @var string $page
 * @var string $tourType
 * @var string $typeTitle
 * @var string $tourRegion
 * @var bool $notFound
 * @var array<string, mixed>|null $tour
 * @var array<string, mixed>|null $selectedSchedule
 * @var array<int, array<string, mixed>> $itineraryWeather
 * @var array<int, array<string, mixed>> $relatedTours
 */
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$adultPrice = $selectedSchedule['adult_price'] ?? 0;
$childPrice = $selectedSchedule['child_price'] ?? 0;

$weatherByDay = [];
if (!empty($itineraryWeather)) {
    foreach ($itineraryWeather as $w) {
        $weatherByDay[(int) $w['day']] = $w;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $escape($notFound ? 'Không tìm thấy tour tại OnTrip.' : $tour['name']) ?>">
    <title><?= $escape($notFound ? 'Không tìm thấy tour' : $tour['name']) ?> | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="detail-page" data-tour-id="<?= $escape($tour['id'] ?? '') ?>">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="detail-main">
<?php if ($notFound): ?>
    <div class="container detail-not-found"><h1>Không tìm thấy tour</h1><p>Tour không tồn tại hoặc chưa được công bố.</p><a class="button button-primary" href="index.php?page=tours&amp;type=<?= $tourType ?>">Xem danh sách tour</a></div>
<?php else: ?>
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><a href="index.php?page=tours&amp;type=<?= $tourType ?>"><?= $typeTitle ?></a><span>/</span><span aria-current="page"><?= $escape($tour['name']) ?></span></nav>
        <header class="detail-heading">
            <div><span class="detail-kicker"><?= $typeTitle ?> <span>·</span> <?= $escape($tour['destination']) ?></span><h1><?= $escape($tour['name']) ?></h1><p>Hành trình <?= $escape($tour['duration']) ?> · <?= $tour['origin'] !== '' ? 'Khởi hành từ ' . $escape($tour['origin']) : 'Chưa có lịch khởi hành' ?></p></div>
            <span class="detail-code">Mã tour: <?= $escape($tour['id']) ?></span>
        </header>
        <div class="detail-hero"><img src="<?= $escape($tour['image']) ?>" alt="<?= $escape($tour['destination']) ?>" width="1200" height="590"></div>
        <div class="detail-facts" aria-label="Thông tin nhanh">
            <div><span>ĐIỂM ĐẾN</span><strong><?= $escape($tour['destination']) ?></strong></div>
            <div><span>THỜI LƯỢNG</span><strong><?= $escape($tour['duration']) ?></strong></div>
            <div><span>KHỞI HÀNH TỪ</span><strong><?= $escape($tour['origin'] ?: 'Đang cập nhật') ?></strong></div>
        </div>
        <div class="detail-layout">
            <div class="detail-content">
                <section class="detail-section" id="tong-quan">
                    <span class="detail-section-label">1 / GIỚI THIỆU TOUR</span><h2>Một chuyến đi, nhiều trải nghiệm</h2>
                    <p><?= nl2br($escape($tour['introduction'] ?: 'Giới thiệu tour đang được cập nhật.')) ?></p>
                </section>
                <section class="detail-section" id="lich-trinh">
                    <span class="detail-section-label">2 / LỊCH TRÌNH</span>

                    <?php if (!$tour['itinerary']): ?><p>Lịch trình đang được cập nhật.</p><?php endif; ?>
                    <div class="detail-days" data-detail-days>
                        <?php foreach ($tour['itinerary'] as $day): ?>
                        <?php
                            $dayNum = (int) $day['day'];
                            $dayWeather = $weatherByDay[$dayNum] ?? null;
                        ?>
                        <article class="detail-day" data-day-item="<?= $dayNum ?>">
                            <span class="detail-day-number"><?= str_pad((string) $dayNum, 2, '0', STR_PAD_LEFT) ?></span>
                            <div class="detail-day-body">
                                <div class="detail-day-title-row">
                                    <h3>Ngày <?= $dayNum ?> <span>·</span> <?= $escape($day['title']) ?></h3>
                                    <?php if ($dayWeather): ?>
                                    <div class="day-weather-badge" data-weather-badge="<?= $dayNum ?>">
                                        <span class="weather-icon" data-weather-icon><?= $dayWeather['icon'] ?></span>
                                        <span class="weather-temp" data-weather-temp><?= $escape($dayWeather['temp_range']) ?></span>
                                        <span class="weather-tag" data-weather-tag><?= $escape($dayWeather['badge']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($dayWeather): ?>
                                <div class="day-weather-card" data-weather-card="<?= $dayNum ?>">
                                    <div class="weather-card-meta">
                                        <span class="weather-meta-item weather-meta-date">
                                            <span aria-hidden="true">📅</span>
                                            <span data-weather-date><?= $escape($dayWeather['date_formatted']) ?></span>
                                        </span>
                                        <span class="weather-meta-item weather-meta-loc">
                                            <span aria-hidden="true">📍</span>
                                            <span data-weather-loc><?= $escape($dayWeather['location']) ?></span>
                                        </span>
                                        <span class="weather-meta-item weather-meta-rain">
                                            <span aria-hidden="true">💧</span>
                                            <span>Khả năng mưa: <strong data-weather-rain><?= (int) $dayWeather['rain'] ?>%</strong></span>
                                        </span>
                                    </div>
                                    <div class="weather-card-desc">
                                        <span class="weather-status-text" data-weather-status><?= $escape($dayWeather['status']) ?></span>
                                    </div>
                                    <?php if (!empty($dayWeather['advice'])): ?>
                                    <div class="weather-card-advice" data-weather-advice-box>
                                        <span class="advice-label">💡 Gợi ý chuẩn bị:</span>
                                        <span class="advice-content" data-weather-advice><?= $escape($dayWeather['advice']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <p class="detail-day-content"><?= nl2br($escape($day['content'])) ?></p>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <section class="detail-section" id="danh-gia"><span class="detail-section-label">3 / CẢM NHẬN</span><h2>Đánh giá tour</h2><div class="detail-review-empty"><span aria-hidden="true">☆</span><div><strong>Chưa có đánh giá</strong><p>Nhận xét của khách đã hoàn thành chuyến đi sẽ xuất hiện tại đây khi chức năng đánh giá được triển khai.</p></div></div></section>
            </div>
            <aside class="detail-sidebar" aria-label="Lịch khởi hành và tạm tính tour">
                <div class="detail-booking" <?= $selectedSchedule ? 'data-booking-preview' : '' ?> data-adult-price="<?= $adultPrice ?>" data-child-price="<?= $childPrice ?>">
                    <span class="detail-booking-label">GIÁ TỪ / NGƯỜI LỚN</span>
                    <p class="detail-price"><?= $tour['price'] !== null ? number_format($tour['price'], 0, ',', '.') . ' <small>₫</small>' : 'Đang cập nhật' ?></p>
                    <p class="detail-price-note">Giá lấy theo lịch khởi hành. Dữ liệu demo phục vụ học tập, chưa nhận đặt tour.</p>
                    <div class="detail-booking-divider"></div><h2>Lịch khởi hành</h2>
                    <?php if (!$selectedSchedule): ?>
                        <p>Chưa có lịch khởi hành đang mở nhận đặt.</p>
                    <?php else: ?>
                        <div class="detail-date-list" role="radiogroup" aria-label="Chọn lịch khởi hành">
                            <?php foreach ($tour['schedules'] as $index => $schedule): ?>
                            <label class="detail-date-option">
                                <input type="radio" name="departure" value="<?= (int) $schedule['id'] ?>" data-date="<?= $escape($schedule['date']) ?>" data-adult-price="<?= $schedule['adult_price'] ?>" data-child-price="<?= $schedule['child_price'] ?>" data-origin="<?= $escape($schedule['origin']) ?>" <?= ($selectedSchedule && (int) $schedule['id'] === (int) $selectedSchedule['id']) ? 'checked' : '' ?>>
                                <span><?= date('d/m/Y', strtotime($schedule['date'])) ?><small><?= $escape($schedule['origin']) ?></small></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="detail-seat-note">Khởi hành từ <span data-schedule-origin><?= $escape($selectedSchedule['origin']) ?></span>. Số chỗ còn lại sẽ được xử lý khi triển khai đặt tour.</p>
                        <div class="detail-booking-divider"></div><h2>Số người</h2>
                        <div class="detail-quantity-row" data-quantity="adult"><div><strong>Người lớn</strong><small><span data-unit-price><?= number_format($adultPrice, 0, ',', '.') ?> ₫</span> / khách</small></div><div class="detail-stepper"><button type="button" data-step="-1" aria-label="Giảm số người lớn">−</button><output data-count>1</output><button type="button" data-step="1" aria-label="Tăng số người lớn">+</button></div></div>
                        <div class="detail-quantity-row" data-quantity="child"><div><strong>Trẻ em</strong><small><span data-unit-price><?= number_format($childPrice, 0, ',', '.') ?> ₫</span> / khách</small></div><div class="detail-stepper"><button type="button" data-step="-1" aria-label="Giảm số trẻ em">−</button><output data-count>0</output><button type="button" data-step="1" aria-label="Tăng số trẻ em">+</button></div></div>
                        <div class="detail-booking-divider"></div>
                        <div class="detail-discount"><strong>Ưu đãi theo nhóm</strong><p>5–9 khách: giảm 5% · Từ 10 khách: giảm 8%</p><small>Áp dụng cho cùng một đơn đặt và lịch đi.</small></div>
                        <div class="detail-estimate" aria-live="polite">
                            <div><span>Người lớn × <span data-adult-count>1</span></span><strong data-adult-total><?= number_format($adultPrice, 0, ',', '.') ?> ₫</strong></div>
                            <div data-child-line hidden><span>Trẻ em × <span data-child-count>0</span></span><strong data-child-total>0 ₫</strong></div>
                            <div class="detail-estimate-subtotal"><span>Tổng trước giảm</span><strong data-subtotal><?= number_format($adultPrice, 0, ',', '.') ?> ₫</strong></div>
                            <div data-discount-line hidden><span>Ưu đãi nhóm <span data-discount-rate></span></span><strong data-discount-amount></strong></div>
                            <div class="detail-estimate-total"><span>Tổng cộng tạm tính</span><strong data-total><?= number_format($adultPrice, 0, ',', '.') ?> ₫</strong></div>
                        </div>
                    <?php endif; ?>
                    <button class="button button-primary detail-book-button" type="button" disabled>Đặt tour ngay</button><p class="detail-static-note">Chưa nhận đặt tour. Tổng tiền chỉ là tạm tính trên trình duyệt.</p>
                </div>
            </aside>
        </div>
        <?php if ($relatedTours): ?>
        <section class="detail-related" aria-labelledby="related-title">
            <div class="detail-related-heading"><div><span class="detail-section-label">TIẾP TỤC KHÁM PHÁ</span><h2 id="related-title">Tour liên quan</h2></div><div class="detail-related-controls"><button type="button" data-related-prev aria-label="Xem các tour liên quan trước">‹</button><button type="button" data-related-next aria-label="Xem các tour liên quan tiếp theo">›</button></div></div>
            <div class="detail-related-grid" data-related-grid>
                <?php foreach ($relatedTours as $index => $related): ?>
                <article class="detail-related-card" data-related-card <?= $index >= 3 ? 'hidden' : '' ?>>
                    <a href="index.php?page=tour-detail&amp;type=<?= $tourType ?>&amp;id=<?= $escape($related['id']) ?>"><img src="<?= $escape($related['image']) ?>" alt="<?= $escape($related['destination']) ?>" width="380" height="250" loading="lazy"><div><span><?= $escape($related['destination']) ?> · <?= $escape($related['duration']) ?></span><h3><?= $escape($related['name']) ?></h3><p><?= $related['price'] !== null ? 'Từ <strong>' . number_format($related['price'], 0, ',', '.') . ' ₫</strong> / người lớn' : 'Chưa có lịch khởi hành' ?></p></div></a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
