<?php $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Tư vấn của tôi | OnTrip</title>
<link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="assets/css/style.css?v=20261114-chat-widget"><script src="assets/js/script.js?v=20261114-chat-widget" defer></script></head>
<body class="booking-page<?= $chatEmbedded ? ' chat-embedded' : '' ?>"><?php if (!$chatEmbedded) require __DIR__ . '/partials/header.php'; ?>
<main class="booking-main customer-chat-page" id="noi-dung"><div class="container">
<header class="booking-heading"><span class="detail-kicker">TÀI KHOẢN</span><h1>Tư vấn của tôi</h1><p>Trao đổi với nhân viên OnTrip và xem lại phản hồi của bạn.</p><a class="chat-start-link" target="_top" href="index.php?page=lien_he#form-section-title">+ Bắt đầu cuộc trò chuyện</a></header>
<div class="customer-chat-layout"><aside class="chat-inbox" aria-label="Các cuộc trò chuyện">
<?php if (!$threads['items']): ?><p class="notification-empty">Bạn chưa có cuộc trò chuyện. Gửi lời nhắn tại trang Liên hệ để được hỗ trợ.</p><?php endif; ?>
<?php foreach ($threads['items'] as $thread): ?><a class="chat-inbox-item<?= $chatThread && (int) $chatThread['id_yeu_cau'] === (int) $thread['id_yeu_cau'] ? ' is-active' : '' ?>" href="index.php?page=tu_van&amp;view=<?= (int) $thread['id_yeu_cau'] ?><?= $chatEmbedded ? '&amp;embed=1' : '' ?>&amp;p=<?= $threads['page'] ?>#conversation"><strong><?= $escape(YeuCauTuVanModel::TOPICS[$thread['chu_de']]) ?></strong><span><?= $escape(mb_strimwidth($thread['noi_dung'], 0, 90, '…', 'UTF-8')) ?></span><small><?= date('d/m/Y H:i', strtotime($thread['cap_nhat_luc'])) ?></small></a><?php endforeach; ?>
<?php if ($threads['pages'] > 1): ?><nav class="chat-inbox-pages" aria-label="Phân trang hội thoại"><?php if ($threads['page'] > 1): ?><a href="index.php?page=tu_van<?= $chatEmbedded ? '&amp;embed=1' : '' ?>&amp;p=<?= $threads['page'] - 1 ?>">← Trước</a><?php endif; ?><span><?= $threads['page'] ?> / <?= $threads['pages'] ?></span><?php if ($threads['page'] < $threads['pages']): ?><a href="index.php?page=tu_van<?= $chatEmbedded ? '&amp;embed=1' : '' ?>&amp;p=<?= $threads['page'] + 1 ?>">Sau →</a><?php endif; ?></nav><?php endif; ?>
</aside><div class="customer-chat-content"><?php if ($chatThread): require __DIR__ . '/partials/hoi_thoai_tu_van.php'; else: ?><div class="chat-placeholder"><h2>OnTrip luôn sẵn sàng lắng nghe</h2><p>Chọn cuộc trò chuyện để xem lịch sử và nhắn tiếp với nhân viên.</p></div><?php endif; ?></div></div>
</div></main><?php if (!$chatEmbedded) require __DIR__ . '/partials/footer.php'; ?></body></html>
