<?php
// Nhãn và định dạng dùng chung cho trang đặt tour và trang quản trị lịch.
if (!function_exists('booking_status_label')) {
    function booking_status_label(string $status): string
    {
        $labels = [
            'cho_xac_nhan' => 'Chờ xác nhận',
            'da_xac_nhan' => 'Đã xác nhận',
            'hoan_thanh' => 'Hoàn thành',
            'da_huy' => 'Đã hủy',
        ];
        return $labels[$status] ?? 'Không rõ';
    }

    function booking_status_class(string $status): string
    {
        return match ($status) {
            'cho_xac_nhan' => 'is-pending',
            'da_xac_nhan' => 'is-confirmed',
            'hoan_thanh' => 'is-done',
            default => 'is-cancelled',
        };
    }

    function booking_money($amount): string
    {
        return number_format((int) $amount, 0, ',', '.') . ' ₫';
    }

    function booking_date(?string $date): string
    {
        return $date ? date('d/m/Y', strtotime($date)) : '—';
    }

    function booking_datetime(?string $datetime): string
    {
        return $datetime ? date('H:i d/m/Y', strtotime($datetime)) : '—';
    }

    function schedule_status_label(string $status): string
    {
        $labels = ['open' => 'Đang mở', 'closed' => 'Đã đóng', 'cancelled' => 'Đã hủy'];
        return $labels[$status] ?? 'Không rõ';
    }

    function schedule_status_class(string $status): string
    {
        return match ($status) {
            'open' => 'is-confirmed',
            'closed' => 'is-pending',
            default => 'is-cancelled',
        };
    }

    /** Số chỗ còn lại được tô màu theo mức độ lấp đầy. */
    function seat_level_class(int $seatsLeft, int $capacity): string
    {
        if ($seatsLeft <= 0) return 'is-full';
        if ($capacity > 0 && $seatsLeft / $capacity <= 0.2) return 'is-low';
        return 'is-ok';
    }
}
