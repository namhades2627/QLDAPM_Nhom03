<?php
/** @var array $account */
/** @var string $roleName */
/** @var string $csrfToken */
/** @var string $adminSection */
/** @var array $profileValues */
/** @var array $profileErrors */
/** @var string $profileMessage */
/** @var bool $profileSuccess */
/** @var string $avatarUrl */
require __DIR__ . '/partials/header.php';
?>
<div class="admin-page-heading">
    <div><h1>Hồ sơ cá nhân</h1><p>Thông tin tài khoản và liên hệ của bạn tại OnTrip.</p></div>
    <a class="admin-back-link" href="index.php"><span aria-hidden="true">←</span> Bảng điều khiển</a>
</div>
<div class="admin-profile-layout">
    <aside class="admin-panel admin-profile-card" aria-label="Thông tin tài khoản">
        <div class="admin-profile-identity">
            <span class="admin-profile-avatar"><img data-admin-avatar-preview alt="Ảnh đại diện" <?= $avatarUrl !== '' ? 'src="' . $escape($avatarUrl) . '"' : 'hidden' ?>><svg class="icon" data-admin-avatar-default aria-hidden="true" <?= $avatarUrl !== '' ? 'hidden' : '' ?>><use href="#a-user"/></svg></span>
            <h2><?= $escape($account['ho_ten']) ?></h2>
            <span class="admin-account-role"><?= $escape($roleName) ?></span>
        </div>
        <div class="admin-profile-details">
            <h3>THÔNG TIN TÀI KHOẢN</h3>
            <dl>
                <div><dt>Email</dt><dd><?= $escape($account['email']) ?></dd></div>
                <div><dt>Số điện thoại</dt><dd><?= $escape($account['so_dien_thoai']) ?></dd></div>
                <div><dt>Trạng thái</dt><dd><span class="admin-account-status"><span aria-hidden="true"></span>Đang hoạt động</span></dd></div>
            </dl>
            <a class="admin-password-link" href="index.php?action=password"><svg class="icon" aria-hidden="true"><use href="#a-key"/></svg>Đổi mật khẩu</a>
        </div>
    </aside>
    <section class="admin-panel admin-profile-editor" aria-labelledby="profile-form-title">
        <div class="admin-form-heading"><span class="admin-form-heading-icon"><svg class="icon" aria-hidden="true"><use href="#a-user"/></svg></span><div><h2 id="profile-form-title">Thông tin cá nhân</h2><p>Cập nhật họ tên, email và số điện thoại liên hệ.</p></div></div>
        <form class="admin-account-form" data-admin-profile-form method="post" enctype="multipart/form-data" action="index.php?action=profile">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="admin-form-message<?= $profileSuccess ? ' is-success' : '' ?>" data-admin-preview-message role="<?= $profileSuccess ? 'status' : 'alert' ?>" <?= $profileMessage === '' ? 'hidden' : '' ?>><?= $escape($profileMessage) ?></div>
            <div class="admin-form-grid">
                <div class="admin-form-field"><label for="profile-name">Họ và tên <span>*</span></label><input id="profile-name" name="ho_ten" type="text" value="<?= $escape($profileValues['ho_ten']) ?>" autocomplete="name" minlength="2" maxlength="120" aria-invalid="<?= isset($profileErrors['ho_ten']) ? 'true' : 'false' ?>" aria-describedby="profile-name-error" required><p class="admin-field-error" id="profile-name-error" <?= isset($profileErrors['ho_ten']) ? '' : 'hidden' ?>><?= $escape($profileErrors['ho_ten'] ?? '') ?></p></div>
                <div class="admin-form-field"><label for="profile-role">Vai trò</label><input id="profile-role" type="text" value="<?= $escape($roleName) ?>" readonly><small>Vai trò do quản trị viên hệ thống cấp.</small></div>
                <div class="admin-form-field"><label for="profile-email">Địa chỉ email <span>*</span></label><input id="profile-email" name="email" type="email" value="<?= $escape($profileValues['email']) ?>" autocomplete="email" maxlength="254" aria-invalid="<?= isset($profileErrors['email']) ? 'true' : 'false' ?>" aria-describedby="profile-email-hint profile-email-error" required><small id="profile-email-hint"></small><p class="admin-field-error" id="profile-email-error" <?= isset($profileErrors['email']) ? '' : 'hidden' ?>><?= $escape($profileErrors['email'] ?? '') ?></p></div>
                <div class="admin-form-field"><label for="profile-phone">Số điện thoại <span>*</span></label><input id="profile-phone" name="so_dien_thoai" type="tel" value="<?= $escape($profileValues['so_dien_thoai']) ?>" autocomplete="tel" maxlength="20" aria-invalid="<?= isset($profileErrors['so_dien_thoai']) ? 'true' : 'false' ?>" aria-describedby="profile-phone-error" required><p class="admin-field-error" id="profile-phone-error" <?= isset($profileErrors['so_dien_thoai']) ? '' : 'hidden' ?>><?= $escape($profileErrors['so_dien_thoai'] ?? '') ?></p></div>
            </div>
            <div class="admin-form-field admin-avatar-field">
                <label for="profile-avatar">Thay đổi ảnh đại diện</label>
                <input id="profile-avatar" name="hinh_anh" type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" data-admin-avatar-input aria-describedby="profile-avatar-hint profile-avatar-error">
                <small id="profile-avatar-hint">JPG, PNG, GIF hoặc WEBP, tối đa 5 MB. Chọn ảnh rồi nhấn Lưu thay đổi để cập nhật.</small>
                <p class="admin-avatar-error" id="profile-avatar-error" role="alert" <?= isset($profileErrors['hinh_anh']) ? '' : 'hidden' ?>><?= $escape($profileErrors['hinh_anh'] ?? '') ?></p>
            </div>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="index.php">Hủy bỏ</a><button class="button button-primary" type="submit">Lưu thay đổi</button></div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
