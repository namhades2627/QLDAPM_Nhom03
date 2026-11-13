<?php
require __DIR__ . '/partials/header.php';
$topics = YeuCauTuVanModel::TOPICS;
?>
<div class="admin-accounts admin-chat-page">
    <div class="admin-page-heading"><div><h1>Trò chuyện tư vấn</h1><p>Xem tin nhắn và phản hồi khách hàng.</p></div></div>
    <?php if ($message !== ''): ?><div class="admin-form-message" role="alert"><?= $escape($message) ?></div><?php endif; ?>
    <div class="admin-chat-layout">
        <aside class="admin-panel admin-chat-inbox" aria-label="Cuộc trò chuyện">
            <form method="get" action="index.php" class="admin-chat-search">
                <input type="hidden" name="action" value="yeu_cau_tu_van">
                <label for="chat-customer-search">Cuộc trò chuyện</label>
                <div><input id="chat-customer-search" type="search" name="q" value="<?= $escape($filters['q']) ?>" maxlength="120" placeholder="Tìm tên, email, điện thoại"><button type="submit" class="button button-primary">Tìm</button></div>
                <?php if ($filters['q'] !== ''): ?><a href="index.php?action=yeu_cau_tu_van">Xóa tìm kiếm</a><?php endif; ?>
            </form>
            <div class="admin-chat-threads">
                <?php if (!$requests['items']): ?><p class="admin-chat-empty">Chưa có cuộc trò chuyện phù hợp.</p><?php endif; ?>
                <?php foreach ($requests['items'] as $request): $selected = $detail && (int) $detail['id_yeu_cau'] === (int) $request['id_yeu_cau']; ?>
                <a class="admin-chat-thread<?= $selected ? ' is-active' : '' ?>" href="<?= $escape($listUrl(['view' => (int) $request['id_yeu_cau']])) ?>" <?= $selected ? 'aria-current="true"' : '' ?>>
                    <strong><?= $escape($request['ho_ten']) ?></strong>
                    <span><?= $escape($topics[$request['chu_de']] ?? $request['chu_de']) ?></span>
                    <p><?= $escape(mb_strimwidth($request['noi_dung'], 0, 95, '…', 'UTF-8')) ?></p>
                    <time><?= date('d/m/Y H:i', strtotime($request['cap_nhat_luc'])) ?></time>
                </a>
                <?php endforeach; ?>
            </div>
            <nav class="admin-chat-pagination" aria-label="Phân trang hội thoại">
                <?php if ($requests['page'] > 1): ?><a href="<?= $escape($listUrl(['p' => $requests['page'] - 1])) ?>">← Trước</a><?php endif; ?>
                <span><?= $requests['page'] ?> / <?= $requests['pages'] ?></span>
                <?php if ($requests['page'] < $requests['pages']): ?><a href="<?= $escape($listUrl(['p' => $requests['page'] + 1])) ?>">Sau →</a><?php endif; ?>
            </nav>
        </aside>
        <section class="admin-panel admin-chat-conversation" aria-label="Phản hồi khách hàng">
            <?php if ($detail): ?>
                <header class="admin-chat-customer"><h2><?= $escape($detail['ho_ten']) ?></h2><p><?= $escape($topics[$detail['chu_de']] ?? $detail['chu_de']) ?></p><span><?= $escape($detail['email']) ?> · <?= $escape($detail['so_dien_thoai']) ?></span></header>
                <?php if ($detail['id_tai_khoan']):
                    $chatThread = $detail;
                    $chatRole = 'staff';
                    $chatUrl = $listUrl(['view' => (int) $detail['id_yeu_cau']]);
                    require __DIR__ . '/../partials/hoi_thoai_tu_van.php';
                else: ?>
                    <div class="admin-chat-empty"><p class="consultation-content"><?= $escape($detail['noi_dung']) ?></p><p>Cuộc trò chuyện cũ chưa liên kết tài khoản. Liên hệ khách qua điện thoại hoặc email ở trên.</p></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="admin-chat-empty"><h2>Chọn cuộc trò chuyện</h2><p>Chọn khách hàng bên cạnh để xem tin nhắn và trả lời.</p></div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
