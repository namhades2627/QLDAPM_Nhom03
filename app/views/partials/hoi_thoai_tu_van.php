<?php
$chatEscape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$chatId = (int) $chatThread['id_yeu_cau'];
$chatErrorKey = 'chat_error_' . $chatRole . '_' . $chatId;
$chatError = $_SESSION[$chatErrorKey] ?? [];
unset($_SESSION[$chatErrorKey]);
$chatToken = isset($chatError['token']) && preg_match('/^[a-f0-9]{64}$/D', $chatError['token']) ? $chatError['token'] : bin2hex(random_bytes(32));
?>
<section class="consultation-chat" id="conversation" data-consultation-chat data-chat-url="<?= $chatEscape($chatUrl) ?>" data-chat-role="<?= $chatRole ?>" aria-label="Cuộc trò chuyện với <?= $chatRole === 'staff' ? 'khách hàng' : 'OnTrip' ?>">
    <div class="consultation-chat-heading"><div><h2><?= $chatRole === 'staff' ? 'Trao đổi với khách hàng' : 'Trò chuyện với OnTrip' ?></h2><p><?= $chatRole === 'staff' ? 'Phản hồi tại đây để trao đổi trực tiếp với khách hàng.' : 'Nhân viên sẽ phản hồi tại đây.' ?> Tin nhắn tự cập nhật khi bạn đang mở cuộc trò chuyện.</p></div><button type="button" class="chat-refresh" data-chat-refresh hidden>Cập nhật</button></div>
    <div class="chat-status" data-chat-status role="status" aria-live="polite"><?= $chatEscape($chatError['message'] ?? '') ?></div>
    <div class="chat-scroll" data-chat-scroll tabindex="0" aria-label="Lịch sử trao đổi">
        <article class="chat-bubble<?= $chatRole === 'customer' ? ' is-own' : '' ?>"><strong><?= $chatRole === 'customer' ? 'Bạn' : $chatEscape($chatThread['ho_ten'] ?? 'Khách hàng') ?></strong><p><?= $chatEscape($chatThread['noi_dung']) ?></p><time><?= date('d/m/Y H:i', strtotime($chatThread['tao_luc'])) ?></time></article>
        <button class="chat-older" type="button" data-chat-older <?= !$chatMessages['has_older'] ? 'hidden' : '' ?>>Xem tin nhắn trước</button>
        <div class="chat-messages" data-chat-messages role="log" aria-live="polite" aria-relevant="additions">
        <?php foreach ($chatMessages['items'] as $item): $own = ($chatRole === 'staff') === ($item['vai_tro_gui'] === 'nhan_vien'); ?>
            <article class="chat-bubble<?= $own ? ' is-own' : '' ?>" data-message-id="<?= (int) $item['id_tin_nhan'] ?>"><strong><?= $chatEscape($item['ten_nguoi_gui']) ?><?= $item['vai_tro_gui'] === 'nhan_vien' ? ' · OnTrip' : '' ?></strong><p><?= $chatEscape($item['noi_dung']) ?></p><time><?= date('d/m/Y H:i', strtotime($item['tao_luc'])) ?></time></article>
        <?php endforeach; ?>
        </div>
    </div>
    <form class="chat-compose" method="post" action="<?= $chatEscape($chatUrl) ?>" data-chat-form>
        <input type="hidden" name="csrf_token" value="<?= $chatEscape($csrfToken) ?>"><input type="hidden" name="operation" value="reply"><input type="hidden" name="id_yeu_cau" value="<?= $chatId ?>"><input type="hidden" name="message_token" value="<?= $chatEscape($chatToken) ?>">
        <label for="chat-message-<?= $chatId ?>"><?= $chatRole === 'staff' ? 'Phản hồi cho khách hàng' : 'Tin nhắn của bạn' ?></label>
        <div><textarea id="chat-message-<?= $chatId ?>" name="noi_dung" rows="3" maxlength="2000" required placeholder="Nhập tin nhắn…"><?= $chatEscape($chatError['content'] ?? '') ?></textarea><button class="button button-primary" type="submit">Gửi</button></div>
        <?php if ($chatRole === 'staff'): ?><small>Nội dung gửi tại đây sẽ hiển thị cho khách và tạo thông báo mới.</small><?php endif; ?>
    </form>
</section>
