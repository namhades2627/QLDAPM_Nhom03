<?php
/** @var array $account */
/** @var string $roleName */
/** @var string $csrfToken */
/** @var string $adminSection */
require __DIR__ . '/partials/header.php';
$passwordFields = [
    ['id' => 'admin-current-password', 'name' => 'mat_khau_hien_tai', 'label' => 'Mật khẩu hiện tại', 'placeholder' => 'Nhập mật khẩu đang sử dụng', 'autocomplete' => 'current-password'],
    ['id' => 'admin-new-password', 'name' => 'mat_khau_moi', 'label' => 'Mật khẩu mới', 'placeholder' => 'Nhập mật khẩu mới', 'autocomplete' => 'new-password'],
    ['id' => 'admin-confirm-password', 'name' => 'xac_nhan_mat_khau', 'label' => 'Xác nhận mật khẩu mới', 'placeholder' => 'Nhập lại mật khẩu mới', 'autocomplete' => 'new-password'],
];
?>
<div class="admin-page-heading">
    <div><h1>Đổi mật khẩu</h1><p>Thiết lập mật khẩu mới cho tài khoản làm việc.</p></div>
    <a class="admin-back-link" href="index.php"><span aria-hidden="true">←</span> Bảng điều khiển</a>
</div>
<section class="admin-panel admin-password-panel" aria-labelledby="password-form-title">
    <div class="admin-form-heading"><span class="admin-form-heading-icon"><svg class="icon" aria-hidden="true"><use href="#a-key"/></svg></span><div><h2 id="password-form-title">Đổi mật khẩu tài khoản</h2><p>Sử dụng mật khẩu riêng cho tài khoản OnTrip của bạn.</p></div></div>
    <form class="admin-account-form" data-admin-preview-form="password" method="post" action="index.php?action=password">
        <div class="admin-password-account"><span class="admin-avatar"><svg class="icon" aria-hidden="true"><use href="#a-user"/></svg></span><div><strong><?= $escape($account['ho_ten']) ?></strong><span><?= $escape($account['email']) ?></span></div></div>
        <div class="admin-form-message" data-admin-preview-message role="status" hidden></div>
        <div class="admin-password-fields">
            <?php foreach ($passwordFields as $field): ?>
            <div class="admin-form-field">
                <label for="<?= $field['id'] ?>"><?= $field['label'] ?> <span>*</span></label>
                <div class="admin-password-wrap">
                    <input id="<?= $field['id'] ?>" name="<?= $field['name'] ?>" type="password" autocomplete="<?= $field['autocomplete'] ?>" placeholder="<?= $field['placeholder'] ?>" <?= $field['name'] === 'mat_khau_moi' ? 'minlength="8" aria-describedby="admin-password-hint"' : '' ?> required>
                    <button class="auth-password-toggle" type="button" data-admin-password-toggle aria-controls="<?= $field['id'] ?>" aria-label="Hiện <?= mb_strtolower($field['label'], 'UTF-8') ?>" aria-pressed="false"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="auth-eye-slash" d="M3 3 21 21"/></svg></button>
                </div>
                <?php if ($field['name'] === 'mat_khau_moi'): ?><small id="admin-password-hint">Ít nhất 8 ký tự và khác mật khẩu hiện tại</small><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="admin-form-actions"><a class="admin-secondary-button" href="index.php?action=profile"><span aria-hidden="true">←</span> Hồ sơ cá nhân</a><button class="button button-primary" type="submit" disabled data-admin-preview-submit>Cập nhật mật khẩu</button></div>
        <p class="admin-form-preview-note">Bản xem trước giao diện · Chưa xác thực hoặc thay đổi mật khẩu.</p>
        <noscript><p class="admin-form-preview-note">Bật JavaScript để xem tương tác giao diện.</p></noscript>
    </form>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
