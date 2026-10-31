<?php
/**
 * AdminController::donDat() chuẩn bị số liệu, danh sách hoặc chi tiết một đơn đặt tour.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var string $adminSection
 * @var string $avatarUrl
 * @var array $orderSummary
 * @var array $orderList
 * @var array $orderFilters
 * @var Closure $ordersUrl
 * @var string $orderMessage
 * @var bool $orderSuccess
 * @var array|null $orderDetail
 * @var array $statusLabels
 * @var array $paymentMethods
 */
require __DIR__ . '/partials/header.php';
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' ₫';
$statusClasses = [
    'cho_xac_nhan' => 'is-pending',
    'da_xac_nhan' => 'is-confirmed',
    'hoan_thanh' => 'is-completed',
    'da_huy' => 'is-cancelled',
];
$orderStats = [
    ['Tổng đơn đặt', $orderSummary['total'], 'Đơn khách đã gửi yêu cầu', 'a-tour', 'orange'],
    ['Chờ xác nhận', $orderSummary['cho_xac_nhan'], 'Cần kiểm tra và xác nhận', 'a-calendar', 'blue'],
    ['Đã xác nhận', $orderSummary['da_xac_nhan'], 'Đã giữ chỗ cho khách', 'a-check', 'green'],
    ['Đã hủy', $orderSummary['da_huy'], 'Đơn không tiếp tục xử lý', 'a-lock', 'purple'],
];
?>
<div class="admin-accounts admin-orders">
    <div class="admin-page-heading admin-accounts-heading">
        <div><h1>Quản lý đơn đặt</h1><p>Theo dõi yêu cầu đặt tour, xác nhận đơn và ghi nhận thanh toán mô phỏng.</p></div>
        <span class="admin-count-tag">Doanh thu đã thu: <?= $money($orderSummary['doanh_thu']) ?></span>
    </div>
    <?php if ($orderDetail === null): ?>
    <section class="admin-stats" aria-label="Tổng quan đơn đặt">
        <?php foreach ($orderStats as [$label, $value, $note, $icon, $color]): ?>
        <article class="admin-stat"><div><h2><?= $escape($label) ?></h2><strong><?= (int) $value ?></strong><p><?= $escape($note) ?></p></div><span class="admin-stat-icon admin-color-<?= $color ?>"><svg class="icon"><use href="#<?= $icon ?>"/></svg></span></article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if ($orderMessage !== ''): ?>
    <div class="admin-form-message<?= $orderSuccess ? ' is-success' : '' ?>" role="<?= $orderSuccess ? 'status' : 'alert' ?>"><?= $escape($orderMessage) ?></div>
    <?php endif; ?>

    <?php if ($orderDetail !== null): ?>
    <?php
        $status = $orderDetail['trang_thai'];
        $nextStatuses = DonDatModel::allowedTransitions($status);
    ?>
    <section class="admin-panel" aria-labelledby="order-detail-title">
        <div class="admin-panel-heading">
            <div><h2 id="order-detail-title">Đơn <?= $escape($orderDetail['ma_don']) ?></h2><p>Đặt lúc <?= date('d/m/Y H:i', strtotime($orderDetail['tao_luc'])) ?> · <span class="order-status <?= $statusClasses[$status] ?? '' ?>"><?= $escape($statusLabels[$status] ?? $status) ?></span></p></div>
            <a class="admin-editor-close" href="<?= $escape($ordersUrl(['id' => null, 'p' => 1])) ?>" aria-label="Đóng chi tiết đơn">×</a>
        </div>
        <div class="order-admin-facts">
            <dl class="order-facts">
                <div><dt>Tour</dt><dd><?= $escape($orderDetail['ten_tour']) ?></dd></div>
                <div><dt>Mã tour</dt><dd><?= $escape($orderDetail['ma_tour']) ?></dd></div>
                <div><dt>Khởi hành</dt><dd><?= date('d/m/Y', strtotime($orderDetail['ngay_khoi_hanh'])) ?> · <?= $escape($orderDetail['diem_khoi_hanh']) ?></dd></div>
                <div><dt>Khách</dt><dd><?= (int) $orderDetail['so_nguoi_lon'] ?> người lớn, <?= (int) $orderDetail['so_tre_em'] ?> trẻ em</dd></div>
                <div><dt>Đơn giá</dt><dd><?= $money($orderDetail['gia_nguoi_lon']) ?> / người lớn · <?= $money($orderDetail['gia_tre_em']) ?> / trẻ em</dd></div>
                <div><dt>Tổng gốc</dt><dd><?= $money($orderDetail['tong_goc']) ?></dd></div>
                <div><dt>Ưu đãi nhóm</dt><dd><?= (int) $orderDetail['muc_giam'] ?>% (−<?= $money($orderDetail['tien_giam']) ?>)</dd></div>
                <div><dt>Tổng thanh toán</dt><dd><strong><?= $money($orderDetail['tong_thanh_toan']) ?></strong></dd></div>
                <div><dt>Liên hệ</dt><dd><?= $escape($orderDetail['ho_ten']) ?> · <?= $escape($orderDetail['email']) ?> · <?= $escape($orderDetail['so_dien_thoai']) ?></dd></div>
                <div><dt>Địa chỉ</dt><dd><?= $orderDetail['dia_chi'] !== '' ? $escape($orderDetail['dia_chi']) : '—' ?></dd></div>
            </dl>
            <?php if ($orderDetail['ghi_chu'] !== ''): ?>
            <p class="order-note"><strong>Ghi chú của khách:</strong> <?= $escape($orderDetail['ghi_chu']) ?></p>
            <?php endif; ?>
        </div>

        <div class="admin-order-columns">
            <div>
                <h3>Người tham gia</h3>
                <ul class="order-participants">
                    <?php foreach ($orderDetail['participants'] as $participant): ?>
                    <li>
                        <span class="order-participant-type"><?= $participant['loai_khach'] === 'tre_em' ? 'Trẻ em' : 'Người lớn' ?></span>
                        <strong><?= $escape($participant['ho_ten']) ?></strong>
                        <span><?= $participant['ngay_sinh'] ? 'Sinh ' . date('d/m/Y', strtotime($participant['ngay_sinh'])) : 'Chưa cung cấp ngày sinh' ?></span>
                        <?php if ($participant['ghi_chu'] !== ''): ?><em><?= $escape($participant['ghi_chu']) ?></em><?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3>Thanh toán</h3>
                <?php if ($orderDetail['da_thanh_toan']): ?>
                <ul class="order-participants">
                    <li><span class="order-participant-type">Mô phỏng</span><strong><?= $money($orderDetail['so_tien_da_tra']) ?></strong><span><?= $escape($paymentMethods[$orderDetail['phuong_thuc']] ?? $orderDetail['phuong_thuc']) ?></span><em>Mã giao dịch <?= $escape($orderDetail['ma_giao_dich']) ?></em><?php if (!empty($orderDetail['thanh_toan_luc'])): ?><span><?= date('d/m/Y H:i', strtotime($orderDetail['thanh_toan_luc'])) ?></span><?php endif; ?></li>
                </ul>
                <?php else: ?>
                <p class="admin-order-empty">Khách chưa thanh toán đơn này.</p>
                <?php endif; ?>
                <h3>Cập nhật trạng thái</h3>
                <?php if ($nextStatuses): ?>
                <div class="admin-order-transitions">
                    <?php foreach ($nextStatuses as $nextStatus): ?>
                    <form method="post" action="index.php?action=orders" data-account-confirm="Chuyển đơn <?= $escape($orderDetail['ma_don']) ?> sang “<?= $escape($statusLabels[$nextStatus]) ?>”?">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="ma_don" value="<?= $escape($orderDetail['ma_don']) ?>">
                        <input type="hidden" name="trang_thai" value="<?= $escape($nextStatus) ?>">
                        <button class="button<?= $nextStatus === 'da_huy' ? ' admin-delete-submit' : ' button-primary' ?>" type="submit"><?= $nextStatus === 'da_huy' ? 'Hủy đơn' : 'Chuyển sang ' . $escape($statusLabels[$nextStatus]) ?></button>
                    </form>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="admin-order-empty">Đơn đã ở trạng thái kết thúc nên không thể chuyển tiếp.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="admin-panel admin-accounts-panel" aria-labelledby="orders-list-title">
        <div class="admin-panel-heading"><div><h2 id="orders-list-title">Danh sách đơn đặt</h2><p>Tra cứu theo mã đơn, tên khách, email, số điện thoại hoặc tên tour.</p></div><span class="admin-count-tag"><?= (int) $orderList['total'] ?> đơn</span></div>
        <form class="admin-accounts-filters" action="index.php" method="get" role="search" aria-label="Tìm và lọc đơn đặt">
            <input type="hidden" name="action" value="orders">
            <label class="admin-accounts-search"><span>Tìm kiếm</span><div><svg class="icon"><use href="#a-search"/></svg><input type="search" name="q" value="<?= $escape($orderFilters['q']) ?>" maxlength="120" placeholder="Mã đơn, khách hàng hoặc tour"></div></label>
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= $escape($value) ?>" <?= $orderFilters['status'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?></select></label>
            <button class="button button-primary" type="submit">Áp dụng</button>
            <a class="admin-filter-clear" href="<?= $escape($ordersUrl(['q' => '', 'status' => '', 'p' => 1])) ?>">Đặt lại</a>
        </form>
        <div class="admin-table-scroll">
            <table class="admin-table admin-orders-table">
                <thead><tr><th scope="col">Mã đơn</th><th scope="col">Tour</th><th scope="col">Khách</th><th scope="col">Khởi hành</th><th scope="col">Tổng tiền</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($orderList['items'] as $row): ?>
                    <tr>
                        <td><strong><?= $escape($row['ma_don']) ?></strong><small class="admin-contact-phone"><?= date('d/m/Y H:i', strtotime($row['tao_luc'])) ?></small></td>
                        <td><?= $escape($row['ten_tour']) ?><small class="admin-contact-phone"><?= $escape($row['ma_tour']) ?></small></td>
                        <td><?= $escape($row['ho_ten']) ?><small class="admin-contact-phone"><?= $escape($row['so_dien_thoai']) ?></small></td>
                        <td><?= date('d/m/Y', strtotime($row['ngay_khoi_hanh'])) ?><small class="admin-contact-phone"><?= (int) $row['so_nguoi_lon'] ?> NL · <?= (int) $row['so_tre_em'] ?> TE</small></td>
                        <td><strong><?= $money($row['tong_thanh_toan']) ?></strong><small class="admin-contact-phone"><?= $row['da_thanh_toan'] ? 'Đã thanh toán' : 'Chưa thanh toán' ?></small></td>
                        <td><span class="order-status <?= $statusClasses[$row['trang_thai']] ?? '' ?>"><?= $escape($row['trang_thai_nhan']) ?></span></td>
                        <td><div class="admin-row-actions"><a class="admin-row-button" href="<?= 'index.php?action=orders&amp;id=' . $escape(rawurlencode($row['ma_don'])) ?>" aria-label="Xem đơn <?= $escape($row['ma_don']) ?>" title="Xem chi tiết"><svg class="icon"><use href="#a-search"/></svg><span>Xem</span></a></div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$orderList['items']): ?><tr><td colspan="7" class="admin-empty"><svg class="icon"><use href="#a-search"/></svg><strong>Không tìm thấy đơn đặt phù hợp</strong><p>Thử thay đổi từ khóa hoặc đặt lại bộ lọc.</p></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-accounts-list-footer">
            <?php if ($orderList['pages'] > 1): ?>
            <nav class="admin-accounts-pagination" aria-label="Phân trang đơn đặt">
                <?php if ($orderList['page'] > 1): ?><a href="<?= $escape($ordersUrl(['p' => $orderList['page'] - 1])) ?>" aria-label="Trang trước">‹</a><?php endif; ?>
                <?php
                    $pageLinks = array_unique(array_merge([1], range(max(1, $orderList['page'] - 2), min($orderList['pages'], $orderList['page'] + 2)), [$orderList['pages']]));
                    sort($pageLinks);
                    $previousPage = 0;
                    foreach ($pageLinks as $number):
                ?>
                    <?php if ($previousPage && $number > $previousPage + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                    <a href="<?= $escape($ordersUrl(['p' => $number])) ?>" <?= $number === $orderList['page'] ? 'aria-current="page"' : '' ?> aria-label="Trang <?= $number ?>"><?= $number ?></a>
                <?php $previousPage = $number; endforeach; ?>
                <?php if ($orderList['page'] < $orderList['pages']): ?><a href="<?= $escape($ordersUrl(['p' => $orderList['page'] + 1])) ?>" aria-label="Trang tiếp theo">›</a><?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
