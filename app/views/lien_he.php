<?php
/**
 * View trang Liên hệ & Đội ngũ Hướng dẫn viên OnTrip.
 * @var array $guides Danh sách 4 hướng dẫn viên lấy từ database
 * @var array $values Giá trị form đã nhập
 * @var array $errors Mảng lỗi kiểm tra dữ liệu
 * @var string $successMessage Thông báo gửi thành công (nếu có)
 * @var string $page
 */

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$csrfToken = $_SESSION['csrf_token'] ?? '';
$isLoggedIn = isset($_SESSION['tai_khoan']);

$contactBanners = [
    ['image' => 'assets/images/banner4.jpg', 'alt' => 'Banner liên hệ OnTrip 1'],
    ['image' => 'assets/images/banner5.jpg', 'alt' => 'Banner liên hệ OnTrip 2'],
    ['image' => 'assets/images/banner6.jpg', 'alt' => 'Banner liên hệ OnTrip 3'],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liên hệ & Đội ngũ Hướng dẫn viên | OnTrip</title>
    <meta name="description" content="Kết nối với OnTrip để được tư vấn lịch trình tour trong nước, tour quốc tế và gặp gỡ đội ngũ 4 hướng dẫn viên tận tâm, giàu kinh nghiệm.">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=20261108-contact">
    <script src="assets/js/script.js?v=20261108-contact" defer></script>
</head>
<body class="contact-page">

<?php require __DIR__ . '/partials/header.php'; ?>

<main id="noi-dung" class="contact-main">

    <!-- 1. PHẦN TRẠNG THÁI & BREADCRUMB (NẰM GIỮA NAVBAR VÀ BANNER) -->
    <section class="contact-status-bar" aria-label="Trạng thái tư vấn">
        <div class="container">
            <div class="contact-status-inner">
                <nav class="contact-breadcrumb" aria-label="Đường dẫn trang">
                    <a href="index.php">Trang chủ</a>
                    <span aria-hidden="true">/</span>
                    <span aria-current="page">Liên hệ</span>
                </nav>
                <div class="contact-badge-pill">
                    <span class="contact-pulse-dot" aria-hidden="true"></span>
                    <span>Đội ngũ tư vấn trực tuyến · Sẵn sàng đồng hành 24/7</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. BANNER LỚN RIÊNG BIỆT CỦA TRANG LIÊN HỆ (banner4, banner5, banner6) — Thuần ảnh, tự động chuyển mỗi 7s -->
    <section class="contact-slider" aria-label="Hình ảnh trang liên hệ" aria-roledescription="trình chiếu">
        <div class="contact-slider-track">
            <?php foreach ($contactBanners as $index => $banner): ?>
                <article class="contact-slider-slide<?= $index === 0 ? ' is-active' : '' ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>" aria-label="<?= $index + 1 ?> / <?= count($contactBanners) ?>">
                    <img class="contact-slider-img" src="<?= $escape($banner['image']) ?>" alt="<?= $escape($banner['alt']) ?>" width="1600" height="900" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>">
                </article>
            <?php endforeach; ?>
        </div>
        <button class="contact-slider-arrow contact-slider-prev" type="button" aria-label="Banner trước"><svg class="icon"><use href="#i-chevron"/></svg></button>
        <button class="contact-slider-arrow contact-slider-next" type="button" aria-label="Banner tiếp theo"><svg class="icon"><use href="#i-chevron"/></svg></button>
        <div class="contact-slider-dots">
            <?php foreach ($contactBanners as $index => $banner): ?>
                <button class="contact-slider-dot<?= $index === 0 ? ' is-active' : '' ?>" type="button" aria-label="Đến banner <?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- SECTION: ĐỘI NGŨ HƯỚNG DẪN VIÊN (TRỌNG TÂM) -->
    <section class="contact-guides-section" id="doi-ngu-huong-dan-vien" aria-labelledby="guides-section-title">
        <div class="container">
            <div class="contact-section-header">
                <span class="contact-section-tag">Đội ngũ chuyên nghiệp</span>
                <h2 id="guides-section-title" class="contact-section-title">Những người bạn đồng hành của bạn</h2>
                <p class="contact-section-desc">
                    Mỗi hướng dẫn viên của OnTrip là một chuyên gia bản địa dày dặn kinh nghiệm, giàu nhiệt huyết và am hiểu sâu sắc từng điểm chạm văn hóa.
                </p>
            </div>

            <div class="contact-guides-grid">
                <?php foreach ($guides as $index => $guide): ?>
                    <article class="guide-card" data-guide-id="<?= $guide['id_tai_khoan'] ?>" style="--card-accent: <?= $escape($guide['accent_color']) ?>;">
                        <div class="guide-card-header">
                            <div class="guide-avatar-wrapper">
                                <?php if (!empty($guide['avatar_url'])): ?>
                                    <img src="<?= $escape($guide['avatar_url']) ?>" alt="Ảnh đại diện hướng dẫn viên <?= $escape($guide['ho_ten']) ?>" class="guide-avatar-img" loading="lazy">
                                <?php else: ?>
                                    <div class="guide-avatar-fallback" aria-hidden="true" style="background: linear-gradient(135deg, <?= $escape($guide['accent_color']) ?>, #202733);">
                                        <span><?= mb_strtoupper(mb_substr($guide['ho_ten'], 0, 1, 'UTF-8'), 'UTF-8') ?></span>
                                    </div>
                                <?php endif; ?>
                                <span class="guide-online-badge" title="Đang hoạt động" aria-label="Đang hoạt động"></span>
                            </div>

                            <div class="guide-meta-badge">
                                <span><?= $escape($guide['badge']) ?></span>
                            </div>
                        </div>

                        <div class="guide-card-body">
                            <h3 class="guide-name"><?= $escape($guide['ho_ten']) ?></h3>
                            <p class="guide-role-title"><?= $escape($guide['role_title']) ?></p>

                            <div class="guide-experience-pill">
                                <svg class="guide-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                                <span><?= $escape($guide['experience']) ?></span>
                            </div>

                            <p class="guide-specialty">
                                <strong>Thế mạnh:</strong> <?= $escape($guide['specialty']) ?>
                            </p>

                            <blockquote class="guide-quote">
                                “<?= $escape($guide['quote']) ?>”
                            </blockquote>
                        </div>

                        <div class="guide-card-footer">
                            <div class="guide-contact-links">
                                <?php if (!empty($guide['so_dien_thoai'])): ?>
                                    <a href="tel:<?= $escape($guide['so_dien_thoai']) ?>" class="guide-action-btn guide-phone-btn" title="Gọi trực tiếp cho <?= $escape($guide['ho_ten']) ?>" aria-label="Gọi <?= $escape($guide['so_dien_thoai']) ?>">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                        <span><?= $escape($guide['so_dien_thoai']) ?></span>
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?= $escape($guide['email']) ?>" class="guide-action-btn guide-email-btn" title="Gửi thư đến <?= $escape($guide['email']) ?>" aria-label="Gửi email">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    <span>Gửi email</span>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- SECTION: BỐ CỤC 2 CỘT THÔNG MINH (FORM TƯ VẤN & THÔNG TIN LIÊN HỆ) -->
    <section class="contact-interaction-section" aria-labelledby="form-section-title">
        <div class="container">
            <div class="contact-grid-layout">

                <!-- CỘT TRÁI: FORM LIÊN HỆ TƯƠNG TÁC -->
                <div class="contact-form-column">
                    <div class="contact-card contact-form-card">
                        <div class="contact-card-header">
                            <span class="contact-form-tag">Nhận tư vấn miễn phí</span>
                            <h2 id="form-section-title" class="contact-card-title">Gửi lời nhắn cho OnTrip</h2>
                            <p class="contact-card-desc">
                                Điền thông tin bên dưới, chuyên viên tư vấn sẽ liên hệ lại với bạn trong vòng 15 phút làm việc.
                            </p>
                        </div>

                        <?php if ($successMessage !== ''): ?>
                            <div class="contact-alert contact-alert-success" role="alert" tabindex="-1">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                <div>
                                    <strong>Gửi yêu cầu thành công!</strong>
                                    <p><?= $escape($successMessage) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors['csrf'])): ?>
                            <div class="contact-alert contact-alert-error" role="alert">
                                <strong>Lỗi:</strong> <?= $escape($errors['csrf']) ?>
                            </div>
                        <?php endif; ?>

                        <form class="contact-form" method="post" action="index.php?page=lien_he" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                            <!-- CHỌN CHỦ ĐỀ NHANH (TOPIC PILLS) -->
                            <div class="contact-field-group">
                                <label class="contact-label">Bạn đang quan tâm đến nội dung nào?</label>
                                <div class="contact-topic-pills" role="radiogroup" aria-label="Chủ đề tư vấn">
                                    <label class="topic-pill <?= $values['chu_de'] === 'tu_van_tour' ? 'is-active' : '' ?>">
                                        <input type="radio" name="chu_de" value="tu_van_tour" <?= $values['chu_de'] === 'tu_van_tour' ? 'checked' : '' ?>>
                                        <span>Tư vấn chọn tour</span>
                                    </label>
                                    <label class="topic-pill <?= $values['chu_de'] === 'dat_tour_doan' ? 'is-active' : '' ?>">
                                        <input type="radio" name="chu_de" value="dat_tour_doan" <?= $values['chu_de'] === 'dat_tour_doan' ? 'checked' : '' ?>>
                                        <span>Thiết kế tour đoàn / gia đình</span>
                                    </label>
                                    <label class="topic-pill <?= $values['chu_de'] === 'thanh_toan_ve' ? 'is-active' : '' ?>">
                                        <input type="radio" name="chu_de" value="thanh_toan_ve" <?= $values['chu_de'] === 'thanh_toan_ve' ? 'checked' : '' ?>>
                                        <span>Hỗ trợ đặt chỗ & thanh toán</span>
                                    </label>
                                    <label class="topic-pill <?= $values['chu_de'] === 'gop_y_dich_vu' ? 'is-active' : '' ?>">
                                        <input type="radio" name="chu_de" value="gop_y_dich_vu" <?= $values['chu_de'] === 'gop_y_dich_vu' ? 'checked' : '' ?>>
                                        <span>Đóng góp ý kiến & hợp tác</span>
                                    </label>
                                </div>
                            </div>

                            <div class="contact-fields-row">
                                <div class="contact-field">
                                    <label for="contact-name" class="contact-label">Họ và tên <span class="required" aria-hidden="true">*</span></label>
                                    <input type="text" id="contact-name" name="ho_ten" class="contact-input <?= !empty($errors['ho_ten']) ? 'is-invalid' : '' ?>" value="<?= $escape($values['ho_ten']) ?>" placeholder="Ví dụ: Nguyễn Văn An" required autocomplete="name">
                                    <?php if (!empty($errors['ho_ten'])): ?>
                                        <p class="contact-error" role="alert"><?= $escape($errors['ho_ten']) ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="contact-field">
                                    <label for="contact-phone" class="contact-label">Số điện thoại <span class="required" aria-hidden="true">*</span></label>
                                    <input type="tel" id="contact-phone" name="so_dien_thoai" class="contact-input <?= !empty($errors['so_dien_thoai']) ? 'is-invalid' : '' ?>" value="<?= $escape($values['so_dien_thoai']) ?>" placeholder="Ví dụ: 0912 345 678" required autocomplete="tel">
                                    <?php if (!empty($errors['so_dien_thoai'])): ?>
                                        <p class="contact-error" role="alert"><?= $escape($errors['so_dien_thoai']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="contact-field">
                                <label for="contact-email" class="contact-label">Địa chỉ email <span class="required" aria-hidden="true">*</span></label>
                                <input type="email" id="contact-email" name="email" class="contact-input <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" value="<?= $escape($values['email']) ?>" placeholder="email@example.com" required autocomplete="email">
                                <?php if (!empty($errors['email'])): ?>
                                    <p class="contact-error" role="alert"><?= $escape($errors['email']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="contact-field">
                                <label for="contact-message" class="contact-label">Nội dung bạn muốn nhắn nhủ <span class="required" aria-hidden="true">*</span></label>
                                <textarea id="contact-message" name="noi_dung" rows="4" class="contact-textarea <?= !empty($errors['noi_dung']) ? 'is-invalid' : '' ?>" placeholder="Hãy chia sẻ về điểm đến yêu thích, số lượng người tham gia hoặc thời gian dự kiến khởi hành..." required><?= $escape($values['noi_dung']) ?></textarea>
                                <?php if (!empty($errors['noi_dung'])): ?>
                                    <p class="contact-error" role="alert"><?= $escape($errors['noi_dung']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="contact-form-footer">
                                <button type="submit" class="contact-submit-btn">
                                    <span>Gửi lời nhắn ngay</span>
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                </button>
                                <p class="contact-privacy-note">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    OnTrip cam kết bảo mật 100% thông tin cá nhân và không gửi thư rác.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- CỘT PHẢI: KÊNH LIÊN HỆ TRỰC TIẾP & TRỤ SỞ ONTRIP -->
                <div class="contact-info-column">
                    <!-- THÔNG TIN LIÊN LẠC NHANH -->
                    <div class="contact-card contact-touchpoints-card">
                        <h3 class="touchpoints-title">Kênh kết nối trực tiếp</h3>

                        <div class="touchpoints-list">
                            <a href="tel:0776512174" class="touchpoint-item">
                                <div class="touchpoint-icon touchpoint-icon-phone">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                </div>
                                <div class="touchpoint-content">
                                    <strong>Hotline Hỗ trợ 24/7</strong>
                                    <span class="touchpoint-value">0776 512 174</span>
                                    <small>Tư vấn đặt tour & hỗ trợ khẩn cấp</small>
                                </div>
                            </a>

                            <a href="mailto:Nhom03@gmail.com" class="touchpoint-item">
                                <div class="touchpoint-icon touchpoint-icon-email">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                </div>
                                <div class="touchpoint-content">
                                    <strong>Hộp thư điện tử</strong>
                                    <span class="touchpoint-value">Nhom03@gmail.com</span>
                                    <small>Gửi câu hỏi hoặc yêu cầu báo giá đoàn</small>
                                </div>
                            </a>

                            <div class="touchpoint-item">
                                <div class="touchpoint-icon touchpoint-icon-time">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div class="touchpoint-content">
                                    <strong>Thời gian phục vụ</strong>
                                    <span class="touchpoint-value">08:00 – 21:00</span>
                                    <small>Tất cả các ngày trong tuần (kể cả Lễ, Tết)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- VĂN PHÒNG ONTRIP -->
                    <div class="contact-card contact-offices-card">
                        <h3 class="touchpoints-title">Văn phòng điều hành</h3>

                        <div class="office-item">
                            <div class="office-tag">Trụ sở chính</div>
                            <h4 class="office-name">OnTrip Hồ Chí Minh</h4>
                            <p class="office-address">Tầng 6, Tòa nhà Lữ Hành OnTrip, Quận 1, TP. Hồ Chí Minh</p>
                        </div>

                        <div class="office-divider" aria-hidden="true"></div>

                        <div class="office-item">
                            <div class="office-tag">Chi nhánh phía Bắc</div>
                            <h4 class="office-name">OnTrip Hà Nội</h4>
                            <p class="office-address">Số 88 Phố Cổ, Quận Hoàn Kiếm, TP. Hà Nội</p>
                        </div>
                    </div>

                    <!-- CAM KẾT DỊCH VỤ -->
                    <div class="contact-card contact-promises-card">
                        <h3 class="promises-title">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>Cam kết từ OnTrip</span>
                        </h3>
                        <ul class="promises-list">
                            <li><strong>Minh bạch chi phí:</strong> Giá công khai, không phụ phí ẩn ngoài hợp đồng.</li>
                            <li><strong>Khởi hành chuẩn giờ:</strong> Đảm bảo lịch trình đúng cam kết cho mọi đoàn.</li>
                            <li><strong>Bảo hiểm du lịch:</strong> 100% khách hàng được bảo hiểm trọn gói suốt hành trình.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- SECTION: CÂU HỎI THƯỜNG GẶP (FAQ ACCORDION) -->
    <section class="contact-faq-section" aria-labelledby="faq-section-title">
        <div class="container">
            <div class="contact-section-header">
                <span class="contact-section-tag">Giải đáp nhanh</span>
                <h2 id="faq-section-title" class="contact-section-title">Những băn khoăn thường gặp</h2>
                <p class="contact-section-desc">
                    Một vài câu trả lời nhanh giúp bạn yên tâm trước khi lên kế hoạch cùng OnTrip.
                </p>
            </div>

            <div class="contact-faq-list">
                <details class="faq-item" open>
                    <summary class="faq-question">
                        <span>Tôi có thể yêu cầu thiết kế tour riêng cho gia đình hoặc công ty không?</span>
                        <svg class="faq-chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="faq-answer">
                        <p>Hoàn toàn được! OnTrip chuyên thiết kế các tour theo yêu cầu riêng (Private Tour) cho gia đình, nhóm bạn hoặc đoàn doanh nghiệp từ 4 người trở lên. Bạn chỉ cần để lại số lượng người và ngày dự kiến khởi hành, chuyên viên sẽ tư vấn lịch trình tối ưu nhất.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        <span>Chính sách giảm giá nhóm của OnTrip được áp dụng như thế nào?</span>
                        <svg class="faq-chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="faq-answer">
                        <p>OnTrip tự động áp dụng ưu đãi nhóm trên cùng một đơn đặt: nhóm từ 5–9 khách được giảm <strong>5%</strong> trên tổng tiền tour; nhóm từ 10 khách trở lên được giảm <strong>8%</strong>. Mức giảm được máy chủ tính toán tự động và hiển thị rõ ràng tại bước đặt tour.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        <span>Làm sao để tôi liên hệ trực tiếp với hướng dẫn viên của chuyến đi?</span>
                        <svg class="faq-chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="faq-answer">
                        <p>Trước ngày khởi hành 24–48 giờ, hướng dẫn viên phụ trách chuyến đi sẽ gọi điện trực tiếp hoặc gửi tin nhắn thông báo điểm tập kết, thời tiết và những lưu ý cần chuẩn bị. Bạn cũng có thể bấm trực tiếp vào số điện thoại hoặc email của 4 hướng dẫn viên ở phần trên để được tư vấn trước.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary class="faq-question">
                        <span>Phương thức thanh toán và xác nhận đặt chỗ diễn ra như thế nào?</span>
                        <svg class="faq-chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="faq-answer">
                        <p>Sau khi đặt tour thành công, bạn có thể kiểm tra chi tiết đơn hàng trong mục “Đơn đặt của tôi”. Hệ thống OnTrip hỗ trợ thanh toán trực tuyến thuận tiện và chuông thông báo sẽ gửi cập nhật trạng thái đơn ngay khi đơn được xác nhận.</p>
                    </div>
                </details>
            </div>
        </div>
    </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

</body>
</html>
