<?php $notificationEscape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<details class="customer-notifications">
    <summary class="icon-button notification-toggle" aria-label="Thông báo, <?= (int) $notificationData['unread'] ?> chưa đọc">
        <svg class="icon" aria-hidden="true"><use href="#i-bell"/></svg>
        <?php if ($notificationData['unread'] > 0): ?><span class="notification-count" aria-hidden="true"><?= $notificationData['unread'] > 99 ? '99+' : (int) $notificationData['unread'] ?></span><?php endif; ?>
    </summary>
    <section class="notification-panel" aria-label="Thông báo gần đây">
        <div class="notification-panel-heading"><strong>Thông báo</strong><span><?= (int) $notificationData['unread'] ?> chưa đọc</span></div>
        <?php if (!$notificationData['items']): ?>
            <p class="notification-empty">Bạn chưa có thông báo.<br>Thay đổi về đơn đặt sẽ xuất hiện ở đây.</p>
        <?php else: ?>
            <?php foreach ($notificationData['items'] as $notice): ?>
            <form method="post" action="index.php?page=thong_bao">
                <input type="hidden" name="csrf_token" value="<?= $notificationEscape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="operation" value="read">
                <input type="hidden" name="id" value="<?= (int) $notice['id_thong_bao'] ?>">
                <button type="submit" class="notification-item<?= !$notice['da_doc'] ? ' is-unread' : '' ?>">
                    <strong><?= $notificationEscape($notice['tieu_de']) ?><?= !$notice['da_doc'] ? '<span class="notification-sr-only"> — Chưa đọc</span>' : '' ?></strong>
                    <span><?= $notificationEscape($notice['noi_dung']) ?></span>
                    <time datetime="<?= $notificationEscape(date('c', strtotime($notice['tao_luc']))) ?>"><?= $notificationEscape(date('d/m/Y H:i', strtotime($notice['tao_luc']))) ?></time>
                </button>
            </form>
            <?php endforeach; ?>
        <?php endif; ?>
        <a class="notification-all" href="index.php?page=thong_bao">Xem tất cả thông báo →</a>
    </section>
</details>
