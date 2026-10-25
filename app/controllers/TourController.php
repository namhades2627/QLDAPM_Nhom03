<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tour.php';

/** Nhận tham số URL, gọi Model và truyền dữ liệu vào View. */
class TourController
{
    private TourModel $model;

    public function __construct()
    {
        $this->model = new TourModel(getDatabaseConnection());
    }

    private function queryString(string $name): string
    {
        return is_string($_GET[$name] ?? null) ? trim($_GET[$name]) : '';
    }

    /** Ảnh chưa được bổ sung sẽ dùng placeholder, không hiện ảnh lỗi. */
    private function prepareImage(array $tour): array
    {
        $publicRoot = realpath(__DIR__ . '/../../public');
        $image = $tour['image'] ?? '';
        $file = $image !== '' ? realpath($publicRoot . '/' . $image) : false;
        $assetsRoot = realpath($publicRoot . '/assets/images') . DIRECTORY_SEPARATOR;
        if ($file === false || !is_file($file) || !str_starts_with($file, $assetsRoot)) {
            $tour['image'] = 'assets/images/placeholder.svg';
        }
        return $tour;
    }

    public function index(): void
    {
        $page = 'tours';
        $tourType = $this->queryString('type') === 'international' ? 'international' : 'domestic';
        $typeTitle = $tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế';
        $domesticRegions = $this->model->getRegions('domestic');
        $internationalRegions = $this->model->getRegions('international');
        $regionNames = $tourType === 'domestic' ? $domesticRegions : $internationalRegions;
        $requestedRegion = $this->queryString('region');
        $tourRegion = isset($regionNames[$requestedRegion]) ? $requestedRegion : '';
        $keyword = $this->queryString('q');
        $catalogNeeds = $this->model->getNeeds();
        $tours = array_map($this->prepareImage(...), $this->model->getPublishedTours($tourType));
        $destinationRegions = array_merge($domesticRegions, $internationalRegions);
        $destinationRegionTypes = array_merge(
            array_fill_keys(array_keys($domesticRegions), 'domestic'),
            array_fill_keys(array_keys($internationalRegions), 'international')
        );
        if (isset($destinationRegions['americas-oceania'])) {
            $destinationRegions['americas-oceania'] = 'Châu Mỹ – Úc';
        }
        $origins = [];
        foreach ($tours as $tour) {
            foreach ($tour['origins'] as $origin) {
                $origins[$origin] = $origin;
            }
        }
        require __DIR__ . '/../views/tours/index.php';
    }

    public function detail(): void
    {
        $page = 'tour-detail';
        $tourType = $this->queryString('type') === 'international' ? 'international' : 'domestic';
        $typeTitle = $tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế';
        $tourRegion = '';
        $code = $this->queryString('id');
        $tour = $this->model->findPublishedTour($tourType, $code);
        $notFound = $tour === null;
        $relatedTours = [];
        $selectedSchedule = null;
        if ($notFound) {
            http_response_code(404);
        } else {
            $tour = $this->prepareImage($tour);
            $selectedSchedule = $tour['schedules'][0] ?? null;
            $relatedTours = array_values(array_filter(
                $this->model->getPublishedTours($tourType),
                static fn($item) => $item['id'] !== $tour['id']
            ));
            usort($relatedTours, static fn($a, $b) =>
                (int) ($b['region'] === $tour['region']) <=> (int) ($a['region'] === $tour['region'])
            );
            $relatedTours = array_map($this->prepareImage(...), array_slice($relatedTours, 0, 6));
        }
        require __DIR__ . '/../views/tours/detail.php';
    }
}
