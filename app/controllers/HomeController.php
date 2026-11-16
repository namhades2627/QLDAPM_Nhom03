<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tour.php';
require_once __DIR__ . '/../models/thoi_tiet.php';

/** Chuẩn bị dữ liệu tour nổi bật, tour theo mùa và tour khuyến mãi cho trang chủ. */
class HomeController
{
    public function index(): void
    {
        $page = 'home';
        $model = new TourModel(getDatabaseConnection());
        $featuredTours = $model->getFeaturedTours();
        $travelNeeds = $model->getNeeds();
        $availableDestinations = $model->getAvailableDestinations();
        $destinationGroups = ['domestic' => [], 'international' => []];
        $publicRoot = realpath(__DIR__ . '/../../public');
        $imagesRoot = realpath($publicRoot . '/assets/images') . DIRECTORY_SEPARATOR;

        // Chuẩn hóa đường dẫn ảnh của tour
        $sanitizeImage = function (array $tour) use ($publicRoot, $imagesRoot): array {
            $image = $tour['image'] ?? '';
            $file = $image !== '' ? realpath($publicRoot . '/' . $image) : false;
            if ($file === false || !is_file($file) || !str_starts_with($file, $imagesRoot)) {
                $tour['image'] = 'assets/images/placeholder.svg';
            }
            return $tour;
        };

        foreach ($featuredTours as &$tour) {
            $tour = $sanitizeImage($tour);
        }
        unset($tour);

        // Lấy toàn bộ tour đã công bố để phục vụ phân loại theo mùa vụ và khuyến mãi hôm nay
        $allPublishedTours = array_map($sanitizeImage, array_merge(
            $model->getPublishedTours('domestic'),
            $model->getPublishedTours('international')
        ));

        // 1. TOUR THEO MÙA (Real-time Seasonality)
        // Nhận diện tháng thực tế của hệ thống (ví dụ: tháng 11 - cuối thu, chớm đông miền Bắc; hoặc tháng 10 - mùa thu lúa chín)
        $currentMonth = (int) date('n');
        $seasonInfo = $this->detectSeason($currentMonth);
        $featuredIds = array_column($featuredTours, 'id');
        $seasonalTours = $this->filterSeasonalTours($allPublishedTours, $seasonInfo, $featuredIds);

        // 2. TOUR KHUYẾN MÃI HÔM NAY (Hot Deals / Best Discounts)
        // Loại trừ các tour đã xuất hiện ở tour nổi bật và tour theo mùa để tránh trùng lặp trên trang chủ
        $excludePromoIds = array_merge($featuredIds, array_column($seasonalTours, 'id'));
        $promoTours = $this->buildPromoTours($allPublishedTours, $excludePromoIds);

        // Bộ ảnh khám phá riêng; tên, ID và số tour vẫn lấy từ database.
        $destinationImages = [
            'domestic' => [
                'Đà Nẵng' => 'assets/images/KhamPha_DN.jpg',
                'Hà Giang' => 'assets/images/KhamPha_HG.jpg',
                'Phú Quốc' => 'assets/images/KhamPha_PQ.jpg',
                'Hà Nội' => 'assets/images/KhamPha_HN.jpg',
                'Đà Lạt' => 'assets/images/KhamPha_DL.jpg',
            ],
            'international' => [
                'Nhật Bản' => 'assets/images/KhamPha_JP.jpg',
                'Đức' => 'assets/images/KhamPha_GM.jpg',
                'Hoa Kỳ' => 'assets/images/KhamPha_US.jpg',
                'Australia' => 'assets/images/KhamPha_AU.jpg',
                'Trung Quốc' => 'assets/images/KhamPha_CN.jpg',
            ],
        ];
        $destinationsByName = [];
        foreach ($availableDestinations as $destination) {
            $destinationsByName[$destination['type']][$destination['name']] = $destination;
        }
        foreach ($destinationImages as $type => $images) {
            foreach ($images as $name => $image) {
                // Điểm đến hết tour khả dụng không được hiển thị chỉ vì còn ảnh.
                if (!isset($destinationsByName[$type][$name])) continue;
                $destination = $destinationsByName[$type][$name];
                $destination['image'] = is_file($publicRoot . '/' . $image)
                    ? $image : 'assets/images/placeholder.svg';
                $destinationGroups[$type][] = $destination;
            }
        }

        require __DIR__ . '/../views/home/index.php';
    }

    /**
     * Nhận diện mùa và các điểm đến tiêu biểu trong tháng
     */
    private function detectSeason(int $month): array
    {
        // Mùa thu & lúa chín, hoa tam giác mạch (Tháng 9 - 11)
        if (in_array($month, [9, 10, 11], true)) {
            return [
                'key'         => 'thu',
                'title'       => 'Tour Mùa Thu Vàng & Lễ Hội Hoa',
                'eyebrow'     => 'TIẾT TRỜ VÀNG · MÙA ĐẸP NHẤT TRONG NĂM',
                'subtitle'    => "Đang là tháng $month – thời điểm lý tưởng ngắm lúa chín vàng ươm, hoa tam giác mạch Tây Bắc và tiết trời se lạnh phố cổ.",
                'badge'       => '🍁 Mùa thu vàng',
                'destinations'=> ['Hà Giang', 'Hà Nội', 'Mộc Châu', 'Cao Bằng', 'Sa Pa', 'Yên Bái', 'Ninh Bình', 'Đà Lạt'],
                'preferred_ids'=> ['mb02', 'mb01', 'mb07', 'mb06', 'mb03', 'mt08'],
            ];
        }

        // Mùa đông & săn mây tuyết rơi (Tháng 12 - 2)
        if (in_array($month, [12, 1, 2], true)) {
            return [
                'key'         => 'dong',
                'title'       => 'Tour Mùa Đông Săn Mây & Nghỉ Dưỡng Ấm Áp',
                'eyebrow'     => 'CHẠM MÂY BỒNG BỀNH · TRẢI NGHIỆM ĐẶC BIỆT',
                'subtitle'    => "Tháng $month với cái lạnh ngọt ngào tại Fansipan, sương sớm Sa Pa hoặc tránh rét tại các thiên đường biển miền Nam.",
                'badge'       => '❄️ Mùa đông săn mây',
                'destinations'=> ['Sa Pa', 'Hà Nội', 'Đà Lạt', 'Phú Quốc', 'Côn Đảo', 'Hạ Long'],
                'preferred_ids'=> ['mb01', 'mb03', 'mt08', 'mn01', 'mb04'],
            ];
        }

        // Mùa hè biển đảo (Tháng 5 - 8)
        if (in_array($month, [5, 6, 7, 8], true)) {
            return [
                'key'         => 'ha',
                'title'       => 'Tour Mùa Hè Rực Rỡ & Thiên Đường Biển Đảo',
                'eyebrow'     => 'BIỂN XANH CÁT TRẮNG · NẮNG VÀNG',
                'subtitle'    => "Tháng $month sóng êm biển lặng, thời điểm vàng để chèo kayak, lặn ngắm san hô và nạp lại năng lượng bên bờ biển.",
                'badge'       => '🏖️ Biển đảo hè',
                'destinations'=> ['Phú Quốc', 'Đà Nẵng', 'Nha Trang', 'Hạ Long', 'Quy Nhơn', 'Phan Thiết'],
                'preferred_ids'=> ['mn01', 'mt01', 'mt07', 'mb04', 'mt04', 'mt06'],
            ];
        }

        // Mùa xuân (Tháng 3 - 4)
        return [
            'key'         => 'xuan',
            'title'       => 'Tour Du Xuân Trẩy Hội & Muôn Hoa Đua Sắc',
            'eyebrow'     => 'XUÂN TƯƠI MÁT · KHỞI ĐẦU BÌNH AN',
            'subtitle'    => "Tiết xuân tháng $month mát dịu, muôn hoa khoe sắc, lý tưởng cho những chuyến hành hương chiêm bái và du xuân gia đình.",
            'badge'       => '🌸 Du xuân đầu năm',
            'destinations'=> ['Ninh Bình', 'Đà Nẵng', 'Huế', 'Hội An', 'Đà Lạt', 'Hà Nội'],
            'preferred_ids'=> ['mb05', 'mt01', 'mt02', 'mt03', 'mt08'],
        ];
    }

    /**
     * Lọc và sắp xếp 4 tour hợp mùa nhất, ưu tiên loại trừ các tour đã hiển thị ở phần trước
     */
    private function filterSeasonalTours(array $allTours, array $seasonInfo, array $excludeIds = []): array
    {
        $seasonCode = $seasonInfo['key'] ?? 'thu';
        $filtered = [];
        $fallback = [];
        foreach ($allTours as $tour) {
            $score = 0;
            // Ưu tiên đọc trực tiếp mùa vụ từ CSDL
            if (isset($tour['season']) && $tour['season'] === $seasonCode) {
                $score += 100;
            }
            if (in_array($tour['id'], $seasonInfo['preferred_ids'], true)) $score += 50;
            if (in_array($tour['destination'], $seasonInfo['destinations'], true)) $score += 30;
            if ($score > 0) {
                $tour['season_score'] = $score;
                $tour['season_badge'] = $seasonInfo['badge'];
                if (in_array($tour['id'], $excludeIds, true)) {
                    $fallback[] = $tour;
                } else {
                    $filtered[] = $tour;
                }
            }
        }
        usort($filtered, static fn($a, $b) => ($b['season_score'] ?? 0) <=> ($a['season_score'] ?? 0));
        usort($fallback, static fn($a, $b) => ($b['season_score'] ?? 0) <=> ($a['season_score'] ?? 0));

        // Nếu danh sách không trùng lặp chưa đủ 4 tour thì bổ sung từ fallback
        if (count($filtered) < 4) {
            foreach ($fallback as $fb) {
                $filtered[] = $fb;
                if (count($filtered) >= 4) break;
            }
        }

        return array_slice($filtered, 0, 4);
    }

    /**
     * Tuyển chọn 4 tour khuyến mãi đặc biệt trong ngày với giá ưu đãi và phần trăm giảm,
     * ưu tiên các tour chưa xuất hiện ở các khối phía trên
     */
    private function buildPromoTours(array $allTours, array $excludeIds = []): array
    {
        $promos = [];
        $fallback = [];
        foreach ($allTours as $tour) {
            $discountPercent = (int) ($tour['discount_percent'] ?? 0);
            if ($discountPercent > 0 && $tour['price'] !== null) {
                $originalPrice = (int) round($tour['price'] / (1 - ($discountPercent / 100)));
                $tour['promo'] = [
                    'percent'   => $discountPercent,
                    'tag'       => !empty($tour['promo_tag']) ? $tour['promo_tag'] : "Giảm −{$discountPercent}%",
                    'old_price' => $originalPrice,
                ];
                if (in_array($tour['id'], $excludeIds, true)) {
                    $fallback[] = $tour;
                } else {
                    $promos[] = $tour;
                }
            }
        }
        // Ưu tiên tour có phần trăm giảm giá cao nhất lên trước
        usort($promos, static fn($a, $b) => ($b['promo']['percent'] ?? 0) <=> ($a['promo']['percent'] ?? 0));
        usort($fallback, static fn($a, $b) => ($b['promo']['percent'] ?? 0) <=> ($a['promo']['percent'] ?? 0));

        // Nếu chưa đủ 4 tour thì bổ sung từ fallback (các tour giảm giá đã ở mục khác)
        if (count($promos) < 4) {
            foreach ($fallback as $fb) {
                $promos[] = $fb;
                if (count($promos) >= 4) break;
            }
        }

        // Nếu vẫn chưa đủ 4 tour thì bổ sung các tour có giá thấp nhất chưa nằm trong promos
        if (count($promos) < 4) {
            $sortedByPrice = $allTours;
            usort($sortedByPrice, static fn($a, $b) => ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX));
            foreach ($sortedByPrice as $t) {
                if ($t['price'] === null) continue;
                if (!in_array($t['id'], array_column($promos, 'id'), true)) {
                    $t['promo'] = [
                        'percent'   => 15,
                        'tag'       => 'Giá tốt hôm nay −15%',
                        'old_price' => (int) round($t['price'] * 1.18)
                    ];
                    $promos[] = $t;
                    if (count($promos) >= 4) break;
                }
            }
        }

        return array_slice($promos, 0, 4);
    }
}
