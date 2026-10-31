<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tour.php';
require_once __DIR__ . '/../models/thoi_tiet.php';

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

    private function queryInteger(string $name, int $default, int $minimum, int $maximum): int
    {
        $value = filter_var($this->queryString($name), FILTER_VALIDATE_INT);
        return $value !== false && $value >= $minimum && $value <= $maximum ? $value : $default;
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
        $requestedType = $this->queryString('type');
        $tourType = in_array($requestedType, ['domestic', 'international', 'all'], true) ? $requestedType : 'domestic';
        $typeTitle = $tourType === 'all' ? 'Kết quả tìm tour' : ($tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế');
        $domesticRegions = $this->model->getRegions('domestic');
        $internationalRegions = $this->model->getRegions('international');
        $regionNames = $tourType === 'all' ? array_merge($domesticRegions, $internationalRegions)
            : ($tourType === 'domestic' ? $domesticRegions : $internationalRegions);
        $requestedRegion = $this->queryString('region');
        $tourRegion = isset($regionNames[$requestedRegion]) ? $requestedRegion : '';
        $keyword = mb_substr($this->queryString('q'), 0, 255, 'UTF-8');
        $catalogNeeds = $this->model->getNeeds();
        $tours = $tourType === 'all'
            ? array_merge($this->model->getPublishedTours('domestic'), $this->model->getPublishedTours('international'))
            : $this->model->getPublishedTours($tourType);
        $adults = $this->queryInteger('adults', 1, 1, 99);
        $children = $this->queryInteger('children', 0, 0, 99);
        $tours = array_map([$this, 'prepareImage'], $this->model->addGroupEstimates($tours, $adults, $children));
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
        $date = $this->queryString('date');
        $parsedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) $date = '';
        $requestedNeeds = $_GET['needs'] ?? [];
        $needs = [];
        if (is_array($requestedNeeds)) {
            foreach ($requestedNeeds as $need) {
                if (is_string($need) && isset($catalogNeeds[$need])) $needs[$need] = $need;
            }
        }
        $origin = $this->queryString('origin');
        $price = $this->queryString('price');
        $hasGroupSearch = isset($_GET['adults']) || isset($_GET['children']) || $this->queryInteger('budget', 0, 0, 1000000000000) > 0;
        $filters = [
            'q' => $keyword, 'region' => $tourRegion,
            'destination' => $this->queryInteger('destination', 0, 0, PHP_INT_MAX),
            'date' => $date, 'origin' => isset($origins[$origin]) ? $origin : '',
            'price' => in_array($price, ['0-5000000', '5000000-10000000', '10000000-20000000', '20000000-50000000', '50000000-'], true) ? $price : '',
            'needs' => array_values($needs), 'adults' => $adults, 'children' => $children,
            'group_search' => $hasGroupSearch,
            'budget' => $this->queryInteger('budget', 0, 0, 1000000000000),
        ];
        $matchedTours = $this->model->filterTours($tours, $filters);
        $searchSummary = [];
        if ($filters['destination'] > 0) {
            foreach ($tours as $tour) {
                if ((int) $tour['destination_id'] === $filters['destination']) {
                    $searchSummary[] = $tour['destination'];
                    break;
                }
            }
        }
        if ($hasGroupSearch) $searchSummary[] = $adults . ' người lớn, ' . $children . ' trẻ em';
        if ($filters['budget'] > 0) $searchSummary[] = 'Ngân sách nhóm: ' . number_format($filters['budget'], 0, ',', '.') . ' ₫ (sau ưu đãi)';
        require __DIR__ . '/../views/tour/index.php';
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
        $itineraryWeather = [];

        if ($notFound) {
            http_response_code(404);
        } else {
            $tour = $this->prepareImage($tour);

            // Tìm lịch khởi hành được chọn: nếu truyền ?date= thì tìm, ngược lại lấy lịch đầu tiên
            $requestedDate = $this->queryString('date');
            if ($requestedDate !== '' && !empty($tour['schedules'])) {
                foreach ($tour['schedules'] as $schedule) {
                    if ($schedule['date'] === $requestedDate) {
                        $selectedSchedule = $schedule;
                        break;
                    }
                }
            }
            if ($selectedSchedule === null) {
                $selectedSchedule = $tour['schedules'][0] ?? null;
            }

            // Tích hợp ThoiTietModel: Dự báo thời tiết chi tiết theo từng ngày theo ngày bắt đầu đã chọn
            $startDate = $selectedSchedule ? $selectedSchedule['date'] : ($requestedDate ?: date('Y-m-d'));
            if (!empty($tour['itinerary'])) {
                $itineraryWeather = ThoiTietModel::getItineraryWeather(
                    $tour['itinerary'],
                    $startDate,
                    $tour['destination'] ?? ''
                );
            }

            // Hỗ trợ API AJAX khi khách bấm chọn lịch khởi hành khác trên giao diện
            if ($this->queryString('action') === 'weather') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'tour_id' => $tour['id'],
                    'departure_date' => $startDate,
                    'departure_formatted' => ThoiTietModel::formatVietnameseDate($startDate),
                    'origin' => $selectedSchedule['origin'] ?? '',
                    'weather' => $itineraryWeather
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $relatedTours = array_values(array_filter(
                $this->model->getPublishedTours($tourType),
                static fn($item) => $item['id'] !== $tour['id']
            ));
            usort($relatedTours, static fn($a, $b) =>
                (int) ($b['region'] === $tour['region']) <=> (int) ($a['region'] === $tour['region'])
            );
            $relatedTours = array_map($this->prepareImage(...), array_slice($relatedTours, 0, 6));
        }
        require __DIR__ . '/../views/tour/detail.php';
    }
}
