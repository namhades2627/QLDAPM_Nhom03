<?php
$page = 'thong_bao';
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$unread = (int) ($GLOBALS['customerNotifications']['unread'] ?? 0);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông báo | OnTrip</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css?v=20261108-notifications">
    <script src="assets/js/script.js?v=20261108-notifications" defer></script>
</head>
<body class="booking-page">
<?php require __DIR__ . '/partials/header.php'; ?>
<main id="noi-dung" class="booking-main notifications-page">
    <div class="container">
        <nav class="detail-breadcrumb" aria-label="Đường dẫn trang"><a href="index.php">Trang chủ</a><span>/</span><span aria-current="page">Thông báo</span></nav>
        <header class="booking-heading">
            <span class="detail-kicker">TÀI KHOẢN</span>
            <h1>Thông báo</h1>
            <p><?= $unread ?> thông báo chưa đọc · Cập nhật đơn đặt và phản hồi tư vấn của bạn.</p>
        </header>
        <?php if ($unread > 0): ?>
        <form method="post" action="index.php?page=thong_bao" class="notification-read-all">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="operation" value="read_all">
            <button type="submit">Đánh dấu tất cả đã đọc</button>
        </form>
        <?php endif; ?>
        <section class="notification-list" aria-label="Danh sách thông báo">
            <?php if (!$notifications['items']): ?>
            <div class="notification-empty"><h2>Bạn chưa có thông báo</h2><p>Cập nhật đơn đặt và phản hồi tư vấn sẽ xuất hiện tại đây.</p><a href="index.php?page=tu_van">Xem tư vấn của tôi →</a></div>
            <?php endif; ?>
            <?php foreach ($notifications['items'] as $notice): ?>
            <form method="post" action="index.php?page=thong_bao">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="operation" value="read">
                <input type="hidden" name="id" value="<?= (int) $notice['id_thong_bao'] ?>">
                <button type="submit" class="notification-item<?= !$notice['da_doc'] ? ' is-unread' : '' ?>">
                    <strong><?= $escape($notice['tieu_de']) ?><?= !$notice['da_doc'] ? '<span class="notification-unread-label">Chưa đọc</span>' : '' ?></strong>
                    <span><?= $escape($notice['noi_dung']) ?></span>
                    <time datetime="<?= $escape(date('c', strtotime($notice['tao_luc']))) ?>"><?= $escape(date('d/m/Y H:i', strtotime($notice['tao_luc']))) ?></time>
                </button>
            </form>
            <?php endforeach; ?>
        </section>
        <?php if ($notifications['pages'] > 1): ?>
        <nav class="notification-pagination" aria-label="Phân trang thông báo">
            <?php if ($notifications['page'] > 1): ?><a href="index.php?page=thong_bao&amp;trang=<?= $notifications['page'] - 1 ?>">← Trang trước</a><?php endif; ?>
            <span>Trang <?= $notifications['page'] ?> / <?= $notifications['pages'] ?></span>
            <?php if ($notifications['page'] < $notifications['pages']): ?><a href="index.php?page=thong_bao&amp;trang=<?= $notifications['page'] + 1 ?>">Trang sau →</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
