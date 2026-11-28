<?php
/**
 * ThongKeController::index() chuẩn bị số liệu thống kê đặt tour và doanh thu.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var string $adminSection
 * @var string $avatarUrl
 * @var array $tourOptions
 * @var array $filters
 * @var Closure $statsUrl
 * @var array $overview
 * @var array $timeSeries
 * @var array $topTours
 * @var array $paymentBreakdown
 * @var array $recentTransactions
 * @var array $statusLabels
 */
require __DIR__ . '/partials/header.php';
$money = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' ₫';

$statusBreakdown = [
    ['key' => 'hoan_thanh', 'label' => $statusLabels['hoan_thanh'] ?? 'Hoàn thành', 'count' => $overview['hoan_thanh'], 'class' => 'is-completed'],
    ['key' => 'da_xac_nhan', 'label' => $statusLabels['da_xac_nhan'] ?? 'Đã xác nhận', 'count' => $overview['da_xac_nhan'], 'class' => 'is-confirmed'],
    ['key' => 'cho_xac_nhan', 'label' => $statusLabels['cho_xac_nhan'] ?? 'Chờ xác nhận', 'count' => $overview['cho_xac_nhan'], 'class' => 'is-pending'],
    ['key' => 'da_huy', 'label' => $statusLabels['da_huy'] ?? 'Đã hủy', 'count' => $overview['da_huy'], 'class' => 'is-cancelled'],
];

$maxPeriodRevenue = 1.0;
foreach ($timeSeries as $item) {
    $maxPeriodRevenue = max($maxPeriodRevenue, (float) $item['expected_revenue'], (float) $item['collected_revenue']);
}
$totalOrdersSafe = max(1, (int) $overview['total_orders']);
?>
<div class="admin-accounts admin-report-page">
    <div class="admin-page-heading admin-accounts-heading">
        <div>
            <h1>Thống kê đặt tour</h1>
            <p>Tổng hợp số lượng đơn đặt tour, lượt khách và doanh thu theo thời gian, theo tour và theo phương thức thanh toán.</p>
        </div>
        <div class="admin-report-head-actions">
            <a class="admin-secondary-button" href="index.php?action=orders">Quản lý đơn đặt <svg class="icon" aria-hidden="true"><use href="#a-arrow"/></svg></a>
        </div>
    </div>

    <section class="admin-panel admin-report-filters" aria-label="Bộ lọc thống kê">
        <div class="admin-report-presets">
            <span>Khoảng nhanh:</span>
            <a class="admin-preset-pill<?= $filters['range'] === 'all' || ($filters['from'] === '' && $filters['to'] === '' && $filters['range'] === '') ? ' is-active' : '' ?>" href="<?= $escape($statsUrl(['range' => 'all', 'from' => '', 'to' => ''])) ?>">Tất cả</a>
            <a class="admin-preset-pill<?= $filters['range'] === 'month' ? ' is-active' : '' ?>" href="<?= $escape($statsUrl(['range' => 'month', 'from' => '', 'to' => ''])) ?>">Tháng này</a>
            <a class="admin-preset-pill<?= $filters['range'] === '30d' ? ' is-active' : '' ?>" href="<?= $escape($statsUrl(['range' => '30d', 'from' => '', 'to' => ''])) ?>">30 ngày qua</a>
            <a class="admin-preset-pill<?= $filters['range'] === 'year' ? ' is-active' : '' ?>" href="<?= $escape($statsUrl(['range' => 'year', 'from' => '', 'to' => ''])) ?>">Năm nay</a>
        </div>

        <form class="admin-report-filter-form" method="get" action="index.php">
            <input type="hidden" name="action" value="thong_ke">
            <label>
                <span>Từ ngày</span>
                <input type="date" name="from" value="<?= $escape($filters['from']) ?>">
            </label>
            <label>
                <span>Đến ngày</span>
                <input type="date" name="to" value="<?= $escape($filters['to']) ?>">
            </label>
            <label>
                <span>Theo tour</span>
                <select name="tour">
                    <option value="">Tất cả tour</option>
                    <?php foreach ($tourOptions as $t): ?>
                    <option value="<?= $escape($t['ma_tour']) ?>" <?= $filters['tour'] === $t['ma_tour'] ? 'selected' : '' ?>><?= $escape($t['ten_tour']) ?> (<?= $escape($t['ma_tour']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Gộp theo</span>
                <select name="group">
                    <option value="month" <?= $filters['group'] === 'month' ? 'selected' : '' ?>>Theo tháng</option>
                    <option value="day" <?= $filters['group'] === 'day' ? 'selected' : '' ?>>Theo ngày</option>
                </select>
            </label>
            <div class="admin-report-filter-buttons">
                <button class="button button-primary" type="submit">Lọc dữ liệu</button>
                <a class="admin-secondary-button" href="index.php?action=thong_ke">Đặt lại</a>
            </div>
        </form>
    </section>

    <section class="admin-stats" aria-label="Chỉ số tổng hợp đặt tour và doanh thu">
        <article class="admin-stat">
            <div>
                <h2>Doanh thu đã thu</h2>
                <strong class="admin-stat-money"><?= $money($overview['collected_revenue']) ?></strong>
                <p>Từ <?= (int) $overview['paid_orders'] ?> đơn đã thanh toán thành công</p>
            </div>
            <span class="admin-stat-icon admin-color-green"><svg class="icon"><use href="#a-check"/></svg></span>
        </article>
        <article class="admin-stat">
            <div>
                <h2>Doanh thu chờ thu</h2>
                <strong class="admin-stat-money"><?= $money($overview['pending_revenue']) ?></strong>
                <p>Đơn đã xác nhận chưa thu · Chờ duyệt: <?= $money($overview['waiting_confirm_revenue']) ?></p>
            </div>
            <span class="admin-stat-icon admin-color-orange"><svg class="icon"><use href="#a-chart"/></svg></span>
        </article>
        <article class="admin-stat">
            <div>
                <h2>Tổng đơn đặt tour</h2>
                <strong><?= (int) $overview['total_orders'] ?></strong>
                <p><?= (int) $overview['hoan_thanh'] ?> hoàn thành · <?= (int) $overview['da_xac_nhan'] ?> đã xác nhận · <?= (int) $overview['cho_xac_nhan'] ?> chờ</p>
            </div>
            <span class="admin-stat-icon admin-color-blue"><svg class="icon"><use href="#a-orders"/></svg></span>
        </article>
        <article class="admin-stat">
            <div>
                <h2>Tổng lượt khách</h2>
                <strong><?= (int) $overview['total_guests'] ?></strong>
                <p><?= (int) $overview['total_adults'] ?> người lớn · <?= (int) $overview['total_children'] ?> trẻ em · Tỷ lệ hủy <?= $escape($overview['cancel_rate']) ?>%</p>
            </div>
            <span class="admin-stat-icon admin-color-purple"><svg class="icon"><use href="#a-users"/></svg></span>
        </article>
    </section>

    <div class="admin-report-grid">
        <section class="admin-panel" aria-labelledby="report-series-title">
            <div class="admin-panel-heading">
                <div>
                    <h2 id="report-series-title">Doanh thu &amp; đơn đặt <?= $filters['group'] === 'day' ? 'theo ngày' : 'theo tháng' ?></h2>
                    <p>So sánh doanh thu thực thu (thanh toán thành công) với tổng giá trị đơn hợp lệ (không tính đơn hủy).</p>
                </div>
                <span class="admin-count-tag"><?= count($timeSeries) ?> mốc</span>
            </div>

            <?php if ($timeSeries): ?>
            <div class="admin-report-bars">
                <?php foreach ($timeSeries as $row): ?>
                <?php
                    $expectedPct = round(((float) $row['expected_revenue'] / $maxPeriodRevenue) * 100, 1);
                    $collectedPct = round(((float) $row['collected_revenue'] / $maxPeriodRevenue) * 100, 1);
                ?>
                <div class="admin-bar-row">
                    <div class="admin-bar-label">
                        <strong><?= $escape($row['period_label']) ?></strong>
                        <small><?= (int) $row['order_count'] ?> đơn · <?= (int) $row['guest_count'] ?> khách</small>
                    </div>
                    <div class="admin-bar-track-wrap">
                        <div class="admin-bar-track" title="Giá trị đơn hợp lệ: <?= $escape($money($row['expected_revenue'])) ?> | Đã thu: <?= $escape($money($row['collected_revenue'])) ?>">
                            <span class="admin-bar-expected" style="width: <?= $expectedPct ?>%"></span>
                            <span class="admin-bar-collected" style="width: <?= $collectedPct ?>%"></span>
                        </div>
                    </div>
                    <div class="admin-bar-values">
                        <strong><?= $money($row['collected_revenue']) ?></strong>
                        <small>/ <?= $money($row['expected_revenue']) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="admin-bar-legend">
                    <span><i class="dot-collected"></i> Doanh thu đã thu</span>
                    <span><i class="dot-expected"></i> Tổng giá trị đơn hợp lệ</span>
                    <span class="admin-bar-discount-note">Tổng ưu đãi đã áp dụng: <strong><?= $money($overview['total_discount']) ?></strong></span>
                </div>
            </div>
            <?php else: ?>
            <p class="admin-report-empty">Chưa có đơn đặt tour nào trong khoảng thời gian hoặc bộ lọc đã chọn.</p>
            <?php endif; ?>
        </section>

        <div class="admin-right-column">
            <section class="admin-panel" aria-labelledby="report-status-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="report-status-title">Trạng thái đơn đặt tour</h2>
                        <p>Phân bố <?= (int) $overview['total_orders'] ?> đơn theo tiến trình xử lý</p>
                    </div>
                    <span class="admin-count-tag">Hủy: <?= $escape($overview['cancel_rate']) ?>%</span>
                </div>
                <div class="admin-report-breakdown">
                    <?php foreach ($statusBreakdown as $st): ?>
                    <?php $pct = $overview['total_orders'] > 0 ? round(($st['count'] / $totalOrdersSafe) * 100, 1) : 0; ?>
                    <div class="admin-breakdown-item">
                        <div class="admin-breakdown-top">
                            <span class="order-status <?= $escape($st['class']) ?>"><?= $escape($st['label']) ?></span>
                            <strong><?= (int) $st['count'] ?> đơn <small>(<?= $pct ?>%)</small></strong>
                        </div>
                        <div class="admin-progress-rail"><span class="admin-progress-fill <?= $escape($st['class']) ?>" style="width: <?= $pct ?>%"></span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="report-payment-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="report-payment-title">Cơ cấu phương thức thanh toán</h2>
                        <p>Tính trên các giao dịch thanh toán thành công</p>
                    </div>
                    <span class="admin-count-tag"><?= (int) $overview['paid_orders'] ?> giao dịch</span>
                </div>
                <div class="admin-report-breakdown">
                    <?php if ($paymentBreakdown): ?>
                        <?php foreach ($paymentBreakdown as $pm): ?>
                        <div class="admin-breakdown-item">
                            <div class="admin-breakdown-top">
                                <span><?= $escape($pm['label']) ?></span>
                                <strong><?= $money($pm['total_amount']) ?> <small>(<?= (int) $pm['tx_count'] ?> · <?= $escape($pm['percent']) ?>%)</small></strong>
                            </div>
                            <div class="admin-progress-rail"><span class="admin-progress-fill is-payment" style="width: <?= (float) $pm['percent'] ?>%"></span></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="admin-report-empty">Chưa ghi nhận giao dịch thanh toán nào trong bộ lọc này.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <section class="admin-panel admin-report-section" aria-labelledby="report-top-tours-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="report-top-tours-title">Top tour theo doanh thu &amp; lượt đặt</h2>
                <p>Xếp hạng tối đa 10 tour có doanh thu đã thu và giá trị đơn cao nhất.</p>
            </div>
            <span class="admin-count-tag"><?= count($topTours) ?> tour</span>
        </div>
        <div class="admin-table-scroll">
            <table class="admin-table admin-report-table">
                <thead>
                    <tr>
                        <th>Tour du lịch</th>
                        <th>Tổng đơn</th>
                        <th>Hoàn thành / Hủy</th>
                        <th>Lượt khách</th>
                        <th>Đã thu</th>
                        <th>Giá trị đơn hợp lệ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topTours as $tourRow): ?>
                    <tr>
                        <td>
                            <small><?= $escape($tourRow['ma_tour']) ?></small>
                            <strong><?= $escape($tourRow['ten_tour']) ?></strong>
                        </td>
                        <td><strong><?= (int) $tourRow['order_count'] ?></strong> đơn</td>
                        <td><?= (int) $tourRow['completed_count'] ?> hoàn thành · <?= (int) $tourRow['cancelled_count'] ?> hủy</td>
                        <td><?= (int) $tourRow['guest_count'] ?> khách</td>
                        <td class="admin-price"><?= $money($tourRow['collected_revenue']) ?></td>
                        <td class="admin-price"><?= $money($tourRow['expected_revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$topTours): ?>
                    <tr><td colspan="6" class="admin-empty">Chưa có dữ liệu đặt tour phù hợp với bộ lọc.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel admin-report-section" aria-labelledby="report-transactions-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="report-transactions-title">Giao dịch thanh toán gần đây</h2>
                <p>Các khoản thanh toán thành công mới nhất gắn với đơn đặt tour.</p>
            </div>
            <span class="admin-count-tag"><?= count($recentTransactions) ?> giao dịch</span>
        </div>
        <div class="admin-table-scroll">
            <table class="admin-table admin-report-table">
                <thead>
                    <tr>
                        <th>Mã giao dịch / Đơn</th>
                        <th>Tour &amp; Khách hàng</th>
                        <th>Phương thức</th>
                        <th>Số tiền</th>
                        <th>Thời điểm</th>
                        <th>Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                    <tr>
                        <td>
                            <small><?= $escape($tx['ma_giao_dich']) ?></small>
                            <strong><?= $escape($tx['ma_don']) ?></strong>
                        </td>
                        <td>
                            <small><?= $escape($tx['ho_ten']) ?></small>
                            <strong><?= $escape($tx['ten_tour']) ?></strong>
                        </td>
                        <td><?= $escape(DonDatModel::paymentMethodLabel((string) $tx['phuong_thuc'])) ?></td>
                        <td class="admin-price"><?= $money($tx['so_tien']) ?></td>
                        <td class="admin-table-date"><?= date('d/m/Y H:i', strtotime((string) $tx['thanh_toan_luc'])) ?></td>
                        <td><a class="admin-row-button" href="index.php?action=orders&amp;id=<?= $escape(rawurlencode($tx['ma_don'])) ?>">Xem đơn</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentTransactions): ?>
                    <tr><td colspan="6" class="admin-empty">Chưa có giao dịch thanh toán nào trong bộ lọc này.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
