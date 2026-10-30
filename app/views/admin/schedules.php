<?php
/**
 * Danh sách lịch khởi hành cho quản trị: lọc, xem sức chứa và chỗ còn.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var array<int, array<string, mixed>> $schedules
 * @var array{tong_lich:int, lich_mo:int, tong_cho:int, cho_da_dat:int} $stats
 * @var array{tour:string, from:string, to:string, status:string} $filters
 * @var array<int, array<string, mixed>> $tourOptions
 * @var string $message
 * @var string $todayLabel
 */
require_once __DIR__ . '/../partials/booking-format.php';
$escape = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$adminTitle = 'Lịch khởi hành';
$adminHeading = 'Lịch khởi hành & số chỗ';
$adminSubtitle = 'Theo dõi sức chứa từng chuyến, số chỗ đã đặt và hạn đặt chỗ.';
$adminActive = 'schedules';
$adminActions = '<span class="admin-date"><svg class="icon"><use href="#a-calendar"/></svg>' . $escape($todayLabel) . '</span>';
require __DIR__ . '/partials/head.php';

$statusLabels = ['open' => 'Đang mở', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy'];
$fillRate = $stats['tong_cho'] > 0 ? (int) round($stats['cho_da_dat'] * 100 / $stats['tong_cho']) : 0;
?>
            <?php if ($message !== ''): ?>
            <div class="admin-alert" role="status"><?= $escape($message) ?></div>
            <?php endif; ?>

            <section class="admin-stats" aria-label="Số liệu lịch khởi hành">
                <article class="admin-stat"><div><h2>Tổng số lịch</h2><strong><?= (int) $stats['tong_lich'] ?></strong><p>Toàn bộ lịch trong hệ thống</p></div><span class="admin-stat-icon admin-color-blue"><svg class="icon"><use href="#a-calendar"/></svg></span></article>
                <article class="admin-stat"><div><h2>Lịch đang mở</h2><strong><?= (int) $stats['lich_mo'] ?></strong><p>Còn nhận đặt, từ hôm nay</p></div><span class="admin-stat-icon admin-color-green"><svg class="icon"><use href="#a-check"/></svg></span></article>
                <article class="admin-stat"><div><h2>Chỗ đã đặt</h2><strong><?= (int) $stats['cho_da_dat'] ?><small> / <?= (int) $stats['tong_cho'] ?></small></strong><p>Tỉ lệ lấp đầy <?= $fillRate ?>%</p></div><span class="admin-stat-icon admin-color-orange"><svg class="icon"><use href="#a-seat"/></svg></span></article>
                <article class="admin-stat"><div><h2>Chỗ còn lại</h2><strong><?= max(0, (int) $stats['tong_cho'] - (int) $stats['cho_da_dat']) ?></strong><p>Tổng trên mọi lịch</p></div><span class="admin-stat-icon admin-color-purple"><svg class="icon"><use href="#a-seat"/></svg></span></article>
            </section>

            <section class="admin-panel" aria-labelledby="admin-filter-title">
                <div class="admin-panel-heading"><div><h2 id="admin-filter-title">Bộ lọc lịch</h2><p>Lọc theo tour, khoảng ngày khởi hành và trạng thái</p></div><span class="admin-count-tag"><?= count($schedules) ?> lịch</span></div>
                <form class="admin-filter" method="get" action="index.php">
                    <input type="hidden" name="action" value="schedules">
                    <label class="admin-field"><span>Tour</span>
                        <select name="tour">
                            <option value="">Tất cả tour</option>
                            <?php foreach ($tourOptions as $option): ?>
                            <option value="<?= $escape($option['ma_tour']) ?>" <?= $filters['tour'] === $option['ma_tour'] ? 'selected' : '' ?>><?= $escape($option['ten_tour']) ?> (<?= $escape(strtoupper($option['ma_tour'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="admin-field"><span>Từ ngày</span><input type="date" name="from" value="<?= $escape($filters['from']) ?>"></label>
                    <label class="admin-field"><span>Đến ngày</span><input type="date" name="to" value="<?= $escape($filters['to']) ?>"></label>
                    <label class="admin-field"><span>Trạng thái</span>
                        <select name="status">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statusLabels as $value => $label): ?>
                            <option value="<?= $escape($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="admin-filter-actions">
                        <button class="button button-primary" type="submit">Lọc lịch</button>
                        <a class="button" href="index.php?action=schedules">Xóa lọc</a>
                    </div>
                </form>
            </section>

            <section class="admin-panel" aria-labelledby="admin-schedule-table-title">
                <div class="admin-panel-heading"><div><h2 id="admin-schedule-table-title">Danh sách lịch khởi hành</h2><p>Nhấn vào một dòng để xem khách của chuyến</p></div></div>
                <div class="admin-table-scroll">
                    <table class="admin-table admin-table-wide">
                        <thead>
                            <tr>
                                <th scope="col">Tour</th>
                                <th scope="col">Ngày đi / về</th>
                                <th scope="col">Điểm đi</th>
                                <th scope="col">Chỗ</th>
                                <th scope="col">Hạn đặt</th>
                                <th scope="col">Đơn</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col"><span class="visually-hidden">Thao tác</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                            <?php
                            $seatsLeft = (int) $schedule['so_cho_con'];
                            $capacity = (int) $schedule['so_cho'];
                            $deadlinePassed = $schedule['han_dat'] !== null && strtotime((string) $schedule['han_dat']) < time();
                            ?>
                            <tr>
                                <td><small><?= $escape(strtoupper($schedule['ma_tour'])) ?></small><strong><?= $escape($schedule['ten_tour']) ?></strong></td>
                                <td><span class="admin-table-date"><?= booking_date($schedule['ngay_khoi_hanh']) ?></span><small>về <?= booking_date($schedule['ngay_ve']) ?></small></td>
                                <td><?= $escape($schedule['diem_khoi_hanh']) ?></td>
                                <td>
                                    <span class="admin-seat <?= seat_level_class($seatsLeft, $capacity) ?>"><?= $seatsLeft ?> / <?= $capacity ?> chỗ</span>
                                    <small>đã đặt <?= (int) $schedule['so_khach_da_dat'] ?></small>
                                </td>
                                <td><?= booking_datetime($schedule['han_dat']) ?><?php if ($deadlinePassed): ?><small class="admin-text-warning">Đã quá hạn</small><?php endif; ?></td>
                                <td><?= (int) $schedule['so_don'] ?></td>
                                <td><span class="booking-status <?= schedule_status_class($schedule['trang_thai']) ?>"><?= schedule_status_label($schedule['trang_thai']) ?></span></td>
                                <td class="admin-table-action"><a class="admin-link" href="index.php?action=schedule&amp;id=<?= (int) $schedule['id_lich_khoi_hanh'] ?>">Khách &amp; chỗ <svg class="icon"><use href="#a-arrow"/></svg></a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$schedules): ?>
                            <tr><td colspan="8" class="admin-empty">Không có lịch khởi hành nào khớp bộ lọc.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
<?php require __DIR__ . '/partials/foot.php'; ?>
