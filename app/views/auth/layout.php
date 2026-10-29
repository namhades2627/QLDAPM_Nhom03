<?php
/**
 * Khung giao diện dùng chung, nhận cấu hình từ login.php hoặc register.php.
 * @var string $page
 * @var bool $isRegister
 * @var array $errors
 * @var array $values
 * @var string $message
 * @var string $csrfToken
 * @var bool $isAdminLogin
 * @var string $authAssetPrefix
 * @var string $authHomeUrl
 * @var string $authFormAction
 */
$errors = $errors ?? [];
$values = $values ?? [];
$message = $message ?? '';
$csrfToken = $csrfToken ?? '';
$escape = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$isAdminLogin = $isAdminLogin ?? false;
$authAssetPrefix = $authAssetPrefix ?? '';
$authHomeUrl = $authHomeUrl ?? 'index.php';
$authFormAction = $authFormAction ?? ('index.php?page=' . ($isRegister ? 'register' : 'login'));
$authTitle = $isAdminLogin ? 'Đăng nhập quản trị' : ($isRegister ? 'Đăng ký' : 'Đăng nhập');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $isRegister ? 'Đăng ký tài khoản' : 'Đăng nhập' ?> OnTrip để bắt đầu hành trình của bạn.">
    <title><?= $authTitle ?> | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="<?= $escape($authAssetPrefix) ?>assets/images/favicon.svg">
    <link rel="stylesheet" href="<?= $escape($authAssetPrefix) ?>assets/css/style.css">
    <script src="<?= $escape($authAssetPrefix) ?>assets/js/script.js" defer></script>
</head>
<body class="auth-page<?= $isAdminLogin ? ' admin-login-page' : '' ?>">
<main id="noi-dung" class="auth-main">
    <div class="auth-shell<?= $isRegister ? ' auth-shell-register' : '' ?>">
        <section class="auth-panel" aria-labelledby="auth-title">
            <a class="auth-back" href="<?= $escape($authHomeUrl) ?>"><span aria-hidden="true">←</span> Về trang chủ</a>
            <a class="brand auth-brand" href="<?= $escape($authHomeUrl) ?>" aria-label="OnTrip — Trang chủ">
                <svg class="brand-mark" viewBox="0 0 44 50" aria-hidden="true"><path d="M22 3C11.8 3 5 10.2 5 19c0 12 17 27 17 27s17-15 17-27C39 10.2 32.2 3 22 3Z"/><circle cx="22" cy="19" r="6.5"/></svg>
                <span class="brand-word">On<span>Trip</span><small>ĐI & KHÁM PHÁ</small></span>
            </a>
            <header class="auth-heading">
                <h1 id="auth-title"><?= $authTitle ?></h1>
                <p><?= $isAdminLogin ? 'Dành cho quản trị viên và nhân viên OnTrip.' : ($isRegister ? 'Tạo tài khoản để bắt đầu hành trình cùng OnTrip.' : 'Sử dụng email và mật khẩu của bạn để tiếp tục.') ?></p>
            </header>

            <!-- Controller kiểm tra lại dữ liệu, kể cả khi tắt JavaScript. -->
            <form class="auth-form" data-auth-form data-auth-mode="<?= $isRegister ? 'register' : 'login' ?>" method="post" action="<?= $escape($authFormAction) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="auth-message" data-auth-message role="alert" <?= $message === '' ? 'hidden' : '' ?>><?= $escape($message) ?></div>
                <?php if ($isRegister): ?>
                <div class="auth-field">
                    <label for="auth-name">Họ và tên</label>
                    <input id="auth-name" aria-invalid="<?= isset($errors['ho_ten']) ? 'true' : 'false' ?>" name="ho_ten" value="<?= $escape($values['ho_ten'] ?? '') ?>" type="text" autocomplete="name" maxlength="120" placeholder="Nhập họ và tên của bạn" aria-describedby="auth-name-error" required>
                    <p id="auth-name-error" class="auth-field-error" <?= isset($errors['ho_ten']) ? '' : 'hidden' ?>><?= $escape($errors['ho_ten'] ?? '') ?></p>
                </div>
                <?php endif; ?>
                <div class="auth-field">
                    <label for="auth-email">Email</label>
                    <input id="auth-email" aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>" name="email" value="<?= $escape($values['email'] ?? '') ?>" type="email" inputmode="email" autocomplete="<?= $isRegister ? 'email' : 'username' ?>" maxlength="254" placeholder="abc123@gmail.com" aria-describedby="auth-email-error" spellcheck="false" autocapitalize="none" required>
                    <p id="auth-email-error" class="auth-field-error" <?= isset($errors['email']) ? '' : 'hidden' ?>><?= $escape($errors['email'] ?? '') ?></p>
                </div>
                <?php if ($isRegister): ?>
                <div class="auth-field">
                    <label for="auth-phone">Số điện thoại</label>
                    <input id="auth-phone" aria-invalid="<?= isset($errors['so_dien_thoai']) ? 'true' : 'false' ?>" name="so_dien_thoai" value="<?= $escape($values['so_dien_thoai'] ?? '') ?>" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" placeholder="Nhập số điện thoại liên hệ" aria-describedby="auth-phone-error" required>
                    <p id="auth-phone-error" class="auth-field-error" <?= isset($errors['so_dien_thoai']) ? '' : 'hidden' ?>><?= $escape($errors['so_dien_thoai'] ?? '') ?></p>
                </div>
                <?php endif; ?>
                <div class="auth-field">
                    <label for="auth-password">Mật khẩu</label>
                    <div class="auth-password-wrap">
                        <input id="auth-password" aria-invalid="<?= isset($errors['mat_khau']) ? 'true' : 'false' ?>" name="mat_khau" type="password" autocomplete="<?= $isRegister ? 'new-password' : 'current-password' ?>" placeholder="<?= $isRegister ? 'Tạo mật khẩu của bạn' : 'Nhập mật khẩu' ?>" aria-describedby="auth-password-error<?= $isRegister ? ' auth-password-hint' : '' ?>" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle aria-controls="auth-password" aria-label="Hiện mật khẩu" aria-pressed="false">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="auth-eye-slash" d="M3 3 21 21"/></svg>
                        </button>
                    </div>
                    <?php if ($isRegister): ?><p id="auth-password-hint" class="auth-field-hint">Sử dụng ít nhất 8 ký tự cho mật khẩu của bạn.</p><?php endif; ?>
                    <p id="auth-password-error" class="auth-field-error" <?= isset($errors['mat_khau']) ? '' : 'hidden' ?>><?= $escape($errors['mat_khau'] ?? '') ?></p>
                </div>
                <?php if ($isRegister): ?>
                <div class="auth-field">
                    <label for="auth-confirm">Xác nhận mật khẩu</label>
                    <div class="auth-password-wrap">
                        <input id="auth-confirm" aria-invalid="<?= isset($errors['xac_nhan_mat_khau']) ? 'true' : 'false' ?>" name="xac_nhan_mat_khau" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu vừa tạo" aria-describedby="auth-confirm-error" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle aria-controls="auth-confirm" aria-label="Hiện xác nhận mật khẩu" aria-pressed="false">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="auth-eye-slash" d="M3 3 21 21"/></svg>
                        </button>
                    </div>
                    <p id="auth-confirm-error" class="auth-field-error" <?= isset($errors['xac_nhan_mat_khau']) ? '' : 'hidden' ?>><?= $escape($errors['xac_nhan_mat_khau'] ?? '') ?></p>
                </div>
                <?php endif; ?>
                <button class="button button-primary auth-submit" type="submit"><?= $isRegister ? 'Đăng ký' : 'Đăng nhập' ?></button>
                <?php if (!$isAdminLogin): ?>
                <p class="auth-switch"><?= $isRegister ? 'Bạn đã có tài khoản?' : 'Bạn chưa có tài khoản?' ?> <a href="index.php?page=<?= $isRegister ? 'login' : 'register' ?>"><?= $isRegister ? 'Đăng nhập' : 'Đăng ký ngay' ?></a></p>
                <?php endif; ?>
            </form>
        </section>
    </div>
</main>
</body>
</html>
