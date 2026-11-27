<?php
/**
 * View Quản lý bình luận & đánh giá tour (Admin)
 * 
 * @var array $account
 * @var string $roleName
 * @var string $csrfToken
 * @var string $adminSection
 * @var string $avatarUrl
 * @var array $reviewSummary
 * @var array $tourOptions
 * @var array $reviewList
 * @var array $reviewFilters
 * @var Closure $reviewsUrl
 * @var string $reviewMessage
 * @var bool $reviewSuccess
 */
require __DIR__ . '/partials/header.php';

$reviewStats = [
    ['Tổng bình luận', $reviewSummary['total'], 'Tất cả đánh giá trên hệ thống', 'a-message', 'orange'],
    ['Đang hiển thị', $reviewSummary['hien_thi'], 'Bình luận hiển thị công khai', 'a-check', 'green'],
    ['Đã ẩn / Tạm khóa', $reviewSummary['an'], 'Bình luận đã bị ẩn', 'a-lock', 'purple'],
    ['Điểm đánh giá TB', $reviewSummary['avg_rating'] . ' ★', 'Chất lượng trải nghiệm tour', 'a-shield', 'blue'],
];
?>
<div class="admin-accounts admin-reviews">
    <div class="admin-page-heading admin-accounts-heading">
        <div>
            <h1>Quản lý bình luận</h1>
            <p>Theo dõi, kiểm duyệt và quản lý các đánh giá, nhận xét của khách hàng về tour du lịch.</p>
        </div>
        <span class="admin-count-tag">Điểm TB toàn hệ thống: <strong><?= $reviewSummary['avg_rating'] ?> ★</strong></span>
    </div>

    <!-- 1. BỐ CỤC THỐNG KÊ -->
    <section class="admin-stats" aria-label="Tổng quan bình luận">
        <?php foreach ($reviewStats as [$label, $value, $note, $icon, $color]): ?>
        <article class="admin-stat">
            <div>
                <h2><?= $escape($label) ?></h2>
                <strong><?= $escape($value) ?></strong>
                <p><?= $escape($note) ?></p>
            </div>
            <span class="admin-stat-icon admin-color-<?= $color ?>"><svg class="icon"><use href="#<?= $icon ?>"/></svg></span>
        </article>
        <?php endforeach; ?>
    </section>

    <!-- 2. THÔNG BÁO FLASH MESSAGE -->
    <?php if ($reviewMessage !== ''): ?>
    <div class="admin-form-message<?= $reviewSuccess ? ' is-success' : '' ?>" role="<?= $reviewSuccess ? 'status' : 'alert' ?>"><?= $escape($reviewMessage) ?></div>
    <?php endif; ?>

    <!-- 3. DANH SÁCH BÌNH LUẬN & BỘ LỌC -->
    <section class="admin-panel admin-accounts-panel" aria-labelledby="reviews-list-title">
        <div class="admin-panel-heading">
            <div>
                <h2 id="reviews-list-title">Danh sách bình luận &amp; đánh giá</h2>
                <p>Kiểm duyệt nội dung, ẩn bình luận không phù hợp hoặc xóa theo quy định.</p>
            </div>
            <span class="admin-count-tag"><?= $reviewList['total'] ?> bình luận</span>
        </div>

        <form class="admin-accounts-filters" action="index.php" method="get" role="search" aria-label="Tìm và lọc bình luận">
            <input type="hidden" name="action" value="danh_gia">
            
            <label class="admin-accounts-search">
                <span>Tìm kiếm</span>
                <div>
                    <svg class="icon"><use href="#a-search"/></svg>
                    <input type="search" name="q" value="<?= $escape($reviewFilters['q']) ?>" maxlength="120" placeholder="Khách hàng, email, tour hoặc nội dung...">
                </div>
            </label>

            <label style="min-width: 220px;">
                <span>Tour</span>
                <select name="tour_id">
                    <option value="">Tất cả tour</option>
                    <?php foreach ($tourOptions as $t): ?>
                    <option value="<?= (int) $t['id_tour'] ?>" <?= (string) $reviewFilters['tour_id'] === (string) $t['id_tour'] ? 'selected' : '' ?>>
                        <?= $escape($t['ten_tour']) ?> (<?= $escape($t['ma_tour']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label style="min-width: 120px;">
                <span>Số sao</span>
                <select name="star">
                    <option value="">Tất cả sao</option>
                    <?php for ($s = 5; $s >= 1; $s--): ?>
                    <option value="<?= $s ?>" <?= (string) $reviewFilters['star'] === (string) $s ? 'selected' : '' ?>><?= $s ?> ★</option>
                    <?php endfor; ?>
                </select>
            </label>

            <label style="min-width: 140px;">
                <span>Trạng thái</span>
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="hien_thi" <?= $reviewFilters['status'] === 'hien_thi' ? 'selected' : '' ?>>Đang hiển thị</option>
                    <option value="an" <?= $reviewFilters['status'] === 'an' ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </label>

            <button class="button button-primary" type="submit">Lọc dữ liệu</button>
            <a class="admin-filter-clear" href="<?= $escape($reviewsUrl(['q' => '', 'tour_id' => '', 'star' => '', 'status' => '', 'p' => 1])) ?>">Đặt lại</a>
        </form>

        <div class="admin-table-scroll">
            <table class="admin-table admin-accounts-table admin-reviews-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 20%;">Khách hàng</th>
                        <th scope="col" style="width: 20%;">Tour</th>
                        <th scope="col" style="width: 35%;">Đánh giá &amp; Nhận xét</th>
                        <th scope="col" style="width: 11%;">Thời gian</th>
                        <th scope="col" style="width: 8%;">Trạng thái</th>
                        <th scope="col" style="width: 6%;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($reviewList['items'])): ?>
                    <tr>
                        <td colspan="6" class="admin-empty">Không tìm thấy bình luận nào phù hợp với điều kiện lọc.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviewList['items'] as $rev):
                        $isShown = $rev['trang_thai'] === 'hien_thi';
                        $stars = (int) $rev['so_sao'];
                        $userAvatar = AdminController::avatarUrl($rev['hinh_anh']);
                    ?>
                    <tr>
                        <td>
                            <div class="admin-account-cell">
                                <span class="admin-list-avatar" aria-hidden="true">
                                    <?php if ($userAvatar !== ''): ?>
                                        <img src="<?= $escape($userAvatar) ?>" alt="">
                                    <?php else: ?>
                                        <?= $escape(mb_strtoupper(mb_substr($rev['ho_ten'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <strong><?= $escape($rev['ho_ten']) ?></strong>
                                    <small><?= $escape($rev['email']) ?></small>
                                    <?php if (!empty($rev['da_dat_tour'])): ?>
                                        <span class="admin-verified-booking-tag" title="Khách hàng đã từng đặt và xác nhận tour này">✓ Đã đặt tour</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?= $escape($rev['ten_tour']) ?></strong>
                            <small style="display:block;margin-top:3px;color:#728093;">Mã tour: <code><?= $escape($rev['ma_tour']) ?></code></small>
                        </td>
                        <td>
                            <div class="admin-review-content-cell">
                                <div class="admin-review-stars" aria-label="<?= $stars ?> trên 5 sao">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="<?= $i <= $stars ? 'admin-star-gold' : 'admin-star-empty' ?>">★</span>
                                    <?php endfor; ?>
                                    <span class="admin-star-number"><?= $stars ?>/5</span>
                                </div>
                                <?php if (!empty($rev['tieu_de'])): ?>
                                    <p class="admin-review-title"><strong><?= $escape($rev['tieu_de']) ?></strong></p>
                                <?php endif; ?>
                                <p class="admin-review-body"><?= nl2br($escape($rev['noi_dung'])) ?></p>
                                <?php if (!empty($rev['phan_hoi_admin'])): ?>
                                    <div class="admin-review-reply-box">
                                        <div class="admin-review-reply-header">
                                            <strong>↳ Phản hồi từ <?= $escape($rev['ten_nguoi_phan_hoi'] ?: 'Quản trị viên OnTrip') ?></strong>
                                            <?php if (!empty($rev['phan_hoi_luc'])): ?>
                                                <span><?= date('d/m/Y H:i', strtotime($rev['phan_hoi_luc'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p><?= nl2br($escape($rev['phan_hoi_admin'])) ?></p>
                                    </div>
                                <?php endif; ?>
                                <form method="post" action="<?= $escape($reviewsUrl()) ?>" class="admin-reply-form" id="reply-form-<?= (int) $rev['id_danh_gia'] ?>" hidden>
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="operation" value="reply">
                                    <input type="hidden" name="review_id" value="<?= (int) $rev['id_danh_gia'] ?>">
                                    <textarea name="reply_content" rows="3" maxlength="2000" required placeholder="Nhập nội dung phản hồi gửi tới khách hàng <?= $escape($rev['ho_ten']) ?>..."><?= $escape($rev['phan_hoi_admin'] ?? '') ?></textarea>
                                    <div class="admin-reply-form-actions">
                                        <button type="submit" class="button button-primary admin-reply-submit">Gửi phản hồi</button>
                                        <button type="button" class="button admin-reply-cancel" data-reply-cancel="<?= (int) $rev['id_danh_gia'] ?>">Hủy</button>
                                        <?php if (!empty($rev['phan_hoi_admin'])): ?>
                                            <button type="submit" name="operation" value="delete_reply" class="admin-reply-delete" formnovalidate onclick="return confirm('Bạn có chắc muốn xóa phản hồi này?');">Xóa phản hồi</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </td>
                        <td class="admin-table-date">
                            <?= date('d/m/Y', strtotime($rev['tao_luc'])) ?>
                            <small style="display:block;color:#8a96a3;"><?= date('H:i', strtotime($rev['tao_luc'])) ?></small>
                        </td>
                        <td>
                            <span class="admin-status-badge<?= $isShown ? ' is-active' : ' is-locked' ?>">
                                <span aria-hidden="true"></span>
                                <?= $isShown ? 'Hiển thị' : 'Đã ẩn' ?>
                            </span>
                        </td>
                        <td>
                            <div class="admin-row-actions">
                                <!-- Nút Trả lời / Sửa phản hồi -->
                                <button type="button" class="admin-row-button is-reply<?= !empty($rev['phan_hoi_admin']) ? ' has-reply' : '' ?>"
                                        data-reply-toggle="<?= (int) $rev['id_danh_gia'] ?>"
                                        aria-label="<?= !empty($rev['phan_hoi_admin']) ? 'Sửa phản hồi' : 'Trả lời đánh giá' ?>"
                                        title="<?= !empty($rev['phan_hoi_admin']) ? 'Sửa phản hồi đã gửi' : 'Trả lời tin nhắn đánh giá' ?>">
                                    <svg class="icon"><use href="#a-message"/></svg>
                                </button>
                                <!-- Nút Ẩn / Hiện -->
                                <form method="post" action="<?= $escape($reviewsUrl()) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="operation" value="toggle">
                                    <input type="hidden" name="review_id" value="<?= (int) $rev['id_danh_gia'] ?>">
                                    <button class="admin-row-button<?= $isShown ? ' is-lock' : ' is-unlock' ?>" type="submit" 
                                            aria-label="<?= $isShown ? 'Ẩn bình luận' : 'Hiển thị bình luận' ?>" 
                                            title="<?= $isShown ? 'Ẩn bình luận khỏi website' : 'Hiển thị lại bình luận' ?>">
                                        <svg class="icon"><use href="#<?= $isShown ? 'a-lock' : 'a-unlock' ?>"/></svg>
                                    </button>
                                </form>
                                <!-- Nút Xóa -->
                                <form method="post" action="<?= $escape($reviewsUrl()) ?>" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn bình luận này của khách hàng?');">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                    <input type="hidden" name="operation" value="delete">
                                    <input type="hidden" name="review_id" value="<?= (int) $rev['id_danh_gia'] ?>">
                                    <button class="admin-row-button is-delete" type="submit" aria-label="Xóa bình luận" title="Xóa bình luận vĩnh viễn">
                                        <svg class="icon"><use href="#a-trash"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-accounts-list-footer">
            <p>Hiển thị <?= $reviewList['total'] ? ($reviewList['page'] - 1) * 10 + 1 : 0 ?>–<?= min($reviewList['page'] * 10, $reviewList['total']) ?> / <?= $reviewList['total'] ?> bình luận</p>
            <?php if ($reviewList['pages'] > 1): ?>
            <nav class="admin-accounts-pagination" aria-label="Phân trang bình luận">
                <?php if ($reviewList['page'] > 1): ?>
                    <a href="<?= $escape($reviewsUrl(['p' => $reviewList['page'] - 1])) ?>" aria-label="Trang trước">‹</a>
                <?php endif; ?>
                <?php
                    $pageLinks = array_unique(array_merge([1], range(max(1, $reviewList['page'] - 2), min($reviewList['pages'], $reviewList['page'] + 2)), [$reviewList['pages']]));
                    sort($pageLinks);
                    $previousPage = 0;
                    foreach ($pageLinks as $number):
                ?>
                    <?php if ($previousPage && $number > $previousPage + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                    <a href="<?= $escape($reviewsUrl(['p' => $number])) ?>" <?= $number === $reviewList['page'] ? 'aria-current="page"' : '' ?> aria-label="Trang <?= $number ?>"><?= $number ?></a>
                <?php $previousPage = $number; endforeach; ?>
                <?php if ($reviewList['page'] < $reviewList['pages']): ?>
                    <a href="<?= $escape($reviewsUrl(['p' => $reviewList['page'] + 1])) ?>" aria-label="Trang tiếp theo">›</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-reply-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-reply-toggle');
            var form = document.getElementById('reply-form-' + id);
            if (!form) return;
            var isHidden = form.hasAttribute('hidden');
            if (isHidden) {
                form.removeAttribute('hidden');
                var textarea = form.querySelector('textarea');
                if (textarea) {
                    textarea.focus();
                    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
                }
            } else {
                form.setAttribute('hidden', '');
            }
        });
    });
    document.querySelectorAll('[data-reply-cancel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-reply-cancel');
            var form = document.getElementById('reply-form-' + id);
            if (form) form.setAttribute('hidden', '');
        });
    });
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
