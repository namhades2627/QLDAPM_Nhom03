<?php
/**
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var array $dashboard
 * @var string $todayLabel
 */
$escape = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$stats = [
    ['label' => 'Tổng số tour', 'value' => $dashboard['tours'], 'note' => 'Toàn bộ danh mục hiện có', 'icon' => 'a-tour', 'color' => 'orange'],
    ['label' => 'Tour đã công bố', 'value' => $dashboard['published'], 'note' => 'Trạng thái đã xuất bản', 'icon' => 'a-check', 'color' => 'green'],
    ['label' => 'Lịch sắp khởi hành', 'value' => $dashboard['schedule_count'], 'note' => 'Lịch mở từ hôm nay trở đi', 'icon' => 'a-calendar', 'color' => 'blue'],
    ['label' => 'Điểm đến', 'value' => $dashboard['destinations'], 'note' => 'Điểm đến đang hoạt động', 'icon' => 'a-pin', 'color' => 'purple'],
];
$adminTitle = 'Bảng điều khiển';
$adminHeading = 'Bảng điều khiển';
$adminSubtitle = 'Tổng quan danh mục và lịch khởi hành của OnTrip.';
$adminActive = 'dashboard';
$adminActions = '<span class="admin-date"><svg class="icon"><use href="#a-calendar"/></svg>' . $escape($todayLabel) . '</span>';
require __DIR__ . '/partials/head.php';
?>
            <section class="admin-welcome" aria-labelledby="admin-welcome-title">
                <div><span class="admin-role-tag"><svg class="icon"><use href="#a-shield"/></svg><?= $escape($roleName) ?></span><h2 id="admin-welcome-title">Xin chào, <?= $escape($account['ho_ten']) ?>!</h2><p>Theo dõi các hành trình và lịch khởi hành trong một không gian làm việc.</p></div>
                <a class="button button-primary" href="index.php?action=schedules">Quản lý lịch &amp; chỗ ngồi <svg class="icon"><use href="#a-arrow"/></svg></a>
            </section>
            <section class="admin-stats" aria-label="Số liệu nhanh">
                <?php foreach ($stats as $stat): ?>
                <article class="admin-stat"><div><h2><?= $escape($stat['label']) ?></h2><strong><?= $escape($stat['value']) ?></strong><p><?= $escape($stat['note']) ?></p></div><span class="admin-stat-icon admin-color-<?= $escape($stat['color']) ?>"><svg class="icon"><use href="#<?= $escape($stat['icon']) ?>"/></svg></span></article>
                <?php endforeach; ?>
            </section>
            <div class="admin-dashboard-grid">
                <section class="admin-panel" id="admin-schedules" aria-labelledby="admin-schedules-title">
                    <div class="admin-panel-heading"><div><h2 id="admin-schedules-title">Lịch khởi hành gần nhất</h2><p>5 lịch mở tiếp theo, từ hôm nay trở đi</p></div><a class="admin-link" href="index.php?action=schedules">Xem tất cả <svg class="icon"><use href="#a-arrow"/></svg></a></div>
                    <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th>Tour du lịch</th><th>Khởi hành</th><th>Điểm đi</th><th>Giá người lớn</th></tr></thead><tbody>
                    <?php foreach ($dashboard['schedules'] as $schedule): ?>
                        <tr><td><small><?= $escape(strtoupper($schedule['ma_tour'])) ?></small><strong><?= $escape($schedule['ten_tour']) ?></strong></td><td><span class="admin-table-date"><?= $escape(date('d/m/Y', strtotime($schedule['ngay_khoi_hanh']))) ?></span></td><td><?= $escape($schedule['diem_khoi_hanh']) ?></td><td class="admin-price"><?= number_format((float) $schedule['gia_nguoi_lon'], 0, ',', '.') ?> <small>đ</small></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$dashboard['schedules']): ?><tr><td colspan="4" class="admin-empty">Chưa có lịch mở sắp khởi hành.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </section>
                <div class="admin-right-column">
                    <section class="admin-panel" id="admin-catalog" aria-labelledby="admin-catalog-title">
                        <div class="admin-panel-heading"><div><h2 id="admin-catalog-title">Danh mục tour</h2><p>Phân bố các tour trong hệ thống</p></div><svg class="icon"><use href="#a-tour"/></svg></div>
                        <div class="admin-category"><span class="admin-category-icon"><svg class="icon"><use href="#a-pin"/></svg></span><div><strong>Tour trong nước</strong><small>Khám phá Việt Nam</small></div><b><?= (int) ($dashboard['groups']['domestic'] ?? 0) ?></b></div>
                        <div class="admin-category"><span class="admin-category-icon admin-color-blue"><svg class="icon"><use href="#a-tour"/></svg></span><div><strong>Tour quốc tế</strong><small>Khám phá thế giới</small></div><b><?= (int) ($dashboard['groups']['international'] ?? 0) ?></b></div>
                    </section>
                    <section class="admin-panel admin-account-panel" aria-labelledby="admin-account-title"><div class="admin-panel-heading"><h2 id="admin-account-title">Tài khoản làm việc</h2><svg class="icon"><use href="#a-user"/></svg></div><p><?= $escape($account['email']) ?></p><span class="admin-account-role"><?= $escape($roleName) ?></span></section>
                </div>
            </div>
<?php require __DIR__ . '/partials/foot.php'; ?>
