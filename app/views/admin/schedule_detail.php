<?php
/**
 * Một lịch khởi hành: thông tin chuyến, sức chứa và danh sách khách theo chuyến.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var array<string, mixed> $schedule
 * @var array<int, array<string, mixed>> $passengers
 * @var array{tong_khach:int, cho_giu:int, cho_huy:int, doanh_thu:int} $summary
 * @var string $message
 * @var string $todayLabel
 */
require_once __DIR__ . '/../partials/booking-format.php';
$escape = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$capacity = (int) $schedule['so_cho'];
$seatsLeft = (int) $schedule['so_cho_con'];
$soldOut = $seatsLeft <= 0;
$adminTitle = 'Khách của chuyến ' . $schedule['ma_tour'];
$adminHeading = $schedule['ten_tour'];
$adminSubtitle = 'Mã tour ' . strtoupper($schedule['ma_tour']) . ' · khởi hành ' . booking_date($schedule['ngay_khoi_hanh']) . ' · '
    . $schedule['diem_khoi_hanh'];
$adminActive = 'schedules';
$adminActions = '<a class="button" href="index.php?action=schedules"><svg class="icon"><use href="#a-back"/></svg>Danh sách lịch</a>';
require __DIR__ . '/partials/head.php';

$statusOptions = [
    'cho_xac_nhan' => 'Chờ xác nhận',
    'da_xac_nhan' => 'Đã xác nhận',
    'hoan_thanh' => 'Hoàn thành',
    'da_huy' => 'Đã hủy (trả chỗ)',
];
$statusLabels = ['open' => 'Đang mở', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy'];
$deadlinePassed = $schedule['han_dat'] !== null && strtotime((string) $schedule['han_dat']) < time();
?>
            <?php if ($message !== ''): ?>
            <div class="admin-alert" role="status"><?= $escape($message) ?></div>
            <?php endif; ?>

            <div class="admin-detail-grid">
                <section class="admin-panel" aria-labelledby="admin-schedule-info-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-schedule-info-title">Thông tin chuyến</h2><p>Sức chứa riêng của lịch này</p></div><span class="booking-status <?= schedule_status_class($schedule['trang_thai']) ?>"><?= schedule_status_label($schedule['trang_thai']) ?></span></div>
                    <dl class="admin-facts">
                        <div><dt>Ngày đi</dt><dd><?= booking_date($schedule['ngay_khoi_hanh']) ?></dd></div>
                        <div><dt>Ngày về</dt><dd><?= booking_date($schedule['ngay_ve']) ?></dd></div>
                        <div><dt>Thời lượng</dt><dd><?= (int) $schedule['so_ngay'] ?> ngày <?= max(0, (int) $schedule['so_ngay'] - 1) ?> đêm</dd></div>
                        <div><dt>Điểm khởi hành</dt><dd><?= $escape($schedule['diem_khoi_hanh']) ?></dd></div>
                        <div><dt>Hạn đặt chỗ</dt><dd><?= booking_datetime($schedule['han_dat']) ?><?= $deadlinePassed ? ' (đã quá hạn)' : '' ?></dd></div>
                        <div><dt>Giá người lớn</dt><dd><?= booking_money($schedule['gia_nguoi_lon']) ?></dd></div>
                        <div><dt>Giá trẻ em</dt><dd><?= booking_money($schedule['gia_tre_em']) ?></dd></div>
                    </dl>
                </section>
                <section class="admin-panel" aria-labelledby="admin-schedule-seat-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-schedule-seat-title">Số chỗ</h2><p>Chỗ còn = sức chứa − số khách của đơn chưa hủy</p></div><svg class="icon"><use href="#a-seat"/></svg></div>
                    <div class="admin-seat-meter">
                        <div class="admin-seat-meter-bar"><span style="width: <?= $capacity > 0 ? min(100, (int) round($summary['cho_giu'] * 100 / $capacity)) : 0 ?>%"></span></div>
                        <div class="admin-seat-meter-labels">
                            <span><strong><?= $summary['cho_giu'] ?></strong> khách đang giữ chỗ</span>
                            <span><strong><?= $seatsLeft ?></strong> / <?= $capacity ?> chỗ còn</span>
                        </div>
                    </div>
                    <?php if ($soldOut): ?>
                    <p class="admin-note is-warning">Lịch đã kín chỗ: hệ thống sẽ chặn mọi đơn mới cho tới khi có đơn bị hủy.</p>
                    <?php elseif ($deadlinePassed): ?>
                    <p class="admin-note is-warning">Đã quá hạn đặt chỗ: khách không thể tạo đơn mới cho lịch này.</p>
                    <?php else: ?>
                    <p class="admin-note">Lịch còn nhận đặt tới <?= booking_datetime($schedule['han_dat']) ?> hoặc tới khi hết chỗ.</p>
                    <?php endif; ?>
                    <dl class="admin-facts admin-facts-inline">
                        <div><dt>Tổng đơn</dt><dd><?= count($passengers) ?></dd></div>
                        <div><dt>Khách đã hủy</dt><dd><?= $summary['cho_huy'] ?></dd></div>
                        <div><dt>Doanh thu giữ chỗ</dt><dd><?= booking_money($summary['doanh_thu']) ?></dd></div>
                    </dl>
                </section>
            </div>

            <section class="admin-panel" aria-labelledby="admin-passenger-title">
                <div class="admin-panel-heading"><div><h2 id="admin-passenger-title">Danh sách khách theo chuyến</h2><p>Đổi trạng thái đơn để xác nhận, hoàn thành hoặc hủy và trả chỗ</p></div><span class="admin-count-tag"><?= count($passengers) ?> đơn · <?= $summary['tong_khach'] ?> khách</span></div>
                <div class="admin-table-scroll">
                    <table class="admin-table admin-table-wide">
                        <thead>
                            <tr>
                                <th scope="col">Mã đơn</th>
                                <th scope="col">Khách liên hệ</th>
                                <th scope="col">Số khách</th>
                                <th scope="col">Tổng thanh toán</th>
                                <th scope="col">Đặt lúc</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($passengers as $passenger): ?>
                            <tr>
                                <td><strong><?= $escape($passenger['ma_don']) ?></strong><?php if ($passenger['ghi_chu'] !== null && $passenger['ghi_chu'] !== ''): ?><small><?= $escape($passenger['ghi_chu']) ?></small><?php endif; ?></td>
                                <td><strong><?= $escape($passenger['ho_ten']) ?></strong><small><?= $escape($passenger['so_dien_thoai']) ?> · <?= $escape($passenger['email']) ?></small></td>
                                <td><?= (int) $passenger['so_nguoi_lon'] ?> NL<small><?= (int) $passenger['so_tre_em'] ?> TE</small></td>
                                <td><?= booking_money($passenger['tong_thanh_toan']) ?><?php if ((int) $passenger['tien_giam'] > 0): ?><small>giảm <?= (int) $passenger['muc_giam'] ?>%</small><?php endif; ?></td>
                                <td><?= booking_datetime($passenger['tao_luc']) ?></td>
                                <td><span class="booking-status <?= booking_status_class($passenger['trang_thai']) ?>"><?= booking_status_label($passenger['trang_thai']) ?></span></td>
                                <td class="admin-table-action">
                                    <?php if ($passenger['trang_thai'] === 'da_huy'): ?>
                                    <span class="admin-muted">Đã hủy</span>
                                    <?php else: ?>
                                    <form class="admin-inline-form" method="post" action="index.php?action=order-status">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="id_don_dat" value="<?= (int) $passenger['id_don_dat'] ?>">
                                        <input type="hidden" name="id_lich_khoi_hanh" value="<?= (int) $schedule['id_lich_khoi_hanh'] ?>">
                                        <label class="visually-hidden" for="status-<?= (int) $passenger['id_don_dat'] ?>">Trạng thái đơn <?= $escape($passenger['ma_don']) ?></label>
                                        <select id="status-<?= (int) $passenger['id_don_dat'] ?>" name="trang_thai">
                                            <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= $escape($value) ?>" <?= $passenger['trang_thai'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="admin-small-button" type="submit">Lưu</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$passengers): ?>
                            <tr><td colspan="7" class="admin-empty">Chuyến này chưa có khách đặt.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/partials/foot.php'; ?>
