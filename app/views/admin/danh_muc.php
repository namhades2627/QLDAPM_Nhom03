<?php
require __DIR__ . '/partials/header.php';
$isCreating = $mode === 'create';
$types = ['domestic' => 'Trong nước', 'international' => 'Quốc tế'];
$icons = ['khu_vuc' => 'a-tour', 'diem_den' => 'a-pin', 'nhu_cau' => 'a-grid'];
$descriptions = ['khu_vuc' => 'Phân nhóm điểm đến theo vùng và loại tour.', 'diem_den' => 'Quản lý điểm đến, khu vực và trạng thái hiển thị.', 'nhu_cau' => 'Các nhu cầu dùng để phân nhóm và tìm kiếm tour.'];
$fieldError = static function (string $field) use ($errors, $escape): void {
    if (isset($errors[$field])) echo '<p class="admin-field-error" id="category-' . $field . '-error">' . $escape($errors[$field]) . '</p>';
};
$fieldAttributes = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="category-' . $field . '-error"' : '';
};
?>
<div class="admin-accounts admin-categories">
    <div class="admin-page-heading admin-accounts-heading">
        <div><h1>Quản lý danh mục</h1><p>Dữ liệu dùng chung cho danh sách và thông tin tour OnTrip.</p></div>
        <a class="button button-primary" href="<?= $escape($catalogUrl(['mode' => 'create'])) ?>#category-editor"><span aria-hidden="true">+</span> Thêm <?= $escape(mb_strtolower($groups[$tab]['label'], 'UTF-8')) ?></a>
    </div>
    <nav class="admin-category-tabs" aria-label="Nhóm danh mục">
        <?php foreach ($groups as $key => $group): ?>
        <a href="index.php?action=danh_muc&amp;tab=<?= $key ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?>><svg class="icon" aria-hidden="true"><use href="#<?= $icons[$key] ?>"/></svg><span><?= $escape($group['label']) ?></span><b><?= $counts[$key] ?></b></a>
        <?php endforeach; ?>
    </nav>

    <?php if ($message !== ''): ?><div class="admin-form-message<?= $success ? ' is-success' : '' ?>" role="<?= $success ? 'status' : 'alert' ?>"><?= $escape($message) ?></div><?php endif; ?>

    <?php if ($deleteRecord): $inUse = (int) $deleteRecord['usage_count'] > 0 || (int) ($deleteRecord['child_count'] ?? 0) > 0; ?>
    <section class="admin-panel admin-managed-editor admin-delete-confirm" id="category-delete" aria-labelledby="category-delete-title">
        <div class="admin-panel-heading"><div><h2 id="category-delete-title"><?= $inUse ? 'Danh mục đang được sử dụng' : 'Xóa danh mục?' ?></h2><p><?= $escape($deleteRecord['name']) ?></p></div><svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg></div>
        <form class="admin-account-form" method="post" action="<?= $escape($catalogUrl()) ?>">
            <?php if ($inUse): ?>
            <p><?= $tab === 'khu_vuc' ? 'Khu vực đã có điểm đến nên không thể xóa. Hãy kiểm tra các điểm đến liên quan trước.' : 'Danh mục đã được tour sử dụng nên không thể xóa. Bạn có thể sửa và chọn Ngừng hoạt động để giữ các liên kết hiện có.' ?></p>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($catalogUrl()) ?>">Đóng</a><a class="button button-primary" href="<?= $escape($catalogUrl(['edit' => $deleteRecord['id']])) ?>#category-editor">Chỉnh sửa</a></div>
            <?php else: ?>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="operation" value="delete"><input type="hidden" name="record_id" value="<?= $escape((string) $deleteRecord['id']) ?>">
            <p>Bạn sắp xóa <strong><?= $escape($deleteRecord['name']) ?></strong>. Thao tác này không thể hoàn tác.</p>
            <label class="admin-delete-check"><input type="checkbox" name="confirm_delete" value="1" required><span>Tôi xác nhận xóa danh mục này.</span></label>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($catalogUrl()) ?>">Hủy bỏ</a><button class="admin-delete-submit" type="submit">Xóa danh mục</button></div>
            <?php endif; ?>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($isCreating || ($mode === 'edit' && $record)): ?>
    <section class="admin-panel admin-managed-editor" id="category-editor" aria-labelledby="category-editor-title">
        <div class="admin-panel-heading"><div><h2 id="category-editor-title"><?= $isCreating ? 'Thêm' : 'Chỉnh sửa' ?> <?= $escape(mb_strtolower($groups[$tab]['label'], 'UTF-8')) ?></h2><p><?= $escape($descriptions[$tab]) ?></p></div></div>
        <form class="admin-account-form" method="post" action="<?= $escape($catalogUrl()) ?>">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="operation" value="<?= $isCreating ? 'create' : 'update' ?>"><input type="hidden" name="record_id" value="<?= $escape((string) ($record['id'] ?? '')) ?>">
            <div class="admin-managed-fields">
                <?php if ($tab !== 'diem_den'): ?>
                <div class="admin-form-field"><label for="category-id">Mã danh mục <span>*</span></label><input id="category-id" name="id" value="<?= $escape($values['id']) ?>" maxlength="32" pattern="[a-z][a-z0-9_-]{0,31}" <?= $isCreating ? 'required' : 'readonly' ?> <?= $fieldAttributes('id') ?>><small><?= $isCreating ? 'Chữ thường không dấu, số, dấu gạch ngang hoặc gạch dưới; bắt đầu bằng chữ.' : 'Mã được giữ cố định sau khi tạo.' ?></small><?php $fieldError('id'); ?></div>
                <?php endif; ?>
                <div class="admin-form-field"><label for="category-name">Tên <?= $escape(mb_strtolower($groups[$tab]['label'], 'UTF-8')) ?> <span>*</span></label><input id="category-name" name="name" value="<?= $escape($values['name']) ?>" minlength="2" maxlength="<?= ['khu_vuc' => 80, 'diem_den' => 120, 'nhu_cau' => 100][$tab] ?>" required <?= $fieldAttributes('name') ?>><?php $fieldError('name'); ?></div>
                <?php if ($tab === 'khu_vuc'): $lockedType = $record && (int) $record['child_count'] > 0; ?>
                <div class="admin-form-field"><label for="category-tour_type">Loại tour <span>*</span></label><?php if ($lockedType): ?><input type="hidden" name="tour_type" value="<?= $escape($values['tour_type']) ?>"><?php endif; ?><select id="category-tour_type" name="tour_type" <?= $lockedType ? 'disabled' : 'required' ?> <?= $fieldAttributes('tour_type') ?>><?php foreach ($types as $key => $name): ?><option value="<?= $key ?>" <?= $values['tour_type'] === $key ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select><?php if ($lockedType): ?><small>Đã có điểm đến; loại tour được giữ cố định.</small><?php endif; ?><?php $fieldError('tour_type'); ?></div>
                <?php endif; ?>
                <?php if ($tab === 'diem_den'): $lockedRegion = $record && (int) $record['usage_count'] > 0; ?>
                <div class="admin-form-field"><label for="category-region_code">Khu vực <span>*</span></label><?php if ($lockedRegion): ?><input type="hidden" name="region_code" value="<?= $escape($values['region_code']) ?>"><?php endif; ?><select id="category-region_code" name="region_code" <?= $lockedRegion ? 'disabled' : 'required' ?> <?= $fieldAttributes('region_code') ?>><option value="">Chọn khu vực</option><?php foreach ($regions as $region): ?><option value="<?= $escape($region['id']) ?>" <?= $values['region_code'] === $region['id'] ? 'selected' : '' ?>><?= $escape($region['name']) ?> · <?= $types[$region['tour_type']] ?></option><?php endforeach; ?></select><small><?= $lockedRegion ? 'Điểm đến đã được tour sử dụng; khu vực được giữ cố định.' : 'Chưa có khu vực phù hợp? Thêm tại tab Khu vực.' ?></small><?php $fieldError('region_code'); ?></div>
                <div class="admin-form-field"><label for="category-image">Ảnh điểm đến</label><select id="category-image" name="image" data-category-image <?= $fieldAttributes('image') ?>><option value="">Không chọn ảnh</option><?php foreach ($imageOptions as $path => $name): ?><option value="<?= $escape($path) ?>" <?= $values['image'] === $path ? 'selected' : '' ?>><?= $escape($name) ?></option><?php endforeach; ?></select><small>Chọn ảnh có sẵn trong thư viện website.</small><?php $fieldError('image'); ?><img class="admin-category-preview" data-category-preview src="<?= $values['image'] !== '' && isset($imageOptions[$values['image']]) ? $escape('../public/' . $values['image']) : '' ?>" alt="Ảnh điểm đến đã chọn" <?= $values['image'] !== '' && isset($imageOptions[$values['image']]) ? '' : 'hidden' ?>></div>
                <?php else: ?>
                <div class="admin-form-field"><label for="category-sort_order">Thứ tự hiển thị</label><input id="category-sort_order" name="sort_order" type="number" min="0" max="255" step="1" value="<?= $escape($values['sort_order']) ?>" required <?= $fieldAttributes('sort_order') ?>><small>Số nhỏ hơn được hiển thị trước, từ 0 đến 255.</small><?php $fieldError('sort_order'); ?></div>
                <?php endif; ?>
                <?php if ($tab !== 'khu_vuc'): ?>
                <div class="admin-form-field"><label for="category-active">Trạng thái <span>*</span></label><select id="category-active" name="active" required <?= $fieldAttributes('active') ?>><option value="1" <?= $values['active'] === '1' ? 'selected' : '' ?>>Đang hoạt động</option><option value="0" <?= $values['active'] === '0' ? 'selected' : '' ?>>Ngừng hoạt động</option></select><small><?= $tab === 'diem_den' ? 'Ngừng hoạt động sẽ ẩn điểm đến và các tour thuộc điểm đến khỏi danh sách công khai.' : 'Ngừng hoạt động sẽ ẩn nhu cầu khỏi bộ lọc công khai, giữ liên kết với tour.' ?></small><?php $fieldError('active'); ?></div>
                <?php endif; ?>
            </div>
            <div class="admin-form-actions"><a class="admin-secondary-button" href="<?= $escape($catalogUrl()) ?>">Hủy bỏ</a><button class="button button-primary" type="submit"><?= $isCreating ? 'Thêm danh mục' : 'Lưu thay đổi' ?></button></div>
        </form>
    </section>
    <?php endif; ?>

    <section class="admin-panel admin-accounts-panel" aria-labelledby="category-list-title">
        <div class="admin-panel-heading"><div><h2 id="category-list-title"><?= $escape($groups[$tab]['label']) ?></h2><p><?= $escape($descriptions[$tab]) ?></p></div><span class="admin-count-tag"><?= $list['total'] ?> kết quả</span></div>
        <form class="admin-accounts-filters" action="index.php" method="get" role="search" aria-label="Tìm danh mục">
            <input type="hidden" name="action" value="danh_muc"><input type="hidden" name="tab" value="<?= $tab ?>">
            <label class="admin-accounts-search"><span>Tìm kiếm</span><div><svg class="icon" aria-hidden="true"><use href="#a-search"/></svg><input type="search" name="q" value="<?= $escape($filters['q']) ?>" maxlength="120" placeholder="Tên hoặc mã danh mục"></div></label>
            <?php if ($tab !== 'nhu_cau'): ?><label><span>Loại tour</span><select name="type"><option value="">Tất cả loại tour</option><?php foreach ($types as $key => $name): ?><option value="<?= $key ?>" <?= $filters['type'] === $key ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <?php if ($tab === 'diem_den'): ?><label><span>Khu vực</span><select name="region"><option value="">Tất cả khu vực</option><?php foreach ($regions as $region): ?><option value="<?= $escape($region['id']) ?>" <?= $filters['region'] === $region['id'] ? 'selected' : '' ?>><?= $escape($region['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
            <?php if ($tab !== 'khu_vuc'): ?><label><span>Trạng thái</span><select name="status"><option value="">Tất cả trạng thái</option><option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>Đang hoạt động</option><option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>Ngừng hoạt động</option></select></label><?php endif; ?>
            <button class="button button-primary" type="submit">Áp dụng</button><a class="admin-filter-clear" href="index.php?action=danh_muc&amp;tab=<?= $tab ?>">Đặt lại</a>
        </form>
        <div class="admin-table-scroll"><table class="admin-table admin-category-table">
            <thead><tr><th scope="col">Tên danh mục</th><?php if ($tab === 'diem_den'): ?><th scope="col">Khu vực</th><?php endif; ?><?php if ($tab !== 'nhu_cau'): ?><th scope="col">Loại tour</th><?php endif; ?><th scope="col">Đang sử dụng</th><th scope="col"><?= $tab === 'khu_vuc' ? 'Thứ tự' : 'Trạng thái' ?></th><th scope="col">Thao tác</th></tr></thead>
            <tbody><?php foreach ($list['rows'] as $row): ?>
                <tr data-category-row="<?= $escape((string) $row['id']) ?>"><td><strong class="admin-category-name"><?= $escape($row['name']) ?></strong><small class="admin-category-code"><?= $escape((string) $row['id']) ?></small></td>
                    <?php if ($tab === 'diem_den'): ?><td><?= $escape($row['region_name']) ?></td><?php endif; ?>
                    <?php if ($tab !== 'nhu_cau'): ?><td><?= $types[$row['tour_type']] ?></td><?php endif; ?>
                    <td><?php if ($tab === 'khu_vuc'): ?><a class="admin-category-related" href="index.php?action=danh_muc&amp;tab=diem_den&amp;region=<?= $escape(rawurlencode($row['id'])) ?>"><?= (int) $row['child_count'] ?> điểm đến</a><small class="admin-category-code"><?= (int) $row['usage_count'] ?> tour</small><?php else: ?><?= (int) $row['usage_count'] ?> tour<?php endif; ?></td>
                    <td><?php if ($tab === 'khu_vuc'): ?><?= (int) $row['sort_order'] ?><?php else: ?><span class="admin-status-badge<?= (int) $row['active'] === 1 ? ' is-active' : ' is-locked' ?>"><span aria-hidden="true"></span><?= (int) $row['active'] === 1 ? 'Đang hoạt động' : 'Ngừng hoạt động' ?></span><?php endif; ?></td>
                    <td><div class="admin-row-actions"><a class="admin-row-button" href="<?= $escape($catalogUrl(['edit' => $row['id']])) ?>#category-editor" aria-label="Sửa <?= $escape($row['name']) ?>"><svg class="icon" aria-hidden="true"><use href="#a-edit"/></svg><span>Sửa</span></a><a class="admin-row-button is-delete" href="<?= $escape($catalogUrl(['delete' => $row['id']])) ?>#category-delete" aria-label="Xóa <?= $escape($row['name']) ?>"><svg class="icon" aria-hidden="true"><use href="#a-trash"/></svg></a></div></td>
                </tr>
            <?php endforeach; ?><?php if (!$list['rows']): ?><tr><td colspan="<?= $tab === 'diem_den' ? 6 : ($tab === 'khu_vuc' ? 5 : 4) ?>" class="admin-empty"><strong>Chưa có danh mục phù hợp</strong><p>Thử thay đổi bộ lọc hoặc thêm danh mục mới.</p></td></tr><?php endif; ?></tbody>
        </table></div>
        <div class="admin-accounts-list-footer"><p>Hiển thị <?= $list['total'] ? ($list['page'] - 1) * 10 + 1 : 0 ?>–<?= min($list['page'] * 10, $list['total']) ?> / <?= $list['total'] ?> kết quả</p>
            <?php if ($list['pages'] > 1): ?><nav class="admin-accounts-pagination" aria-label="Phân trang danh mục"><?php if ($list['page'] > 1): ?><a href="<?= $escape($catalogUrl(['p' => $list['page'] - 1])) ?>" aria-label="Trang trước">‹</a><?php endif; ?><?php for ($number = max(1, $list['page'] - 2); $number <= min($list['pages'], $list['page'] + 2); $number++): ?><a href="<?= $escape($catalogUrl(['p' => $number])) ?>" <?= $list['page'] === $number ? 'aria-current="page"' : '' ?>><?= $number ?></a><?php endfor; ?><?php if ($list['page'] < $list['pages']): ?><a href="<?= $escape($catalogUrl(['p' => $list['page'] + 1])) ?>" aria-label="Trang tiếp theo">›</a><?php endif; ?></nav><?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
