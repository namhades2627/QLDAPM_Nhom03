<?php
/**
 * LichKhoiHanhController::index() chuẩn bị số liệu, danh sách, biểu mẫu hoặc danh sách khách theo chuyến.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var string $adminSection
 * @var string $avatarUrl
 * @var array $summary
 * @var array $list
 * @var array $tours
 * @var array $filters
 * @var array $statuses
 * @var Closure $schedulesUrl
 * @var string $message
 * @var bool $success
 * @var string $mode
 * @var array $values
 * @var array $errors
 * @var array|null $record
 * @var array|null $deleteSchedule
 * @var array|null $customerDetail
 */
require __DIR__ . '/partials/header.php';
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' ₫';
$isCreating = $mode === 'create';
$editing = $isCreating || ($mode === 'edit' && $record);
$fieldAttributes = static fn(string $key): string => isset($errors[$key]) ? 'aria-invalid="true" aria-describedby="schedule-' . $key . '-error"' : '';
$fieldError = static function (string $key) use ($errors, $escape): void {
    if (isset($errors[$key])) echo '<p class="admin-field-error" id="schedule-' . $key . '-error">' . $escape($errors[$key]) . '</p>';
};
$statusClasses = ['open' => 'is-confirmed', 'closed' => 'is-pending', 'cancelled' => 'is-cancelled'];
$orderStatuses = DonDatModel::STATUS_LABELS;
$orderClasses = ['cho_xac_nhan' => 'is-pending', 'da_xac_nhan' => 'is-confirmed', 'hoan_thanh' => 'is-completed', 'da_huy' => 'is-cancelled'];
$scheduleStats = [
    ['Tổng lịch khởi hành', $summary['total'], 'Toàn bộ lịch đang quản lý', 'a-calendar', 'orange'],
    ['Đang mở nhận đặt', $summary['open'], 'Lịch ở trạng thái mở', 'a-check', 'green'],
    ['Sắp khởi hành', $summary['upcoming'], 'Lịch mở từ hôm nay trở đi', 'a-tour', 'blue'],
    ['Chỗ đã đặt', $summary['booked'] . '/' . $summary['seats'], 'Tỉ lệ lấp ' . $summary['fill_rate'] . '%', 'a-users', 'purple'],
];
$tourNames = [];
foreach ($tours as $tourOption) $tourNames[(string) $tourOption['id_tour']] = $tourOption;
?>
<div class="admin-accounts admin-schedules">
    <div class="admin-page-heading admin-accounts-heading">
        <div>
            <h1>Quản lý lịch khởi hành</h1>
            <p><?= $editing ? 'Khai báo ngày đi, ngày về, hạn đặt, giá và số chỗ riêng của từng chuyến.' : ($customerDetail !== null ? 'Khách đã đặt và người tham gia của chuyến này.' : 'Theo dõi lịch mở bán, số chỗ còn lại và khách của từng chuyến.') ?></p>
        </div>
        <?php if ($editing || $deleteSchedule || $customerDetail !== null): ?>
        <a class="admin-secondary-button" href="<?= $escape($schedulesUrl()) ?>">← Danh sách lịch khởi hành</a>
        <?php else: ?>
        <a class="button button-primary admin-create-account" href="<?= $escape($schedulesUrl(['mode' => 'create', 'edit' => null, 'delete' => null, 'customers' => null])) ?>"><span aria-hidden="true">+</span> Thêm lịch khởi hành</a>
        <?php endif; ?>
    </div>

    <?php if (!$editing && $deleteSchedule === null && $customerDetail === null): ?>
    <section class="admin-stats" aria-label="Tổng quan lịch khởi hành">
        <?php foreach ($scheduleStats as [$label, $value, $note, $icon, $color]): ?>
        <article class="admin-stat"><div><h2><?= $escape($label) ?></h2><strong><?= $escape((string) $value) ?></strong><p><?= $escape($note) ?></p></div><span class="admin-stat-icon admin-color-<?= $color ?>"><svg class="icon" aria-hidden="true"><use href="#<?= $icon ?>"/></svg></span></article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
    <div class="admin-form-message<?= $success ? ' is-success' : '' ?>" role="<?= $success ? 'status' : 'alert' ?>"><?= $escape($message) ?></div>
    <?php endif; ?>

    <?php if ($deleteSchedule): $orderCount = (int) $deleteSchedule['order_count']; ?>
    <section class="admin-panel admin-managed-editor admin-delete-confirm" id="schedule-delete" aria-labelledby="schedule-delete-title">
        <div class="admin-panel-heading">
            <div><h2 id="schedule-delete-title"><?= $orderCount > 0 ? 'Lịch đã có đơn đặt' : 'Xóa lịch khởi hành?' ?></h2><p><?= $escape($deleteSchedule['ten_tour']) ?> · <?= date('d/m/Y', strtotime($deleteSchedule['ngay_khoi_hanh'])) ?></p></div>
            <svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg>
        </div>
        <form class="admin-account-form" method="post" action="<?= $escape($schedulesUrl()) ?>">
            <?php if ($orderCount > 0): ?>
            <p>Lịch đã có <?= $orderCount ?> đơn đặt nên không thể xóa vì sẽ làm mất dữ liệu đơn và người tham gia. Hãy chuyển lịch sang <strong>Đã hủy</strong> để ngừng nhận đặt và giữ lịch sử.</p>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($schedulesUrl()) ?>">Đóng</a><a class="button button-primary" href="<?= $escape($schedulesUrl(['edit' => $deleteSchedule['id_lich_khoi_hanh'], 'delete' => null])) ?>">Chuyển trạng thái</a></div>
            <?php else: ?>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="operation" value="delete">
            <input type="hidden" name="record_id" value="<?= (int) $deleteSchedule['id_lich_khoi_hanh'] ?>">
            <p>Xóa lịch <strong><?= date('d/m/Y', strtotime($deleteSchedule['ngay_khoi_hanh'])) ?></strong> của tour <strong><?= $escape($deleteSchedule['ten_tour']) ?></strong>. Thao tác này không thể hoàn tác.</p>
            <label class="admin-delete-check"><input type="checkbox" name="confirm_delete" value="1" required><span>Tôi xác nhận xóa lịch khởi hành này.</span></label>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($schedulesUrl()) ?>">Hủy bỏ</a><button class="admin-delete-submit" type="submit">Xóa lịch</button></div>
            <?php endif; ?>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($editing): ?>
    <form method="post" action="<?= $escape($schedulesUrl()) ?>" class="admin-tour-form" id="schedule-editor">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
        <input type="hidden" name="operation" value="<?= $isCreating ? 'create' : 'update' ?>">
        <input type="hidden" name="record_id" value="<?= (int) ($record['id_lich_khoi_hanh'] ?? 0) ?>">
        <section class="admin-panel admin-tour-section" aria-labelledby="schedule-information-title">
            <div class="admin-panel-heading">
                <div><h2 id="schedule-information-title"><?= $isCreating ? 'Thêm lịch khởi hành' : 'Thông tin lịch khởi hành' ?></h2><p><?= $isCreating ? 'Mỗi lịch có ngày đi, ngày về, giá và số chỗ riêng. Các mục có dấu * cần được điền.' : $escape($record['ma_tour'] . ' · ' . $record['ten_tour']) . ' — đã đặt ' . (int) $record['booked'] . '/' . (int) $record['so_cho'] . ' chỗ.' ?></p></div>
            </div>
            <div class="admin-account-form admin-managed-fields admin-schedule-fields">
                <div class="admin-form-field admin-tour-wide">
                    <label for="schedule-id_tour">Tour <span>*</span></label>
                    <select id="schedule-id_tour" name="id_tour" required <?= $fieldAttributes('id_tour') ?> <?= $record !== null && $record['booked'] > 0 ? 'data-schedule-tour-locked' : '' ?>>
                        <option value="">Chọn tour</option>
                        <?php foreach ($tours as $tourOption): $tourId = (string) $tourOption['id_tour']; ?>
                        <option value="<?= $tourId ?>" <?= $values['id_tour'] === $tourId ? 'selected' : '' ?> <?= $record !== null && $record['booked'] > 0 && (int) $record['id_tour'] !== (int) $tourId ? 'disabled' : '' ?>><?= $escape($tourOption['ma_tour'] . ' · ' . $tourOption['ten_tour']) ?><?= $tourOption['trang_thai'] !== 'published' ? ' (' . $escape(TourModel::STATUSES[$tourOption['trang_thai']] ?? $tourOption['trang_thai']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($record !== null && $record['booked'] > 0): ?><small>Lịch đã có khách đặt nên giữ cố định tour.</small><?php endif; ?>
                    <?php $fieldError('id_tour'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-ngay_khoi_hanh">Ngày khởi hành <span>*</span></label>
                    <input id="schedule-ngay_khoi_hanh" name="ngay_khoi_hanh" type="date" value="<?= $escape($values['ngay_khoi_hanh']) ?>" required <?= $fieldAttributes('ngay_khoi_hanh') ?>>
                    <?php $fieldError('ngay_khoi_hanh'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-ngay_ve">Ngày về <span>*</span></label>
                    <input id="schedule-ngay_ve" name="ngay_ve" type="date" value="<?= $escape($values['ngay_ve']) ?>" required <?= $fieldAttributes('ngay_ve') ?>>
                    <small>Bằng hoặc sau ngày khởi hành; gợi ý bằng ngày đi cộng số ngày của tour trừ 1.</small>
                    <?php $fieldError('ngay_ve'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-han_dat">Hạn đặt chỗ</label>
                    <input id="schedule-han_dat" name="han_dat" type="datetime-local" value="<?= $escape($values['han_dat']) ?>" <?= $fieldAttributes('han_dat') ?>>
                    <small>Để trống nếu không giới hạn; hệ thống vẫn chặn đặt sau ngày khởi hành.</small>
                    <?php $fieldError('han_dat'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-diem_khoi_hanh">Điểm khởi hành <span>*</span></label>
                    <input id="schedule-diem_khoi_hanh" name="diem_khoi_hanh" value="<?= $escape($values['diem_khoi_hanh']) ?>" minlength="2" maxlength="120" placeholder="Ví dụ: Hà Nội" required <?= $fieldAttributes('diem_khoi_hanh') ?>>
                    <?php $fieldError('diem_khoi_hanh'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-gia_nguoi_lon">Giá người lớn (₫) <span>*</span></label>
                    <input id="schedule-gia_nguoi_lon" name="gia_nguoi_lon" type="number" min="0" max="9999999999" step="1" value="<?= $escape($values['gia_nguoi_lon']) ?>" required <?= $fieldAttributes('gia_nguoi_lon') ?>>
                    <?php $fieldError('gia_nguoi_lon'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-gia_tre_em">Giá trẻ em (₫) <span>*</span></label>
                    <input id="schedule-gia_tre_em" name="gia_tre_em" type="number" min="0" max="9999999999" step="1" value="<?= $escape($values['gia_tre_em']) ?>" required <?= $fieldAttributes('gia_tre_em') ?>>
                    <?php $fieldError('gia_tre_em'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-so_cho">Số chỗ <span>*</span></label>
                    <input id="schedule-so_cho" name="so_cho" type="number" min="1" max="65535" step="1" value="<?= $escape($values['so_cho']) ?>" required <?= $fieldAttributes('so_cho') ?>>
                    <small>Chỗ còn lại = số chỗ trừ tổng khách của các đơn chưa hủy.<?= $record !== null ? ' Hiện đã đặt ' . (int) $record['booked'] . ' chỗ.' : '' ?></small>
                    <?php $fieldError('so_cho'); ?>
                </div>
                <div class="admin-form-field">
                    <label for="schedule-trang_thai">Trạng thái <span>*</span></label>
                    <select id="schedule-trang_thai" name="trang_thai" required <?= $fieldAttributes('trang_thai') ?>>
                        <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?= $escape($value) ?>" <?= $values['trang_thai'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Chỉ lịch <strong>Đang mở</strong>, chưa qua ngày đi, chưa hết hạn đặt và còn chỗ mới nhận đặt.</small>
                    <?php $fieldError('trang_thai'); ?>
                </div>
            </div>
            <div class="admin-form-actions">
                <a class="admin-secondary-button" href="<?= $escape($schedulesUrl()) ?>">Hủy bỏ</a>
                <?php if (!$isCreating): ?><a class="admin-secondary-button" href="<?= $escape($schedulesUrl(['customers' => $record['id_lich_khoi_hanh'], 'edit' => null])) ?>">Xem khách của chuyến</a><?php endif; ?>
                <button class="button button-primary" type="submit"><?= $isCreating ? 'Thêm lịch khởi hành' : 'Lưu thay đổi' ?></button>
            </div>
        </section>
    </form>
    <?php endif; ?>

    <?php if ($customerDetail !== null): $schedule = $customerDetail['schedule']; $totals = $customerDetail['totals']; ?>
    <section class="admin-panel admin-schedule-customers" aria-labelledby="schedule-customers-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="schedule-customers-title">Khách theo chuyến · <?= date('d/m/Y', strtotime($schedule['ngay_khoi_hanh'])) ?></h2>
                <p><?= $escape($schedule['ten_tour']) ?> · <?= $escape($schedule['diem_khoi_hanh']) ?> · về <?= $schedule['ngay_ve'] ? date('d/m/Y', strtotime($schedule['ngay_ve'])) : 'chưa cập nhật' ?></p>
            </div>
            <a class="admin-editor-close" href="<?= $escape($schedulesUrl()) ?>" aria-label="Đóng danh sách khách">×</a>
        </div>
        <div class="order-admin-facts">
            <dl class="order-facts">
                <div><dt>Mã tour</dt><dd><?= $escape($schedule['ma_tour']) ?></dd></div>
                <div><dt>Khởi hành</dt><dd><?= date('d/m/Y', strtotime($schedule['ngay_khoi_hanh'])) ?> · <?= $escape($schedule['diem_khoi_hanh']) ?></dd></div>
                <div><dt>Ngày về</dt><dd><?= $schedule['ngay_ve'] ? date('d/m/Y', strtotime($schedule['ngay_ve'])) : '—' ?></dd></div>
                <div><dt>Hạn đặt</dt><dd><?= $schedule['han_dat'] ? date('d/m/Y H:i', strtotime($schedule['han_dat'])) : 'Không giới hạn' ?></dd></div>
                <div><dt>Giá</dt><dd><?= $money($schedule['gia_nguoi_lon']) ?> / người lớn · <?= $money($schedule['gia_tre_em']) ?> / trẻ em</dd></div>
                <div><dt>Chỗ</dt><dd>Đã đặt <strong><?= (int) $schedule['booked'] ?></strong> / <?= (int) $schedule['so_cho'] ?> · còn <strong><?= (int) $schedule['seats_left'] ?></strong></dd></div>
                <div><dt>Trạng thái</dt><dd><span class="order-status <?= $statusClasses[$schedule['trang_thai']] ?? '' ?>"><?= $escape($schedule['status_label']) ?></span></dd></div>
                <div><dt>Đơn hiệu lực</dt><dd><?= (int) $totals['orders'] ?> đơn · <?= (int) $totals['guests'] ?> khách<?= $totals['cancelled_orders'] > 0 ? ' · ' . (int) $totals['cancelled_orders'] . ' đơn đã hủy' : '' ?></dd></div>
                <div><dt>Tổng thu tạm tính</dt><dd><strong><?= $money($totals['revenue']) ?></strong></dd></div>
            </dl>
        </div>
        <?php if ($customerDetail['orders']): ?>
        <div class="admin-table-scroll">
            <table class="admin-table admin-schedule-customers-table">
                <thead><tr><th scope="col">Mã đơn</th><th scope="col">Khách liên hệ</th><th scope="col">Số khách</th><th scope="col">Người tham gia</th><th scope="col">Tổng tiền</th><th scope="col">Trạng thái</th></tr></thead>
                <tbody>
                <?php foreach ($customerDetail['orders'] as $order): ?>
                    <tr class="<?= $order['trang_thai'] === 'da_huy' ? 'is-cancelled-order' : '' ?>">
                        <td><strong><?= $escape($order['ma_don']) ?></strong><small class="admin-contact-phone"><?= date('d/m/Y H:i', strtotime($order['tao_luc'])) ?></small></td>
                        <td><?= $escape($order['ho_ten']) ?><small class="admin-contact-phone"><?= $escape($order['so_dien_thoai']) ?></small><small class="admin-contact-phone"><?= $escape($order['email']) ?></small></td>
                        <td><?= (int) $order['so_nguoi_lon'] ?> NL · <?= (int) $order['so_tre_em'] ?> TE<small class="admin-contact-phone"><?= (int) $order['so_khach'] ?> khách</small></td>
                        <td>
                            <?php if ($order['participants']): ?>
                            <ul class="admin-passenger-list">
                                <?php foreach ($order['participants'] as $participant): ?>
                                <li><span class="order-participant-type"><?= $participant['loai_khach'] === 'tre_em' ? 'Trẻ em' : 'Người lớn' ?></span><strong><?= $escape($participant['ho_ten']) ?></strong><span><?= $participant['ngay_sinh'] ? date('d/m/Y', strtotime($participant['ngay_sinh'])) : '—' ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?><span class="admin-contact-phone">Chưa có thông tin</span><?php endif; ?>
                        </td>
                        <td><strong><?= $money($order['tong_thanh_toan']) ?></strong><small class="admin-contact-phone"><?= $order['ma_giao_dich'] ? 'Đã thanh toán' : 'Chưa thanh toán' ?></small></td>
                        <td><span class="order-status <?= $orderClasses[$order['trang_thai']] ?? '' ?>"><?= $escape($orderStatuses[$order['trang_thai']] ?? $order['trang_thai']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="admin-empty admin-schedule-empty"><svg class="icon" aria-hidden="true"><use href="#a-users"/></svg><strong>Chuyến này chưa có đơn đặt</strong><p>Khách đặt tour sẽ xuất hiện tại đây kèm danh sách người tham gia.</p></p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if (!$editing && $deleteSchedule === null && $customerDetail === null): ?>
    <section class="admin-panel admin-accounts-panel" aria-labelledby="schedules-list-title">
        <div class="admin-panel-heading">
            <div><h2 id="schedules-list-title">Danh sách lịch khởi hành</h2><p>Tìm theo tour hoặc điểm khởi hành; số chỗ còn lại được tính từ các đơn chưa hủy.</p></div>
            <span class="admin-count-tag"><?= (int) $list['total'] ?> lịch</span>
        </div>
        <form class="admin-accounts-filters" action="index.php" method="get" role="search" aria-label="Tìm và lọc lịch khởi hành">
            <input type="hidden" name="action" value="lich_khoi_hanh">
            <label class="admin-accounts-search"><span>Tìm kiếm</span><div><svg class="icon" aria-hidden="true"><use href="#a-search"/></svg><input type="search" name="q" value="<?= $escape($filters['q']) ?>" maxlength="120" placeholder="Mã tour, tên tour hoặc điểm khởi hành"></div></label>
            <label><span>Tour</span><select name="tour"><option value="">Tất cả tour</option><?php foreach ($tours as $tourOption): $tourId = (string) $tourOption['id_tour']; ?><option value="<?= $tourId ?>" <?= $filters['tour'] === $tourId ? 'selected' : '' ?>><?= $escape($tourOption['ma_tour'] . ' · ' . $tourOption['ten_tour']) ?></option><?php endforeach; ?></select></label>
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><?php foreach ($statuses as $value => $label): ?><option value="<?= $escape($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?></select></label>
            <label><span>Thời gian</span><select name="scope"><option value="">Mọi thời điểm</option><option value="upcoming" <?= $filters['scope'] === 'upcoming' ? 'selected' : '' ?>>Sắp khởi hành</option><option value="past" <?= $filters['scope'] === 'past' ? 'selected' : '' ?>>Đã qua ngày đi</option></select></label>
            <button class="button button-primary" type="submit">Áp dụng</button>
            <a class="admin-filter-clear" href="<?= $escape($schedulesUrl(['q' => '', 'status' => '', 'tour' => '', 'scope' => '', 'p' => 1])) ?>">Đặt lại</a>
        </form>
        <div class="admin-table-scroll">
            <table class="admin-table admin-schedules-table">
                <thead><tr><th scope="col">Tour</th><th scope="col">Khởi hành</th><th scope="col">Ngày về</th><th scope="col">Hạn đặt</th><th scope="col">Giá người lớn</th><th scope="col">Chỗ</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($list['items'] as $row): ?>
                    <tr>
                        <td><strong><?= $escape($row['ten_tour']) ?></strong><small class="admin-contact-phone"><?= $escape($row['ma_tour']) ?> · <?= (int) $row['so_ngay'] ?> ngày</small><small class="admin-contact-phone"><?= $escape($row['diem_khoi_hanh']) ?></small></td>
                        <td><strong><?= date('d/m/Y', strtotime($row['ngay_khoi_hanh'])) ?></strong><small class="admin-contact-phone"><?= $row['is_upcoming'] ? 'Sắp khởi hành' : 'Đã qua ngày đi' ?></small></td>
                        <td><?= $row['ngay_ve'] ? date('d/m/Y', strtotime($row['ngay_ve'])) : '—' ?><?php if ($row['ngay_ve'] && $row['so_ngay'] > 0): ?><small class="admin-contact-phone"><?= (int) floor((strtotime($row['ngay_ve']) - strtotime($row['ngay_khoi_hanh'])) / 86400) + 1 ?> ngày</small><?php endif; ?></td>
                        <td><?= $row['han_dat'] ? date('d/m/Y H:i', strtotime($row['han_dat'])) : 'Không giới hạn' ?><?php if ($row['deadline_passed']): ?><small class="admin-schedule-warning">Đã hết hạn đặt</small><?php endif; ?></td>
                        <td><?= $money($row['gia_nguoi_lon']) ?><small class="admin-contact-phone">Trẻ em <?= $money($row['gia_tre_em']) ?></small></td>
                        <td>
                            <div class="admin-seat-usage">
                                <strong><?= (int) $row['booked'] ?>/<?= (int) $row['so_cho'] ?></strong>
                                <span class="admin-seat-meter" role="img" aria-label="Đã đặt <?= (int) $row['booked'] ?> trên <?= (int) $row['so_cho'] ?> chỗ"><span class="admin-seat-fill<?= $row['so_cho'] > 0 && $row['booked'] * 100 / $row['so_cho'] >= 90 ? ' is-full' : '' ?>" style="width:<?= $row['so_cho'] > 0 ? min(100, (int) round($row['booked'] * 100 / $row['so_cho'])) : 0 ?>%"></span></span>
                                <small><?= $row['is_sold_out'] ? 'Đã hết chỗ' : 'Còn ' . (int) $row['seats_left'] . ' chỗ' ?></small>
                            </div>
                        </td>
                        <td><span class="order-status <?= $statusClasses[$row['trang_thai']] ?? '' ?>"><?= $escape($row['status_label']) ?></span><small class="admin-contact-phone"><?= $row['accepting'] ? 'Đang nhận đặt' : 'Không nhận đặt' ?></small></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="admin-row-button" href="<?= $escape($schedulesUrl(['edit' => $row['id_lich_khoi_hanh'], 'delete' => null, 'customers' => null, 'mode' => null])) ?>" title="Sửa lịch" aria-label="Sửa lịch <?= date('d/m/Y', strtotime($row['ngay_khoi_hanh'])) ?>"><svg class="icon" aria-hidden="true"><use href="#a-edit"/></svg><span>Sửa</span></a>
                                <a class="admin-row-button" href="<?= $escape($schedulesUrl(['customers' => $row['id_lich_khoi_hanh'], 'edit' => null])) ?>" title="Xem khách theo chuyến" aria-label="Xem khách của chuyến <?= date('d/m/Y', strtotime($row['ngay_khoi_hanh'])) ?>"><svg class="icon" aria-hidden="true"><use href="#a-users"/></svg><span>Khách</span></a>
                                <a class="admin-row-button is-delete" href="<?= $escape($schedulesUrl(['delete' => $row['id_lich_khoi_hanh'], 'edit' => null])) ?>" title="Xóa lịch" aria-label="Xóa lịch <?= date('d/m/Y', strtotime($row['ngay_khoi_hanh'])) ?>"><svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg><span>Xóa</span></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$list['items']): ?><tr><td colspan="8" class="admin-empty"><svg class="icon" aria-hidden="true"><use href="#a-calendar"/></svg><strong>Không tìm thấy lịch khởi hành phù hợp</strong><p>Thử đổi bộ lọc hoặc thêm lịch mới cho tour.</p></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-accounts-list-footer">
            <p>Hiển thị <?= count($list['items']) ?> trên <?= (int) $list['total'] ?> lịch<?= $list['pages'] > 1 ? ' · trang ' . (int) $list['page'] . '/' . (int) $list['pages'] : '' ?>.</p>
            <?php if ($list['pages'] > 1): ?>
            <nav class="admin-accounts-pagination" aria-label="Phân trang lịch khởi hành">
                <?php if ($list['page'] > 1): ?><a href="<?= $escape($schedulesUrl(['p' => $list['page'] - 1])) ?>" aria-label="Trang trước">‹</a><?php endif; ?>
                <?php
                    $pageLinks = array_unique(array_merge([1], range(max(1, $list['page'] - 2), min($list['pages'], $list['page'] + 2)), [$list['pages']]));
                    sort($pageLinks);
                    $previousPage = 0;
                    foreach ($pageLinks as $number):
                ?>
                    <?php if ($previousPage && $number > $previousPage + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                    <a href="<?= $escape($schedulesUrl(['p' => $number])) ?>" <?= $number === $list['page'] ? 'aria-current="page"' : '' ?> aria-label="Trang <?= $number ?>"><?= $number ?></a>
                <?php $previousPage = $number; endforeach; ?>
                <?php if ($list['page'] < $list['pages']): ?><a href="<?= $escape($schedulesUrl(['p' => $list['page'] + 1])) ?>" aria-label="Trang tiếp theo">›</a><?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
