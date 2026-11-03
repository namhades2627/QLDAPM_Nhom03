<?php

require_once __DIR__ . '/../models/tour.php';
require_once __DIR__ . '/../models/danh_muc.php';

/** Quản lý nội dung tour; phiên và quyền vào quản trị do AdminController kiểm tra. */
class AdminTourController
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
        $model = new TourModel(getDatabaseConnection());
        $adminSection = 'tours';
        $avatarUrl = AdminController::avatarUrl($account['hinh_anh'] ?? null);
        $options = $model->getManagementOptions();
        $imageOptions = (new DanhMucModel(getDatabaseConnection()))->getImageOptions();
        $statuses = TourModel::STATUSES;
        $seasons = TourModel::SEASONS;
        $filters = [
            'q' => mb_substr($this->query('q'), 0, 120, 'UTF-8'),
            'type' => in_array($this->query('type'), ['domestic', 'international'], true) ? $this->query('type') : '',
            'status' => isset($statuses[$this->query('status')]) ? $this->query('status') : '',
            'destination' => in_array($this->query('destination'), array_map('strval', array_column($options['destinations'], 'id_diem_den')), true) ? $this->query('destination') : '',
            'need' => in_array($this->query('need'), array_column($options['needs'], 'ma_nhu_cau'), true) ? $this->query('need') : '',
        ];
        $page = filter_var($this->query('p'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $toursUrl = static function (array $extra = []) use ($filters, $page): string {
            return 'index.php?' . http_build_query(array_filter(array_merge(['action' => 'tours'], $filters, ['p' => $page], $extra), static fn($value) => $value !== '' && $value !== null));
        };
        $defaults = ['ma_tour' => '', 'ten_tour' => '', 'id_diem_den_chinh' => '', 'gioi_thieu' => '', 'so_ngay' => '3',
            'duong_dan_anh' => '', 'trang_thai' => 'draft', 'noi_bat' => '0', 'mua_vu' => 'tat_ca', 'phan_tram_giam' => '0', 'nhan_uu_dai' => ''];
        $values = $defaults + ['needs' => [], 'itinerary' => array_fill(0, 3, ['tieu_de' => '', 'noi_dung' => ''])];
        $mode = $this->query('mode') === 'create' ? 'create' : '';
        $record = $deleteTour = $detail = null;
        $errors = [];
        $message = $_SESSION['tour_message'] ?? '';
        $success = $message !== '';
        unset($_SESSION['tour_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = false;
            $operation = $this->input('operation');
            $id = $this->input('record_id');
            if (in_array($operation, ['create', 'update'], true)) {
                $mode = $operation === 'create' ? 'create' : 'edit';
                if ($mode === 'edit') $record = $model->findManagedTour($id);
                foreach ($defaults as $key => $default) $values[$key] = $this->input($key);
                if ($record) $values['ma_tour'] = $record['ma_tour'];
                $needs = $_POST['needs'] ?? [];
                if (!is_array($needs) || !array_is_list($needs) || count($needs) > count($options['needs'])
                    || count(array_filter($needs, 'is_string')) !== count($needs)) $errors['needs'] = 'Danh sách nhu cầu không hợp lệ.';
                else $values['needs'] = array_values(array_unique($needs));
                $titles = $_POST['day_title'] ?? [];
                $contents = $_POST['day_content'] ?? [];
                $values['itinerary'] = [];
                if (!is_array($titles) || !is_array($contents) || !array_is_list($titles) || !array_is_list($contents)
                    || count($titles) !== count($contents) || count($titles) > 255
                    || count(array_filter($titles, 'is_string')) !== count($titles) || count(array_filter($contents, 'is_string')) !== count($contents)) {
                    $errors['itinerary'] = 'Dữ liệu lịch trình không hợp lệ. Vui lòng tải lại biểu mẫu.';
                } else {
                    foreach ($titles as $index => $title) $values['itinerary'][] = ['tieu_de' => trim($title), 'noi_dung' => trim($contents[$index])];
                }
            } elseif ($operation === 'delete') {
                $mode = '';
                $deleteTour = $model->findManagedTour($id);
            }
            if (!hash_equals($csrfToken, $this->input('csrf_token'))) {
                http_response_code(403);
                $message = 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.';
            } elseif (!in_array($operation, ['create', 'update', 'delete'], true)) {
                http_response_code(400);
                $message = 'Thao tác không hợp lệ.';
            } else {
                $newImage = null;
                try {
                    if ($operation === 'delete') {
                        if ($this->input('confirm_delete') !== '1') throw new DomainException('Vui lòng tích xác nhận trước khi xóa tour.');
                        $model->deleteManagedTour((int) $account['id_tai_khoan'], $_SESSION['admin_credential_stamp'], $id);
                        $_SESSION['tour_message'] = 'Đã xóa tour cùng lịch trình và liên kết nhu cầu của tour.';
                        header('Location: ' . $toursUrl(), true, 303);
                        return;
                    }
                    if ($mode === 'edit' && !$record) {
                        http_response_code(404);
                        throw new DomainException('Tour không còn tồn tại. Vui lòng tải lại danh sách.');
                    }
                    if ($this->input('resize_itinerary') === '1') {
                        $days = filter_var($values['so_ngay'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 255]]);
                        if (!$days) $errors['so_ngay'] = 'Số ngày là số nguyên từ 1 đến 255.';
                        if (!$errors) {
                            $values['itinerary'] = array_pad(array_slice($values['itinerary'], 0, $days), $days, ['tieu_de' => '', 'noi_dung' => '']);
                            $message = 'Đã áp dụng số ngày vào biểu mẫu. Chọn Lưu để ghi thay đổi.';
                            $success = true;
                        } else $message = 'Vui lòng kiểm tra số ngày và dữ liệu lịch trình.';
                    } else {
                        $errors = array_merge($model->validateManagedTour($values, $record), $errors);
                        if ($errors) throw new DomainException('Vui lòng kiểm tra các thông tin được đánh dấu bên dưới.');
                        $file = $_FILES['anh_tour'] ?? [];
                        if (!is_array($file)) throw new InvalidArgumentException('Ảnh tải lên không hợp lệ.');
                        $newImage = $this->saveImage($file);
                        if ($newImage !== null) $values['duong_dan_anh'] = $newImage;
                        $savedId = $model->saveManagedTour((int) $account['id_tai_khoan'], $_SESSION['admin_credential_stamp'], $mode === 'create' ? null : $id, $values);
                        $_SESSION['tour_message'] = $mode === 'create' ? 'Đã thêm tour mới.' : 'Đã lưu thay đổi tour.';
                        header('Location: ' . $toursUrl(['edit' => $savedId]) . '#tour-editor', true, 303);
                        return;
                    }
                } catch (DomainException $exception) {
                    if ($exception->getCode() === 403) http_response_code(403);
                    $message = $exception->getMessage();
                } catch (InvalidArgumentException $exception) {
                    $errors['anh_tour'] = $exception->getMessage();
                    $message = 'Ảnh chưa hợp lệ. Các thay đổi chưa được lưu.';
                } catch (PDOException $exception) {
                    $code = (int) ($exception->errorInfo[1] ?? 0);
                    if ($code === 1062) { $errors['ma_tour'] = 'Mã tour đã tồn tại.'; $message = 'Vui lòng chọn mã tour khác.'; }
                    elseif ($code === 1451) $message = 'Tour có dữ liệu liên quan nên không thể xóa. Hãy chuyển sang Lưu trữ.';
                    elseif ($code === 1452) $message = 'Danh mục vừa thay đổi hoặc bị xóa. Vui lòng tải lại và chọn lại.';
                    elseif (in_array($code, [1205, 1213], true)) $message = 'Tour đang được cập nhật ở phiên khác. Vui lòng thử lưu lại.';
                    else { error_log('OnTrip tour management: ' . $exception->getMessage()); http_response_code(503); $message = 'Chưa thể lưu tour. Vui lòng thử lại sau.'; }
                } catch (RuntimeException $exception) {
                    error_log('OnTrip tour image: ' . $exception->getMessage());
                    http_response_code(503);
                    $message = 'Chưa thể lưu ảnh tour. Vui lòng thử lại sau.';
                }
                // Ảnh chỉ được tạo trong request này và chưa lưu thành công; giữ nguyên ảnh thư viện đã có.
                if ($newImage !== null) {
                    $this->discardNewImage($newImage);
                    $values['duong_dan_anh'] = $this->input('duong_dan_anh');
                }
            }
        } else {
            foreach (['edit', 'delete', 'view'] as $action) {
                if ($this->query($action) === '') continue;
                $found = $model->findManagedTour($this->query($action));
                if (!$found) { http_response_code(404); $message = 'Không tìm thấy tour.'; $success = false; $mode = ''; break; }
                $mode = '';
                if ($action === 'edit') {
                    $mode = 'edit';
                    $record = $found;
                    foreach ($defaults as $key => $default) $values[$key] = (string) ($record[$key] ?? $default);
                    $values['needs'] = $record['needs'];
                    $values['itinerary'] = array_fill(0, (int) $record['so_ngay'], ['tieu_de' => '', 'noi_dung' => '']);
                    foreach ($record['itinerary'] as $day) {
                        if ((int) $day['ngay_thu'] <= (int) $record['so_ngay']) $values['itinerary'][(int) $day['ngay_thu'] - 1] = ['tieu_de' => $day['tieu_de'], 'noi_dung' => $day['noi_dung']];
                    }
                } elseif ($action === 'delete') $deleteTour = $found;
                else $detail = $found;
                break;
            }
        }
        $list = $model->getManagedTours($filters, $page);
        require __DIR__ . '/../views/admin/tour.php';
    }

    /** Tên file do máy chủ cấp, không dùng tên/đuôi ảnh người dùng gửi. */
    private function saveImage(array $file): ?string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Không tải được ảnh. Vui lòng chọn lại ảnh tối đa 5 MB.');
        $temp = $file['tmp_name'] ?? '';
        if (!is_string($temp) || !is_uploaded_file($temp) || filesize($temp) < 1 || filesize($temp) > 5 * 1024 * 1024) throw new InvalidArgumentException('Ảnh không hợp lệ hoặc vượt quá 5 MB.');
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
        $size = @getimagesize($temp);
        if (!isset($types[$mime]) || !$size || ($size['mime'] ?? '') !== $mime) throw new InvalidArgumentException('Chỉ nhận ảnh JPG, PNG hoặc WEBP có nội dung hợp lệ.');
        if ($size[0] > 4096 || $size[1] > 4096 || $size[0] * $size[1] > 12000000) throw new InvalidArgumentException('Ảnh tối đa 4096 × 4096 và 12 triệu điểm ảnh.');
        $image = extension_loaded('gd') ? @imagecreatefromstring(file_get_contents($temp)) : null;
        if ($image === false) throw new InvalidArgumentException('Không đọc được nội dung ảnh này.');
        $path = 'assets/images/tours/tour_' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
        $target = __DIR__ . '/../../public/' . $path;
        try {
            if ($image === null) $saved = move_uploaded_file($temp, $target);
            elseif ($mime === 'image/jpeg') $saved = imagejpeg($image, $target, 85);
            elseif ($mime === 'image/png') $saved = imagepng($image, $target, 6);
            else $saved = imagewebp($image, $target, 85);
            if (!$saved) throw new RuntimeException('Không ghi được ảnh tour.');
        } catch (Throwable $exception) {
            $this->discardNewImage($path);
            throw $exception;
        } finally {
            if ($image !== null) imagedestroy($image);
        }
        return $path;
    }

    private function discardNewImage(string $path): void
    {
        if (preg_match('~^assets/images/tours/tour_[a-f0-9]{32}\.(jpg|png|webp)$~D', $path)) {
            $file = __DIR__ . '/../../public/' . $path;
            if (is_file($file) && !unlink($file)) error_log('OnTrip: cannot remove unsaved tour image.');
        }
    }
}
