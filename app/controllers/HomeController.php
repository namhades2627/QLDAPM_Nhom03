<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tour.php';

/** Chuẩn bị dữ liệu tour nổi bật cho trang chủ. */
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

        foreach ($featuredTours as &$tour) {
            $image = $tour['image'] ?? '';
            $file = $image !== '' ? realpath($publicRoot . '/' . $image) : false;
            if ($file === false || !is_file($file) || !str_starts_with($file, $imagesRoot)) {
                $tour['image'] = 'assets/images/placeholder.svg';
            }
        }
        unset($tour);

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
}
