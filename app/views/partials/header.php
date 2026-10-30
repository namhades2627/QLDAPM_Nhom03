<?php
// Trang chủ không truyền loại tour/vùng; dùng chuỗi trống để menu có trạng thái mặc định.
$tourType = $tourType ?? '';
$tourRegion = $tourRegion ?? '';
$isToursPage = in_array(($page ?? 'home'), ['tours', 'tour-detail'], true);
$isHomePage = ($page ?? 'home') === 'home';
?>
<!-- Biểu tượng vector nội bộ, không cần thư viện ngoài. -->
<svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="i-pin" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.8"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></symbol>
    <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
    <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m8 4 8 8-8 8"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></symbol>
    <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></symbol>
</svg>
<a class="skip-link" href="#noi-dung">Đi đến nội dung</a>
<header class="site-header">
    <div class="container navbar">
        <a class="brand" href="index.php" aria-label="OnTrip — Trang chủ">
            <svg class="brand-mark" viewBox="0 0 44 50" aria-hidden="true"><path d="M22 3C11.8 3 5 10.2 5 19c0 12 17 27 17 27s17-15 17-27C39 10.2 32.2 3 22 3Z"/><circle cx="22" cy="19" r="6.5"/></svg>
            <span class="brand-word">On<span>Trip</span><small>ĐI & KHÁM PHÁ</small></span>
        </a>
        <nav id="main-navigation" class="main-navigation" aria-label="Điều hướng chính">
            <a class="nav-link<?= $isHomePage ? ' is-current' : '' ?>" href="index.php" <?= $isHomePage ? 'aria-current="page"' : '' ?>>Trang chủ</a>
            <a class="nav-link" href="#gioi-thieu">Giới thiệu</a>
            <div class="dropdown">
                <button class="nav-link dropdown-toggle<?= $isToursPage ? ' is-current' : '' ?>" type="button" aria-expanded="false" aria-controls="tour-menu">Tour <svg class="icon chevron-down"><use href="#i-chevron"/></svg></button>
                <div id="tour-menu" class="dropdown-menu tour-menu">
                    <div class="tour-menu-column" role="group" aria-labelledby="domestic-menu-title">
                        <p id="domestic-menu-title" class="tour-menu-title">Tour trong nước</p>
                        <a href="index.php?page=tours&amp;type=domestic" data-tour-type="domestic" data-tour-region="" <?= $isToursPage && $tourType === 'domestic' && $tourRegion === '' ? 'class="is-selected"' : '' ?>>Tất cả tour trong nước</a>
                        <a href="index.php?page=tours&amp;type=domestic&amp;region=north" data-tour-type="domestic" data-tour-region="north" <?= $isToursPage && $tourType === 'domestic' && $tourRegion === 'north' ? 'class="is-selected"' : '' ?>>Miền Bắc</a>
                        <a href="index.php?page=tours&amp;type=domestic&amp;region=central" data-tour-type="domestic" data-tour-region="central" <?= $isToursPage && $tourType === 'domestic' && $tourRegion === 'central' ? 'class="is-selected"' : '' ?>>Miền Trung</a>
                        <a href="index.php?page=tours&amp;type=domestic&amp;region=south" data-tour-type="domestic" data-tour-region="south" <?= $isToursPage && $tourType === 'domestic' && $tourRegion === 'south' ? 'class="is-selected"' : '' ?>>Miền Nam</a>
                    </div>
                    <div class="tour-menu-column" role="group" aria-labelledby="international-menu-title">
                        <p id="international-menu-title" class="tour-menu-title">Tour quốc tế</p>
                        <a href="index.php?page=tours&amp;type=international" data-tour-type="international" data-tour-region="" <?= $isToursPage && $tourType === 'international' && $tourRegion === '' ? 'class="is-selected"' : '' ?>>Tất cả tour quốc tế</a>
                        <a href="index.php?page=tours&amp;type=international&amp;region=asia" data-tour-type="international" data-tour-region="asia" <?= $isToursPage && $tourType === 'international' && $tourRegion === 'asia' ? 'class="is-selected"' : '' ?>>Châu Á</a>
                        <a href="index.php?page=tours&amp;type=international&amp;region=europe" data-tour-type="international" data-tour-region="europe" <?= $isToursPage && $tourType === 'international' && $tourRegion === 'europe' ? 'class="is-selected"' : '' ?>>Châu Âu</a>
                        <a href="index.php?page=tours&amp;type=international&amp;region=americas-oceania" data-tour-type="international" data-tour-region="americas-oceania" <?= $isToursPage && $tourType === 'international' && $tourRegion === 'americas-oceania' ? 'class="is-selected"' : '' ?>>Châu Mỹ – Châu Úc</a>
                    </div>
                </div>
            </div>
            <a class="nav-link" href="#lien-he">Liên hệ</a>
        </nav>
        <div class="nav-actions">
            <!-- Ô tìm kiếm nhanh trên navbar; hiện chưa xử lý tìm dữ liệu tour. -->
            <div class="nav-search">
                <button class="icon-button nav-search-toggle" type="button" aria-label="Mở ô tìm tour" aria-expanded="false" aria-controls="navbar-search">
                    <svg class="icon"><use href="#i-search"/></svg>
                </button>
                <form id="navbar-search" class="nav-search-panel" role="search" aria-label="Tìm tour trên navbar" hidden>
                    <input id="navbar-search-input" type="search" name="q" placeholder="Tìm tour..." aria-label="Tìm tour" autocomplete="off">
                    <button class="icon-button nav-search-submit" type="submit" aria-label="Tìm tour">
                        <svg class="icon"><use href="#i-search"/></svg>
                    </button>
                </form>
            </div>
            <span class="action-divider" aria-hidden="true"></span>
            <div class="dropdown account-dropdown">
                <button class="icon-button dropdown-toggle" type="button" aria-label="Tài khoản" aria-expanded="false" aria-controls="account-menu"><svg class="icon"><use href="#i-user"/></svg></button>
                <div id="account-menu" class="dropdown-menu dropdown-menu-right">
                    <?php if (isset($_SESSION['tai_khoan'])): ?>
                    <span class="account-name"><?= htmlspecialchars($_SESSION['tai_khoan']['ho_ten'], ENT_QUOTES, 'UTF-8') ?></span>
                    <form method="post" action="index.php?page=logout">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button class="account-logout" type="submit">Đăng xuất</button>
                    </form>
                    <?php else: ?>
                    <a href="index.php?page=login">Đăng nhập</a>
                    <a href="index.php?page=register">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
            <button class="icon-button mobile-menu-toggle" type="button" aria-label="Mở menu" aria-expanded="false" aria-controls="main-navigation"><svg class="icon"><use href="#i-menu"/></svg></button>
        </div>
    </div>
</header>
