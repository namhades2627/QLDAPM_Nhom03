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
 * @var bool $authRequired
 * @var bool $notFound
 * @var string $csrfToken
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
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
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
            <p><?= $notFound ? 'Tour này hiện không có lịch khởi hành đang mở nhận đặt.' : 'Chọn lịch khởi hành, nhập thông tin liên hệ và người tham gia, sau đó gửi yêu cầu để OnTrip xác nhận.' ?></p>
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
                    <a class="button button-primary" href="index.php?page=login">Đăng nhập</a>
                    <a class="button booking-secondary-button" href="index.php?page=register">Tạo tài khoản</a>
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
            <form class="booking-form" method="post" action="index.php?page=booking&amp;type=<?= $tourType ?>&amp;id=<?= $escape($code) ?>" data-booking-form data-adult-price="<?= (int) $adultPrice ?>" data-child-price="<?= (int) $childPrice ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <?php if ($message !== ''): ?>
                <div class="auth-message" role="alert"><?= $escape($message) ?></div>
                <?php endif; ?>

                <section class="booking-card" aria-labelledby="booking-schedule-title">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">1</span><div><h2 id="booking-schedule-title">Chọn lịch khởi hành</h2><p>Giá và số chỗ được kiểm tra lại trên máy chủ khi bạn gửi yêu cầu.</p></div></div>
                    <?php if (isset($errors['id_lich_khoi_hanh'])): ?><p class="booking-error"><?= $escape($errors['id_lich_khoi_hanh']) ?></p><?php endif; ?>
                    <div class="booking-schedules" role="radiogroup" aria-label="Lịch khởi hành">
                        <?php foreach ($schedules as $schedule): $soldOut = (int) $schedule['seats_left'] < 1; ?>
                        <label class="booking-schedule<?= $soldOut ? ' is-sold-out' : '' ?>">
                            <input type="radio" name="id_lich_khoi_hanh" value="<?= (int) $schedule['id'] ?>" data-adult-price="<?= (int) $schedule['adult_price'] ?>" data-child-price="<?= (int) $schedule['child_price'] ?>" data-seats-left="<?= (int) $schedule['seats_left'] ?>" data-date="<?= date('d/m/Y', strtotime($schedule['date'])) ?>" data-origin="<?= $escape($schedule['origin']) ?>" <?= (int) $schedule['id'] === (int) ($selectedSchedule['id'] ?? 0) ? 'checked' : '' ?> <?= $soldOut ? 'disabled' : '' ?>>
                            <span>
                                <strong><?= date('d/m/Y', strtotime($schedule['date'])) ?></strong>
                                <small><?= $escape($schedule['origin']) ?></small>
                                <small><?= $money($schedule['adult_price']) ?> / người lớn</small>
                                <em><?= $soldOut ? 'Đã hết chỗ' : 'Còn ' . (int) $schedule['seats_left'] . ' chỗ' ?></em>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="booking-card" aria-labelledby="booking-guest-title">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">2</span><div><h2 id="booking-guest-title">Người tham gia</h2><p>Mỗi khách chiếm một chỗ. Cần ít nhất một người lớn trong đơn.</p></div></div>
                    <?php if (isset($errors['khach'])): ?><p class="booking-error"><?= $escape($errors['khach']) ?></p><?php endif; ?>
                    <div class="booking-guests" data-participant-list>
                        <?php foreach ($participants as $index => $participant): ?>
                        <?php
                            $type = $participant['loai_khach'] === 'tre_em' ? 'tre_em' : 'nguoi_lon';
                            $guestLabels[$type]++;
                            $guestLabel = ($type === 'tre_em' ? 'Trẻ em ' : 'Người lớn ') . $guestLabels[$type];
                            $guestError = $errors['khach-' . $index] ?? '';
                        ?>
                        <div class="booking-guest" data-participant data-type="<?= $type ?>">
                            <input type="hidden" name="khach[loai_khach][]" value="<?= $type ?>">
                            <span class="booking-guest-label" data-participant-label><?= $escape($guestLabel) ?></span>
                            <label class="booking-field"><span>Họ và tên <b>*</b></span><input type="text" name="khach[ho_ten][]" value="<?= $escape($participant['ho_ten']) ?>" maxlength="120" autocomplete="off" aria-invalid="<?= $guestError !== '' ? 'true' : 'false' ?>" required></label>
                            <label class="booking-field"><span>Ngày sinh</span><input type="date" name="khach[ngay_sinh][]" value="<?= $escape($participant['ngay_sinh']) ?>" max="<?= date('Y-m-d') ?>" min="1900-01-01"></label>
                            <label class="booking-field"><span>Ghi chú</span><input type="text" name="khach[ghi_chu][]" value="<?= $escape($participant['ghi_chu']) ?>" maxlength="255" placeholder="Ăn chay, dị ứng..."></label>
                            <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa <?= $escape($guestLabel) ?>">Xóa</button>
                            <?php if ($guestError !== ''): ?><p class="booking-error booking-guest-error"><?= $escape($guestError) ?></p><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="booking-guest-actions">
                        <button class="booking-add-guest" type="button" data-add-participant="nguoi_lon">+ Thêm người lớn</button>
                        <button class="booking-add-guest" type="button" data-add-participant="tre_em">+ Thêm trẻ em</button>
                    </div>
                    <p class="booking-hint">Ưu đãi nhóm tính trên tổng số khách của cùng một đơn: 5–9 khách giảm 5%, từ 10 khách giảm 8%.</p>
                </section>

                <section class="booking-card" aria-labelledby="booking-contact-title">
                    <div class="booking-card-heading"><span class="booking-step" aria-hidden="true">3</span><div><h2 id="booking-contact-title">Thông tin liên hệ</h2><p>OnTrip dùng thông tin này để xác nhận đơn đặt tour.</p></div></div>
                    <div class="booking-fields">
                        <label class="booking-field"><span>Họ và tên <b>*</b></span><input type="text" name="ho_ten" value="<?= $escape($values['ho_ten']) ?>" maxlength="120" minlength="2" autocomplete="name" aria-invalid="<?= isset($errors['ho_ten']) ? 'true' : 'false' ?>" required><?php if (isset($errors['ho_ten'])): ?><em class="booking-error"><?= $escape($errors['ho_ten']) ?></em><?php endif; ?></label>
                        <label class="booking-field"><span>Email <b>*</b></span><input type="email" name="email" value="<?= $escape($values['email']) ?>" maxlength="254" autocomplete="email" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>" required><?php if (isset($errors['email'])): ?><em class="booking-error"><?= $escape($errors['email']) ?></em><?php endif; ?></label>
                        <label class="booking-field"><span>Số điện thoại <b>*</b></span><input type="tel" name="so_dien_thoai" value="<?= $escape($values['so_dien_thoai']) ?>" maxlength="20" autocomplete="tel" aria-invalid="<?= isset($errors['so_dien_thoai']) ? 'true' : 'false' ?>" required><?php if (isset($errors['so_dien_thoai'])): ?><em class="booking-error"><?= $escape($errors['so_dien_thoai']) ?></em><?php endif; ?></label>
                        <label class="booking-field"><span>Địa chỉ</span><input type="text" name="dia_chi" value="<?= $escape($values['dia_chi']) ?>" maxlength="255" autocomplete="street-address"><?php if (isset($errors['dia_chi'])): ?><em class="booking-error"><?= $escape($errors['dia_chi']) ?></em><?php endif; ?></label>
                    </div>
                    <label class="booking-field booking-field-wide"><span>Ghi chú cho OnTrip</span><textarea name="ghi_chu" rows="3" maxlength="500" placeholder="Yêu cầu riêng về phòng, ăn uống, đưa đón..."><?= $escape($values['ghi_chu']) ?></textarea><?php if (isset($errors['ghi_chu'])): ?><em class="booking-error"><?= $escape($errors['ghi_chu']) ?></em><?php endif; ?></label>
                </section>

                <div class="booking-submit-row">
                    <p class="booking-consent">Sau khi gửi, đơn ở trạng thái <strong>chờ xác nhận</strong>. Thanh toán trong bản demo chỉ là mô phỏng, không phát sinh giao dịch thật.</p>
                    <button class="button button-primary booking-submit" type="submit">Gửi yêu cầu đặt tour</button>
                </div>

                <template data-participant-template="nguoi_lon">
                    <div class="booking-guest" data-participant data-type="nguoi_lon">
                        <input type="hidden" name="khach[loai_khach][]" value="nguoi_lon">
                        <span class="booking-guest-label" data-participant-label>Người lớn</span>
                        <label class="booking-field"><span>Họ và tên <b>*</b></span><input type="text" name="khach[ho_ten][]" value="" maxlength="120" autocomplete="off" required></label>
                        <label class="booking-field"><span>Ngày sinh</span><input type="date" name="khach[ngay_sinh][]" value="" max="<?= date('Y-m-d') ?>" min="1900-01-01"></label>
                        <label class="booking-field"><span>Ghi chú</span><input type="text" name="khach[ghi_chu][]" value="" maxlength="255" placeholder="Ăn chay, dị ứng..."></label>
                        <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa người lớn">Xóa</button>
                    </div>
                </template>
                <template data-participant-template="tre_em">
                    <div class="booking-guest" data-participant data-type="tre_em">
                        <input type="hidden" name="khach[loai_khach][]" value="tre_em">
                        <span class="booking-guest-label" data-participant-label>Trẻ em</span>
                        <label class="booking-field"><span>Họ và tên <b>*</b></span><input type="text" name="khach[ho_ten][]" value="" maxlength="120" autocomplete="off" required></label>
                        <label class="booking-field"><span>Ngày sinh</span><input type="date" name="khach[ngay_sinh][]" value="" max="<?= date('Y-m-d') ?>" min="1900-01-01"></label>
                        <label class="booking-field"><span>Ghi chú</span><input type="text" name="khach[ghi_chu][]" value="" maxlength="255" placeholder="Ăn chay, dị ứng..."></label>
                        <button class="booking-guest-remove" type="button" data-remove-participant aria-label="Xóa trẻ em">Xóa</button>
                    </div>
                </template>
            </form>

            <aside class="booking-sidebar" aria-label="Tóm tắt đơn đặt">
                <div class="booking-summary" data-booking-summary>
                    <span class="detail-booking-label">TẠM TÍNH ĐƠN ĐẶT</span>
                    <h2><?= $escape($tourName) ?></h2>
                    <p class="booking-summary-meta" data-summary-schedule>
                        <?php if ($selectedSchedule): ?>
                        Khởi hành <?= date('d/m/Y', strtotime($selectedSchedule['date'])) ?> · <?= $escape($selectedSchedule['origin']) ?>
                        <?php endif; ?>
                    </p>
                    <div class="booking-summary-row"><span>Người lớn × <b data-summary-adult-count><?= (int) $totals['so_nguoi_lon'] ?></b></span><strong data-summary-adult-total><?= $money($totals['so_nguoi_lon'] * $adultPrice) ?></strong></div>
                    <div class="booking-summary-row" data-summary-child-line <?= (int) $totals['so_tre_em'] === 0 ? 'hidden' : '' ?>><span>Trẻ em × <b data-summary-child-count><?= (int) $totals['so_tre_em'] ?></b></span><strong data-summary-child-total><?= $money($totals['so_tre_em'] * $childPrice) ?></strong></div>
                    <div class="booking-summary-row booking-summary-subtotal"><span>Tổng trước ưu đãi</span><strong data-summary-subtotal><?= $money($totals['tong_goc']) ?></strong></div>
                    <div class="booking-summary-row" data-summary-discount-line <?= (int) $totals['muc_giam'] === 0 ? 'hidden' : '' ?>><span>Ưu đãi nhóm <b data-summary-discount-rate><?= (int) $totals['muc_giam'] ?>%</b></span><strong data-summary-discount-amount>−<?= $money($totals['tien_giam']) ?></strong></div>
                    <div class="booking-summary-total"><span>Tổng thanh toán</span><strong data-summary-total><?= $money($totals['tong_thanh_toan']) ?></strong></div>
                    <p class="booking-summary-note">Tổng tiền được máy chủ tính lại từ đơn giá của lịch khởi hành khi lưu đơn.</p>
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
