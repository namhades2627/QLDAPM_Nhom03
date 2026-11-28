<?php
/**
 * DonDatController::form() chuẩn bị lịch khởi hành, người tham gia và tổng tiền tạm tính.
 * Tổng tiền hiển thị chỉ là tạm tính trên trình duyệt; máy chủ tính lại khi lưu đơn.
 * @var string $tourType
 * @var string $code
 * @var array<int, array<string, mixed>> $schedules
 * @var array<string, mixed>|null $selectedSchedule
 * @var array<string, string> $values
 * @var array<int, array<string, string>> $participants
 * @var array<string, string> $errors
 * @var string $message
 * @var array<string, int> $totals
 * @var bool $isFirstPassenger
 * @var bool $authRequired
 * @var bool $notFound
 * @var string $csrfToken
 * @var string $scheduleNotice
 * @var string $loginUrl
 * @var string $registerUrl
 */
$page = 'booking';
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' ₫';
$adultPrice = $selectedSchedule['adult_price'] ?? 0;
$childPrice = $selectedSchedule['child_price'] ?? 0;
$tourName = $selectedSchedule['ten_tour'] ?? 'Tour OnTrip';
$tourDetailUrl = 'index.php?page=tour-detail&type=' . $tourType . '&id=' . rawurlencode($code);
$guestLabels = ['nguoi_lon' => 0, 'tre_em' => 0];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gửi yêu cầu đặt tour <?= $escape($tourName) ?> trên OnTrip.">
    <title>Đặt tour | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../../public/assets/css/style.css') ?>">
    <script src="assets/js/script.js?v=<?= filemtime(__DIR__ . '/../../../public/assets/js/script.js') ?>" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang">
            <a href="index.php">Trang chủ</a><span>/</span>
            <a href="<?= $escape($tourDetailUrl) ?>"><?= $escape($tourName) ?></a><span>/</span>
            <span aria-current="page">Đặt tour</span>
        </nav>

        <header class="booking-heading">
            <span class="detail-kicker">YÊU CẦU ĐẶT TOUR</span>
            <h1><?= $escape($notFound ? 'Đặt tour' : $tourName) ?></h1>
            <p><?= $notFound ? 'Tour này hiện không có lịch khởi hành đang mở nhận đặt.' : 'Nhập thông tin người tham gia và thông tin liên hệ, sau đó gửi yêu cầu để OnTrip xác nhận.' ?></p>
        </header>

        <?php if ($notFound): ?>
        <div class="booking-empty">
            <p>Bạn có thể xem các tour khác đang mở nhận đặt.</p>
            <a class="button button-primary" href="index.php?page=tours&amp;type=<?= $tourType ?>">Xem danh sách tour</a>
        </div>
        <?php elseif ($authRequired): ?>
        <div class="booking-layout">
            <section class="booking-card booking-auth-required">
                <h2>Đăng nhập để đặt tour</h2>
                <p>OnTrip lưu đơn đặt theo tài khoản để bạn theo dõi trạng thái đơn và thanh toán mô phỏng.</p>
                <div class="booking-auth-actions">
                    <a class="button button-primary" href="<?= $escape($loginUrl) ?>">Đăng nhập</a>
                    <a class="button booking-secondary-button" href="<?= $escape($registerUrl) ?>">Tạo tài khoản</a>
                </div>
            </section>
            <aside class="booking-sidebar" aria-label="Thông tin lịch khởi hành">
                <div class="booking-summary">
                    <span class="detail-booking-label">LỊCH GẦN NHẤT</span>
                    <h2><?= $escape($tourName) ?></h2>
                    <?php if ($selectedSchedule): ?>
                    <p class="booking-summary-meta">Khởi hành <?= date('d/m/Y', strtotime($selectedSchedule['date'])) ?> · <?= $escape($selectedSchedule['origin']) ?></p>
                    <div class="booking-summary-row"><span>Người lớn</span><strong><?= $money($adultPrice) ?></strong></div>
                    <div class="booking-summary-row"><span>Trẻ em</span><strong><?= $money($childPrice) ?></strong></div>
                    <div class="booking-summary-row"><span>Còn lại</span><strong><?= (int) $selectedSchedule['seats_left'] ?> chỗ</strong></div>
                    <?php endif; ?>
                    <a class="booking-back-link" href="<?= $escape($tourDetailUrl) ?>">← Xem chi tiết tour</a>
                </div>
            </aside>
        </div>
        <?php else: ?>
        <div class="booking-layout">
            <form class="booking-form" method="post" action="index.php?page=booking&amp;type=<?= $tourType ?>&amp;id=<?= $escape($code) ?>" data-booking-form data-adult-price="<?= (int) $adultPrice ?>" data-child-price="<?= (int) $childPrice ?>" data-seats-left="<?= (int) ($selectedSchedule['seats_left'] ?? 99) ?>" data-departure-date="<?= $selectedSchedule ? $escape($selectedSchedule['date']) : date('Y-m-d') ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <?php if ($selectedSchedule): ?>
                <input type="hidden" name="id_lich_khoi_hanh" value="<?= (int) $selectedSchedule['id'] ?>">
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                <div class="auth-message" role="alert"><?= $escape($message) ?></div>
                <?php endif; ?>
                <?php if (isset($errors['id_lich_khoi_hanh'])): ?>
                <div class="auth-message" role="alert"><?= $escape($errors['id_lich_khoi_hanh']) ?> <a href="<?= $escape($tourDetailUrl) ?>">Chọn lịch khác</a></div>
                <?php elseif ($scheduleNotice !== ''): ?>
                <p class="booking-hint"><?= $escape($scheduleNotice) ?> <a href="<?= $escape($tourDetailUrl) ?>">Đổi lịch khởi hành</a></p>
                <?php endif; ?>

                <section class="booking-card" aria-labelledby="booking-guest-title">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">1</span><div><h2 id="booking-guest-title">Người tham gia</h2><p>Mỗi khách chiếm một chỗ. Cần ít nhất một người lớn trong đơn.</p></div></div>
                    <?php if (isset($errors['khach'])): ?><p class="booking-error"><?= $escape($errors['khach']) ?></p><?php endif; ?>
                    <div class="booking-guests" data-participant-list>
                        <?php foreach ($participants as $index => $participant): ?>
                        <?php
                            $type = ($participant['loai_khach'] ?? '') === 'tre_em' ? 'tre_em' : 'nguoi_lon';
                            $guestLabels[$type]++;
                            $guestLabel = ($type === 'tre_em' ? 'Trẻ em ' : 'Người lớn ') . $guestLabels[$type];
                            $guestError = $errors['khach-' . $index] ?? '';
                            $gender = ($participant['gioi_tinh'] ?? 'nam') === 'nu' ? 'nu' : 'nam';
                            $cccd = $participant['cccd'] ?? '';
                            $isFirstAdult = ($type === 'nguoi_lon' && $guestLabels['nguoi_lon'] === 1);
                        ?>
                        <div class="booking-guest" data-participant data-type="<?= $type ?>">
                            <input type="hidden" data-field="loai_khach" name="passengers[<?= $index ?>][loai_khach]" value="<?= $type ?>">
                            <div class="booking-guest-header">
                                <span class="booking-guest-label" data-participant-label><?= $escape($guestLabel) ?></span>
                                <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa <?= $escape($guestLabel) ?>" <?= $isFirstAdult ? 'disabled title="Đơn đặt bắt buộc có ít nhất 1 người lớn"' : '' ?>>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Xóa
                                </button>
                            </div>
                            <div class="booking-guest-grid">
                                <label class="booking-field">
                                    <span>Họ và tên <b>*</b></span>
                                    <input type="text" data-field="ho_ten" name="passengers[<?= $index ?>][ho_ten]" value="<?= $escape($participant['ho_ten'] ?? '') ?>" maxlength="120" placeholder="<?= $type === 'tre_em' ? 'Ví dụ: Nguyễn Văn B' : 'Ví dụ: Nguyễn Văn A' ?>" autocomplete="off" aria-invalid="<?= $guestError !== '' ? 'true' : 'false' ?>" required>
                                </label>
                                <label class="booking-field">
                                    <span>Giới tính <b>*</b></span>
                                    <select data-field="gioi_tinh" name="passengers[<?= $index ?>][gioi_tinh]">
                                        <option value="nam" <?= $gender === 'nam' ? 'selected' : '' ?>>Nam</option>
                                        <option value="nu" <?= $gender === 'nu' ? 'selected' : '' ?>>Nữ</option>
                                    </select>
                                </label>
                                <label class="booking-field">
                                    <span>Ngày sinh <b>*</b> <?= $type === 'tre_em' ? '(&lt; 12 tuổi)' : '(≥ 12 tuổi)' ?></span>
                                    <input type="date" data-field="ngay_sinh" name="passengers[<?= $index ?>][ngay_sinh]" value="<?= $escape($participant['ngay_sinh'] ?? '') ?>" max="<?= date('Y-m-d') ?>" min="1900-01-01" required>
                                </label>
                                <label class="booking-field">
                                    <span>CCCD / Hộ chiếu <?= $type === 'nguoi_lon' ? '<b>*</b>' : '<small style="color:var(--muted);font-weight:normal">(không bắt buộc)</small>' ?></span>
                                    <input type="text" data-field="cccd" name="passengers[<?= $index ?>][cccd]" value="<?= $escape($cccd) ?>" maxlength="30" placeholder="<?= $type === 'nguoi_lon' ? 'Số CCCD hoặc Hộ chiếu' : 'Không bắt buộc với trẻ em' ?>" autocomplete="off" <?= $type === 'nguoi_lon' ? 'required' : '' ?>>
                                </label>
                                <label class="booking-field booking-field-full">
                                    <span>Ghi chú riêng</span>
                                    <input type="text" data-field="ghi_chu" name="passengers[<?= $index ?>][ghi_chu]" value="<?= $escape($participant['ghi_chu'] ?? '') ?>" maxlength="255" placeholder="Ăn chay, dị ứng, say xe...">
                                </label>
                            </div>
                            <?php if ($guestError !== ''): ?><p class="booking-error booking-guest-error"><?= $escape($guestError) ?></p><?php endif; ?>
                            <div class="booking-guest-age-note" data-age-warning hidden></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="booking-guest-actions">
                        <button class="booking-add-guest" type="button" data-add-participant="nguoi_lon">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Thêm người lớn
                        </button>
                        <button class="booking-add-guest" type="button" data-add-participant="tre_em">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            Thêm trẻ em
                        </button>
                        <div class="booking-seats-indicator">
                            Chỗ còn nhận: <strong data-seats-remaining><?= (int) ($selectedSchedule['seats_left'] ?? 0) ?></strong> chỗ
                        </div>
                    </div>
                    <p class="booking-error" data-seats-warning hidden>Đã đạt số chỗ trống tối đa của lịch khởi hành này.</p>
                    <p class="booking-hint">Ưu đãi nhóm tính trên tổng số khách của cùng một đơn: 5–9 khách giảm 5%, từ 10 khách giảm 10%.</p>
                </section>

                <section class="booking-card" aria-labelledby="booking-contact-title">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">2</span><div><h2 id="booking-contact-title">Thông tin người liên hệ</h2><p>OnTrip dùng thông tin người đại diện này để gửi vé và xác nhận đơn đặt tour.</p></div></div>
                    
                    <div class="booking-sync-option">
                        <label class="booking-checkbox-label">
                            <input type="checkbox" id="sync-first-passenger" name="is_first_passenger" value="1" data-sync-passenger <?= !empty($isFirstPassenger) ? 'checked' : '' ?>>
                            <span>Tôi là hành khách tham gia đầu tiên</span>
                        </label>
                        <p class="booking-sync-hint">Tự động đồng bộ họ và tên giữa Người liên hệ và Người lớn 1.</p>
                    </div>

                    <div class="booking-fields">
                        <label class="booking-field">
                            <span>Họ và tên người liên hệ <b>*</b></span>
                            <input type="text" name="ho_ten" value="<?= $escape($values['ho_ten']) ?>" maxlength="120" minlength="2" autocomplete="name" placeholder="Ví dụ: Nguyễn Văn A" aria-invalid="<?= isset($errors['ho_ten']) ? 'true' : 'false' ?>" required>
                            <?php if (isset($errors['ho_ten'])): ?><em class="booking-error"><?= $escape($errors['ho_ten']) ?></em><?php endif; ?>
                        </label>
                        <label class="booking-field">
                            <span>Email nhận xác nhận <b>*</b></span>
                            <input type="email" name="email" value="<?= $escape($values['email']) ?>" maxlength="254" autocomplete="email" placeholder="email@vidu.com" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>" required>
                            <?php if (isset($errors['email'])): ?><em class="booking-error"><?= $escape($errors['email']) ?></em><?php endif; ?>
                        </label>
                        <label class="booking-field">
                            <span>Số điện thoại <b>*</b> (10 số VN)</span>
                            <input type="tel" name="so_dien_thoai" value="<?= $escape($values['so_dien_thoai']) ?>" maxlength="10" pattern="0[0-9]{9}" autocomplete="tel" placeholder="Ví dụ: 0912345678" aria-invalid="<?= isset($errors['so_dien_thoai']) ? 'true' : 'false' ?>" required>
                            <?php if (isset($errors['so_dien_thoai'])): ?><em class="booking-error"><?= $escape($errors['so_dien_thoai']) ?></em><?php endif; ?>
                        </label>
                        <label class="booking-field">
                            <span>Địa chỉ <small style="color:var(--muted);font-weight:normal">(không bắt buộc)</small></span>
                            <input type="text" name="dia_chi" value="<?= $escape($values['dia_chi']) ?>" maxlength="255" autocomplete="street-address" placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
                            <?php if (isset($errors['dia_chi'])): ?><em class="booking-error"><?= $escape($errors['dia_chi']) ?></em><?php endif; ?>
                        </label>
                    </div>
                    <label class="booking-field booking-field-wide">
                        <span>Ghi chú chung cho đoàn</span>
                        <textarea name="ghi_chu" rows="3" maxlength="500" placeholder="Yêu cầu phòng gần nhau, đón tại điểm hẹn, chế độ ăn uống riêng cho đoàn..."><?= $escape($values['ghi_chu']) ?></textarea>
                        <?php if (isset($errors['ghi_chu'])): ?><em class="booking-error"><?= $escape($errors['ghi_chu']) ?></em><?php endif; ?>
                    </label>
                </section>

                <section class="booking-card" aria-labelledby="booking-payment-title">
                    <div class="booking-card-heading">
                        <span class="booking-step" aria-hidden="true">3</span>
                        <div>
                            <h2 id="booking-payment-title">Phương thức thanh toán &amp; Xác nhận</h2>
                            <p>Chọn phương thức thanh toán phù hợp và xác nhận điều khoản dịch vụ trước khi gửi đơn.</p>
                        </div>
                    </div>

                    <div class="booking-payment-section">
                        <span class="booking-payment-legend">Phương thức thanh toán <b>*</b></span>
                        <?php if (isset($errors['phuong_thuc_thanh_toan'])): ?>
                            <p class="booking-error"><?= $escape($errors['phuong_thuc_thanh_toan']) ?></p>
                        <?php endif; ?>

                        <div class="booking-payment-methods">
                            <label class="booking-payment-option <?= ($selectedPaymentMethod ?? 'thanh_toan_sau') === 'thanh_toan_sau' ? 'is-selected' : '' ?>">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="thanh_toan_sau" <?= ($selectedPaymentMethod ?? 'thanh_toan_sau') === 'thanh_toan_sau' ? 'checked' : '' ?> required>
                                <div class="booking-payment-content">
                                    <div class="booking-payment-header">
                                        <span class="booking-payment-icon" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        </span>
                                        <strong>Thanh toán khi nhận xác nhận</strong>
                                        <span class="booking-payment-badge">Khuyên dùng</span>
                                    </div>
                                    <p>Nhân viên OnTrip liên hệ trực tiếp qua điện thoại để kiểm tra lịch trình, chốt thông tin và hướng dẫn quý khách thanh toán linh hoạt bằng tiền mặt hoặc chuyển khoản.</p>
                                </div>
                            </label>

                            <label class="booking-payment-option <?= ($selectedPaymentMethod ?? '') === 'chuyen_khoan' ? 'is-selected' : '' ?>">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="chuyen_khoan" <?= ($selectedPaymentMethod ?? '') === 'chuyen_khoan' ? 'checked' : '' ?>>
                                <div class="booking-payment-content">
                                    <div class="booking-payment-header">
                                        <span class="booking-payment-icon" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                        </span>
                                        <strong>Chuyển khoản ngân hàng (VietQR)</strong>
                                    </div>
                                    <p>Quét mã VietQR hoặc chuyển khoản vào tài khoản ngân hàng chính thức của OnTrip kèm nội dung mã đơn hàng để được xác nhận tự động.</p>
                                </div>
                            </label>

                            <label class="booking-payment-option <?= ($selectedPaymentMethod ?? '') === 'vi_dien_tu' ? 'is-selected' : '' ?>">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="vi_dien_tu" <?= ($selectedPaymentMethod ?? '') === 'vi_dien_tu' ? 'checked' : '' ?>>
                                <div class="booking-payment-content">
                                    <div class="booking-payment-header">
                                        <span class="booking-payment-icon" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path></svg>
                                        </span>
                                        <strong>Ví điện tử (MoMo / ZaloPay)</strong>
                                    </div>
                                    <p>Thanh toán nhanh chóng và tiện lợi qua ví điện tử MoMo hoặc ZaloPay sau khi OnTrip xác nhận giữ chỗ thành công.</p>
                                </div>
                            </label>

                            <label class="booking-payment-option <?= ($selectedPaymentMethod ?? '') === 'the' ? 'is-selected' : '' ?>">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="the" <?= ($selectedPaymentMethod ?? '') === 'the' ? 'checked' : '' ?>>
                                <div class="booking-payment-content">
                                    <div class="booking-payment-header">
                                        <span class="booking-payment-icon" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line><line x1="5" y1="15" x2="9" y2="15"></line></svg>
                                        </span>
                                        <strong>Thẻ quốc tế (Visa / Mastercard)</strong>
                                    </div>
                                    <p>Thanh toán trực tuyến bằng thẻ tín dụng hoặc thẻ ghi nợ quốc tế Visa/Mastercard sau khi đơn được xác nhận.</p>
                                </div>
                            </label>

                            <label class="booking-payment-option <?= ($selectedPaymentMethod ?? '') === 'tai_van_phong' ? 'is-selected' : '' ?>">
                                <input type="radio" name="phuong_thuc_thanh_toan" value="tai_van_phong" <?= ($selectedPaymentMethod ?? '') === 'tai_van_phong' ? 'checked' : '' ?>>
                                <div class="booking-payment-content">
                                    <div class="booking-payment-header">
                                        <span class="booking-payment-icon" aria-hidden="true">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                        </span>
                                        <strong>Thanh toán tại văn phòng</strong>
                                    </div>
                                    <p>Thanh toán trực tiếp bằng tiền mặt hoặc quẹt thẻ POS tại văn phòng đại diện OnTrip (Hà Nội, Đà Nẵng hoặc TP. Hồ Chí Minh).</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="booking-terms-box">
                        <label class="booking-checkbox-label booking-terms-label">
                            <input type="checkbox" name="dong_y_dieu_khoan" value="1" data-agree-terms <?= !empty($selectedAgreeTerms) ? 'checked' : '' ?> required>
                            <span>Tôi đồng ý với <strong>điều khoản dịch vụ &amp; chính sách hủy tour</strong> của OnTrip <b>*</b></span>
                        </label>
                        <?php if (isset($errors['dong_y_dieu_khoan'])): ?>
                            <p class="booking-error"><?= $escape($errors['dong_y_dieu_khoan']) ?></p>
                        <?php endif; ?>

                        <div class="booking-terms-detail">
                            <strong>Chính sách hoàn hủy tour chuẩn OnTrip:</strong>
                            <ul>
                                <li>Hủy trước ngày khởi hành <strong>từ 7 ngày trở lên</strong>: Hoàn lại 100% chi phí đã thanh toán.</li>
                                <li>Hủy trước ngày khởi hành <strong>từ 3 đến dưới 7 ngày</strong>: Thu phí 30% giá trị tour.</li>
                                <li>Hủy trong vòng <strong>dưới 3 ngày</strong> trước khởi hành: Thu phí 100% giá trị tour.</li>
                            </ul>
                        </div>
                    </div>

                    <input type="hidden" name="ma_giam_gia" id="booking-form-promo-input" value="<?= $escape($promoCode ?? '') ?>">

                    <div class="booking-submit-row">
                        <p class="booking-consent">Sau khi gửi, đơn sẽ được lưu ở trạng thái <strong>chờ xác nhận</strong>. Đội ngũ CSKH OnTrip sẽ liên hệ xác nhận trong vòng 30 phút.</p>
                        <button class="button button-primary booking-submit" type="submit" id="booking-submit-btn">Gửi yêu cầu đặt tour</button>
                    </div>
                </section>

                <template data-participant-template="nguoi_lon">
                    <div class="booking-guest" data-participant data-type="nguoi_lon">
                        <input type="hidden" data-field="loai_khach" name="passengers[0][loai_khach]" value="nguoi_lon">
                        <div class="booking-guest-header">
                            <span class="booking-guest-label" data-participant-label>Người lớn</span>
                            <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa hành khách">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Xóa
                            </button>
                        </div>
                        <div class="booking-guest-grid">
                            <label class="booking-field">
                                <span>Họ và tên <b>*</b></span>
                                <input type="text" data-field="ho_ten" name="passengers[0][ho_ten]" value="" maxlength="120" placeholder="Ví dụ: Nguyễn Văn A" autocomplete="off" required>
                            </label>
                            <label class="booking-field">
                                <span>Giới tính <b>*</b></span>
                                <select data-field="gioi_tinh" name="passengers[0][gioi_tinh]">
                                    <option value="nam">Nam</option>
                                    <option value="nu">Nữ</option>
                                </select>
                            </label>
                            <label class="booking-field">
                                <span>Ngày sinh <b>*</b> (≥ 12 tuổi)</span>
                                <input type="date" data-field="ngay_sinh" name="passengers[0][ngay_sinh]" value="" max="<?= date('Y-m-d') ?>" min="1900-01-01" required>
                            </label>
                            <label class="booking-field">
                                <span>CCCD / Hộ chiếu <b>*</b></span>
                                <input type="text" data-field="cccd" name="passengers[0][cccd]" value="" maxlength="30" placeholder="Số CCCD hoặc Hộ chiếu" autocomplete="off" required>
                            </label>
                            <label class="booking-field booking-field-full">
                                <span>Ghi chú riêng</span>
                                <input type="text" data-field="ghi_chu" name="passengers[0][ghi_chu]" value="" maxlength="255" placeholder="Ăn chay, dị ứng, say xe...">
                            </label>
                        </div>
                        <div class="booking-guest-age-note" data-age-warning hidden></div>
                    </div>
                </template>
                <template data-participant-template="tre_em">
                    <div class="booking-guest" data-participant data-type="tre_em">
                        <input type="hidden" data-field="loai_khach" name="passengers[0][loai_khach]" value="tre_em">
                        <div class="booking-guest-header">
                            <span class="booking-guest-label" data-participant-label>Trẻ em</span>
                            <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa hành khách">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Xóa
                            </button>
                        </div>
                        <div class="booking-guest-grid">
                            <label class="booking-field">
                                <span>Họ và tên <b>*</b></span>
                                <input type="text" data-field="ho_ten" name="passengers[0][ho_ten]" value="" maxlength="120" placeholder="Ví dụ: Nguyễn Văn B" autocomplete="off" required>
                            </label>
                            <label class="booking-field">
                                <span>Giới tính <b>*</b></span>
                                <select data-field="gioi_tinh" name="passengers[0][gioi_tinh]">
                                    <option value="nam">Nam</option>
                                    <option value="nu">Nữ</option>
                                </select>
                            </label>
                            <label class="booking-field">
                                <span>Ngày sinh <b>*</b> (&lt; 12 tuổi)</span>
                                <input type="date" data-field="ngay_sinh" name="passengers[0][ngay_sinh]" value="" max="<?= date('Y-m-d') ?>" min="1900-01-01" required>
                            </label>
                            <label class="booking-field">
                                <span>CCCD / Hộ chiếu <small style="color:var(--muted);font-weight:normal">(không bắt buộc)</small></span>
                                <input type="text" data-field="cccd" name="passengers[0][cccd]" value="" maxlength="30" placeholder="Không bắt buộc với trẻ em" autocomplete="off">
                            </label>
                            <label class="booking-field booking-field-full">
                                <span>Ghi chú riêng</span>
                                <input type="text" data-field="ghi_chu" name="passengers[0][ghi_chu]" value="" maxlength="255" placeholder="Ăn chay, dị ứng, say xe...">
                            </label>
                        </div>
                        <div class="booking-guest-age-note" data-age-warning hidden></div>
                    </div>
                </template>
            </form>

            <aside class="booking-sidebar" aria-label="Tóm tắt đơn đặt">
                <div class="booking-summary" data-booking-summary>
                    <div class="booking-summary-badge">TẠM TÍNH ĐƠN ĐẶT</div>
                    <h2 class="booking-summary-tour-name"><?= $escape($tourName) ?></h2>
                    <div class="booking-summary-tour-info">
                        <p class="booking-summary-meta" data-summary-schedule>
                            <?php if ($selectedSchedule): ?>
                            <strong>Khởi hành:</strong> <?= date('d/m/Y', strtotime($selectedSchedule['date'])) ?> &bull; <strong>Từ:</strong> <?= $escape($selectedSchedule['origin']) ?>
                            <?php endif; ?>
                        </p>
                        <div class="booking-summary-seats-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Số chỗ còn nhận: <strong data-summary-seats-left><?= (int) ($selectedSchedule['seats_left'] ?? 0) ?></strong> chỗ
                        </div>
                    </div>

                    <div class="booking-summary-divider"></div>

                    <div class="booking-summary-breakdown">
                        <div class="booking-summary-row">
                            <span>Người lớn × <b data-summary-adult-count><?= (int) $totals['so_nguoi_lon'] ?></b></span>
                            <strong data-summary-adult-total><?= $money($totals['so_nguoi_lon'] * $adultPrice) ?></strong>
                        </div>
                        <div class="booking-summary-row" data-summary-child-line <?= (int) $totals['so_tre_em'] === 0 ? 'hidden' : '' ?>>
                            <span>Trẻ em × <b data-summary-child-count><?= (int) $totals['so_tre_em'] ?></b></span>
                            <strong data-summary-child-total><?= $money($totals['so_tre_em'] * $childPrice) ?></strong>
                        </div>
                        <div class="booking-summary-row booking-summary-subtotal">
                            <span>Tổng trước ưu đãi</span>
                            <strong data-summary-subtotal><?= $money($totals['tong_goc']) ?></strong>
                        </div>
                        <div class="booking-summary-row booking-summary-discount" data-summary-discount-line <?= (int) $totals['muc_giam'] === 0 ? 'hidden' : '' ?>>
                            <span>Giảm giá nhóm (<b data-summary-discount-rate><?= (int) $totals['muc_giam'] ?>%</b>)</span>
                            <strong data-summary-discount-amount>−<?= $money($totals['tien_giam']) ?></strong>
                        </div>
                        <div class="booking-summary-row booking-summary-voucher" data-summary-promo-line <?= empty($totals['tien_giam_voucher']) ? 'hidden' : '' ?>>
                            <span>Mã giảm giá (<b data-summary-promo-code><?= $escape($totals['ma_giam_gia'] ?? '') ?></b>)</span>
                            <strong data-summary-promo-amount>−<?= $money($totals['tien_giam_voucher'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="booking-promo-widget">
                        <label for="promo-input-field" class="booking-promo-label">Mã giảm giá / Ưu đãi</label>
                        <div class="booking-promo-input-group">
                            <input type="text" id="promo-input-field" class="booking-promo-input" placeholder="Ví dụ: ONTRIP10" value="<?= $escape($promoCode ?? '') ?>" autocomplete="off" spellcheck="false">
                            <button type="button" class="booking-promo-btn" data-apply-promo>Áp dụng</button>
                        </div>
                        <p class="booking-promo-message" data-promo-message hidden></p>
                        <div class="booking-promo-quick">
                            <span>Gợi ý mã:</span>
                            <button type="button" class="booking-promo-tag" data-tag-promo="ONTRIP10">ONTRIP10 (-10%)</button>
                            <button type="button" class="booking-promo-tag" data-tag-promo="HE2026">HE2026 (-200k)</button>
                            <button type="button" class="booking-promo-tag" data-tag-promo="VIPTOUR">VIPTOUR (-500k)</button>
                        </div>
                    </div>

                    <div class="booking-summary-total-card">
                        <span class="booking-summary-total-label">Tổng thanh toán</span>
                        <strong class="booking-summary-total-val" data-summary-total><?= $money($totals['tong_thanh_toan']) ?></strong>
                    </div>

                    <p class="booking-summary-note">Giá đã bao gồm toàn bộ thuế, phí và chiết khấu. Nhân viên OnTrip sẽ xác nhận chính xác trước khi thanh toán.</p>
                    <a class="booking-back-link" href="<?= $escape($tourDetailUrl) ?>">← Xem chi tiết tour</a>
                </div>
            </aside>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
