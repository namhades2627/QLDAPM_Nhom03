<?php
$catalogDestinations = [
    'domestic' => [
        [
            'name' => 'Đà Nẵng', 'region' => 'central', 'image' => 'assets/images/BanaHills.jpg', 'base_price' => 5990000,
            'routes' => ['Đà Nẵng – Bà Nà Hills – Phố cổ Hội An', 'Đà Nẵng – Sơn Trà – Ngũ Hành Sơn', 'Đà Nẵng – Huế – Vịnh Lăng Cô'],
            'needs' => ['culture', 'entertainment', 'food'],
        ],
        [
            'name' => 'Hà Giang', 'region' => 'north', 'image' => 'assets/images/SongNhoQue.jpg', 'base_price' => 4590000,
            'routes' => ['Hà Giang – Đồng Văn – Sông Nho Quế', 'Hà Giang – Hoàng Su Phì – Xín Mần', 'Hà Giang – Lũng Cú – Mã Pí Lèng'],
            'needs' => ['nature', 'adventure', 'culture'],
        ],
        [
            'name' => 'Phú Quốc', 'region' => 'south', 'image' => 'assets/images/KhamPha_PQ.jpg', 'base_price' => 6290000,
            'routes' => ['Phú Quốc – Nam đảo – Hoàng hôn Địa Trung Hải', 'Phú Quốc – Bắc đảo – VinWonders', 'Phú Quốc – Hòn Thơm – Khám phá ẩm thực'],
            'needs' => ['relaxation', 'entertainment', 'food'],
        ],
        [
            'name' => 'Hà Nội', 'region' => 'north', 'image' => 'assets/images/KhamPha_HN.jpg', 'base_price' => 6790000,
            'routes' => ['Hà Nội – Phố cổ – Văn Miếu', 'Hà Nội – Bát Tràng – Làng cổ Đường Lâm', 'Hà Nội – Hạ Long – Yên Tử'],
            'needs' => ['culture', 'food'],
        ],
        [
            'name' => 'Ninh Bình', 'region' => 'north', 'image' => 'assets/images/placeholder.svg', 'base_price' => 3990000,
            'routes' => ['Ninh Bình – Tràng An – Bái Đính', 'Ninh Bình – Tam Cốc – Hang Múa'],
            'needs' => ['nature', 'culture', 'adventure'],
        ],
        [
            'name' => 'Huế', 'region' => 'central', 'image' => 'assets/images/placeholder.svg', 'base_price' => 4990000,
            'routes' => ['Huế – Đại Nội – Lăng Minh Mạng', 'Huế – Phá Tam Giang – Chùa Thiên Mụ', 'Huế – Bạch Mã – Biển Lăng Cô'],
            'needs' => ['culture', 'food', 'nature'],
        ],
        [
            'name' => 'Đà Lạt', 'region' => 'central', 'image' => 'assets/images/KhamPha_DL.jpg', 'base_price' => 3290000,
            'routes' => ['Đà Lạt – Langbiang – Hồ Tuyền Lâm', 'Đà Lạt – Cầu Đất – Săn mây'],
            'needs' => ['relaxation', 'nature', 'adventure'],
        ],
        [
            'name' => 'Cần Thơ', 'region' => 'south', 'image' => 'assets/images/placeholder.svg', 'base_price' => 2490000,
            'routes' => ['Cần Thơ – Chợ nổi Cái Răng – Cồn Sơn', 'Cần Thơ – Sóc Trăng – Bạc Liêu', 'Cần Thơ – Châu Đốc – Rừng tràm Trà Sư'],
            'needs' => ['culture', 'food', 'nature'],
        ],
        [
            'name' => 'Côn Đảo', 'region' => 'south', 'image' => 'assets/images/placeholder.svg', 'base_price' => 7490000,
            'routes' => ['Côn Đảo – Dấu ấn lịch sử – Biển Đầm Trầu', 'Côn Đảo – Vịnh Côn Sơn – Hòn Bảy Cạnh'],
            'needs' => ['relaxation', 'nature', 'culture'],
        ],
    ],
    'international' => [
        [
            'name' => 'Nhật Bản', 'region' => 'asia', 'image' => 'assets/images/Kiyomizudera.jpg', 'base_price' => 29990000,
            'routes' => ['Nhật Bản – Osaka – Kyoto – Núi Phú Sĩ', 'Nhật Bản – Tokyo – Nikko – Yokohama', 'Nhật Bản – Hokkaido – Sapporo – Otaru'],
            'needs' => ['culture', 'food', 'nature'],
        ],
        [
            'name' => 'Thụy Sĩ', 'region' => 'europe', 'image' => 'assets/images/Valais.jpg', 'base_price' => 89990000,
            'routes' => ['Thụy Sĩ – Valais – Đường sắt Gornergrat', 'Thụy Sĩ – Zurich – Lucerne – Interlaken', 'Thụy Sĩ – Geneva – Montreux – Zermatt'],
            'needs' => ['nature', 'relaxation', 'adventure'],
        ],
        [
            'name' => 'Australia', 'region' => 'americas-oceania', 'image' => 'assets/images/KhamPha_AU.jpg', 'base_price' => 54990000,
            'routes' => ['Australia – Sydney – Blue Mountains', 'Australia – Melbourne – Great Ocean Road', 'Australia – Brisbane – Gold Coast'],
            'needs' => ['nature', 'entertainment', 'culture'],
        ],
        [
            'name' => 'Thái Lan', 'region' => 'asia', 'image' => 'assets/images/placeholder.svg', 'base_price' => 7990000,
            'routes' => ['Thái Lan – Bangkok – Pattaya', 'Thái Lan – Chiang Mai – Chiang Rai', 'Thái Lan – Phuket – Vịnh Phang Nga'],
            'needs' => ['culture', 'food', 'relaxation'],
        ],
        [
            'name' => 'Trung Quốc', 'region' => 'asia', 'image' => 'assets/images/KhamPha_CN.jpg', 'base_price' => 18990000,
            'routes' => ['Trung Quốc – Bắc Kinh – Vạn Lý Trường Thành', 'Trung Quốc – Thượng Hải – Hàng Châu'],
            'needs' => ['culture', 'nature', 'food'],
        ],
        [
            'name' => 'Pháp', 'region' => 'europe', 'image' => 'assets/images/placeholder.svg', 'base_price' => 69990000,
            'routes' => ['Pháp – Paris – Versailles – Thung lũng Loire', 'Pháp – Provence – Avignon – Nice', 'Pháp – Strasbourg – Colmar – Alsace'],
            'needs' => ['culture', 'food', 'relaxation'],
        ],
        [
            'name' => 'Đức', 'region' => 'europe', 'image' => 'assets/images/KhamPha_GM.jpg', 'base_price' => 64990000,
            'routes' => ['Đức – Munich – Lâu đài Neuschwanstein', 'Đức – Berlin – Dresden – Potsdam'],
            'needs' => ['culture', 'nature', 'food'],
        ],
        [
            'name' => 'Mỹ', 'region' => 'americas-oceania', 'image' => 'assets/images/KhamPha_US.jpg', 'base_price' => 79990000,
            'routes' => ['Mỹ – Los Angeles – Las Vegas – Grand Canyon', 'Mỹ – New York – Washington D.C.', 'Mỹ – San Francisco – Yosemite'],
            'needs' => ['entertainment', 'nature', 'culture'],
        ],
        [
            'name' => 'Canada', 'region' => 'americas-oceania', 'image' => 'assets/images/placeholder.svg', 'base_price' => 85990000,
            'routes' => ['Canada – Vancouver – Victoria', 'Canada – Toronto – Niagara – Ottawa'],
            'needs' => ['nature', 'relaxation', 'adventure'],
        ],
    ],
];

$catalogNeeds = [
    'relaxation' => 'Nghỉ dưỡng',
    'nature' => 'Khám phá thiên nhiên',
    'culture' => 'Văn hóa – lịch sử',
    'food' => 'Ẩm thực',
    'entertainment' => 'Vui chơi – giải trí',
    'adventure' => 'Vận động – trải nghiệm',
];

// Chuyển từng hành trình thành một thẻ tour có mã, giá, thời lượng và lịch mẫu.
$catalogTours = [];
foreach ($catalogDestinations as $type => $destinations) {
    foreach ($destinations as $destinationIndex => $destination) {
        foreach ($destination['routes'] as $routeIndex => $name) {
            $days = ($type === 'domestic' ? 3 : 5) + $routeIndex;
            $catalogTours[$type][] = [
                'id' => $type . '-' . ($destinationIndex * 3 + $routeIndex + 1),
                'name' => $name,
                'destination' => $destination['name'],
                'region' => $destination['region'],
                'image' => $destination['image'],
                'price' => $destination['base_price'] + $routeIndex * ($type === 'domestic' ? 500000 : 3000000),
                'days' => $days,
                'duration' => $days . ' ngày ' . ($days - 1) . ' đêm',
                'origin' => $routeIndex === 1 ? 'Hà Nội' : 'TP. Hồ Chí Minh',
                'needs' => $destination['needs'],
                'dates' => ['2026-11-07', '2026-11-14', '2026-11-21', '2026-12-05'],
            ];
        }
    }
}
