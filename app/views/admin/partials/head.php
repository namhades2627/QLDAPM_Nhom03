<?php
/**
 * Khung đầu trang dùng chung cho mọi trang quản trị (sidebar + topbar + tiêu đề trang).
 * @var string $adminTitle    Tiêu đề tab trình duyệt
 * @var string $adminHeading  Tiêu đề trang
 * @var string $adminSubtitle Mô tả ngắn dưới tiêu đề
 * @var string $adminActive   'dashboard' | 'schedules'
 * @var string $adminActions  HTML tuỳ chọn hiển thị bên phải tiêu đề
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 */
$escape = static function ($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$adminActive = $adminActive ?? 'dashboard';
$adminActions = $adminActions ?? '';
$navItems = ['dashboard' => 'Bảng điều khiển', 'schedules' => 'Lịch khởi hành'];
$crumb = $navItems[$adminActive] ?? 'Bảng điều khiển';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $escape($adminTitle) ?> | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <link rel="stylesheet" href="../public/assets/css/admin.css">
    <script src="../public/assets/js/script.js" defer></script>
</head>
<body class="admin-page">
<svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="a-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
    <symbol id="a-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
    <symbol id="a-pin" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></symbol>
    <symbol id="a-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="a-exit" viewBox="0 0 24 24"><path d="M9 4H4v16h5m5-14 6 6-6 6M9 12h11"/></symbol>
    <symbol id="a-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
    <symbol id="a-message" viewBox="0 0 24 24"><path d="M21 14a3 3 0 0 1-3 3H8l-5 4V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8Z"/><path d="M7 8h10M7 12h6"/></symbol>
    <symbol id="a-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></symbol>
    <symbol id="a-key" viewBox="0 0 24 24"><circle cx="8" cy="8" r="5"/><path d="m12 12 9 9m-5-5 3-3m-6 0 3-3"/></symbol>
    <symbol id="a-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
    <symbol id="a-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h2m4 0h2m-8 3h2"/></symbol>
    <symbol id="a-tour" viewBox="0 0 24 24"><path d="m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2V5Zm6-2v16m6-14v16"/></symbol>
    <symbol id="a-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></symbol>
    <symbol id="a-shield" viewBox="0 0 24 24"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/></symbol>
    <symbol id="a-seat" viewBox="0 0 24 24"><path d="M6 4v9a3 3 0 0 0 3 3h6"/><path d="M18 20v-3a2 2 0 0 0-2-2H9"/><path d="M6 8h6"/></symbol>
    <symbol id="a-back" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6"/></symbol>
</svg>
<a class="skip-link" href="#admin-content">Đi đến nội dung</a>
<div class="admin-layout" data-admin-layout>
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Thanh bên quản trị">
        <a class="brand admin-brand" href="index.php" aria-label="OnTrip quản trị">
            <svg class="brand-mark" viewBox="0 0 44 50" aria-hidden="true"><path d="M22 3C11.8 3 5 10.2 5 19c0 12 17 27 17 27s17-15 17-27C39 10.2 32.2 3 22 3Z"/><circle cx="22" cy="19" r="6.5"/></svg>
            <span class="brand-word">On<span>Trip</span></span>
            <span class="admin-brand-role"><?= $account['vai_tro'] === 'admin' ? 'ADMIN' : 'STAFF' ?></span>
        </a>
        <nav aria-label="Điều hướng quản trị" class="admin-navigation">
            <p class="admin-sidebar-label">HỆ THỐNG</p>
            <a class="admin-nav-link<?= $adminActive === 'dashboard' ? ' is-active' : '' ?>" href="index.php"<?= $adminActive === 'dashboard' ? ' aria-current="page"' : '' ?>><svg class="icon"><use href="#a-grid"/></svg><span>Bảng điều khiển</span></a>
            <p class="admin-sidebar-label">DỮ LIỆU ĐIỀU HÀNH</p>
            <a class="admin-nav-link<?= $adminActive === 'schedules' ? ' is-active' : '' ?>" href="index.php?action=schedules"<?= $adminActive === 'schedules' ? ' aria-current="page"' : '' ?>><svg class="icon"><use href="#a-calendar"/></svg><span>Lịch khởi hành</span></a>
            <a class="admin-nav-link" href="index.php#admin-catalog"><svg class="icon"><use href="#a-tour"/></svg><span>Danh mục tour</span></a>
        </nav>
        <div class="admin-sidebar-bottom">
            <a class="admin-nav-link" href="../public/index.php"><svg class="icon"><use href="#a-arrow"/></svg><span>Về website</span></a>
            <div class="admin-sidebar-caption">OnTrip · Quản trị &amp; vận hành</div>
        </div>
    </aside>
    <div class="admin-workspace">
        <header class="admin-topbar">
            <div class="admin-topbar-start">
                <button class="icon-button" type="button" data-admin-toggle aria-label="Thu gọn thanh bên" aria-controls="admin-sidebar" aria-expanded="true"><svg class="icon"><use href="#a-menu"/></svg></button>
                <span>Quản trị <b>/</b> <strong><?= $escape($crumb) ?></strong></span>
            </div>
            <div class="admin-topbar-actions">
                <button class="icon-button admin-toolbar-icon" type="button" aria-label="Tin nhắn" title="Tin nhắn"><svg class="icon"><use href="#a-message"/></svg></button>
                <button class="icon-button admin-toolbar-icon" type="button" aria-label="Thông báo" title="Thông báo"><svg class="icon"><use href="#a-bell"/></svg></button>
                <div class="admin-account-dropdown" data-admin-account>
                    <button class="admin-user" type="button" data-admin-account-toggle aria-expanded="false" aria-controls="admin-account-menu" aria-label="Mở menu tài khoản">
                        <span class="admin-avatar"><svg class="icon"><use href="#a-user"/></svg></span>
                        <span><strong><?= $escape($account['ho_ten']) ?></strong><small><?= $escape($roleName) ?></small></span>
                        <svg class="icon admin-account-chevron"><use href="#a-down"/></svg>
                    </button>
                    <div class="admin-account-menu" id="admin-account-menu" hidden>
                        <button class="admin-account-item" type="button"><svg class="icon"><use href="#a-user"/></svg>Hồ sơ cá nhân</button>
                        <button class="admin-account-item" type="button"><svg class="icon"><use href="#a-key"/></svg>Đổi mật khẩu</button>
                        <form method="post" action="index.php?action=logout">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <button class="admin-account-item admin-account-signout" type="submit"><svg class="icon"><use href="#a-exit"/></svg>Đăng xuất</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <main class="admin-content" id="admin-content">
            <div class="admin-page-heading">
                <div><h1><?= $escape($adminHeading) ?></h1><p><?= $escape($adminSubtitle) ?></p></div>
                <?= $adminActions ?>
            </div>
