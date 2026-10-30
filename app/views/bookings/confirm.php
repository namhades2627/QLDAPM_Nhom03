<?php
/**
 * Trang xác nhận trước khi ghi đơn. BookingController::form() truyền dữ liệu qua require.
 * @var string $page
 * @var array<string, mixed> $schedule
 * @var int $adults
 * @var int $children
 * @var array{subtotal:int, rate:int, discount:int, total:int} $pricing
 * @var array<string, string> $values
 * @var array<string, string> $errors
 */
require_once __DIR__ . '/../partials/booking-format.php';
$page = 'booking';
$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$errors = $errors ?? [];
$guests = $adults + $children;
$seatsLeft = (int) $schedule['so_cho_con'];
$tourType = 'domestic';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Xác nhận thông tin đặt tour <?= $escape($schedule['ten_tour']) ?> tại OnTrip.">
    <title>Xác nhận đặt tour | OnTrip</title>
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
            <a href="index.php?page=tour-detail&amp;type=<?= $tourType ?>&amp;id=<?= $escape($schedule['ma_tour']) ?>"><?= $escape($schedule['ten_tour']) ?></a><span>/</span>
            <span aria-current="page">Xác nhận đặt tour</span>
        </nav>

        <header class="booking-heading">
            <span class="detail-section-label">BƯỚC 2 / 2 — XÁC NHẬN</span>
            <h1>Xác nhận thông tin đặt tour</h1>
            <p>Kiểm tra lại lịch khởi hành, số khách và thông tin liên hệ trước khi gửi đơn. Chỗ chỉ được giữ sau khi đơn tạo thành công.</p>
        </header>

        <?php if (!empty($errors['form'])): ?>
        <div class="booking-alert is-error" role="alert"><?= $escape($errors['form']) ?></div>
        <?php endif; ?>

        <div class="booking-layout">
            <section class="booking-summary" aria-labelledby="booking-summary-title">
                <h2 id="booking-summary-title">Chuyến đi của bạn</h2>
                <div class="booking-tour">
                    <img src="<?= $escape($schedule['duong_dan_anh'] ?: 'assets/images/placeholder.svg') ?>" alt="<?= $escape($schedule['ten_tour']) ?>" width="160" height="110" loading="lazy">
                    <div>
                        <strong><?= $escape($schedule['ten_tour']) ?></strong>
                        <span>Mã tour: <?= $escape($schedule['ma_tour']) ?> · <?= (int) $schedule['so_ngay'] ?> ngày</span>
                    </div>
                </div>
                <dl class="booking-facts">
                    <div><dt>Ngày đi</dt><dd><?= booking_date($schedule['ngay_khoi_hanh']) ?></dd></div>
                    <div><dt>Ngày về</dt><dd><?= booking_date($schedule['ngay_ve']) ?></dd></div>
                    <div><dt>Khởi hành từ</dt><dd><?= $escape($schedule['diem_khoi_hanh']) ?></dd></div>
                    <div><dt>Hạn đặt chỗ</dt><dd><?= booking_datetime($schedule['han_dat']) ?></dd></div>
                    <div><dt>Chỗ còn lại</dt><dd><?= $seatsLeft ?> / <?= (int) $schedule['so_cho'] ?> chỗ</dd></div>
                </dl>

                <table class="booking-price-table">
                    <caption class="visually-hidden">Chi tiết giá</caption>
                    <thead>
                        <tr><th scope="col">Khách</th><th scope="col">Số lượng</th><th scope="col">Đơn giá</th><th scope="col">Thành tiền</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Người lớn</td>
                            <td><?= $adults ?></td>
                            <td><?= booking_money($schedule['gia_nguoi_lon']) ?></td>
                            <td><?= booking_money($adults * (int) $schedule['gia_nguoi_lon']) ?></td>
                        </tr>
                        <tr>
                            <td>Trẻ em</td>
                            <td><?= $children ?></td>
                            <td><?= booking_money($schedule['gia_tre_em']) ?></td>
                            <td><?= booking_money($children * (int) $schedule['gia_tre_em']) ?></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr><th scope="row" colspan="3">Tổng trước ưu đãi</th><td><?= booking_money($pricing['subtotal']) ?></td></tr>
                        <?php if ($pricing['rate'] > 0): ?>
                        <tr class="booking-discount-row"><th scope="row" colspan="3">Ưu đãi nhóm <?= (int) $pricing['rate'] ?>% · <?= $guests ?> khách</th><td>−<?= booking_money($pricing['discount']) ?></td></tr>
                        <?php endif; ?>
                        <tr class="booking-total-row"><th scope="row" colspan="3">Tổng thanh toán</th><td><?= booking_money($pricing['total']) ?></td></tr>
                    </tfoot>
                </table>
                <p class="booking-note">Giá và ưu đãi được máy chủ tính lại khi tạo đơn; đơn đã đặt giữ nguyên đơn giá tại thời điểm đặt.</p>
            </section>

            <section class="booking-contact" aria-labelledby="booking-contact-title">
                <h2 id="booking-contact-title">Thông tin liên hệ</h2>
                <form class="booking-form" method="post" action="index.php?page=booking&amp;action=create" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id_lich_khoi_hanh" value="<?= (int) $schedule['id_lich_khoi_hanh'] ?>">
                    <input type="hidden" name="so_nguoi_lon" value="<?= $adults ?>">
                    <input type="hidden" name="so_tre_em" value="<?= $children ?>">
                    <input type="hidden" name="ma_tour" value="<?= $escape($schedule['ma_tour']) ?>">
                    <input type="hidden" name="type" value="<?= $tourType ?>">

                    <div class="booking-field">
                        <label for="booking-name">Họ và tên người đặt</label>
                        <input id="booking-name" name="ho_ten" type="text" maxlength="120" autocomplete="name" required
                               value="<?= $escape($values['ho_ten'] ?? '') ?>"
                               aria-invalid="<?= isset($errors['ho_ten']) ? 'true' : 'false' ?>"
                               <?= isset($errors['ho_ten']) ? 'aria-describedby="booking-name-error"' : '' ?>>
                        <?php if (isset($errors['ho_ten'])): ?><p id="booking-name-error" class="booking-field-error"><?= $escape($errors['ho_ten']) ?></p><?php endif; ?>
                    </div>
                    <div class="booking-field">
                        <label for="booking-email">Email</label>
                        <input id="booking-email" name="email" type="email" maxlength="254" inputmode="email" autocomplete="email"
                               spellcheck="false" autocapitalize="none" required
                               value="<?= $escape($values['email'] ?? '') ?>"
                               aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
                               <?= isset($errors['email']) ? 'aria-describedby="booking-email-error"' : '' ?>>
                        <?php if (isset($errors['email'])): ?><p id="booking-email-error" class="booking-field-error"><?= $escape($errors['email']) ?></p><?php endif; ?>
                    </div>
                    <div class="booking-field">
                        <label for="booking-phone">Số điện thoại</label>
                        <input id="booking-phone" name="so_dien_thoai" type="tel" maxlength="20" inputmode="tel" autocomplete="tel" required
                               value="<?= $escape($values['so_dien_thoai'] ?? '') ?>"
                               aria-invalid="<?= isset($errors['so_dien_thoai']) ? 'true' : 'false' ?>"
                               <?= isset($errors['so_dien_thoai']) ? 'aria-describedby="booking-phone-error"' : '' ?>>
                        <?php if (isset($errors['so_dien_thoai'])): ?><p id="booking-phone-error" class="booking-field-error"><?= $escape($errors['so_dien_thoai']) ?></p><?php endif; ?>
                    </div>
                    <div class="booking-field">
                        <label for="booking-note">Ghi chú (không bắt buộc)</label>
                        <textarea id="booking-note" name="ghi_chu" rows="3" maxlength="500" placeholder="Ví dụ: cần phòng gia đình, ăn chay..."><?= $escape($values['ghi_chu'] ?? '') ?></textarea>
                    </div>

                    <button class="button button-primary booking-submit" type="submit">Gửi đơn đặt tour</button>
                    <p class="booking-note">Đơn ở trạng thái chờ xác nhận vẫn giữ chỗ cho bạn cho tới khi nhân viên OnTrip liên hệ.</p>
                </form>
            </section>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
