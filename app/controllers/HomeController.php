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

        require __DIR__ . '/../views/home/index.php';
    }
}
