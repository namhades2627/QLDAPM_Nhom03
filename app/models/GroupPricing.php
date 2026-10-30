<?php

/**
 * Quy tắc ưu đãi nhóm dùng chung cho trang tour, trang đặt tour và đơn đã lưu.
 * Chỉ áp dụng một mức cao nhất, không cộng dồn: 1-4 khách 0%, 5-9 khách 5%, từ 10 khách 8%.
 */
class GroupPricing
{
    /** @var array<int, array{0:int,1:int}> Ngưỡng khách tối thiểu => phần trăm giảm, giảm dần. */
    private const TIERS = [
        [10, 8],
        [5, 5],
    ];

    public static function rateFor(int $guests): int
    {
        if ($guests <= 0) {
            return 0;
        }
        foreach (self::TIERS as [$minimum, $rate]) {
            if ($guests >= $minimum) {
                return $rate;
            }
        }
        return 0;
    }

    /**
     * @return array{adults:int, children:int, guests:int, subtotal:int, rate:int, discount:int, total:int}
     */
    public static function calculate(int $adults, int $children, int $adultPrice, int $childPrice): array
    {
        $adults = max(0, $adults);
        $children = max(0, $children);
        $guests = $adults + $children;
        $rate = self::rateFor($guests);
        $subtotal = $adults * max(0, $adultPrice) + $children * max(0, $childPrice);
        $discount = (int) round($subtotal * $rate / 100);

        return [
            'adults' => $adults,
            'children' => $children,
            'guests' => $guests,
            'subtotal' => $subtotal,
            'rate' => $rate,
            'discount' => $discount,
            'total' => $subtotal - $discount,
        ];
    }
}
