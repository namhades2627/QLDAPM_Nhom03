<?php

require_once __DIR__ . '/../models/lich_khoi_hanh.php';
require_once __DIR__ . '/../models/tour.php';

/**
 * Quản lý lịch khởi hành, số chỗ và danh sách khách theo chuyến.
 * Phiên đăng nhập và quyền quản trị đã được AdminController kiểm tra trước khi gọi Controller này.
 */
class LichKhoiHanhController
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
        $model = new LichKhoiHanhModel(getDatabaseConnection());
        $adminSection = 'schedules';
        $avatarUrl = AdminController::avatarUrl($account['hinh_anh'] ?? null);
        $statuses = LichKhoiHanhModel::STATUSES;
        $tours = $model->getTourOptions();
        $tourIds = array_map('strval', array_column($tours, 'id_tour'));

        $filters = [
            'q' => mb_substr($this->query('q'), 0, 120, 'UTF-8'),
            'status' => isset($statuses[$this->query('status')]) ? $this->query('status') : '',
            'tour' => in_array($this->query('tour'), $tourIds, true) ? $this->query('tour') : '',
            'scope' => in_array($this->query('scope'), ['upcoming', 'past'], true) ? $this->query('scope') : '',
        ];
        $page = filter_var($this->query('p'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $schedulesUrl = static function (array $extra = []) use ($filters, $page): string {
            $parameters = array_merge(['action' => 'lich_khoi_hanh'], $filters, ['p' => $page], $extra);
            return 'index.php?' . http_build_query(array_filter($parameters, static function ($value): bool {
                return $value !== '' && $value !== null;
            }));
        };

        $defaults = ['id_tour' => $filters['tour'], 'ngay_khoi_hanh' => '', 'ngay_ve' => '', 'diem_khoi_hanh' => '',
            'gia_nguoi_lon' => '', 'gia_tre_em' => '', 'so_cho' => '20', 'han_dat' => '', 'trang_thai' => 'open'];
        $values = $defaults;
        $mode = $this->query('mode') === 'create' ? 'create' : '';
        $record = $deleteSchedule = $customerDetail = null;
        $errors = [];
        $message = $_SESSION['admin_schedules_message'] ?? '';
        $success = $message !== '';
        unset($_SESSION['admin_schedules_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = false;
            $operation = $this->input('operation');
            $recordId = filter_var($this->input('record_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } elseif (!in_array($operation, ['create', 'update', 'delete'], true)) {
                http_response_code(400);
                $message = 'Thao tác không hợp lệ.';
            } else {
                try {
                    if ($operation === 'delete') {
                        if ($this->input('confirm_delete') !== '1') {
                            throw new DomainException('Vui lòng tích xác nhận trước khi xóa lịch khởi hành.');
                        }
                        $model->deleteManagedSchedule($recordId);
                        $_SESSION['admin_schedules_message'] = 'Đã xóa lịch khởi hành chưa có đơn đặt.';
                        header('Location: ' . $schedulesUrl(), true, 303);
                        return;
                    }

                    $mode = $operation === 'create' ? 'create' : 'edit';
                    if ($mode === 'edit') {
                        $record = $model->findManagedSchedule($recordId);
                        if ($record === null) {
                            http_response_code(404);
                            throw new DomainException('Lịch khởi hành không còn tồn tại. Vui lòng tải lại danh sách.');
                        }
                    }
                    foreach ($defaults as $key => $default) {
                        $values[$key] = $this->input($key);
                    }
                    $errors = $model->validateSchedule($values, $tours, $record);
                    if ($errors) {
                        throw new DomainException('Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.');
                    }
                    $savedId = $model->saveManagedSchedule($values, $mode === 'create' ? null : $recordId);
                    $_SESSION['admin_schedules_message'] = $mode === 'create'
                        ? 'Đã thêm lịch khởi hành mới.'
                        : 'Đã lưu thay đổi lịch khởi hành.';
                    header('Location: ' . $schedulesUrl(['edit' => $savedId]) . '#schedule-editor', true, 303);
                    return;
                } catch (DomainException $exception) {
                    $message = $exception->getMessage();
                } catch (PDOException $exception) {
                    $code = (int) ($exception->errorInfo[1] ?? 0);
                    if ($code === 1451) {
                        $message = 'Lịch đã có đơn đặt nên không thể xóa. Hãy chuyển lịch sang trạng thái “Đã hủy”.';
                    } elseif ($code === 1452) {
                        $message = 'Tour vừa thay đổi hoặc bị xóa. Vui lòng tải lại và chọn lại tour.';
                    } elseif ($code === 3819) {
                        $message = 'Dữ liệu vi phạm ràng buộc của lịch khởi hành. Vui lòng kiểm tra lại giá và số chỗ.';
                    } elseif (in_array($code, [1205, 1213], true)) {
                        $message = 'Lịch đang được cập nhật ở phiên khác. Vui lòng thử lưu lại.';
                    } else {
                        error_log('OnTrip schedule management: ' . $exception->getMessage());
                        http_response_code(503);
                        $message = 'Chưa thể lưu lịch khởi hành. Vui lòng thử lại sau.';
                    }
                }
            }
        } else {
            foreach (['edit' => 'edit', 'delete' => 'delete', 'customers' => 'customers'] as $key => $target) {
                if ($this->query($key) === '') {
                    continue;
                }
                $id = filter_var($this->query($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
                $found = $model->findManagedSchedule($id);
                if ($found === null) {
                    http_response_code(404);
                    $message = 'Không tìm thấy lịch khởi hành.';
                    $success = false;
                    break;
                }
                if ($target === 'edit') {
                    $mode = 'edit';
                    $record = $found;
                    foreach ($defaults as $field => $default) {
                        $values[$field] = (string) ($record[$field] ?? $default);
                    }
                    $values['han_dat'] = $record['han_dat'] ? date('Y-m-d\TH:i', strtotime($record['han_dat'])) : '';
                } elseif ($target === 'delete') {
                    $deleteSchedule = $found;
                } else {
                    $customerDetail = $model->getScheduleCustomers($id);
                }
            }
        }

        $isList = $mode === '' && $deleteSchedule === null && $customerDetail === null;
        $list = $isList
            ? $model->getManagedSchedules($filters, $page)
            : ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 0, 'per_page' => 10];
        $summary = $model->getSummary();

        require __DIR__ . '/../views/admin/lich_khoi_hanh.php';
    }
}
