<?php
/**
 * View hồ sơ cá nhân & đổi mật khẩu cho tài khoản khách hàng.
 * @var array $account
 * @var array $profileValues
 * @var array $profileErrors
 * @var string $profileMessage
 * @var bool $profileSuccess
 * @var array $passwordErrors
 * @var string $passwordMessage
 * @var bool $passwordSuccess
 * @var string $avatarUrl
 * @var string $csrfToken
 */
$page = 'profile';
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$roleLabels = [
    'khach_hang' => 'Khách hàng',
    'admin' => 'Quản trị viên',
    'nhan_vien' => 'Nhân viên',
];
$roleLabel = $roleLabels[$account['vai_tro'] ?? 'khach_hang'] ?? 'Thành viên';
$joinedDate = !empty($account['tao_luc']) ? date('d/m/Y', strtotime((string) $account['tao_luc'])) : 'Chưa cập nhật';
$cssVersion = (string) @filemtime(__DIR__ . '/../../../public/assets/css/style.css');
$jsVersion = (string) @filemtime(__DIR__ . '/../../../public/assets/js/script.js');

$passwordFields = [
    [
        'id' => 'customer-current-password',
        'name' => 'mat_khau_hien_tai',
        'label' => 'Mật khẩu hiện tại',
        'placeholder' => 'Nhập mật khẩu đang sử dụng',
        'autocomplete' => 'current-password',
    ],
    [
        'id' => 'customer-new-password',
        'name' => 'mat_khau_moi',
        'label' => 'Mật khẩu mới',
        'placeholder' => 'Nhập mật khẩu mới',
        'autocomplete' => 'new-password',
    ],
    [
        'id' => 'customer-confirm-password',
        'name' => 'xac_nhan_mat_khau',
        'label' => 'Xác nhận mật khẩu mới',
        'placeholder' => 'Nhập lại mật khẩu mới',
        'autocomplete' => 'new-password',
    ],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Quản lý thông tin hồ sơ cá nhân và đổi mật khẩu tài khoản OnTrip.">
    <title>Hồ sơ cá nhân | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cssVersion ?>">
    <script src="assets/js/script.js?v=<?= $jsVersion ?>" defer></script>
</head>
<body class="booking-page customer-profile-page">
<?php require __DIR__ . '/../partials/header.php'; ?>
<main id="noi-dung" class="booking-main">
    <div class="container">
        <header class="booking-heading customer-profile-heading">
            <div>
                <span class="detail-kicker">TÀI KHOẢN CỦA TÔI</span>
                <h1>Hồ sơ cá nhân</h1>
                <p>Cập nhật thông tin liên hệ, ảnh đại diện và đổi mật khẩu cho tài khoản OnTrip của bạn.</p>
            </div>
            <a class="customer-profile-back" href="index.php"><span aria-hidden="true">←</span> Về trang chủ</a>
        </header>

        <div class="customer-profile-layout">
            <aside class="customer-profile-card" aria-label="Tóm tắt tài khoản">
                <div class="customer-profile-identity">
                    <span class="customer-profile-avatar">
                        <img data-customer-avatar-preview alt="Ảnh đại diện" <?= $avatarUrl !== '' ? 'src="' . $escape($avatarUrl) . '"' : 'hidden' ?>>
                        <svg class="icon" data-customer-avatar-default aria-hidden="true" <?= $avatarUrl !== '' ? 'hidden' : '' ?>><use href="#i-user"/></svg>
                    </span>
                    <h2><?= $escape($account['ho_ten']) ?></h2>
                    <span class="customer-role-badge"><?= $escape($roleLabel) ?></span>
                </div>

                <div class="customer-profile-details">
                    <h3>THÔNG TIN TÀI KHOẢN</h3>
                    <dl>
                        <div>
                            <dt>Email</dt>
                            <dd><?= $escape($account['email']) ?></dd>
                        </div>
                        <div>
                            <dt>Số điện thoại</dt>
                            <dd><?= $escape($account['so_dien_thoai'] ?: 'Chưa cập nhật') ?></dd>
                        </div>
                        <div>
                            <dt>Ngày tham gia</dt>
                            <dd><?= $escape($joinedDate) ?></dd>
                        </div>
                        <div>
                            <dt>Trạng thái</dt>
                            <dd><span class="customer-account-status"><span aria-hidden="true"></span>Đang hoạt động</span></dd>
                        </div>
                    </dl>
                    <nav class="customer-profile-links" aria-label="Liên kết tài khoản">
                        <a class="customer-profile-anchor" href="#doi-mat-khau">Đổi mật khẩu</a>
                        <a class="customer-profile-anchor is-secondary" href="index.php?page=orders">Đơn đặt của tôi</a>
                        <a class="customer-profile-anchor is-secondary" href="index.php?page=tu_van">Tư vấn của tôi</a>
                    </nav>
                </div>
            </aside>

            <div class="customer-profile-sections">
                <section id="thong-tin-ca-nhan" class="customer-profile-panel" aria-labelledby="customer-profile-form-title">
                    <div class="customer-panel-heading">
                        <span class="customer-panel-icon" aria-hidden="true"><svg class="icon"><use href="#i-user"/></svg></span>
                        <div>
                            <h2 id="customer-profile-form-title">Thông tin cá nhân</h2>
                            <p>Chỉnh sửa họ tên, email, số điện thoại và ảnh đại diện của bạn.</p>
                        </div>
                    </div>

                    <form class="customer-profile-form" data-customer-profile-form method="post" enctype="multipart/form-data" action="index.php?page=profile">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="form_type" value="profile">

                        <div class="customer-form-message<?= $profileSuccess ? ' is-success' : '' ?>" data-customer-form-message role="<?= $profileSuccess ? 'status' : 'alert' ?>" <?= $profileMessage === '' ? 'hidden' : '' ?>><?= $escape($profileMessage) ?></div>

                        <div class="customer-form-grid">
                            <div class="customer-form-field">
                                <label for="customer-profile-name">Họ và tên <span>*</span></label>
                                <input id="customer-profile-name" name="ho_ten" type="text" value="<?= $escape($profileValues['ho_ten']) ?>" autocomplete="name" minlength="2" maxlength="120" aria-invalid="<?= isset($profileErrors['ho_ten']) ? 'true' : 'false' ?>" aria-describedby="customer-profile-name-error" required>
                                <p class="customer-field-error" id="customer-profile-name-error" <?= isset($profileErrors['ho_ten']) ? '' : 'hidden' ?>><?= $escape($profileErrors['ho_ten'] ?? '') ?></p>
                            </div>

                            <div class="customer-form-field">
                                <label for="customer-profile-role">Loại tài khoản</label>
                                <input id="customer-profile-role" type="text" value="<?= $escape($roleLabel) ?>" readonly>
                            </div>

                            <div class="customer-form-field">
                                <label for="customer-profile-email">Địa chỉ email <span>*</span></label>
                                <input id="customer-profile-email" name="email" type="email" value="<?= $escape($profileValues['email']) ?>" autocomplete="email" maxlength="254" aria-invalid="<?= isset($profileErrors['email']) ? 'true' : 'false' ?>" aria-describedby="customer-profile-email-error" required>
                                <p class="customer-field-error" id="customer-profile-email-error" <?= isset($profileErrors['email']) ? '' : 'hidden' ?>><?= $escape($profileErrors['email'] ?? '') ?></p>
                            </div>

                            <div class="customer-form-field">
                                <label for="customer-profile-phone">Số điện thoại <span>*</span></label>
                                <input id="customer-profile-phone" name="so_dien_thoai" type="tel" value="<?= $escape($profileValues['so_dien_thoai']) ?>" autocomplete="tel" maxlength="20" aria-invalid="<?= isset($profileErrors['so_dien_thoai']) ? 'true' : 'false' ?>" aria-describedby="customer-profile-phone-error" required>
                                <p class="customer-field-error" id="customer-profile-phone-error" <?= isset($profileErrors['so_dien_thoai']) ? '' : 'hidden' ?>><?= $escape($profileErrors['so_dien_thoai'] ?? '') ?></p>
                            </div>
                        </div>

                        <div class="customer-form-field customer-avatar-field">
                            <label for="customer-profile-avatar">Ảnh đại diện</label>
                            <input id="customer-profile-avatar" name="hinh_anh" type="file" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" data-customer-avatar-input aria-describedby="customer-avatar-hint customer-avatar-error">
                            <small id="customer-avatar-hint">Hỗ trợ JPG, PNG, GIF hoặc WEBP, tối đa 5 MB. Chọn ảnh rồi nhấn Lưu thay đổi.</small>
                            <p class="customer-field-error" id="customer-avatar-error" role="alert" <?= isset($profileErrors['hinh_anh']) ? '' : 'hidden' ?>><?= $escape($profileErrors['hinh_anh'] ?? '') ?></p>
                        </div>

                        <div class="customer-form-actions">
                            <button class="button button-primary" type="submit">Lưu thay đổi</button>
                        </div>
                    </form>
                </section>

                <section id="doi-mat-khau" class="customer-profile-panel" aria-labelledby="customer-password-form-title">
                    <div class="customer-panel-heading">
                        <span class="customer-panel-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5 19 11"/></svg>
                        </span>
                        <div>
                            <h2 id="customer-password-form-title">Đổi mật khẩu</h2>
                            <p>Cập nhật mật khẩu mới để tăng cường bảo mật cho tài khoản của bạn.</p>
                        </div>
                    </div>

                    <form class="customer-profile-form" data-customer-password-form method="post" action="index.php?page=profile#doi-mat-khau">
                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                        <input type="hidden" name="form_type" value="password">

                        <div class="customer-form-message<?= $passwordSuccess ? ' is-success' : '' ?>" data-customer-form-message role="<?= $passwordSuccess ? 'status' : 'alert' ?>" <?= $passwordMessage === '' ? 'hidden' : '' ?>><?= $escape($passwordMessage) ?></div>

                        <div class="customer-password-fields">
                            <?php foreach ($passwordFields as $field): ?>
                            <div class="customer-form-field">
                                <label for="<?= $field['id'] ?>"><?= $field['label'] ?> <span>*</span></label>
                                <div class="auth-password-wrap customer-password-wrap">
                                    <input id="<?= $field['id'] ?>" name="<?= $field['name'] ?>" type="password" autocomplete="<?= $field['autocomplete'] ?>" placeholder="<?= $field['placeholder'] ?>" aria-invalid="<?= isset($passwordErrors[$field['name']]) ? 'true' : 'false' ?>" aria-describedby="<?= $field['id'] ?>-error<?= $field['name'] === 'mat_khau_moi' ? ' customer-password-hint' : '' ?>" <?= $field['name'] === 'mat_khau_moi' ? 'minlength="8"' : '' ?> required>
                                    <button class="auth-password-toggle" type="button" data-customer-password-toggle aria-controls="<?= $field['id'] ?>" aria-label="Hiện <?= mb_strtolower($field['label'], 'UTF-8') ?>" aria-pressed="false">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="auth-eye-slash" d="M3 3 21 21"/></svg>
                                    </button>
                                </div>
                                <?php if ($field['name'] === 'mat_khau_moi'): ?>
                                <small id="customer-password-hint">Ít nhất 8 ký tự, tối đa 72 byte và khác mật khẩu hiện tại.</small>
                                <?php endif; ?>
                                <p class="customer-field-error" id="<?= $field['id'] ?>-error" <?= isset($passwordErrors[$field['name']]) ? '' : 'hidden' ?>><?= $escape($passwordErrors[$field['name']] ?? '') ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="customer-form-actions">
                            <button class="button button-primary" type="submit">Cập nhật mật khẩu</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
