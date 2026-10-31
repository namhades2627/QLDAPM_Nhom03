<?php
/**
 * AdminController chuẩn bị danh sách, bộ lọc và biểu mẫu; View chỉ hiển thị.
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var string $adminSection
 * @var string $avatarUrl
 * @var array $accountList
 * @var array $accountRoles
 * @var array $accountFilters
 * @var Closure $accountsUrl
 * @var string $accountMessage
 * @var bool $accountSuccess
 * @var array $accountErrors
 * @var string $accountFormMode
 * @var int $editAccountId
 * @var array $accountValues
 * @var array|null $deleteAccount
 * @var bool $isCustomerManagement
 */
require __DIR__ . '/partials/header.php';
$summary = $accountList['summary'];
$accountStats = $isCustomerManagement ? [
    ['Tổng khách hàng', $summary['total'], 'Tài khoản khách hàng trên website', 'a-users', 'orange'],
    ['Đang hoạt động', $summary['active'], 'Khách hàng có thể đăng nhập', 'a-check', 'green'],
    ['Đang khóa', $summary['locked'], 'Tạm ngừng quyền đăng nhập', 'a-lock', 'purple'],
    ['Đăng ký trong tháng', $summary['registered_month'], 'Khách hàng mới trong tháng này', 'a-calendar', 'blue'],
] : [
    ['Tổng tài khoản', $summary['total'], 'Quản trị viên và nhân viên', 'a-users', 'orange'],
    ['Quản trị viên', $summary['admins'], 'Tài khoản quản trị hệ thống', 'a-shield', 'purple'],
    ['Nhân viên', $summary['staff'], 'Tài khoản vận hành OnTrip', 'a-shield', 'blue'],
    ['Đang khóa', $summary['locked'], 'Tạm ngừng quyền đăng nhập', 'a-lock', 'purple'],
];
$isCreating = $accountFormMode === 'create';
$accountNoun = $isCustomerManagement ? 'khách hàng' : 'tài khoản';
$accountColumnCount = $isCustomerManagement ? 5 : 6;
?>
<div class="admin-accounts">
    <div class="admin-page-heading admin-accounts-heading">
        <div><h1><?= $isCustomerManagement ? 'Quản lý khách hàng' : 'Quản lý tài khoản' ?></h1><p><?= $isCustomerManagement ? 'Thông tin liên hệ và trạng thái của khách hàng OnTrip.' : 'Quản lý tài khoản quản trị viên và nhân viên OnTrip.' ?></p></div>
        <?php if (!$isCustomerManagement): ?>
        <a class="button button-primary admin-create-account" href="<?= $escape($accountsUrl(['mode' => 'create', 'edit' => null])) ?>"><span aria-hidden="true">+</span> Thêm nhân viên</a>
        <?php endif; ?>
    </div>
    <section class="admin-stats" aria-label="Tổng quan <?= $accountNoun ?>">
        <?php foreach ($accountStats as [$label, $value, $note, $icon, $color]): ?>
        <article class="admin-stat"><div><h2><?= $escape($label) ?></h2><strong><?= (int) $value ?></strong><p><?= $escape($note) ?></p></div><span class="admin-stat-icon admin-color-<?= $color ?>"><svg class="icon"><use href="#<?= $icon ?>"/></svg></span></article>
        <?php endforeach; ?>
    </section>

    <?php if ($accountMessage !== ''): ?>
    <div class="admin-form-message<?= $accountSuccess ? ' is-success' : '' ?>" role="<?= $accountSuccess ? 'status' : 'alert' ?>"><?= $escape($accountMessage) ?></div>
    <?php endif; ?>

    <?php if ($deleteAccount !== null): ?>
    <section class="admin-panel admin-managed-editor admin-delete-confirm" id="account-delete" aria-labelledby="account-delete-title">
        <div class="admin-panel-heading"><div><h2 id="account-delete-title">Xóa tài khoản?</h2><p>Kiểm tra tài khoản trước khi xác nhận.</p></div><svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg></div>
        <form class="admin-account-form" method="post" action="<?= $escape($accountsUrl()) ?>">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="operation" value="delete">
            <input type="hidden" name="id_tai_khoan" value="<?= (int) $deleteAccount['id_tai_khoan'] ?>">
            <p>Bạn sắp xóa vĩnh viễn tài khoản <strong><?= $escape($deleteAccount['ho_ten']) ?></strong> — <strong><?= $escape($deleteAccount['email']) ?></strong>.</p>
            <p class="admin-delete-note">Thao tác này không thể hoàn tác. Nếu chỉ muốn tạm ngừng đăng nhập, hãy dùng Khóa tài khoản.</p>
            <label class="admin-delete-check"><input type="checkbox" name="confirm_delete" value="1" required><span>Tôi xác nhận xóa tài khoản này.</span></label>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($accountsUrl()) ?>">Hủy bỏ</a><button class="button admin-delete-submit" type="submit"><svg class="icon"><use href="#a-trash"/></svg>Xóa tài khoản</button></div>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($accountFormMode !== ''): ?>
    <section class="admin-panel admin-managed-editor" aria-labelledby="account-editor-title" id="account-editor">
        <div class="admin-panel-heading"><div><h2 id="account-editor-title"><?= $isCreating ? 'Thêm tài khoản nhân viên' : 'Chỉnh sửa ' . $accountNoun . ' #' . $editAccountId ?></h2><p><?= $isCreating ? 'Khách hàng tự đăng ký tài khoản trên website.' : ($isCustomerManagement ? 'Cập nhật thông tin liên hệ và trạng thái khách hàng.' : 'Cập nhật thông tin liên hệ, vai trò và trạng thái tài khoản.') ?></p></div><a class="admin-editor-close" href="<?= $escape($accountsUrl()) ?>" aria-label="Đóng biểu mẫu">×</a></div>
        <form class="admin-account-form" method="post" action="<?= $escape($accountsUrl()) ?>" data-managed-account-form>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="operation" value="<?= $isCreating ? 'create' : 'update' ?>">
            <?php if (!$isCreating): ?><input type="hidden" name="id_tai_khoan" value="<?= $editAccountId ?>"><?php endif; ?>
            <div class="admin-managed-fields">
                <?php foreach ([['ho_ten', 'Họ và tên', 'text', 'name', 120], ['email', 'Địa chỉ email', 'email', 'off', 254], ['so_dien_thoai', 'Số điện thoại', 'tel', 'off', 20]] as [$name, $label, $type, $autocomplete, $max]): ?>
                <div class="admin-form-field"><label for="managed-<?= $name ?>"><?= $label ?> <span>*</span></label><input id="managed-<?= $name ?>" name="<?= $name ?>" type="<?= $type ?>" value="<?= $escape($accountValues[$name]) ?>" maxlength="<?= $max ?>" autocomplete="<?= $autocomplete ?>" <?= $name === 'ho_ten' ? 'minlength="2"' : '' ?> aria-invalid="<?= isset($accountErrors[$name]) ? 'true' : 'false' ?>" aria-describedby="managed-<?= $name ?>-error" required><p class="admin-field-error" id="managed-<?= $name ?>-error" <?= isset($accountErrors[$name]) ? '' : 'hidden' ?>><?= $escape($accountErrors[$name] ?? '') ?></p></div>
                <?php endforeach; ?>
                <?php if ($isCreating): ?>
                    <?php foreach (['mat_khau' => 'Mật khẩu', 'xac_nhan_mat_khau' => 'Xác nhận mật khẩu'] as $name => $label): ?>
                    <div class="admin-form-field"><label for="managed-<?= $name ?>"><?= $label ?> <span>*</span></label><div class="admin-password-wrap"><input id="managed-<?= $name ?>" name="<?= $name ?>" type="password" autocomplete="new-password" minlength="8" aria-invalid="<?= isset($accountErrors[$name]) ? 'true' : 'false' ?>" aria-describedby="managed-<?= $name ?>-error" required><button class="auth-password-toggle" type="button" data-account-password-toggle aria-controls="managed-<?= $name ?>" aria-label="Hiện <?= mb_strtolower($label, 'UTF-8') ?>" aria-pressed="false"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="auth-eye-slash" d="M3 3 21 21"/></svg></button></div><p class="admin-field-error" id="managed-<?= $name ?>-error" <?= isset($accountErrors[$name]) ? '' : 'hidden' ?>><?= $escape($accountErrors[$name] ?? '') ?></p><?php if ($name === 'mat_khau'): ?><small>Ít nhất 8 ký tự, tối đa 72 byte.</small><?php endif; ?></div>
                    <?php endforeach; ?>
                    <div class="admin-new-staff-note"><svg class="icon"><use href="#a-shield"/></svg><div><strong>Vai trò nhân viên</strong><p>Tài khoản mới được kích hoạt để đăng nhập ngay.</p></div></div>
                <?php else: ?>
                    <?php if ($isCustomerManagement): ?>
                    <input type="hidden" name="vai_tro" value="khach_hang">
                    <div class="admin-form-field"><label for="managed-role">Vai trò</label><input id="managed-role" type="text" value="Khách hàng" readonly></div>
                    <?php else: ?>
                    <div class="admin-form-field"><label for="managed-role">Vai trò <span>*</span></label><select id="managed-role" name="vai_tro" aria-invalid="<?= isset($accountErrors['vai_tro']) ? 'true' : 'false' ?>" aria-describedby="managed-role-error" required><?php foreach ($accountRoles as $value => $label): ?><option value="<?= $value ?>" <?= $accountValues['vai_tro'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><p class="admin-field-error" id="managed-role-error" <?= isset($accountErrors['vai_tro']) ? '' : 'hidden' ?>><?= $escape($accountErrors['vai_tro'] ?? '') ?></p></div>
                    <?php endif; ?>
                    <div class="admin-form-field"><label for="managed-status">Trạng thái <span>*</span></label><select id="managed-status" name="dang_hoat_dong" aria-invalid="<?= isset($accountErrors['dang_hoat_dong']) ? 'true' : 'false' ?>" aria-describedby="managed-status-error" required><option value="1" <?= $accountValues['dang_hoat_dong'] === '1' ? 'selected' : '' ?>>Đang hoạt động</option><option value="0" <?= $accountValues['dang_hoat_dong'] === '0' ? 'selected' : '' ?>>Đang khóa</option></select><p class="admin-field-error" id="managed-status-error" <?= isset($accountErrors['dang_hoat_dong']) ? '' : 'hidden' ?>><?= $escape($accountErrors['dang_hoat_dong'] ?? '') ?></p></div>
                <?php endif; ?>
            </div>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($accountsUrl()) ?>">Hủy bỏ</a><button class="button button-primary" type="submit"><?= $isCreating ? 'Tạo tài khoản' : 'Lưu thay đổi' ?></button></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="admin-panel admin-accounts-panel" aria-labelledby="accounts-list-title">
        <div class="admin-panel-heading"><div><h2 id="accounts-list-title">Danh sách <?= $accountNoun ?></h2><p><?= $isCustomerManagement ? 'Tra cứu thông tin khách hàng đã đăng ký trên website.' : 'Tra cứu và quản lý quyền truy cập của quản trị viên, nhân viên.' ?></p></div><span class="admin-count-tag"><?= $accountList['total'] ?> <?= $accountNoun ?></span></div>
        <form class="admin-accounts-filters" action="index.php" method="get" role="search" aria-label="Tìm và lọc <?= $accountNoun ?>">
            <input type="hidden" name="action" value="<?= $escape($adminSection) ?>">
            <label class="admin-accounts-search"><span>Tìm kiếm</span><div><svg class="icon"><use href="#a-search"/></svg><input type="search" name="q" value="<?= $escape($accountFilters['q']) ?>" maxlength="120" placeholder="Họ tên, email hoặc số điện thoại"></div></label>
            <?php if (!$isCustomerManagement): ?><label><span>Vai trò</span><select name="role"><option value="">Tất cả vai trò</option><?php foreach ($accountRoles as $value => $label): ?><option value="<?= $value ?>" <?= $accountFilters['role'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><option value="1" <?= $accountFilters['status'] === '1' ? 'selected' : '' ?>>Đang hoạt động</option><option value="0" <?= $accountFilters['status'] === '0' ? 'selected' : '' ?>>Đang khóa</option></select></label>
            <button class="button button-primary" type="submit">Áp dụng</button>
            <a class="admin-filter-clear" href="<?= $escape($accountsUrl(['q' => '', 'role' => '', 'status' => '', 'p' => 1])) ?>">Đặt lại</a>
        </form>
        <div class="admin-table-scroll">
            <table class="admin-table admin-accounts-table">
                <thead><tr><th scope="col"><?= $isCustomerManagement ? 'Khách hàng' : 'Tài khoản' ?></th><th scope="col">Liên hệ</th><?php if (!$isCustomerManagement): ?><th scope="col">Vai trò</th><?php endif; ?><th scope="col">Trạng thái</th><th scope="col"><?= $isCustomerManagement ? 'Ngày đăng ký' : 'Ngày tạo' ?></th><th scope="col">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($accountList['items'] as $row):
                    $isSelf = (int) $row['id_tai_khoan'] === (int) $account['id_tai_khoan'];
                    $isActive = (int) $row['dang_hoat_dong'] === 1;
                    $rowAvatar = AdminController::avatarUrl($row['hinh_anh']);
                ?>
                    <tr data-account-row="<?= (int) $row['id_tai_khoan'] ?>">
                        <td><div class="admin-account-cell"><span class="admin-list-avatar" aria-hidden="true"><?php if ($rowAvatar !== ''): ?><img src="<?= $escape($rowAvatar) ?>" alt=""><?php else: ?><?= $escape(mb_strtoupper(mb_substr($row['ho_ten'], 0, 1, 'UTF-8'), 'UTF-8')) ?><?php endif; ?></span><div><strong><?= $escape($row['ho_ten']) ?><?php if ($isSelf): ?> <span class="admin-self-tag">Bạn</span><?php endif; ?></strong><small>#<?= (int) $row['id_tai_khoan'] ?></small></div></div></td>
                        <td><span class="admin-contact-email"><?= $escape($row['email']) ?></span><small class="admin-contact-phone"><?= $escape($row['so_dien_thoai']) ?></small></td>
                        <?php if (!$isCustomerManagement): ?><td><span class="admin-role-badge admin-role-<?= $escape($row['vai_tro']) ?>"><?= $escape($accountRoles[$row['vai_tro']] ?? $row['vai_tro']) ?></span></td><?php endif; ?>
                        <td><span class="admin-status-badge<?= $isActive ? ' is-active' : ' is-locked' ?>"><span aria-hidden="true"></span><?= $isActive ? 'Đang hoạt động' : 'Đang khóa' ?></span></td>
                        <td class="admin-account-created"><?= $escape(date('d/m/Y', strtotime($row['tao_luc']))) ?></td>
                        <td><div class="admin-row-actions">
                            <?php if ($isSelf): ?>
                                <a class="admin-row-profile" href="index.php?action=profile">Hồ sơ của tôi <span aria-hidden="true">↗</span></a>
                            <?php else: ?>
                                <a class="admin-row-button" href="<?= $escape($accountsUrl(['edit' => (int) $row['id_tai_khoan'], 'mode' => null])) ?>#account-editor" aria-label="Sửa tài khoản <?= $escape($row['ho_ten']) ?>" title="Chỉnh sửa"><svg class="icon"><use href="#a-edit"/></svg><span>Sửa</span></a>
                                <form method="post" action="<?= $escape($accountsUrl()) ?>" data-account-confirm="<?= $escape(($isActive ? 'Khóa' : 'Mở khóa') . ' tài khoản ' . $row['email'] . '?') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="operation" value="status"><input type="hidden" name="id_tai_khoan" value="<?= (int) $row['id_tai_khoan'] ?>"><input type="hidden" name="dang_hoat_dong" value="<?= $isActive ? '0' : '1' ?>">
                                    <button class="admin-row-button<?= $isActive ? ' is-lock' : ' is-unlock' ?>" type="submit" aria-label="<?= $isActive ? 'Khóa' : 'Mở khóa' ?> tài khoản <?= $escape($row['ho_ten']) ?>" title="<?= $isActive ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>"><svg class="icon"><use href="#<?= $isActive ? 'a-lock' : 'a-unlock' ?>"/></svg></button>
                                </form>
                                <a class="admin-row-button is-delete" href="<?= $escape($accountsUrl(['delete' => (int) $row['id_tai_khoan']])) ?>#account-delete" aria-label="Xóa tài khoản <?= $escape($row['ho_ten']) ?>" title="Xóa tài khoản"><svg class="icon"><use href="#a-trash"/></svg></a>
                            <?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$accountList['items']): ?><tr><td colspan="<?= $accountColumnCount ?>" class="admin-empty"><svg class="icon"><use href="#a-search"/></svg><strong>Không tìm thấy <?= $accountNoun ?> phù hợp</strong><p>Thử thay đổi từ khóa hoặc đặt lại bộ lọc.</p></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="admin-accounts-list-footer">
            <?php if ($accountList['pages'] > 1): ?>
            <nav class="admin-accounts-pagination" aria-label="Phân trang <?= $accountNoun ?>">
                <?php if ($accountList['page'] > 1): ?><a href="<?= $escape($accountsUrl(['p' => $accountList['page'] - 1])) ?>" aria-label="Trang trước">‹</a><?php endif; ?>
                <?php
                    $pageLinks = array_unique(array_merge([1], range(max(1, $accountList['page'] - 2), min($accountList['pages'], $accountList['page'] + 2)), [$accountList['pages']]));
                    sort($pageLinks);
                    $previousPage = 0;
                    foreach ($pageLinks as $number):
                ?>
                    <?php if ($previousPage && $number > $previousPage + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                    <a href="<?= $escape($accountsUrl(['p' => $number])) ?>" <?= $number === $accountList['page'] ? 'aria-current="page"' : '' ?> aria-label="Trang <?= $number ?>"><?= $number ?></a>
                <?php $previousPage = $number; endforeach; ?>
                <?php if ($accountList['page'] < $accountList['pages']): ?><a href="<?= $escape($accountsUrl(['p' => $accountList['page'] + 1])) ?>" aria-label="Trang tiếp theo">›</a><?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>
    <p class="admin-accounts-help"><svg class="icon"><use href="#a-shield"/></svg>Chỉ quản trị viên được quản lý <?= $accountNoun ?>. Khóa tài khoản sẽ giữ lại thông tin đã có.</p>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
