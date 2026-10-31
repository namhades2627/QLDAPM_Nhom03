<?php

/** Truy vấn tài khoản; không nhận trực tiếp dữ liệu từ form. */
class TaiKhoanModel
{
    /** @var PDO */
    private $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function findByEmail(string $email): ?array
    {
        $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE id_tai_khoan = ? LIMIT 1');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }

    public function updateStaffProfile(int $id, string $name, string $email, string $phone, ?string $avatar = null): void
    {
        // Chỉ cập nhật thông tin liên hệ, không đổi vai trò, trạng thái hay mật khẩu.
        $query = $this->connection->prepare(
            "UPDATE tai_khoan SET ho_ten = ?, email = ?, so_dien_thoai = ?, hinh_anh = COALESCE(?, hinh_anh)
             WHERE id_tai_khoan = ? AND dang_hoat_dong = 1 AND vai_tro IN ('admin', 'nhan_vien')"
        );
        $query->execute([$name, $email, $phone, $avatar, $id]);
    }

    public function updateStaffPassword(int $id, string $oldHash, string $newHash): bool
    {
        $query = $this->connection->prepare(
            "UPDATE tai_khoan SET mat_khau = ? WHERE id_tai_khoan = ? AND mat_khau = ?
             AND dang_hoat_dong = 1 AND vai_tro IN ('admin', 'nhan_vien')"
        );
        $query->execute([$newHash, $id, $oldHash]);
        return $query->rowCount() === 1;
    }

    public function createCustomer(string $name, string $email, string $phone, string $passwordHash): void
    {
        // Vai trò và trạng thái do máy chủ quyết định, không lấy từ form đăng ký.
        $query = $this->connection->prepare(
            "INSERT INTO tai_khoan (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, dang_hoat_dong)
             VALUES (?, ?, ?, ?, 'khach_hang', 1)"
        );
        $query->execute([$name, $email, $phone, $passwordHash]);
    }

    /** Phạm vi được chọn từ route phía máy chủ, không nhận từ form hoặc bộ lọc. */
    private function managedRoles(string $group): array
    {
        if ($group === 'staff') return ['admin', 'nhan_vien'];
        if ($group === 'customers') return ['khach_hang'];
        throw new InvalidArgumentException('Nhóm tài khoản không hợp lệ.');
    }

    public function findManagedAccount(int $id, string $group): ?array
    {
        $roles = $this->managedRoles($group);
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE id_tai_khoan = ? AND vai_tro IN (' . $placeholders . ')');
        $query->execute(array_merge([$id], $roles));
        return $query->fetch() ?: null;
    }

    /** Danh sách và thống kê chỉ thuộc đúng nhóm; không đưa hash mật khẩu vào dữ liệu hiển thị. */
    public function getManagedAccounts(string $keyword, string $role, string $status, int $page, string $group = 'staff'): array
    {
        $roles = $this->managedRoles($group);
        $groupCondition = 'vai_tro IN (' . implode(',', array_fill(0, count($roles), '?')) . ')';
        $conditions = [$groupCondition];
        $parameters = $roles;
        if ($keyword !== '') {
            $conditions[] = "LOCATE(?, CONCAT(ho_ten, ' ', email, ' ', so_dien_thoai)) > 0";
            $parameters[] = $keyword;
        }
        if (in_array($role, $roles, true)) {
            $conditions[] = 'vai_tro = ?';
            $parameters[] = $role;
        }
        if (in_array($status, ['0', '1'], true)) {
            $conditions[] = 'dang_hoat_dong = ?';
            $parameters[] = (int) $status;
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $query = $this->connection->prepare('SELECT COUNT(*) FROM tai_khoan' . $where);
        $query->execute($parameters);
        $total = (int) $query->fetchColumn();
        $pages = max(1, (int) ceil($total / 10));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * 10;
        $query = $this->connection->prepare(
            'SELECT id_tai_khoan, ho_ten, email, so_dien_thoai, hinh_anh, vai_tro, dang_hoat_dong, tao_luc
             FROM tai_khoan' . $where . ' ORDER BY id_tai_khoan DESC LIMIT 10 OFFSET ' . $offset
        );
        $query->execute($parameters);
        $items = $query->fetchAll();
        $monthStart = (new DateTimeImmutable('first day of this month 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s');
        $query = $this->connection->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(vai_tro = 'admin'), 0) AS admins,
                    COALESCE(SUM(vai_tro = 'nhan_vien'), 0) AS staff,
                    COALESCE(SUM(dang_hoat_dong = 1), 0) AS active,
                    COALESCE(SUM(dang_hoat_dong = 0), 0) AS locked,
                    COALESCE(SUM(tao_luc >= ?), 0) AS registered_month FROM tai_khoan WHERE " . $groupCondition
        );
        $query->execute(array_merge([$monthStart], $roles));
        $summary = $query->fetch();
        return compact('items', 'total', 'pages', 'page', 'summary');
    }

    /** Khóa các dòng admin theo cùng thứ tự để hai admin không đồng thời loại bỏ quyền của nhau. */
    private function lockAccountManager(int $managerId, string $credentialStamp): array
    {
        $admins = $this->connection->query(
            "SELECT id_tai_khoan, dang_hoat_dong, mat_khau FROM tai_khoan
             WHERE vai_tro = 'admin' ORDER BY id_tai_khoan FOR UPDATE"
        )->fetchAll();
        foreach ($admins as $admin) {
            if ((int) $admin['id_tai_khoan'] === $managerId && (int) $admin['dang_hoat_dong'] === 1
                && hash_equals(hash('sha256', $admin['mat_khau']), $credentialStamp)) {
                return $admins;
            }
        }
        throw new DomainException('Quyền quản lý tài khoản đã thay đổi. Vui lòng đăng nhập lại.');
    }

    public function createEmployee(int $managerId, string $credentialStamp, string $name, string $email, string $phone, string $passwordHash): void
    {
        $this->connection->beginTransaction();
        try {
            $this->lockAccountManager($managerId, $credentialStamp);
            $query = $this->connection->prepare(
                "INSERT INTO tai_khoan (ho_ten, email, so_dien_thoai, mat_khau, vai_tro, dang_hoat_dong)
                 VALUES (?, ?, ?, ?, 'nhan_vien', 1)"
            );
            $query->execute([$name, $email, $phone, $passwordHash]);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    /** Xóa trong transaction; trả đường dẫn ảnh để Controller dọn sau khi commit thành công. */
    public function deleteManagedAccount(int $managerId, string $credentialStamp, int $targetId, string $group = 'staff'): ?string
    {
        $this->connection->beginTransaction();
        try {
            $admins = $this->lockAccountManager($managerId, $credentialStamp);
            $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE id_tai_khoan = ? FOR UPDATE');
            $query->execute([$targetId]);
            $target = $query->fetch();
            if (!$target) throw new DomainException('Tài khoản không còn tồn tại. Vui lòng tải lại danh sách.');
            if (!in_array($target['vai_tro'], $this->managedRoles($group), true)) {
                throw new DomainException('Tài khoản không thuộc nhóm đang quản lý. Vui lòng mở đúng mục trong menu.');
            }
            if ($targetId === $managerId) throw new DomainException('Không thể xóa tài khoản đang đăng nhập của chính mình.');
            $activeAdmins = count(array_filter($admins, static fn($row) => (int) $row['dang_hoat_dong'] === 1));
            if ($target['vai_tro'] === 'admin' && (int) $target['dang_hoat_dong'] === 1 && $activeAdmins <= 1) {
                throw new DomainException('Cần giữ ít nhất một quản trị viên đang hoạt động.');
            }
            // Kiểm tra các khóa ngoại thực tế, kể cả ON DELETE CASCADE/SET NULL,
            // để không xóa theo dữ liệu giao dịch khi có thêm module liên quan.
            $references = $this->connection->query(
                "SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'tai_khoan'
                   AND REFERENCED_COLUMN_NAME = 'id_tai_khoan'"
            )->fetchAll();
            $quoteIdentifier = static fn(string $name): string => '`' . str_replace('`', '``', $name) . '`';
            foreach ($references as $reference) {
                $query = $this->connection->prepare(
                    'SELECT 1 FROM ' . $quoteIdentifier($reference['TABLE_SCHEMA']) . '.' . $quoteIdentifier($reference['TABLE_NAME'])
                    . ' WHERE ' . $quoteIdentifier($reference['COLUMN_NAME']) . ' = ? LIMIT 1'
                );
                $query->execute([$targetId]);
                if ($query->fetchColumn() !== false) {
                    throw new DomainException('Tài khoản đã có dữ liệu liên quan nên không thể xóa. Hãy khóa tài khoản để giữ lịch sử.');
                }
            }
            $query = $this->connection->prepare('DELETE FROM tai_khoan WHERE id_tai_khoan = ?');
            $query->execute([$targetId]);
            $avatar = $target['hinh_anh'];
            if ($avatar !== null) {
                // Không xóa file nếu đường dẫn ảnh vẫn được tài khoản khác sử dụng.
                $query = $this->connection->prepare('SELECT 1 FROM tai_khoan WHERE hinh_anh = ? LIMIT 1');
                $query->execute([$avatar]);
                if ($query->fetchColumn() !== false) $avatar = null;
            }
            $this->connection->commit();
            return $avatar;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }

    /** Chỉ sửa liên hệ/quyền/trạng thái; thao tác khóa chỉ truyền dang_hoat_dong. */
    public function updateManagedAccount(int $managerId, string $credentialStamp, int $targetId, array $changes, string $group = 'staff'): void
    {
        $this->connection->beginTransaction();
        try {
            $admins = $this->lockAccountManager($managerId, $credentialStamp);
            $query = $this->connection->prepare('SELECT * FROM tai_khoan WHERE id_tai_khoan = ? FOR UPDATE');
            $query->execute([$targetId]);
            $target = $query->fetch();
            if (!$target) throw new DomainException('Tài khoản không còn tồn tại. Vui lòng tải lại danh sách.');
            $roles = $this->managedRoles($group);
            if (!in_array($target['vai_tro'], $roles, true)) {
                throw new DomainException('Tài khoản không thuộc nhóm đang quản lý. Vui lòng mở đúng mục trong menu.');
            }
            if ($targetId === $managerId) {
                throw new DomainException('Không thể thay đổi quyền hoặc khóa chính mình. Hãy dùng Hồ sơ cá nhân để sửa thông tin.');
            }
            $role = $changes['vai_tro'] ?? $target['vai_tro'];
            $active = $changes['dang_hoat_dong'] ?? (int) $target['dang_hoat_dong'];
            if (!in_array($role, $roles, true) || !in_array($active, [0, 1], true)) {
                throw new DomainException('Vai trò hoặc trạng thái tài khoản không hợp lệ.');
            }
            $activeAdmins = count(array_filter($admins, static fn($row) => (int) $row['dang_hoat_dong'] === 1));
            if ($target['vai_tro'] === 'admin' && (int) $target['dang_hoat_dong'] === 1
                && ($role !== 'admin' || $active === 0) && $activeAdmins <= 1) {
                throw new DomainException('Cần giữ ít nhất một quản trị viên đang hoạt động.');
            }
            $assignments = [];
            $parameters = [];
            foreach (['ho_ten', 'email', 'so_dien_thoai', 'vai_tro', 'dang_hoat_dong'] as $column) {
                if (!array_key_exists($column, $changes)) continue;
                $assignments[] = $column . ' = ?';
                $parameters[] = $changes[$column];
            }
            if (!$assignments) throw new DomainException('Chưa có thông tin cần cập nhật.');
            $parameters[] = $targetId;
            $query = $this->connection->prepare('UPDATE tai_khoan SET ' . implode(', ', $assignments) . ' WHERE id_tai_khoan = ?');
            $query->execute($parameters);
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) $this->connection->rollBack();
            throw $exception;
        }
    }
}
