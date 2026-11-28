<?php

require_once __DIR__ . '/../models/thong_ke.php';

/**
 * Thống kê tình hình đặt tour và doanh thu cho trang quản trị.
 * Phiên đăng nhập và quyền quản trị được AdminController kiểm tra trước khi gọi.
 */
class ThongKeController
{
    private function query(string $key): string
    {
        return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : '';
    }

    private function validDate(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value ? $value : '';
    }

    public function index(array $account, string $roleName, string $csrfToken): void
    {
        $model = new ThongKeModel(getDatabaseConnection());
        $adminSection = 'thong_ke';
        $avatarUrl = AdminController::avatarUrl($account['hinh_anh'] ?? null);
        $tourOptions = $model->getTourOptions();
        $validTourCodes = array_column($tourOptions, 'ma_tour');

        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $preset = $this->query('range');
        $from = $this->validDate($this->query('from'));
        $to = $this->validDate($this->query('to'));

        if ($preset === 'month') {
            $from = $now->modify('first day of this month')->format('Y-m-d');
            $to = $now->format('Y-m-d');
        } elseif ($preset === '30d') {
            $from = $now->modify('-29 days')->format('Y-m-d');
            $to = $now->format('Y-m-d');
        } elseif ($preset === 'year') {
            $from = $now->format('Y-01-01');
            $to = $now->format('Y-m-d');
        } elseif ($preset === 'all') {
            $from = '';
            $to = '';
        }

        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $groupBy = $this->query('group') === 'day' ? 'day' : 'month';
        $tourCode = in_array($this->query('tour'), $validTourCodes, true) ? $this->query('tour') : '';

        $filters = [
            'from' => $from,
            'to' => $to,
            'tour' => $tourCode,
            'group' => $groupBy,
            'range' => in_array($preset, ['month', '30d', 'year', 'all'], true) ? $preset : '',
        ];

        $statsUrl = static function (array $extra = []) use ($filters): string {
            $params = array_merge(['action' => 'thong_ke'], $filters, $extra);
            return 'index.php?' . http_build_query(array_filter($params, static function ($v): bool {
                return $v !== '' && $v !== null;
            }));
        };

        $overview = $model->getOverviewStats($filters);
        $timeSeries = $model->getTimeSeries($filters, $groupBy);
        $topTours = $model->getTopTours($filters, 10);
        $paymentBreakdown = $model->getPaymentBreakdown($filters);
        $recentTransactions = $model->getRecentTransactions($filters, 10);
        $statusLabels = DonDatModel::STATUS_LABELS;

        require __DIR__ . '/../views/admin/thong_ke.php';
    }
}
