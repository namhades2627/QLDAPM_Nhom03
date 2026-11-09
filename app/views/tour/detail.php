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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?>">
    <script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/script.js') ?>" defer></script>
</head>
<body class="detail-page" data-tour-id="<?= $escape($tour['id'] ?? '') ?>" data-tour-type="<?= $escape($tourType ?? '') ?>">
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
            <div><span>ĐÃ ĐẶT THÀNH CÔNG</span><strong><?= !empty($tour['booked_count']) ? number_format($tour['booked_count'], 0, ',', '.') . ' khách' : 'Chưa có khách' ?></strong></div>
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
                                </div>
                                <?php endif; ?>

                                <p class="detail-day-content"><?= nl2br($escape($day['content'])) ?></p>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- GỢI Ý CHUẨN BỊ CHO CHUYẾN ĐI (Nói chung 1 lần cho cả hành trình) -->
                    <div class="detail-preparation-box" data-preparation-box>
                        <div class="preparation-header">
                            <span class="preparation-badge">💡 HÀNH TRANG CHUYẾN ĐI</span>
                            <h3>Gợi ý chuẩn bị cho chuyến đi</h3>
                            <p class="preparation-subtitle" data-general-weather-summary><?= $escape($generalAdvice['weather_summary'] ?? 'Được tổng hợp tự động theo dự báo thời tiết và tính chất điểm đến của chuyến đi.') ?></p>
                        </div>
                        <div class="preparation-general-grid">
                            <div class="prep-general-card">
                                <div class="prep-card-icon">👕</div>
                                <div class="prep-card-body">
                                    <strong>Trang phục &amp; Giày dép</strong>
                                    <p data-prep-clothing><?= $escape($generalAdvice['clothing'] ?? 'Trang phục thoải mái, co giãn tốt. Mang giày thể thao hoặc sandal đế mềm để tiện di chuyển.') ?></p>
                                </div>
                            </div>
                            <div class="prep-general-card">
                                <div class="prep-card-icon">🧴</div>
                                <div class="prep-card-body">
                                    <strong>Vật dụng cá nhân</strong>
                                    <p data-prep-items><?= $escape($generalAdvice['personal_items'] ?? 'Kem chống nắng, kính râm, mũ nón, bình nước cá nhân và ô dù che mưa/nắng gấp gọn.') ?></p>
                                </div>
                            </div>
                            <div class="prep-general-card">
                                <div class="prep-card-icon">🪪</div>
                                <div class="prep-card-body">
                                    <strong>Giấy tờ tùy thân</strong>
                                    <p>Mang theo bản gốc CCCD/Hộ chiếu còn hạn; giấy khai sinh (bản sao trích lục/mộc đỏ) cho trẻ em nếu đi cùng để làm thủ tục lưu trú và di chuyển.</p>
                                </div>
                            </div>
                            <div class="prep-general-card">
                                <div class="prep-card-icon">💊</div>
                                <div class="prep-card-body">
                                    <strong>Y tế &amp; Sức khỏe</strong>
                                    <p>Nên chuẩn bị sẵn thuốc say xe/say tàu, thuốc cảm sốt, tiêu hóa, băng cá nhân và sạc dự phòng để luôn chủ động trong suốt chuyến đi.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="detail-section" id="danh-gia">
                    <div class="detail-section-header">
                        <span class="detail-section-label">3 / CẢM NHẬN</span>
                        <h2>Đánh giá từ du khách</h2>
                    </div>

                    <?php if (!empty($reviewFlash)): ?>
                        <div class="review-flash-alert review-flash-<?= $escape($reviewFlash['type']) ?>" role="alert">
                            <?= $reviewFlash['type'] === 'success' ? '✓' : '⚠' ?> <?= $escape($reviewFlash['message']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Thống kê điểm số tổng quan -->
                    <div class="review-overview-card">
                        <div class="review-score-summary">
                            <div class="review-score-big"><?= number_format($reviewStats['average'], 1) ?></div>
                            <div class="review-stars-display" aria-label="<?= $reviewStats['average'] ?> trên 5 sao">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <span class="star-icon <?= $s <= round($reviewStats['average']) ? 'star-filled' : 'star-empty' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="review-count-text">
                                <?= $reviewStats['count'] > 0 ? 'Dựa trên ' . $reviewStats['count'] . ' đánh giá' : 'Chưa có đánh giá nào' ?>
                            </div>
                        </div>

                        <div class="review-rating-breakdown">
                            <?php for ($star = 5; $star >= 1; $star--): 
                                $count = $reviewStats['stars'][$star] ?? 0;
                                $pct = $reviewStats['percentages'][$star] ?? 0;
                            ?>
                                <div class="rating-bar-row" data-star-filter="<?= $star ?>">
                                    <span class="rating-bar-label"><?= $star ?> ★</span>
                                    <div class="rating-progress-track">
                                        <div class="rating-progress-fill" style="width: <?= $pct ?>%;"></div>
                                    </div>
                                    <span class="rating-bar-count"><?= $count ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="review-action-box">
                            <?php if (isset($_SESSION['tai_khoan'])): ?>
                                <?php if ($userReview): ?>
                                    <p class="review-action-desc">Bạn đã đánh giá <strong><?= (int) $userReview['so_sao'] ?> ★</strong> cho tour này.</p>
                                    <a href="#form-danh-gia" class="btn btn-outline review-btn-scroll">Sửa đánh giá</a>
                                <?php else: ?>
                                    <p class="review-action-desc">Bạn đã đi tour này hoặc có cảm nhận muốn chia sẻ?</p>
                                    <a href="#form-danh-gia" class="btn btn-primary review-btn-scroll">Viết đánh giá ngay</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="review-action-desc">Đăng nhập để chia sẻ trải nghiệm chuyến đi của bạn cùng cộng đồng OnTrip.</p>
                                <a href="index.php?page=login&redirect=<?= urlencode('index.php?page=tour-detail&id=' . ($tour['id'] ?? '') . '#danh-gia') ?>" class="btn btn-primary">Đăng nhập để đánh giá</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form viết/sửa đánh giá nếu đã đăng nhập -->
                    <?php if (isset($_SESSION['tai_khoan'])): ?>
                        <div class="review-form-wrapper" id="form-danh-gia">
                            <h3 class="review-form-title">
                                <?= $userReview ? 'Chỉnh sửa đánh giá của bạn' : 'Chia sẻ cảm nhận về chuyến đi' ?>
                            </h3>

                            <?php if ($hasBooked): ?>
                                <div class="verified-booking-notice">
                                    <span class="notice-icon">✓</span>
                                    <span>Bạn đã đặt tour này. Đánh giá của bạn sẽ được gắn huy hiệu <strong>Đã trải nghiệm tour</strong>.</span>
                                </div>
                            <?php endif; ?>

                            <form action="index.php?page=tour-detail&id=<?= $escape($tour['id']) ?>#danh-gia" method="post" class="review-submit-form" id="tour-review-form">
                                <input type="hidden" name="action" value="submit_review">
                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                
                                <div class="form-group-rating">
                                    <label class="form-label-rating">Đánh giá chung của bạn: <span class="required">*</span></label>
                                    <div class="rating-stars-input" role="radiogroup" aria-label="Chọn số sao">
                                        <?php 
                                        $currentStar = $userReview ? (int) $userReview['so_sao'] : 5;
                                        $starLabels = [
                                            5 => 'Tuyệt vời',
                                            4 => 'Rất tốt',
                                            3 => 'Bình thường',
                                            2 => 'Tạm được',
                                            1 => 'Chưa hài lòng'
                                        ];
                                        for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" id="star-<?= $i ?>" name="so_sao" value="<?= $i ?>" <?= $currentStar === $i ? 'checked' : '' ?> required>
                                            <label for="star-<?= $i ?>" title="<?= $starLabels[$i] ?>" aria-label="<?= $i ?> sao">★</label>
                                        <?php endfor; ?>
                                        <span class="rating-current-text" id="rating-text-feedback"><?= $starLabels[$currentStar] ?? 'Tuyệt vời' ?></span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="review-title">Tiêu đề đánh giá (tùy chọn):</label>
                                    <input type="text" id="review-title" name="tieu_de" maxlength="150" placeholder="Ví dụ: Chuyến đi đáng nhớ, hướng dẫn viên chu đáo..." value="<?= $escape($userReview['tieu_de'] ?? '') ?>" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label for="review-content">Nội dung đánh giá: <span class="required">*</span></label>
                                    <textarea id="review-content" name="noi_dung" rows="4" minlength="5" maxlength="3000" required placeholder="Chia sẻ chi tiết về khách sạn, dịch vụ, điểm tham quan, ẩm thực hoặc điều bạn tâm đắc nhất..." class="form-textarea"><?= $escape($userReview['noi_dung'] ?? '') ?></textarea>
                                    <small class="form-help">Tối thiểu 5 ký tự. Hãy chia sẻ chân thực để giúp đỡ những người đi sau.</small>
                                </div>

                                <div class="review-form-actions">
                                    <button type="submit" class="btn btn-primary" id="btn-submit-review">
                                        <?= $userReview ? 'Cập nhật đánh giá' : 'Gửi đánh giá' ?>
                                    </button>
                                    <?php if ($userReview): ?>
                                        <button type="button" class="btn btn-outline-danger" id="btn-delete-review">
                                            Xóa đánh giá
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <?php if ($userReview): ?>
                                <form id="delete-review-form" action="index.php?page=tour-detail&id=<?= $escape($tour['id']) ?>#danh-gia" method="post" style="display: none;">
                                    <input type="hidden" name="action" value="delete_review">
                                    <input type="hidden" name="id_danh_gia" value="<?= (int) $userReview['id_danh_gia'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bộ lọc theo số sao và danh sách đánh giá -->
                    <div class="reviews-list-container">
                        <div class="reviews-filter-header">
                            <h3 class="reviews-list-title">Nhận xét từ du khách (<?= count($reviews) ?>)</h3>
                            <div class="review-filter-pills" id="review-filters">
                                <button type="button" class="review-pill-btn is-active" data-filter="all">Tất cả (<?= $reviewStats['count'] ?>)</button>
                                <button type="button" class="review-pill-btn" data-filter="5">5 ★ (<?= $reviewStats['stars'][5] ?>)</button>
                                <button type="button" class="review-pill-btn" data-filter="4">4 ★ (<?= $reviewStats['stars'][4] ?>)</button>
                                <button type="button" class="review-pill-btn" data-filter="3">3 ★ (<?= $reviewStats['stars'][3] ?>)</button>
                                <button type="button" class="review-pill-btn" data-filter="2">2 ★ (<?= $reviewStats['stars'][2] ?>)</button>
                                <button type="button" class="review-pill-btn" data-filter="1">1 ★ (<?= $reviewStats['stars'][1] ?>)</button>
                            </div>
                        </div>

                        <?php if (empty($reviews)): ?>
                            <div class="detail-review-empty">
                                <span aria-hidden="true">★</span>
                                <div>
                                    <strong>Chưa có đánh giá nào cho tour này</strong>
                                    <p>Hãy là người đầu tiên trải nghiệm và chia sẻ những khoảnh khắc tuyệt vời của chuyến đi!</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="review-items-list" id="review-items-wrapper">
                                <?php foreach ($reviews as $rev): 
                                    $revStars = (int) $rev['so_sao'];
                                    $userInitials = mb_substr(trim($rev['ho_ten'] ?? 'K'), 0, 1, 'UTF-8');
                                    $avatarUrl = !empty($rev['hinh_anh']) ? $rev['hinh_anh'] : null;
                                    $formattedDate = date('d/m/Y', strtotime($rev['tao_luc']));
                                ?>
                                    <article class="review-item" data-stars="<?= $revStars ?>">
                                        <div class="review-item-header">
                                            <div class="reviewer-avatar">
                                                <?php if ($avatarUrl): ?>
                                                    <img src="<?= $escape($avatarUrl) ?>" alt="<?= $escape($rev['ho_ten']) ?>" loading="lazy">
                                                <?php else: ?>
                                                    <span class="avatar-initial"><?= $escape(mb_strtoupper($userInitials, 'UTF-8')) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reviewer-info">
                                                <div class="reviewer-name-row">
                                                    <strong class="reviewer-name"><?= $escape($rev['ho_ten']) ?></strong>
                                                    <?php if (!empty($rev['da_dat_tour'])): ?>
                                                        <span class="badge-verified-buyer" title="Đã hoàn thành chuyến đi cùng OnTrip">
                                                            <span class="badge-icon">✓</span> Đã trải nghiệm tour
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="review-meta-row">
                                                    <div class="review-stars-icons" aria-label="<?= $revStars ?> sao">
                                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                                            <span class="star-icon <?= $s <= $revStars ? 'star-filled' : 'star-empty' ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <time class="review-date" datetime="<?= $rev['tao_luc'] ?>"><?= $formattedDate ?></time>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($rev['tieu_de'])): ?>
                                            <h4 class="review-item-title"><?= $escape($rev['tieu_de']) ?></h4>
                                        <?php endif; ?>

                                        <div class="review-item-content">
                                            <?= nl2br($escape($rev['noi_dung'])) ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                            <div class="review-filter-no-results" id="review-filter-empty" style="display: none;">
                                <p>Không có đánh giá nào phù hợp với số sao đã chọn.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <aside class="detail-sidebar" aria-label="Lịch khởi hành và tạm tính tour">
                <div class="detail-booking" <?= $selectedSchedule ? 'data-booking-preview' : '' ?> data-adult-price="<?= $adultPrice ?>" data-child-price="<?= $childPrice ?>">
                    <span class="detail-booking-label">GIÁ TỪ / NGƯỜI LỚN</span>
                    <p class="detail-price"><?= $tour['price'] !== null ? number_format($tour['price'], 0, ',', '.') . ' <small>₫</small>' : 'Đang cập nhật' ?></p>
                    <p class="detail-price-note">Giá lấy theo lịch khởi hành đang mở nhận đặt. Tổng tiền được OnTrip tính lại khi lưu đơn.</p>
                    <div class="detail-booking-divider"></div><h2>Lịch khởi hành</h2>
                    <?php if (!$selectedSchedule): ?>
                        <p>Chưa có lịch khởi hành đang mở nhận đặt.</p>
                    <?php else: ?>
                        <div class="detail-date-list" role="radiogroup" aria-label="Chọn lịch khởi hành">
                            <?php foreach ($tour['schedules'] as $index => $schedule): ?>
                            <label class="detail-date-option<?= (int) $schedule['seats_left'] < 1 ? ' is-sold-out' : '' ?>">
                                <input type="radio" name="departure" value="<?= (int) $schedule['id'] ?>" data-date="<?= $escape($schedule['date']) ?>" data-adult-price="<?= $schedule['adult_price'] ?>" data-child-price="<?= $schedule['child_price'] ?>" data-origin="<?= $escape($schedule['origin']) ?>" data-seats-left="<?= (int) $schedule['seats_left'] ?>" <?= ($selectedSchedule && (int) $schedule['id'] === (int) $selectedSchedule['id']) ? 'checked' : '' ?>>
                                <span><?= date('d/m/Y', strtotime($schedule['date'])) ?><small><?= $escape($schedule['origin']) ?></small><small class="detail-date-seats"><?= (int) $schedule['seats_left'] < 1 ? 'Đã hết chỗ' : 'Còn ' . (int) $schedule['seats_left'] . ' chỗ' ?></small></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="detail-seat-note">Khởi hành từ <span data-schedule-origin><?= $escape($selectedSchedule['origin']) ?></span>. Còn <span data-schedule-seats><?= (int) $selectedSchedule['seats_left'] ?></span> chỗ cho lịch đang chọn.</p>
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
                    <a class="button button-primary detail-book-button" href="index.php?page=booking&amp;type=<?= $tourType ?>&amp;id=<?= $escape($tour['id']) ?>&amp;schedule=<?= (int) $selectedSchedule['id'] ?>" data-book-link>Đặt tour ngay</a><p class="detail-static-note">Tổng tiền trên đây chỉ là tạm tính; OnTrip kiểm tra lại số chỗ và đơn giá khi bạn gửi yêu cầu.</p>
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
