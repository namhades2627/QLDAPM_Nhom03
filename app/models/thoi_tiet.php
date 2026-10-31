<?php
declare(strict_types=1);

/**
 * ThoiTietModel: Tích hợp Open-Meteo Weather API và mô hình khí hậu du lịch theo ngày.
 * Cung cấp dự báo thời tiết chi tiết từng ngày cho lịch trình tour dựa trên ngày khởi hành đã chọn.
 */
class ThoiTietModel
{
    /**
     * Tọa độ địa lý (vĩ độ, kinh độ) của các điểm đến phổ biến và chặng tour
     * @var array<string, array{lat: float, lon: float, name: string}>
     */
    private static array $coordinates = [
        // Miền Bắc
        'hà nội'      => ['lat' => 21.0285, 'lon' => 105.8542, 'name' => 'Hà Nội'],
        'ha noi'      => ['lat' => 21.0285, 'lon' => 105.8542, 'name' => 'Hà Nội'],
        'sa pa'       => ['lat' => 22.3364, 'lon' => 103.8438, 'name' => 'Sa Pa'],
        'sapa'        => ['lat' => 22.3364, 'lon' => 103.8438, 'name' => 'Sa Pa'],
        'fansipan'    => ['lat' => 22.3034, 'lon' => 103.7753, 'name' => 'Fansipan (Sa Pa)'],
        'lào cai'     => ['lat' => 22.4856, 'lon' => 103.9707, 'name' => 'Lào Cai'],
        'lao cai'     => ['lat' => 22.4856, 'lon' => 103.9707, 'name' => 'Lào Cai'],
        'hạ long'     => ['lat' => 20.9505, 'lon' => 107.0734, 'name' => 'Hạ Long'],
        'ha long'     => ['lat' => 20.9505, 'lon' => 107.0734, 'name' => 'Hạ Long'],
        'vịnh hạ long'=> ['lat' => 20.9505, 'lon' => 107.0734, 'name' => 'Vịnh Hạ Long'],
        'hà giang'    => ['lat' => 22.8233, 'lon' => 104.9839, 'name' => 'Hà Giang'],
        'ha giang'    => ['lat' => 22.8233, 'lon' => 104.9839, 'name' => 'Hà Giang'],
        'đồng văn'    => ['lat' => 23.2789, 'lon' => 105.3622, 'name' => 'Đồng Văn (Hà Giang)'],
        'ninh bình'   => ['lat' => 20.2506, 'lon' => 105.9745, 'name' => 'Ninh Bình'],
        'ninh binh'   => ['lat' => 20.2506, 'lon' => 105.9745, 'name' => 'Ninh Bình'],
        'tràng an'    => ['lat' => 20.2550, 'lon' => 105.9180, 'name' => 'Tràng An (Ninh Bình)'],
        'bái đính'    => ['lat' => 20.2683, 'lon' => 105.8577, 'name' => 'Bái Đính (Ninh Bình)'],
        'hang múa'    => ['lat' => 20.2319, 'lon' => 105.9425, 'name' => 'Hang Múa (Ninh Bình)'],
        'mộc châu'    => ['lat' => 20.8437, 'lon' => 104.6325, 'name' => 'Mộc Châu'],
        'moc chau'    => ['lat' => 20.8437, 'lon' => 104.6325, 'name' => 'Mộc Châu'],
        'cao bằng'    => ['lat' => 22.6666, 'lon' => 106.2639, 'name' => 'Cao Bằng'],
        'cao bang'    => ['lat' => 22.6666, 'lon' => 106.2639, 'name' => 'Cao Bằng'],
        'bản giốc'    => ['lat' => 22.8542, 'lon' => 106.7233, 'name' => 'Thác Bản Giốc'],
        'yên bái'     => ['lat' => 21.7168, 'lon' => 104.8972, 'name' => 'Yên Bái'],
        'hồ ba bể'    => ['lat' => 22.4042, 'lon' => 105.6178, 'name' => 'Hồ Ba Bể'],
        'lai châu'    => ['lat' => 22.3962, 'lon' => 103.4583, 'name' => 'Lai Châu'],
        
        // Miền Trung
        'đà nẵng'     => ['lat' => 16.0544, 'lon' => 108.2022, 'name' => 'Đà Nẵng'],
        'da nang'     => ['lat' => 16.0544, 'lon' => 108.2022, 'name' => 'Đà Nẵng'],
        'bà nà'       => ['lat' => 15.9989, 'lon' => 107.9877, 'name' => 'Bà Nà Hills'],
        'huế'         => ['lat' => 16.4637, 'lon' => 107.5909, 'name' => 'Huế'],
        'hue'         => ['lat' => 16.4637, 'lon' => 107.5909, 'name' => 'Huế'],
        'hội an'      => ['lat' => 15.8801, 'lon' => 108.3380, 'name' => 'Hội An'],
        'hoi an'      => ['lat' => 15.8801, 'lon' => 108.3380, 'name' => 'Hội An'],
        'nha trang'   => ['lat' => 12.2388, 'lon' => 109.1967, 'name' => 'Nha Trang'],
        'đà lạt'      => ['lat' => 11.9404, 'lon' => 108.4583, 'name' => 'Đà Lạt'],
        'da lat'      => ['lat' => 11.9404, 'lon' => 108.4583, 'name' => 'Đà Lạt'],
        'phan thiết'  => ['lat' => 10.9805, 'lon' => 108.2615, 'name' => 'Phan Thiết'],
        'mũi né'      => ['lat' => 10.9333, 'lon' => 108.2833, 'name' => 'Mũi Né'],
        'quy nhơn'    => ['lat' => 13.7820, 'lon' => 109.2197, 'name' => 'Quy Nhơn'],
        'quy nhon'    => ['lat' => 13.7820, 'lon' => 109.2197, 'name' => 'Quy Nhơn'],
        'phú yên'     => ['lat' => 13.0882, 'lon' => 109.0924, 'name' => 'Phú Yên'],
        'phu yen'     => ['lat' => 13.0882, 'lon' => 109.0924, 'name' => 'Phú Yên'],

        // Miền Nam
        'hồ chí minh' => ['lat' => 10.8231, 'lon' => 106.6297, 'name' => 'TP. Hồ Chí Minh'],
        'sài gòn'     => ['lat' => 10.8231, 'lon' => 106.6297, 'name' => 'TP. Hồ Chí Minh'],
        'phú quốc'    => ['lat' => 10.2899, 'lon' => 103.9840, 'name' => 'Phú Quốc'],
        'phu quoc'    => ['lat' => 10.2899, 'lon' => 103.9840, 'name' => 'Phú Quốc'],
        'cần thơ'     => ['lat' => 10.0452, 'lon' => 105.7469, 'name' => 'Cần Thơ'],
        'can tho'     => ['lat' => 10.0452, 'lon' => 105.7469, 'name' => 'Cần Thơ'],
        'côn đảo'     => ['lat' => 8.6835,  'lon' => 106.6074, 'name' => 'Côn Đảo'],
        'vũng tàu'    => ['lat' => 10.3460, 'lon' => 107.0843, 'name' => 'Vũng Tàu'],
        'tây ninh'    => ['lat' => 11.3101, 'lon' => 106.0984, 'name' => 'Tây Ninh'],
        'an giang'    => ['lat' => 10.3711, 'lon' => 105.4243, 'name' => 'An Giang'],
        'cà mau'      => ['lat' => 9.1769,  'lon' => 105.1524, 'name' => 'Cà Mau'],
        'kiên giang'  => ['lat' => 10.0125, 'lon' => 105.0809, 'name' => 'Kiên Giang'],

        // Quốc tế
        'thái lan'    => ['lat' => 13.7563, 'lon' => 100.5018, 'name' => 'Bangkok (Thái Lan)'],
        'nhật bản'    => ['lat' => 35.6762, 'lon' => 139.6503, 'name' => 'Tokyo (Nhật Bản)'],
        'hàn quốc'    => ['lat' => 37.5665, 'lon' => 126.9780, 'name' => 'Seoul (Hàn Quốc)'],
        'singapore'   => ['lat' => 1.3521,  'lon' => 103.8198, 'name' => 'Singapore'],
        'đài loan'    => ['lat' => 25.0330, 'lon' => 121.5654, 'name' => 'Đài Bắc (Đài Loan)'],
        'trung quốc'  => ['lat' => 39.9042, 'lon' => 116.4074, 'name' => 'Bắc Kinh (Trung Quốc)'],
        'malaysia'    => ['lat' => 3.1390,  'lon' => 101.6869, 'name' => 'Kuala Lumpur (Malaysia)'],
        'indonesia'   => ['lat' => -8.3405, 'lon' => 115.0920, 'name' => 'Bali (Indonesia)'],
        'pháp'        => ['lat' => 48.8566, 'lon' => 2.3522,   'name' => 'Paris (Pháp)'],
        'thụy sĩ'     => ['lat' => 46.8182, 'lon' => 8.2275,   'name' => 'Thụy Sĩ'],
        'ý'           => ['lat' => 41.9028, 'lon' => 12.4964,  'name' => 'Rome (Ý)'],
        'đức'         => ['lat' => 52.5200, 'lon' => 13.4050,  'name' => 'Berlin (Đức)'],
        'anh'         => ['lat' => 51.5074, 'lon' => -0.1278,  'name' => 'London (Anh)'],
        'hoa kỳ'      => ['lat' => 40.7128, 'lon' => -74.0060, 'name' => 'New York (Hoa Kỳ)'],
        'australia'   => ['lat' => -33.8688,'lon' => 151.2093, 'name' => 'Sydney (Australia)'],
    ];

    /**
     * Dịch mã thời tiết WMO của Open-Meteo sang tiếng Việt và biểu tượng
     */
    private static function interpretWmoCode(int $code): array
    {
        return match (true) {
            $code === 0 => [
                'status' => 'Trời quang đãng, nắng đẹp',
                'icon'   => '☀️',
                'badge'  => 'Nắng ráo',
                'type'   => 'sunny'
            ],
            in_array($code, [1, 2], true) => [
                'status' => 'Trời ít mây, nắng nhẹ',
                'icon'   => '🌤️',
                'badge'  => 'Nắng nhẹ',
                'type'   => 'fair'
            ],
            $code === 3 => [
                'status' => 'Trời nhiều mây, râm mát',
                'icon'   => '⛅',
                'badge'  => 'Nhiều mây',
                'type'   => 'cloudy'
            ],
            in_array($code, [45, 48], true) => [
                'status' => 'Có sương mù, tầm nhìn hạn chế',
                'icon'   => '🌫️',
                'badge'  => 'Sương mù',
                'type'   => 'fog'
            ],
            in_array($code, [51, 53, 55], true) => [
                'status' => 'Mưa phùn nhẹ rải rác',
                'icon'   => '🌦️',
                'badge'  => 'Mưa phùn',
                'type'   => 'drizzle'
            ],
            in_array($code, [61, 63], true) => [
                'status' => 'Có mưa rào ngắt quãng',
                'icon'   => '🌧️',
                'badge'  => 'Mưa rào',
                'type'   => 'rain'
            ],
            $code === 65 => [
                'status' => 'Mưa lớn, cần mang ô dù',
                'icon'   => '🌧️',
                'badge'  => 'Mưa to',
                'type'   => 'heavy-rain'
            ],
            in_array($code, [71, 73, 75], true) => [
                'status' => 'Có tuyết hoặc sương muối giá lạnh',
                'icon'   => '❄️',
                'badge'  => 'Tuyết / Giá lạnh',
                'type'   => 'snow'
            ],
            in_array($code, [80, 81, 82], true) => [
                'status' => 'Mưa rào thoáng qua rồi tạnh',
                'icon'   => '🌦️',
                'badge'  => 'Mưa rào nhẹ',
                'type'   => 'showers'
            ],
            in_array($code, [95, 96, 99], true) => [
                'status' => 'Có dông sét, cần lưu ý an toàn',
                'icon'   => '⛈️',
                'badge'  => 'Dông sét',
                'type'   => 'thunder'
            ],
            default => [
                'status' => 'Tiết trời thuận lợi cho tham quan',
                'icon'   => '🌤️',
                'badge'  => 'Dịu mát',
                'type'   => 'fair'
            ]
        };
    }

    /**
     * Xác định địa danh / điểm đến cụ thể của từng ngày dựa trên tiêu đề lịch trình
     */
    public static function detectDayLocation(string $dayTitle, string $defaultDestination = ''): array
    {
        $normalized = mb_strtolower(trim($dayTitle), 'UTF-8');

        // Tách các phân đoạn tiêu đề theo gạch nối (– hoặc -)
        $parts = preg_split('/\s*[–\-]\s*/u', $normalized);
        if ($parts !== false && count($parts) > 1) {
            $part1 = trim($parts[0]);
            $part2 = trim($parts[1]);

            // Nếu vế 2 là "trở về...", "kết thúc...", "tiễn sân bay..." thì điểm tham quan chính ở vế 1
            if (preg_match('/(trở về|ve lai|kết thúc|ket thuc|tiễn|tam biet)/u', $part2)) {
                $found = self::matchLocationInText($part1);
                if ($found !== null) return $found;
            }

            // Nếu vế 2 có "đến..." hoặc chứa điểm đến mới (vd: "Khởi hành đến Sa Pa", "Hạ Long")
            $found2 = self::matchLocationInText($part2);
            if ($found2 !== null) return $found2;

            $found1 = self::matchLocationInText($part1);
            if ($found1 !== null) return $found1;
        }

        // Tìm kiếm toàn diện trong tiêu đề
        $found = self::matchLocationInText($normalized);
        if ($found !== null) {
            return $found;
        }

        // Fallback: Tìm theo điểm đến chính của tour
        $normalizedDefault = mb_strtolower(trim($defaultDestination), 'UTF-8');
        $foundDefault = self::matchLocationInText($normalizedDefault);
        if ($foundDefault !== null) {
            return $foundDefault;
        }

        return [
            'lat'  => 21.0285,
            'lon'  => 105.8542,
            'name' => $defaultDestination ?: 'Điểm tham quan'
        ];
    }

    /**
     * Khớp từ khóa địa danh với danh sách tọa độ (ưu tiên từ khóa dài hơn trước)
     */
    private static function matchLocationInText(string $text): ?array
    {
        // Sắp xếp các từ khóa theo độ dài giảm dần để ưu tiên 'vịnh hạ long' trước 'hạ long', 'fansipan' trước 'sa pa'
        $sortedKeys = array_keys(self::$coordinates);
        usort($sortedKeys, static fn($a, $b) => mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8'));

        foreach ($sortedKeys as $key) {
            if (mb_strpos($text, $key, 0, 'UTF-8') !== false) {
                return self::$coordinates[$key];
            }
        }
        return null;
    }

    /**
     * Mô hình khí hậu và tư vấn theo điểm đến và tháng (Fallback đáng tin cậy khi >16 ngày hoặc offline)
     */
    private static function getClimateProjection(string $locationName, int $month, int $dayOffset = 0): array
    {
        $loc = mb_strtolower($locationName, 'UTF-8');

        // Sa Pa, Fansipan, Tây Bắc
        if (str_contains($loc, 'sa pa') || str_contains($loc, 'sapa') || str_contains($loc, 'fansipan') || str_contains($loc, 'lào cai')) {
            if (in_array($month, [12, 1, 2], true)) {
                $tempMin = 7 + ($dayOffset % 3);
                $tempMax = 15 + (($dayOffset * 2) % 4);
                return [
                    'temp_min' => $tempMin,
                    'temp_max' => $tempMax,
                    'status'   => 'Trời rét buốt về đêm, trưa hửng nắng nhẹ, sương mù bao phủ',
                    'icon'     => '🌫️',
                    'badge'    => 'Rét ngọt · Sương mờ',
                    'rain'     => 10 + ($dayOffset * 3) % 15,
                    'advice'   => 'Nhiệt độ Sa Pa buổi tối xuống rất thấp (dưới 10°C). Nên mang áo phao ấm, găng tay, khăn len và giày leo núi chống trơn.'
                ];
            }
            if (in_array($month, [3, 4, 5], true)) {
                return [
                    'temp_min' => 14 + ($dayOffset % 3),
                    'temp_max' => 22 + ($dayOffset % 3),
                    'status'   => 'Mùa hoa khoe sắc, tiết trời mát mẻ, khô ráo dễ chịu',
                    'icon'     => '🌸',
                    'badge'    => 'Mát mẻ · Hoa nở',
                    'rain'     => 20,
                    'advice'   => 'Tiết trời mùa xuân lý tưởng để đi bộ ngắm bản làng Cát Cát, Tả Van và chụp ảnh check-in Fansipan.'
                ];
            }
            if (in_array($month, [9, 10, 11], true)) {
                return [
                    'temp_min' => 12 + ($dayOffset % 3),
                    'temp_max' => 20 + ($dayOffset % 3),
                    'status'   => 'Mùa lúa chín vàng và săn mây tuyệt đẹp, trời quang mây tạnh',
                    'icon'     => '🌤️',
                    'badge'    => 'Săn mây · Lúa chín',
                    'rain'     => 15,
                    'advice'   => 'Thời tiết vàng để săn mây tại đèo Ô Quy Hồ và đỉnh Fansipan. Buổi sáng se lạnh, trưa nắng đẹp.'
                ];
            }
            return [
                'temp_min' => 18,
                'temp_max' => 25,
                'status'   => 'Mùa hè tránh nóng, ngày nắng dịu, chiều tối có thể có mưa rào nhẹ',
                'icon'     => '⛅',
                'badge'    => 'Tránh nóng · Dịu mát',
                'rain'     => 40,
                'advice'   => 'Khí hậu mát mẻ như máy điều hòa tự nhiên, nên đem ô che nắng và áo khoác mỏng.'
            ];
        }

        // Hạ Long, Quảng Ninh
        if (str_contains($loc, 'hạ long') || str_contains($loc, 'quảng ninh')) {
            if (in_array($month, [11, 12, 1, 2], true)) {
                return [
                    'temp_min' => 15 + ($dayOffset % 3),
                    'temp_max' => 22 + ($dayOffset % 3),
                    'status'   => 'Biển êm sóng lặng, trời se lạnh khô ráo, nắng hanh vàng',
                    'icon'     => '⛵',
                    'badge'    => 'Biển êm · Se lạnh',
                    'rain'     => 10,
                    'advice'   => 'Thời điểm tuyệt đẹp cho tour du thuyền ngủ đêm trên vịnh; biển êm, không lo say sóng. Chuẩn bị áo khoác cản gió.'
                ];
            }
            if (in_array($month, [5, 6, 7, 8], true)) {
                return [
                    'temp_min' => 26,
                    'temp_max' => 33,
                    'status'   => 'Nắng rực rỡ, nước biển xanh biếc, rất thích hợp chèo kayak và tắm biển',
                    'icon'     => '☀️',
                    'badge'    => 'Nắng biển · Kayak',
                    'rain'     => 25,
                    'advice'   => 'Rất lý tưởng tắm biển Bãi Cháy và chèo thuyền Kayak xuyên hang Luồn. Đừng quên kem chống nắng và mũ rộng vành.'
                ];
            }
            return [
                'temp_min' => 20,
                'temp_max' => 27,
                'status'   => 'Trời trong xanh, tầm nhìn thoáng đãng chiêm ngưỡng trọn vẹn vịnh kỳ quan',
                'icon'     => '🌤️',
                'badge'    => 'Thoáng đãng · Đẹp',
                'rain'     => 15,
                'advice'   => 'Thời tiết hoàn hảo để chụp ảnh cảnh quan karst đá vôi trên vịnh.'
            ];
        }

        // Hà Nội & Đồng bằng Bắc Bộ (Ninh Bình, v.v.)
        if (str_contains($loc, 'hà nội') || str_contains($loc, 'ninh bình') || str_contains($loc, 'tràng an')) {
            if (in_array($month, [11, 12, 1, 2], true)) {
                return [
                    'temp_min' => 16 + ($dayOffset % 2),
                    'temp_max' => 23 + ($dayOffset % 3),
                    'status'   => 'Tiết trời mùa đông se lạnh đặc trưng, ít mưa, phố xá khô ráo',
                    'icon'     => '🍂',
                    'badge'    => 'Đông Hà Nội · Se lạnh',
                    'rain'     => 15,
                    'advice'   => 'Rất thuận lợi đi dạo phố cổ, thưởng thức ẩm thực nóng hổi và ngồi thuyền vãn cảnh Tràng An.'
                ];
            }
            if (in_array($month, [8, 9, 10], true)) {
                return [
                    'temp_min' => 22,
                    'temp_max' => 29,
                    'status'   => 'Mùa thu Hà Nội nồng nàn hoa sữa, nắng vàng như rót mật, gió heo may',
                    'icon'     => '🍁',
                    'badge'    => 'Thu vàng · Tuyệt đẹp',
                    'rain'     => 10,
                    'advice'   => 'Mùa đẹp nhất trong năm của thủ đô! Trời trong xanh, không khí mát lành dễ chịu.'
                ];
            }
            return [
                'temp_min' => 25,
                'temp_max' => 33,
                'status'   => 'Ngày có nắng, độ ẩm trung bình, buổi chiều dịu mát',
                'icon'     => '🌤️',
                'badge'    => 'Nắng ấm',
                'rain'     => 30,
                'advice'   => 'Nên uống đủ nước và chuẩn bị trang phục gọn nhẹ khi tham quan các di tích.'
            ];
        }

        // Đà Nẵng, Huế, Hội An
        if (str_contains($loc, 'đà nẵng') || str_contains($loc, 'hội an') || str_contains($loc, 'huế')) {
            if (in_array($month, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                return [
                    'temp_min' => 23 + ($dayOffset % 3),
                    'temp_max' => 31 + ($dayOffset % 3),
                    'status'   => 'Nắng vàng biển xanh, khô ráo, biển êm thuận lợi vui chơi',
                    'icon'     => '🏖️',
                    'badge'    => 'Nắng biển · Khô ráo',
                    'rain'     => 15,
                    'advice'   => 'Thời tiết tuyệt vời khám phá Bà Nà Hills, dạo phố cổ Hội An về đêm và tắm biển Mỹ Khê.'
                ];
            }
            return [
                'temp_min' => 21,
                'temp_max' => 26,
                'status'   => 'Tiết trời mát mẻ, thỉnh thoảng có mưa thu lãng mạn xứ Huế',
                'icon'     => '🌦️',
                'badge'    => 'Mát mẻ · Lãng mạn',
                'rain'     => 45,
                'advice'   => 'Nên mang theo ô (dù) xếp nhỏ hoặc áo mưa tiện lợi khi tham quan ngoài trời.'
            ];
        }

        // Phú Quốc, Miền Nam, Miền Tây
        if (str_contains($loc, 'phú quốc') || str_contains($loc, 'hồ chí minh') || str_contains($loc, 'cần thơ')) {
            if (in_array($month, [11, 12, 1, 2, 3, 4], true)) {
                return [
                    'temp_min' => 25 + ($dayOffset % 2),
                    'temp_max' => 32 + ($dayOffset % 2),
                    'status'   => 'Mùa khô rực rỡ, biển lặng như gương, nước trong vắt thấy đáy',
                    'icon'     => '☀️',
                    'badge'    => 'Mùa khô · Biển đẹp',
                    'rain'     => 5,
                    'advice'   => 'Thời điểm vàng của Phú Quốc! Rất tuyệt vời lặn ngắm san hô tại hòn Móng Tay, Gầm Ghì.'
                ];
            }
            return [
                'temp_min' => 25,
                'temp_max' => 31,
                'status'   => 'Ngày nắng chan hòa, có thể có mưa rào chợt đến chợt đi vào chiều muộn',
                'icon'     => '🌤️',
                'badge'    => 'Nắng ấm',
                'rain'     => 35,
                'advice'   => 'Mưa Nam Bộ tan rất nhanh, không ảnh hưởng nhiều đến kế hoạch tham quan.'
            ];
        }

        // Đà Lạt
        if (str_contains($loc, 'đà lạt')) {
            return [
                'temp_min' => 13 + ($dayOffset % 2),
                'temp_max' => 22 + ($dayOffset % 3),
                'status'   => 'Một ngày bốn mùa, sáng sương mù, trưa nắng ấm, chiều tối se lạnh',
                'icon'     => '🌲',
                'badge'    => 'Se lạnh · 4 Mùa',
                'rain'     => 20,
                'advice'   => 'Thời tiết lãng mạn thích hợp cà phê săn mây, ngắm đồi thông. Cần mang áo ấm nhẹ khi ra đường tối.'
            ];
        }

        // Mặc định chung
        return [
            'temp_min' => 20 + ($dayOffset % 3),
            'temp_max' => 28 + ($dayOffset % 3),
            'status'   => 'Thời tiết thuận lợi cho các hoạt động tham quan và chụp ảnh',
            'icon'     => '🌤️',
            'badge'    => 'Thuận lợi',
            'rain'     => 20,
            'advice'   => 'Trang phục thoải mái, mang giày thể thao hoặc sandal để tiện di chuyển theo đoàn.'
        ];
    }

    /**
     * Gọi Open-Meteo API để lấy dự báo thời tiết thực tế
     * @return array<string, array{temp_min: float, temp_max: float, code: int, rain_chance: int}>|null
     */
    private static function fetchLiveForecast(float $lat, float $lon): ?array
    {
        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%.4f&longitude=%.4f&daily=weathercode,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Asia%%2FBangkok',
            $lat,
            $lon
        );

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 2.5, // 2.5 giây timeout để không làm trễ tải trang
                'header'  => "User-Agent: OnTripWeatherClient/1.0\r\n"
            ]
        ]);

        try {
            $raw = @file_get_contents($url, false, $context);
            if (!$raw) {
                return null;
            }
            $data = json_decode($raw, true);
            if (!isset($data['daily']['time'])) {
                return null;
            }

            $dailyMap = [];
            foreach ($data['daily']['time'] as $i => $dateStr) {
                $dailyMap[$dateStr] = [
                    'temp_min'    => (float) ($data['daily']['temperature_2m_min'][$i] ?? 20),
                    'temp_max'    => (float) ($data['daily']['temperature_2m_max'][$i] ?? 28),
                    'code'        => (int) ($data['daily']['weathercode'][$i] ?? 1),
                    'rain_chance' => (int) ($data['daily']['precipitation_probability_max'][$i] ?? 20),
                ];
            }
            return $dailyMap;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Chuyển thứ trong tuần sang định dạng tiếng Việt
     */
    public static function formatVietnameseDate(string $ymd): string
    {
        $timestamp = strtotime($ymd);
        $daysOfWeek = [
            'Sunday'    => 'Chủ Nhật',
            'Monday'    => 'Thứ Hai',
            'Tuesday'   => 'Thứ Ba',
            'Wednesday' => 'Thứ Tư',
            'Thursday'  => 'Thứ Năm',
            'Friday'    => 'Thứ Sáu',
            'Saturday'  => 'Thứ Bảy',
        ];
        $englishDay = date('l', $timestamp);
        $vnDay = $daysOfWeek[$englishDay] ?? 'Thứ';
        return $vnDay . ', ' . date('d/m/Y', $timestamp);
    }

    /**
     * Tính toán dự báo thời tiết cho từng ngày trong lịch trình tour
     * 
     * @param array<int, array{day: int, title: string, content: string}> $itinerary
     * @param string $startDate Ngày bắt đầu định dạng YYYY-MM-DD
     * @param string $defaultDestination Tên điểm đến chính của tour
     * @return array<int, array<string, mixed>> Danh sách dự báo thời tiết từng ngày
     */
    public static function getItineraryWeather(array $itinerary, string $startDate, string $defaultDestination = ''): array
    {
        if (empty($startDate) || !strtotime($startDate)) {
            $startDate = date('Y-m-d');
        }

        $startTimestamp = strtotime($startDate);
        $todayTimestamp = strtotime(date('Y-m-d'));
        $daysDiff = (int) round(($startTimestamp - $todayTimestamp) / 86400);

        // Cache các kết quả API theo tọa độ trong một lượt gọi để tối ưu tốc độ
        $liveCache = [];

        $result = [];
        foreach ($itinerary as $index => $day) {
            $dayNum = (int) ($day['day'] ?? ($index + 1));
            $dayOffset = $dayNum - 1;
            $currentTimestamp = strtotime("+$dayOffset days", $startTimestamp);
            $currentDate = date('Y-m-d', $currentTimestamp);
            $currentMonth = (int) date('n', $currentTimestamp);

            // Xác định vị trí của ngày này
            $locationInfo = self::detectDayLocation($day['title'] ?? '', $defaultDestination);
            $locationName = $locationInfo['name'];
            $lat = $locationInfo['lat'];
            $lon = $locationInfo['lon'];

            $isLive = false;
            $dayWeather = null;

            // Nếu ngày nằm trong khoảng từ hôm nay đến 14 ngày tới thì thử gọi Live Open-Meteo API
            $targetDiff = (int) round(($currentTimestamp - $todayTimestamp) / 86400);
            if ($targetDiff >= 0 && $targetDiff <= 14) {
                $coordKey = round($lat, 2) . '_' . round($lon, 2);
                if (!isset($liveCache[$coordKey])) {
                    $liveCache[$coordKey] = self::fetchLiveForecast($lat, $lon);
                }

                if (isset($liveCache[$coordKey][$currentDate])) {
                    $apiData = $liveCache[$coordKey][$currentDate];
                    $wmo = self::interpretWmoCode($apiData['code']);
                    $isLive = true;
                    $dayWeather = [
                        'temp_min' => (int) round($apiData['temp_min']),
                        'temp_max' => (int) round($apiData['temp_max']),
                        'status'   => $wmo['status'],
                        'icon'     => $wmo['icon'],
                        'badge'    => $wmo['badge'],
                        'rain'     => $apiData['rain_chance'],
                        'advice'   => self::generateLiveAdvice($wmo['type'], $locationName, (int) round($apiData['temp_min']), (int) round($apiData['temp_max'])),
                    ];
                }
            }

            // Nếu không có live API hoặc ngày xa trong tương lai (>16 ngày), sử dụng Mô hình Khí hậu chính xác
            if ($dayWeather === null) {
                $climate = self::getClimateProjection($locationName, $currentMonth, $dayOffset);
                $dayWeather = [
                    'temp_min' => $climate['temp_min'],
                    'temp_max' => $climate['temp_max'],
                    'status'   => $climate['status'],
                    'icon'     => $climate['icon'],
                    'badge'    => $climate['badge'],
                    'rain'     => $climate['rain'],
                    'advice'   => $climate['advice'],
                ];
            }

            $result[] = [
                'day'             => $dayNum,
                'date'            => $currentDate,
                'date_formatted'  => self::formatVietnameseDate($currentDate),
                'short_date'      => date('d/m', $currentTimestamp),
                'location'        => $locationName,
                'temp_min'        => $dayWeather['temp_min'],
                'temp_max'        => $dayWeather['temp_max'],
                'temp_range'      => "{$dayWeather['temp_min']}°C – {$dayWeather['temp_max']}°C",
                'status'          => $dayWeather['status'],
                'icon'            => $dayWeather['icon'],
                'badge'           => $dayWeather['badge'],
                'rain'            => $dayWeather['rain'],
                'advice'          => $dayWeather['advice'],
                'is_live'         => $isLive,
                'source_label'    => $isLive ? 'Dự báo vệ tinh Open-Meteo' : 'Khí hậu dự báo theo mùa',
            ];
        }

        return $result;
    }

    /**
     * Sinh lời khuyên du lịch dựa trên thời tiết thực tế
     */
    private static function generateLiveAdvice(string $type, string $location, int $tempMin, int $tempMax): string
    {
        if ($tempMin <= 12) {
            return "Nhiệt độ $location thấp nhất {$tempMin}°C, trời rét. Quý khách nhớ mang áo ấm dày, găng tay và khăn quàng cổ.";
        }
        if (in_array($type, ['rain', 'heavy-rain', 'thunder'], true)) {
            return "Dự báo có mưa tại $location ({$tempMin}°C - {$tempMax}°C). Quý khách chuẩn bị ô (dù), áo mưa nhẹ và bọc chống nước điện thoại.";
        }
        if ($type === 'fog') {
            return "Buổi sáng tại $location có sương mù, tầm nhìn giảm. Khi di chuyển đèo dốc cần chú ý quan sát và giữ ấm cơ thể.";
        }
        if ($tempMax >= 34) {
            return "Trời nắng gắt trưa chiều ({$tempMax}°C). Quý khách chuẩn bị kem chống nắng, mũ nón rộng vành và uống đủ nước.";
        }
        return "Thời tiết tại $location rất thuận lợi ({$tempMin}°C - {$tempMax}°C), khô ráo, tuyệt vời cho các hoạt động tham quan ngoài trời.";
    }
}

class_alias(ThoiTietModel::class, "ThoiTiet");
