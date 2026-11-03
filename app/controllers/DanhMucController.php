<?php

require_once __DIR__ . '/../models/danh_muc.php';

/** Được gọi sau kiểm tra phiên/quyền của AdminController. */
class DanhMucController
{
    private function query(string $key): string
    {
        return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
    }

    private function input(string $key): string
    {
        return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    }

    public function index(array $account, string $roleName, string $csrfToken): void
    {
        $model = new DanhMucModel(getDatabaseConnection());
        $adminSection = 'danh_muc';
        $avatarUrl = AdminController::avatarUrl($account['hinh_anh'] ?? null);
        $groups = DanhMucModel::GROUPS;
        $tab = isset($groups[$this->query('tab')]) ? $this->query('tab') : 'khu_vuc';
        $label = $groups[$tab]['label'];
        $regions = $model->getRegions();
        $regionIds = array_column($regions, 'id');
        $filters = [
            'q' => mb_substr($this->query('q'), 0, 120, 'UTF-8'),
            'type' => $tab !== 'nhu_cau' && in_array($this->query('type'), ['domestic', 'international'], true) ? $this->query('type') : '',
            'region' => $tab === 'diem_den' && in_array($this->query('region'), $regionIds, true) ? $this->query('region') : '',
            'status' => $tab !== 'khu_vuc' && in_array($this->query('status'), ['0', '1'], true) ? $this->query('status') : '',
        ];
        $page = filter_var($this->query('p'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $catalogUrl = static function (array $extra = []) use ($tab, $filters, $page): string {
            $parameters = array_merge(['action' => 'danh_muc', 'tab' => $tab], $filters, ['p' => $page], $extra);
            return 'index.php?' . http_build_query(array_filter($parameters, static fn($value) => $value !== '' && $value !== null));
        };
        $mode = $this->query('mode') === 'create' ? 'create' : '';
        $record = null;
        $deleteRecord = null;
        $errors = [];
        $message = $_SESSION['catalog_message'] ?? '';
        $success = $message !== '';
        unset($_SESSION['catalog_message']);
        $values = ['id' => '', 'name' => '', 'tour_type' => 'domestic', 'region_code' => '', 'image' => '', 'sort_order' => '0', 'active' => '1'];
        $imageOptions = $tab === 'diem_den' ? $model->getImageOptions() : [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = false;
            $operation = $this->input('operation');
            $id = $this->input('record_id');
            if (in_array($operation, ['create', 'update'], true)) {
                $mode = $operation === 'create' ? 'create' : 'edit';
                foreach (array_keys($values) as $key) $values[$key] = $this->input($key);
                if ($mode === 'edit') $record = $model->find($tab, $id);
            } elseif ($operation === 'delete') {
                $deleteRecord = $model->find($tab, $id);
            }
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } elseif (!in_array($operation, ['create', 'update', 'delete'], true)) {
                http_response_code(400);
                $message = 'Thao tác không hợp lệ.';
            } else {
                try {
                    if ($operation === 'delete') {
                        if ($this->input('confirm_delete') !== '1') throw new DomainException('Vui lòng tích xác nhận trước khi xóa.');
                        $model->delete((int) $account['id_tai_khoan'], $_SESSION['admin_credential_stamp'], $tab, $id);
                        $_SESSION['catalog_message'] = 'Đã xóa danh mục chưa có dữ liệu liên quan.';
                    } else {
                        if ($mode === 'edit' && !$record) throw new DomainException('Danh mục không còn tồn tại. Vui lòng tải lại danh sách.');
                        // Mã đã tạo là cố định; không đổi mã bằng dữ liệu gửi thêm trong form.
                        if ($record) $values['id'] = (string) $record['id'];
                        $errors = $model->validate($tab, $values, $mode === 'create');
                        if ($errors) throw new DomainException('Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.');
                        $model->save((int) $account['id_tai_khoan'], $_SESSION['admin_credential_stamp'], $tab, $mode === 'create' ? null : $id, $values);
                        $_SESSION['catalog_message'] = $mode === 'create' ? 'Đã thêm danh mục mới.' : 'Đã lưu thay đổi danh mục.';
                    }
                    header('Location: ' . $catalogUrl(), true, 303);
                    return;
                } catch (DomainException $exception) {
                    if ($exception->getCode() === 403) http_response_code(403);
                    $message = $exception->getMessage();
                } catch (PDOException $exception) {
                    $code = (int) ($exception->errorInfo[1] ?? 0);
                    if ($code === 1062) $message = $tab === 'diem_den' ? 'Tên điểm đến đã tồn tại trong khu vực này.' : 'Mã danh mục đã tồn tại. Vui lòng chọn mã khác.';
                    elseif ($code === 1451) $message = 'Danh mục đã có dữ liệu liên quan nên không thể xóa.';
                    elseif ($code === 1452) $message = 'Khu vực không còn tồn tại. Vui lòng chọn lại.';
                    elseif (in_array($code, [1205, 1213], true)) $message = 'Danh mục đang được cập nhật ở phiên khác. Vui lòng thử lưu lại.';
                    else {
                        error_log('OnTrip category error: ' . $exception->getMessage());
                        http_response_code(503);
                        $message = 'Chưa thể lưu danh mục. Vui lòng thử lại sau.';
                    }
                }
            }
        } else {
            $editId = $this->query('edit');
            $deleteId = $this->query('delete');
            if ($editId !== '') {
                $record = $model->find($tab, $editId);
                if ($record) {
                    $mode = 'edit';
                    foreach ($values as $key => $default) $values[$key] = isset($record[$key]) ? (string) $record[$key] : $default;
                } else {
                    http_response_code(404);
                    $message = 'Không tìm thấy danh mục cần sửa.';
                    $success = false;
                    $mode = '';
                }
            } elseif ($deleteId !== '') {
                $mode = '';
                $deleteRecord = $model->find($tab, $deleteId);
                if (!$deleteRecord) {
                    http_response_code(404);
                    $message = 'Không tìm thấy danh mục cần xóa.';
                    $success = false;
                }
            }
        }
        $counts = $model->getCounts();
        $list = $model->getList($tab, $filters, $page);
        require __DIR__ . '/../views/admin/danh_muc.php';
    }
}
