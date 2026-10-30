<?php
/** @var array $account */
/** @var string $roleName */
/** @var string $csrfToken */
/** @var string $adminSection */
require __DIR__ . '/partials/header.php';
?>
<div class="admin-page-heading">
    <div><h1>Hồ sơ cá nhân</h1><p>Thông tin tài khoản và liên hệ của bạn tại OnTrip.</p></div>
    <a class="admin-back-link" href="index.php"><span aria-hidden="true">←</span> Bảng điều khiển</a>
</div>
<div class="admin-profile-layout">
    <aside class="admin-panel admin-profile-card" aria-label="Thông tin tài khoản">
        <div class="admin-profile-identity">
            <span class="admin-profile-avatar"><svg class="icon" aria-hidden="true"><use href="#a-user"/></svg></span>
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
        <div class="admin-form-heading"><span class="admin-form-heading-icon"><svg class="icon" aria-hidden="true"><use href="#a-user"/></svg></span><div><h2 id="profile-form-title">Thông tin cá nhân</h2><p>Cập nhật họ tên và số điện thoại liên hệ.</p></div></div>
        <form class="admin-account-form" data-admin-preview-form="profile" method="post" action="index.php?action=profile">
            <div class="admin-form-message" data-admin-preview-message role="status" hidden></div>
            <div class="admin-form-grid">
                <div class="admin-form-field"><label for="profile-name">Họ và tên <span>*</span></label><input id="profile-name" name="ho_ten" type="text" value="<?= $escape($account['ho_ten']) ?>" autocomplete="name" minlength="2" maxlength="120" required></div>
                <div class="admin-form-field"><label for="profile-role">Vai trò</label><input id="profile-role" type="text" value="<?= $escape($roleName) ?>" readonly><small>Vai trò do quản trị viên hệ thống cấp.</small></div>
                <div class="admin-form-field"><label for="profile-email">Địa chỉ email</label><input id="profile-email" type="email" value="<?= $escape($account['email']) ?>" readonly><small>Email được dùng để đăng nhập.</small></div>
                <div class="admin-form-field"><label for="profile-phone">Số điện thoại <span>*</span></label><input id="profile-phone" name="so_dien_thoai" type="tel" value="<?= $escape($account['so_dien_thoai']) ?>" autocomplete="tel" maxlength="20" required></div>
            </div>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="index.php">Hủy bỏ</a><button class="button button-primary" type="submit" disabled data-admin-preview-submit>Lưu thay đổi</button></div>
            <p class="admin-form-preview-note">Bản xem trước giao diện · Chưa lưu thay đổi tài khoản.</p>
            <noscript><p class="admin-form-preview-note">Bật JavaScript để xem tương tác giao diện.</p></noscript>
        </form>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
