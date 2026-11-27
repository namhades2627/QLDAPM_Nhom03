<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/tour.php';
require_once __DIR__ . '/../models/thoi_tiet.php';
require_once __DIR__ . '/../models/danh_gia.php';

/** Nhận tham số URL, gọi Model và truyền dữ liệu vào View. */
class TourController
{
    private TourModel $model;
    private ?DanhGiaModel $danhGiaModel = null;

    public function __construct()
    {
        $this->model = new TourModel(getDatabaseConnection());
    }

    private function getDanhGiaModel(): DanhGiaModel
    {
        if ($this->danhGiaModel === null) {
            $this->danhGiaModel = new DanhGiaModel(getDatabaseConnection());
        }
        return $this->danhGiaModel;
    }

    private function isAjaxRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] === '1');
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
        // Xử lý yêu cầu Live Autocomplete khi gõ phím
        if ($this->queryString('action') === 'autocomplete') {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            $query = mb_substr(trim($this->queryString('q')), 0, 100, 'UTF-8');
            if ($query === '') {
                echo json_encode(['success' => true, 'destinations' => [], 'tours' => []], JSON_UNESCAPED_UNICODE);
                return;
            }

            $normQuery = TourModel::normalizeSearch($query);
            $compactQuery = str_replace([' ', '-', '.'], '', $normQuery);
            $availableDestinations = $this->model->getAvailableDestinations();
            $allTours = array_map([$this, 'prepareImage'], array_merge(
                $this->model->getPublishedTours('domestic'),
                $this->model->getPublishedTours('international')
            ));

            // 1. Tìm tối đa 3 điểm đến phù hợp nhất
            $matchedDestinations = [];
            foreach ($availableDestinations as $dest) {
                $name = $dest['name'];
                $normName = TourModel::normalizeSearch($name);
                $compactName = str_replace([' ', '-', '.'], '', $normName);
                $score = 0;
                if (mb_stripos($name, $query) === 0 || mb_stripos($normName, $normQuery) === 0 || ($compactQuery !== '' && str_starts_with($compactName, $compactQuery))) {
                    $score = 100; // Khớp từ đầu
                } elseif (mb_stripos($name, $query) !== false || mb_stripos($normName, $normQuery) !== false || ($compactQuery !== '' && str_contains($compactName, $compactQuery))) {
                    $score = 50; // Chứa từ khóa
                }
                if ($score > 0) {
                    $score += (int) ($dest['tour_count'] ?? 0);
                    $matchedDestinations[] = [
                        'id' => (int) $dest['id'],
                        'name' => $name,
                        'type' => $dest['type'] ?? 'domestic',
                        'tour_count' => (int) ($dest['tour_count'] ?? 0),
                        'score' => $score,
                    ];
                }
            }
            usort($matchedDestinations, static fn($a, $b) => $b['score'] <=> $a['score']);
            $matchedDestinations = array_slice($matchedDestinations, 0, 3);

            // 2. Tìm tối đa 2 tour gợi ý phù hợp nhất (theo tên tour, điểm đến)
            $scoredTours = [];
            foreach ($allTours as $t) {
                $score = 0;
                $tName = $t['name'];
                $normTName = TourModel::normalizeSearch($tName);
                $compactTName = str_replace([' ', '-', '.'], '', $normTName);
                $tDest = $t['destination'];
                $normTDest = TourModel::normalizeSearch($tDest);
                $compactTDest = str_replace([' ', '-', '.'], '', $normTDest);

                if (mb_stripos($tName, $query) !== false) {
                    $score += 100;
                } elseif (mb_stripos($normTName, $normQuery) !== false || ($compactQuery !== '' && str_contains($compactTName, $compactQuery))) {
                    $score += 80;
                }
                if (mb_stripos($tDest, $query) !== false) {
                    $score += 50;
                } elseif (mb_stripos($normTDest, $normQuery) !== false || ($compactQuery !== '' && str_contains($compactTDest, $compactQuery))) {
                    $score += 40;
                }

                if ($score > 0) {
                    // Ưu tiên tour có số lượt đặt cao hơn khi điểm khớp tương đương
                    $score += min(50, (int) ($t['booked_count'] ?? 0));
                    $scoredTours[] = ['tour' => $t, 'score' => $score];
                }
            }
            usort($scoredTours, static fn($a, $b) => $b['score'] <=> $a['score']);
            $matchedTours = [];
            foreach (array_slice($scoredTours, 0, 2) as $item) {
                $t = $item['tour'];
                $matchedTours[] = [
                    'id' => $t['id'],
                    'type' => $t['type'],
                    'name' => $t['name'],
                    'destination' => $t['destination'],
                    'duration' => $t['duration'],
                    'price' => $t['price'],
                    'price_format' => $t['price'] !== null ? number_format($t['price'], 0, ',', '.') . ' ₫' : 'Đang cập nhật',
                    'image' => $t['image'],
                    'booked_count' => (int) ($t['booked_count'] ?? 0),
                ];
            }

            echo json_encode([
                'success' => true,
                'destinations' => $matchedDestinations,
                'tours' => $matchedTours,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $page = 'tours';
        $requestedType = $this->queryString('type');
        $keyword = mb_substr($this->queryString('q'), 0, 255, 'UTF-8');
        $rawQuery = mb_substr($this->queryString('raw_q'), 0, 255, 'UTF-8');
        $displayQuery = $rawQuery !== '' ? $rawQuery : $keyword;
        $tourType = in_array($requestedType, ['domestic', 'international', 'all'], true) ? $requestedType : 'all';
        $typeTitle = $tourType === 'all'
            ? ($displayQuery !== '' ? 'Kết quả tìm tour' : 'Tất cả tour du lịch')
            : ($tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế');
        $domesticRegions = $this->model->getRegions('domestic');
        $internationalRegions = $this->model->getRegions('international');
        $regionNames = $tourType === 'all' ? array_merge($domesticRegions, $internationalRegions)
            : ($tourType === 'domestic' ? $domesticRegions : $internationalRegions);
        $requestedRegion = $this->queryString('region');
        $tourRegion = isset($regionNames[$requestedRegion]) ? $requestedRegion : '';
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
        $month = $this->queryInteger('month', 0, 1, 12);
        $season = $this->queryString('season');
        if (!in_array($season, ['xuan', 'ha', 'thu', 'dong'], true)) {
            $season = '';
        }
        $hasGroupSearch = isset($_GET['adults']) || isset($_GET['children']) || $this->queryInteger('budget', 0, 0, 1000000000000) > 0;
        $requestedSort = $this->queryString('sort');
        $validSorts = ['default', 'booked', 'featured', 'seasonal', 'promo', 'price-asc', 'price-desc', 'duration'];
        $sort = in_array($requestedSort, $validSorts, true) ? $requestedSort : 'default';

        $currentMonth = (int) date('n');
        $currentSeason = in_array($currentMonth, [9, 10, 11], true) ? 'thu'
            : (in_array($currentMonth, [12, 1, 2], true) ? 'dong'
            : (in_array($currentMonth, [3, 4], true) ? 'xuan' : 'ha'));

        if ($sort === 'featured' || $sort === 'booked') {
            usort($tours, static function ($a, $b) {
                $diff = (int) ($b['booked_count'] ?? 0) <=> (int) ($a['booked_count'] ?? 0);
                if ($diff !== 0) return $diff;
                $diffFeatured = (int) ($b['featured'] ?? 0) <=> (int) ($a['featured'] ?? 0);
                return $diffFeatured !== 0 ? $diffFeatured : strcmp($a['id'], $b['id']);
            });
        } elseif ($sort === 'seasonal') {
            usort($tours, static function ($a, $b) use ($currentSeason) {
                $aMatch = ($a['season'] ?? '') === $currentSeason ? 2 : (!empty($a['season']) && $a['season'] !== 'tat_ca' ? 1 : 0);
                $bMatch = ($b['season'] ?? '') === $currentSeason ? 2 : (!empty($b['season']) && $b['season'] !== 'tat_ca' ? 1 : 0);
                $diff = $bMatch <=> $aMatch;
                if ($diff !== 0) return $diff;
                $diffBooked = (int) ($b['booked_count'] ?? 0) <=> (int) ($a['booked_count'] ?? 0);
                return $diffBooked !== 0 ? $diffBooked : strcmp($a['id'], $b['id']);
            });
        } elseif ($sort === 'promo') {
            usort($tours, static function ($a, $b) {
                $diff = (int) ($b['discount_percent'] ?? 0) <=> (int) ($a['discount_percent'] ?? 0);
                if ($diff !== 0) return $diff;
                $diffBooked = (int) ($b['booked_count'] ?? 0) <=> (int) ($a['booked_count'] ?? 0);
                return $diffBooked !== 0 ? $diffBooked : strcmp($a['id'], $b['id']);
            });
        } elseif ($sort === 'price-asc') {
            usort($tours, static function ($a, $b) {
                $priceA = $a['price'] ?? PHP_INT_MAX;
                $priceB = $b['price'] ?? PHP_INT_MAX;
                return $priceA <=> $priceB;
            });
        } elseif ($sort === 'price-desc') {
            usort($tours, static function ($a, $b) {
                $priceA = $a['price'] ?? -1;
                $priceB = $b['price'] ?? -1;
                return $priceB <=> $priceA;
            });
        } elseif ($sort === 'duration') {
            usort($tours, static function ($a, $b) {
                return ((int) ($a['days'] ?? 0)) <=> ((int) ($b['days'] ?? 0));
            });
        }

        $filters = [
            'q' => $keyword, 'region' => $tourRegion,
            'destination' => $this->queryInteger('destination', 0, 0, PHP_INT_MAX),
            'days' => $this->queryInteger('days', 0, 0, 30),
            'date' => $date, 'origin' => isset($origins[$origin]) ? $origin : '',
            'price' => in_array($price, ['0-5000000', '5000000-10000000', '10000000-20000000', '20000000-50000000', '50000000-'], true) ? $price : '',
            'needs' => array_values($needs), 'adults' => $adults, 'children' => $children,
            'group_search' => $hasGroupSearch,
            'budget' => $this->queryInteger('budget', 0, 0, 1000000000000),
            'month' => $month,
            'season' => $season,
        ];
        $matchedTours = $this->model->filterTours($tours, $filters);
        if ($sort === 'promo') {
            $matchedTours = array_values(array_filter($matchedTours, static fn($t) => !empty($t['discount_percent']) && (int) $t['discount_percent'] > 0));
        } elseif ($sort === 'seasonal') {
            $seasonMatched = array_values(array_filter($matchedTours, static fn($t) => ($t['season'] ?? '') === $currentSeason));
            $matchedTours = !empty($seasonMatched)
                ? $seasonMatched
                : array_values(array_filter($matchedTours, static fn($t) => !empty($t['season']) && $t['season'] !== 'tat_ca'));
        }
        $searchSummary = [];
        if ($filters['destination'] > 0) {
            foreach ($tours as $tour) {
                if ((int) $tour['destination_id'] === $filters['destination']) {
                    $searchSummary[] = $tour['destination'];
                    break;
                }
            }
        } elseif ($tourRegion !== '' && isset($regionNames[$tourRegion])) {
            $searchSummary[] = $regionNames[$tourRegion];
        }
        if ($filters['origin'] !== '') {
            $searchSummary[] = 'Từ ' . $filters['origin'];
        }
        if ($filters['days'] > 0) {
            $searchSummary[] = $filters['days'] . ' ngày';
        }
        if ($filters['month'] > 0) {
            $searchSummary[] = 'Khởi hành: Tháng ' . $filters['month'];
        }
        if ($filters['season'] !== '') {
            $seasonLabels = ['xuan' => 'Mùa Xuân', 'ha' => 'Mùa Hè', 'thu' => 'Mùa Thu', 'dong' => 'Mùa Đông'];
            if (isset($seasonLabels[$filters['season']])) {
                $searchSummary[] = $seasonLabels[$filters['season']];
            }
        }
        foreach ($filters['needs'] as $need) {
            if (isset($catalogNeeds[$need])) $searchSummary[] = $catalogNeeds[$need];
        }
        $unrecognizedWord = $keyword;
        $highlightedQuery = '';
        if ($unrecognizedWord !== '' && $displayQuery !== '') {
            $escapedWord = preg_quote($unrecognizedWord, '/');
            $safeDisplay = htmlspecialchars($displayQuery, ENT_QUOTES, 'UTF-8');
            $highlightedQuery = preg_replace(
                '/(' . $escapedWord . ')/ui',
                '<span class="unrecognized-word" title="Từ chưa nhận diện được">$1</span>',
                $safeDisplay
            );
        }
        // Xử lý cơ chế gợi ý nới lỏng khi 0 kết quả (Fallback Recommendations)
        $fallbackDiagnostic = null;
        if (empty($matchedTours)) {
            $reason = 'general';
            $message = 'Chưa có tour phù hợp chính xác mọi tiêu chí đã chọn, OnTrip gợi ý cho bạn 3 tour nổi bật được nhiều khách yêu thích nhất:';
            $candidateTours = $tours;
            if ($filters['destination'] > 0) {
                $sameDestTours = array_filter($tours, static fn($t) => (int) $t['destination_id'] === $filters['destination']);
                if (!empty($sameDestTours)) $candidateTours = array_values($sameDestTours);
            } elseif ($tourRegion !== '') {
                $sameRegionTours = array_filter($tours, static fn($t) => $t['region'] === $tourRegion);
                if (!empty($sameRegionTours)) $candidateTours = array_values($sameRegionTours);
            }

            if ($filters['budget'] > 0) {
                $reason = 'budget';
                $validPrices = array_filter(array_column($candidateTours, 'price'), static fn($p) => $p !== null && $p > 0);
                $minPrice = !empty($validPrices) ? min($validPrices) : 0;
                $formattedBudget = number_format($filters['budget'], 0, ',', '.') . ' ₫';
                $formattedMin = $minPrice > 0 ? number_format($minPrice, 0, ',', '.') . ' ₫' : 'mức giá tốt nhất';
                $message = "Không có tour dưới {$formattedBudget}, nhưng OnTrip gợi ý cho bạn 3 tour tương tự chỉ từ {$formattedMin}:";
                usort($candidateTours, static fn($a, $b) => ($a['price'] ?? PHP_INT_MAX) <=> ($b['price'] ?? PHP_INT_MAX));
                $fallbackTours = array_slice($candidateTours, 0, 3);
            } elseif ($filters['date'] !== '') {
                $reason = 'date';
                $formattedDate = date('d/m/Y', strtotime($filters['date']));
                $message = "Chưa có lịch khởi hành vào ngày {$formattedDate}, OnTrip gợi ý cho bạn các tour có lịch khởi hành gần nhất:";
                usort($candidateTours, static fn($a, $b) => ((int) ($b['booked_count'] ?? 0)) <=> ((int) ($a['booked_count'] ?? 0)));
                $fallbackTours = array_slice($candidateTours, 0, 3);
            } elseif ($filters['days'] > 0) {
                $reason = 'days';
                $message = "Chưa có tour đúng {$filters['days']} ngày, OnTrip gợi ý cho bạn 3 tour có thời lượng tương tự:";
                usort($candidateTours, static function ($a, $b) use ($filters) {
                    $diffA = abs((int) ($a['days'] ?? 0) - $filters['days']);
                    $diffB = abs((int) ($b['days'] ?? 0) - $filters['days']);
                    return $diffA <=> $diffB;
                });
                $fallbackTours = array_slice($candidateTours, 0, 3);
            } else {
                usort($candidateTours, static fn($a, $b) => ((int) ($b['booked_count'] ?? 0)) <=> ((int) ($a['booked_count'] ?? 0)));
                $fallbackTours = array_slice($candidateTours, 0, 3);
            }

            $fallbackDiagnostic = [
                'reason' => $reason,
                'message' => $message,
                'tours' => $fallbackTours,
            ];
        }

        require __DIR__ . '/../views/tour/index.php';
    }

    public function detail(): void
    {
        $page = 'tour-detail';
        $rawType = $this->queryString('type');
        $code = $this->queryString('id');
        
        // Tìm tour: nếu có type thì tìm theo type, nếu không tìm cả 2 loại để không bị 404
        $tour = null;
        $tourType = $rawType === 'international' ? 'international' : 'domestic';
        if ($rawType !== '') {
            $tour = $this->model->findPublishedTour($tourType, $code);
        } else {
            $tour = $this->model->findPublishedTour('domestic', $code);
            if ($tour === null) {
                $tour = $this->model->findPublishedTour('international', $code);
                if ($tour !== null) $tourType = 'international';
            }
        }
        $typeTitle = $tourType === 'domestic' ? 'Tour trong nước' : 'Tour quốc tế';
        $tourRegion = '';
        $notFound = $tour === null;
        $relatedTours = [];
        $selectedSchedule = null;
        $itineraryWeather = [];

        if ($notFound) {
            http_response_code(404);
        } else {
            $tour = $this->prepareImage($tour);

            // Xử lý gửi hoặc xóa đánh giá của khách hàng
            $action = $_POST['action'] ?? $this->queryString('action');
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if ($action === 'submit_review') {
                    $account = $_SESSION['tai_khoan'] ?? null;
                    if (!$account) {
                        if ($this->isAjaxRequest()) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để gửi đánh giá.']);
                            return;
                        }
                        $_SESSION['review_flash'] = ['type' => 'error', 'message' => 'Vui lòng đăng nhập để gửi đánh giá.'];
                        header('Location: index.php?page=login&redirect=' . urlencode('index.php?page=tour-detail&id=' . $tour['id'] . '#danh-gia'), true, 303);
                        return;
                    }

                    $csrf = $_POST['csrf_token'] ?? '';
                    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                        if ($this->isAjaxRequest()) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => 'Phiên làm việc hết hạn hoặc token bảo mật không khớp.']);
                            return;
                        }
                        $_SESSION['review_flash'] = ['type' => 'error', 'message' => 'Phiên làm việc hết hạn. Vui lòng thử lại.'];
                        header('Location: index.php?page=tour-detail&id=' . urlencode($tour['id']) . '#danh-gia', true, 303);
                        return;
                    }

                    $stars = filter_var($_POST['so_sao'] ?? 5, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) ?: 5;
                    $title = is_string($_POST['tieu_de'] ?? null) ? trim($_POST['tieu_de']) : '';
                    $content = is_string($_POST['noi_dung'] ?? null) ? trim($_POST['noi_dung']) : '';
                    $orderId = $this->getDanhGiaModel()->getUserOrderIdForTour((int) $tour['id_tour'], (int) $account['id_tai_khoan']);

                    $result = $this->getDanhGiaModel()->saveReview(
                        (int) $tour['id_tour'],
                        (int) $account['id_tai_khoan'],
                        $stars,
                        $content,
                        $title,
                        $orderId
                    );

                    if ($this->isAjaxRequest()) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($result, JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $_SESSION['review_flash'] = [
                        'type' => $result['success'] ? 'success' : 'error',
                        'message' => $result['message']
                    ];
                    header('Location: index.php?page=tour-detail&id=' . urlencode($tour['id']) . '#danh-gia', true, 303);
                    return;
                }

                if ($action === 'delete_review') {
                    $account = $_SESSION['tai_khoan'] ?? null;
                    $reviewId = filter_var($_POST['id_danh_gia'] ?? 0, FILTER_VALIDATE_INT);
                    $csrf = $_POST['csrf_token'] ?? '';

                    if (!$account || !$reviewId || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                        if ($this->isAjaxRequest()) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'message' => 'Yêu cầu xóa đánh giá không hợp lệ.']);
                            return;
                        }
                        $_SESSION['review_flash'] = ['type' => 'error', 'message' => 'Không thể xóa đánh giá này.'];
                        header('Location: index.php?page=tour-detail&id=' . urlencode($tour['id']) . '#danh-gia', true, 303);
                        return;
                    }

                    $isAdmin = ($account['vai_tro'] ?? '') === 'admin';
                    $success = $this->getDanhGiaModel()->deleteReview((int) $reviewId, (int) $account['id_tai_khoan'], $isAdmin);

                    if ($this->isAjaxRequest()) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => $success, 'message' => $success ? 'Đã xóa đánh giá của bạn.' : 'Không thể xóa đánh giá.'], JSON_UNESCAPED_UNICODE);
                        return;
                    }

                    $_SESSION['review_flash'] = [
                        'type' => $success ? 'success' : 'error',
                        'message' => $success ? 'Đã xóa đánh giá của bạn.' : 'Không thể xóa đánh giá.'
                    ];
                    header('Location: index.php?page=tour-detail&id=' . urlencode($tour['id']) . '#danh-gia', true, 303);
                    return;
                }
            }

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

            $generalAdvice = ThoiTietModel::getGeneralPreparationAdvice($itineraryWeather, $tour);

            // Hỗ trợ API AJAX khi khách bấm chọn lịch khởi hành khác trên giao diện
            if ($this->queryString('action') === 'weather') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'tour_id' => $tour['id'],
                    'departure_date' => $startDate,
                    'departure_formatted' => ThoiTietModel::formatVietnameseDate($startDate),
                    'origin' => $selectedSchedule['origin'] ?? '',
                    'weather' => $itineraryWeather,
                    'general_advice' => $generalAdvice
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Dữ liệu đánh giá tour
            $currentUserId = isset($_SESSION['tai_khoan']['id_tai_khoan']) ? (int) $_SESSION['tai_khoan']['id_tai_khoan'] : null;
            $reviewStats = $this->getDanhGiaModel()->getTourRatingStats((int) $tour['id_tour']);
            $reviews = $this->getDanhGiaModel()->getTourReviews((int) $tour['id_tour']);
            $userReview = $currentUserId ? $this->getDanhGiaModel()->getUserReview((int) $tour['id_tour'], $currentUserId) : null;
            $hasBooked = $currentUserId ? $this->getDanhGiaModel()->hasUserBookedTour((int) $tour['id_tour'], $currentUserId) : false;
            $csrfToken = $_SESSION['csrf_token'] ?? '';
            $reviewFlash = $_SESSION['review_flash'] ?? null;
            unset($_SESSION['review_flash']);

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
