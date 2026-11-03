<?php

/** Danh mục dùng chung: khu vực, điểm đến và nhu cầu; không nhận trực tiếp dữ liệu form. */
class DanhMucModel
{
    public const GROUPS = [
        'khu_vuc' => ['label' => 'Khu vực', 'table' => 'khu_vuc_tour', 'key' => 'ma_khu_vuc', 'name' => 'ten_khu_vuc'],
        'diem_den' => ['label' => 'Điểm đến', 'table' => 'diem_den', 'key' => 'id_diem_den', 'name' => 'ten_diem_den'],
        'nhu_cau' => ['label' => 'Nhu cầu du lịch', 'table' => 'nhu_cau', 'key' => 'ma_nhu_cau', 'name' => 'ten_nhu_cau'],
    ];

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    private function group(string $group): array
    {
        if (!isset(self::GROUPS[$group])) throw new InvalidArgumentException('Danh mục không hợp lệ.');
        return self::GROUPS[$group];
    }

    public function getCounts(): array
    {
        $counts = [];
        foreach (self::GROUPS as $key => $group) {
            $counts[$key] = (int) $this->connection->query('SELECT COUNT(*) FROM ' . $group['table'])->fetchColumn();
        }
        return $counts;
    }

    public function getRegions(): array
    {
        return $this->connection->query(
            'SELECT ma_khu_vuc AS id, ten_khu_vuc AS name, loai_tour AS tour_type FROM khu_vuc_tour ORDER BY loai_tour, thu_tu, ten_khu_vuc'
        )->fetchAll();
    }

    /** Chỉ chọn ảnh có sẵn trong thư viện, không nhận URL hoặc đường dẫn tùy ý. */
    public function getImageOptions(): array
    {
        $root = realpath(__DIR__ . '/../../public/assets/images');
        if ($root === false) return [];
        $images = [];
        foreach (['', '/tours'] as $folder) {
            if (!is_dir($root . $folder)) continue;
            foreach (new DirectoryIterator($root . $folder) as $file) {
                $resolved = $file->getRealPath();
                if (!$file->isFile() || $resolved === false || strpos($resolved, $root . DIRECTORY_SEPARATOR) !== 0) continue;
                if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'], true)) continue;
                if ($file->getFilename() === 'favicon.svg') continue;
                $relative = 'assets/images' . $folder . '/' . $file->getFilename();
                $images[$relative] = str_replace(['_', '-'], ' ', pathinfo($file->getFilename(), PATHINFO_FILENAME));
            }
        }
        natcasesort($images);
        return $images;
    }

    /** Tên bảng/cột chỉ lấy từ danh sách cố định, mọi giá trị đều dùng prepared statements. */
    private function selectSql(string $group): string
    {
        $info = $this->group($group);
        $fields = 'x.' . $info['key'] . ' AS id, x.' . $info['name'] . ' AS name';
        if ($group === 'khu_vuc') {
            $fields .= ", x.loai_tour AS tour_type, x.thu_tu AS sort_order,
                (SELECT COUNT(*) FROM diem_den d WHERE d.ma_khu_vuc = x.ma_khu_vuc) AS child_count,
                (SELECT COUNT(*) FROM tour t JOIN diem_den d ON d.id_diem_den = t.id_diem_den_chinh WHERE d.ma_khu_vuc = x.ma_khu_vuc) AS usage_count";
        } elseif ($group === 'diem_den') {
            $fields .= ', x.ma_khu_vuc AS region_code, r.ten_khu_vuc AS region_name, r.loai_tour AS tour_type,
                x.duong_dan_anh AS image, x.dang_hoat_dong AS active,
                (SELECT COUNT(*) FROM tour t WHERE t.id_diem_den_chinh = x.id_diem_den) AS usage_count';
        } else {
            $fields .= ', x.thu_tu AS sort_order, x.dang_hoat_dong AS active,
                (SELECT COUNT(*) FROM tour_nhu_cau t WHERE t.ma_nhu_cau = x.ma_nhu_cau) AS usage_count';
        }
        return 'SELECT ' . $fields . ' FROM ' . $info['table'] . ' x'
            . ($group === 'diem_den' ? ' JOIN khu_vuc_tour r ON r.ma_khu_vuc = x.ma_khu_vuc' : '');
    }

    public function getList(string $group, array $filters, int $page): array
    {
        $info = $this->group($group);
        $conditions = [];
        $parameters = [];
        if ($filters['q'] !== '') {
            $conditions[] = '(x.' . $info['name'] . ' LIKE ? OR x.' . $info['key'] . ' LIKE ?)';
            $parameters[] = '%' . $filters['q'] . '%';
            $parameters[] = '%' . $filters['q'] . '%';
        }
        if ($group !== 'nhu_cau' && $filters['type'] !== '') {
            $conditions[] = ($group === 'diem_den' ? 'r' : 'x') . '.loai_tour = ?';
            $parameters[] = $filters['type'];
        }
        if ($group === 'diem_den' && $filters['region'] !== '') {
            $conditions[] = 'x.ma_khu_vuc = ?';
            $parameters[] = $filters['region'];
        }
        if ($group !== 'khu_vuc' && $filters['status'] !== '') {
            $conditions[] = 'x.dang_hoat_dong = ?';
            $parameters[] = (int) $filters['status'];
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $join = $group === 'diem_den' ? ' JOIN khu_vuc_tour r ON r.ma_khu_vuc = x.ma_khu_vuc' : '';
        $query = $this->connection->prepare('SELECT COUNT(*) FROM ' . $info['table'] . ' x' . $join . $where);
        $query->execute($parameters);
        $total = (int) $query->fetchColumn();
        $pages = max(1, (int) ceil($total / 10));
        $page = min(max(1, $page), $pages);
        $order = $group === 'diem_den' ? 'r.loai_tour, r.thu_tu, x.ten_diem_den, x.id_diem_den' : 'x.thu_tu, x.' . $info['key'];
        $query = $this->connection->prepare($this->selectSql($group) . $where . ' ORDER BY ' . $order . ' LIMIT 10 OFFSET ' . (($page - 1) * 10));
        $query->execute($parameters);
        return ['rows' => $query->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    public function find(string $group, string $id, bool $lock = false): ?array
    {
        $info = $this->group($group);
        if ($group === 'diem_den' && !preg_match('/^[1-9][0-9]{0,19}$/D', $id)) return null;
        $query = $this->connection->prepare($this->selectSql($group) . ' WHERE x.' . $info['key'] . ' = ?' . ($lock ? ' FOR UPDATE' : ''));
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function validate(string $group, array $values, bool $creating): array
    {
        $this->group($group);
        $errors = [];
        $max = ['khu_vuc' => 80, 'diem_den' => 120, 'nhu_cau' => 100][$group];
        if (!mb_check_encoding($values['name'], 'UTF-8') || mb_strlen($values['name'], 'UTF-8') < 2
            || mb_strlen($values['name'], 'UTF-8') > $max || preg_match('/[\x00-\x1F\x7F]/u', $values['name'])) {
            $errors['name'] = 'Tên cần từ 2 đến ' . $max . ' ký tự hợp lệ.';
        }
        if ($creating && $group !== 'diem_den' && !preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $values['id'])) {
            $errors['id'] = 'Mã tối đa 32 ký tự; bắt đầu bằng chữ thường, chỉ gồm chữ không dấu, số, dấu gạch ngang hoặc gạch dưới.';
        }
        if ($group === 'khu_vuc' && !in_array($values['tour_type'], ['domestic', 'international'], true)) $errors['tour_type'] = 'Vui lòng chọn loại tour.';
        if ($group !== 'diem_den' && (!ctype_digit($values['sort_order']) || (int) $values['sort_order'] > 255)) $errors['sort_order'] = 'Thứ tự là số nguyên từ 0 đến 255.';
        if ($group !== 'khu_vuc' && !in_array($values['active'], ['0', '1'], true)) $errors['active'] = 'Trạng thái không hợp lệ.';
        if ($group === 'diem_den') {
            $regions = array_column($this->getRegions(), 'id');
            if (!in_array($values['region_code'], $regions, true)) $errors['region_code'] = 'Vui lòng chọn khu vực đang tồn tại.';
            if ($values['image'] !== '' && !array_key_exists($values['image'], $this->getImageOptions())) $errors['image'] = 'Vui lòng chọn ảnh trong thư viện.';
        }
        return $errors;
    }

    /** Khóa và kiểm tra lại người thao tác để không dùng quyền đã bị thu hồi. */
    private function authorize(int $actorId, string $stamp): void
    {
        $query = $this->connection->prepare('SELECT vai_tro, dang_hoat_dong, mat_khau FROM tai_khoan WHERE id_tai_khoan = ? FOR UPDATE');
        $query->execute([$actorId]);
        $actor = $query->fetch();
        if (!$actor || (int) $actor['dang_hoat_dong'] !== 1 || !in_array($actor['vai_tro'], ['admin', 'nhan_vien'], true)
            || !hash_equals(hash('sha256', $actor['mat_khau']), $stamp)) {
            throw new DomainException('Phiên quản trị không còn quyền thao tác. Vui lòng đăng nhập lại.', 403);
        }
    }

    public function save(int $actorId, string $stamp, string $group, ?string $id, array $values): void
    {
        $info = $this->group($group);
        $errors = $this->validate($group, $values, $id === null);
        if ($errors) throw new DomainException(reset($errors));
        $this->connection->beginTransaction();
        try {
            $this->authorize($actorId, $stamp);
            $existing = $id === null ? null : $this->find($group, $id, true);
            if ($id !== null && !$existing) throw new DomainException('Danh mục không còn tồn tại. Vui lòng tải lại danh sách.');
            if ($group === 'khu_vuc' && $existing && $existing['tour_type'] !== $values['tour_type'] && (int) $existing['child_count'] > 0) {
                throw new DomainException('Khu vực đã có điểm đến nên không thể đổi loại tour. Hãy tạo khu vực mới nếu cần.');
            }
            if ($group === 'diem_den') {
                if ($existing && $existing['region_code'] !== $values['region_code'] && (int) $existing['usage_count'] > 0) {
                    throw new DomainException('Điểm đến đang được tour sử dụng nên không thể chuyển khu vực.');
                }
                $parent = $this->connection->prepare('SELECT ma_khu_vuc FROM khu_vuc_tour WHERE ma_khu_vuc = ? FOR UPDATE');
                $parent->execute([$values['region_code']]);
                if ($parent->fetchColumn() === false) throw new DomainException('Khu vực đã bị xóa. Vui lòng chọn lại.');
            }
            $data = [$info['name'] => $values['name']];
            if ($group === 'khu_vuc') $data['loai_tour'] = $values['tour_type'];
            if ($group !== 'diem_den') $data['thu_tu'] = (int) $values['sort_order'];
            if ($group !== 'khu_vuc') $data['dang_hoat_dong'] = (int) $values['active'];
            if ($group === 'diem_den') {
                $data['ma_khu_vuc'] = $values['region_code'];
                $data['duong_dan_anh'] = $values['image'] === '' ? null : $values['image'];
            }
            if ($id === null) {
                if ($group !== 'diem_den') $data[$info['key']] = $values['id'];
                $query = $this->connection->prepare('INSERT INTO ' . $info['table'] . ' (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')');
                $query->execute(array_values($data));
            } else {
                $assignments = array_map(static fn($column) => $column . ' = ?', array_keys($data));
                $query = $this->connection->prepare('UPDATE ' . $info['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE ' . $info['key'] . ' = ?');
                $query->execute(array_merge(array_values($data), [$id]));
            }
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    public function delete(int $actorId, string $stamp, string $group, string $id): void
    {
        $info = $this->group($group);
        $this->connection->beginTransaction();
        try {
            $this->authorize($actorId, $stamp);
            $row = $this->find($group, $id, true);
            if (!$row) throw new DomainException('Danh mục không còn tồn tại.');
            if ((int) $row['usage_count'] > 0 || (int) ($row['child_count'] ?? 0) > 0) {
                throw new DomainException($group === 'khu_vuc'
                    ? 'Khu vực đã có điểm đến nên không thể xóa.'
                    : 'Danh mục đang được tour sử dụng nên không thể xóa. Bạn có thể ngừng hoạt động để giữ liên kết.');
            }
            $query = $this->connection->prepare('DELETE FROM ' . $info['table'] . ' WHERE ' . $info['key'] . ' = ?');
            $query->execute([$id]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }
}
