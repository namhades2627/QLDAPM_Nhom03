<?php

class ThongBaoModel
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function isCustomer(int $accountId): bool
    {
        $query = $this->connection->prepare("SELECT 1 FROM tai_khoan WHERE id_tai_khoan = ? AND vai_tro = 'khach_hang' AND dang_hoat_dong = 1");
        $query->execute([$accountId]);
        return (bool) $query->fetchColumn();
    }

    /** Gọi trong cùng transaction với thay đổi đơn/thanh toán. */
    public function createForOrder(int $orderId, string $type): void
    {
        $titles = [
            'xac_nhan_don' => 'Đơn đặt đã được xác nhận',
            'thanh_toan' => 'Đã ghi nhận thanh toán mô phỏng',
            'huy_don' => 'Đơn đặt đã được hủy',
        ];
        if (!$this->connection->inTransaction() || !isset($titles[$type])) {
            throw new LogicException('Thông báo phải thuộc một sự kiện đơn đặt trong transaction.');
        }
        $messages = [
            'xac_nhan_don' => 'Đơn %s đã được xác nhận. Xem chi tiết chuyến đi của bạn.',
            'thanh_toan' => 'Đã ghi nhận thanh toán mô phỏng cho đơn %s. Xem chi tiết giao dịch trong đơn.',
            'huy_don' => 'Đơn %s đã được hủy. Xem trạng thái và thông tin đơn.',
        ];
        $query = $this->connection->prepare("SELECT d.id_tai_khoan, d.ma_don FROM don_dat_tour d JOIN tai_khoan t ON t.id_tai_khoan = d.id_tai_khoan WHERE d.id_don_dat = ? AND t.vai_tro = 'khach_hang'");
        $query->execute([$orderId]);
        $order = $query->fetch();
        if (!$order) return;
        $this->connection->prepare(
            'INSERT INTO thong_bao (id_tai_khoan, id_don_dat, loai_thong_bao, tieu_de, noi_dung) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE id_thong_bao = id_thong_bao'
        )->execute([(int) $order['id_tai_khoan'], $orderId, $type, $titles[$type], sprintf($messages[$type], $order['ma_don'])]);
    }

    private function ownedRows(): string
    {
        return ' FROM thong_bao n' . $this->ownedJoins() . $this->ownedWhere();
    }

    private function ownedJoins(): string
    {
        return ' LEFT JOIN don_dat_tour d ON d.id_don_dat = n.id_don_dat
            LEFT JOIN tin_nhan_tu_van m ON m.id_tin_nhan = n.id_tin_nhan
            LEFT JOIN yeu_cau_tu_van y ON y.id_yeu_cau = m.id_yeu_cau';
    }

    private function ownedWhere(): string
    {
        return " WHERE n.id_tai_khoan = ? AND ((n.id_don_dat IS NOT NULL AND d.id_tai_khoan = n.id_tai_khoan)
            OR (n.id_tin_nhan IS NOT NULL AND y.id_tai_khoan = n.id_tai_khoan AND m.vai_tro_gui = 'nhan_vien'))";
    }

    public function unreadCount(int $accountId): int
    {
        $query = $this->connection->prepare('SELECT COUNT(*)' . $this->ownedRows() . ' AND n.da_doc = 0');
        $query->execute([$accountId]);
        return (int) $query->fetchColumn();
    }

    public function listForAccount(int $accountId, int $page = 1, int $perPage = 10): array
    {
        $query = $this->connection->prepare('SELECT COUNT(*)' . $this->ownedRows());
        $query->execute([$accountId]);
        $total = (int) $query->fetchColumn();
        $perPage = max(1, min(20, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, $page));
        $query = $this->connection->prepare('SELECT n.*, d.ma_don' . $this->ownedRows() . ' ORDER BY n.id_thong_bao DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage));
        $query->execute([$accountId]);
        return ['items' => $query->fetchAll(), 'page' => $page, 'pages' => $pages, 'total' => $total];
    }

    public function readOne(int $accountId, int $id): ?string
    {
        $query = $this->connection->prepare('SELECT d.ma_don, m.id_yeu_cau' . $this->ownedRows() . ' AND n.id_thong_bao = ?');
        $query->execute([$accountId, $id]);
        $row = $query->fetch();
        if (!$row) return null;
        $this->connection->prepare('UPDATE thong_bao n' . $this->ownedJoins() . ' SET n.da_doc = 1' . $this->ownedWhere() . ' AND n.id_thong_bao = ?')->execute([$accountId, $id]);
        return $row['ma_don'] !== null ? 'index.php?page=orders&id=' . rawurlencode($row['ma_don']) : 'index.php?page=tu_van&view=' . (int) $row['id_yeu_cau'];
    }

    public function readAll(int $accountId): void
    {
        $this->connection->prepare('UPDATE thong_bao n' . $this->ownedJoins() . ' SET n.da_doc = 1' . $this->ownedWhere() . ' AND n.da_doc = 0')->execute([$accountId]);
    }
}
