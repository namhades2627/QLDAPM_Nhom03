<?php
/**
 * Khối mã QR mô phỏng chi tiết cho:
 * - Chuyển khoản ngân hàng (VietQR · napas 247 · Vietcombank)
 * - Thẻ quốc tế (VISA / Mastercard · VietQR)
 * Số tài khoản được sinh ngẫu nhiên (10 chữ số Vietcombank) và tên người nhận lấy từ tài khoản admin.
 *
 * @var bool $vietqrVisible
 * @var string $vietqrMethod
 * @var string $vietqrAmountText
 * @var string $vietqrTransferNote
 * @var string $vietqrRecipientName
 * @var string $vietqrSeed
 * @var bool $vietqrCompact
 */
$vietqrVisible = !empty($vietqrVisible);
$vietqrMethod = ($vietqrMethod ?? '') === 'the' ? 'the' : 'chuyen_khoan';
$vietqrAmountText = (string) ($vietqrAmountText ?? '0 VND');
$vietqrTransferNote = (string) ($vietqrTransferNote ?? 'ONTRIP');
$vietqrRecipientName = (string) ($vietqrRecipientName ?? 'QUẢN TRỊ VIÊN ONTRIP');
$vietqrSeed = (string) ($vietqrSeed ?? 'VCB-ONTRIP');
$vietqrCompact = !empty($vietqrCompact);

$qrEscape = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

// Số tài khoản Vietcombank ngẫu nhiên 10 chữ số (đầu số 10xxxxxxxx) và 4 số cuối thẻ Visa mô phỏng.
$randomVcbAccount = '10' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
$randomVisaLast4 = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

/**
 * Tạo ma trận QR Version 6 (41x41) với 3 Finder Patterns, 6 Alignment Patterns, Timing Patterns
 * và mật độ điểm dữ liệu cao giống mã VietQR / EMVCo QR thực tế.
 */
$buildQrMatrix = static function (string $seed): array {
    $size = 41;
    $matrix = array_fill(0, $size, array_fill(0, $size, false));
    $reserved = array_fill(0, $size, array_fill(0, $size, false));

    // Finder pattern 7x7 + viền trắng 1 ô quanh góc
    $drawFinder = static function (int $r0, int $c0) use (&$matrix, &$reserved, $size): void {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $rr = $r0 + $r;
                $cc = $c0 + $c;
                if ($rr < 0 || $rr >= $size || $cc < 0 || $cc >= $size) {
                    continue;
                }
                $reserved[$rr][$cc] = true;
                if ($r === -1 || $r === 7 || $c === -1 || $c === 7) {
                    $matrix[$rr][$cc] = false;
                    continue;
                }
                $isOuter = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                $isCore = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                $matrix[$rr][$cc] = $isOuter || $isCore;
            }
        }
    };
    $drawFinder(0, 0);
    $drawFinder(0, $size - 7);
    $drawFinder($size - 7, 0);

    // 6 Alignment patterns 5x5 cho QR Version 6 (tọa độ chuẩn 6, 20, 34 trừ các góc đã có finder)
    $drawAlignment = static function (int $cr, int $cc) use (&$matrix, &$reserved): void {
        for ($dr = -2; $dr <= 2; $dr++) {
            for ($dc = -2; $dc <= 2; $dc++) {
                $rr = $cr + $dr;
                $cc2 = $cc + $dc;
                $reserved[$rr][$cc2] = true;
                $isBorder = (abs($dr) === 2 || abs($dc) === 2);
                $isDot = ($dr === 0 && $dc === 0);
                $matrix[$rr][$cc2] = $isBorder || $isDot;
            }
        }
    };
    foreach ([[6, 20], [20, 6], [20, 20], [20, 34], [34, 20], [34, 34]] as [$ar, $ac]) {
        $drawAlignment($ar, $ac);
    }

    // Timing patterns (hàng 6 và cột 6) + dark module
    for ($i = 8; $i < $size - 8; $i++) {
        if (!$reserved[6][$i]) {
            $reserved[6][$i] = true;
            $matrix[6][$i] = ($i % 2 === 0);
        }
        if (!$reserved[$i][6]) {
            $reserved[$i][6] = true;
            $matrix[$i][6] = ($i % 2 === 0);
        }
    }
    $matrix[$size - 8][8] = true;
    $reserved[$size - 8][8] = true;

    // Vùng định danh (format strips) quanh 3 finder
    $fmtSeed = hash('sha256', $seed . '|fmt', true);
    for ($i = 0; $i < 9; $i++) {
        if (!$reserved[8][$i]) {
            $reserved[8][$i] = true;
            $matrix[8][$i] = ((ord($fmtSeed[$i % 32]) >> ($i % 8)) & 1) === 1;
        }
        if (!$reserved[$i][8]) {
            $reserved[$i][8] = true;
            $matrix[$i][8] = ((ord($fmtSeed[($i + 7) % 32]) >> ($i % 8)) & 1) === 1;
        }
    }
    for ($i = 0; $i < 8; $i++) {
        $c = $size - 1 - $i;
        if (!$reserved[8][$c]) {
            $reserved[8][$c] = true;
            $matrix[8][$c] = ((ord($fmtSeed[($i + 11) % 32]) >> ($i % 8)) & 1) === 1;
        }
        $r = $size - 1 - $i;
        if ($i < 7 && !$reserved[$r][8]) {
            $reserved[$r][8] = true;
            $matrix[$r][8] = ((ord($fmtSeed[($i + 19) % 32]) >> ($i % 8)) & 1) === 1;
        }
    }

    // Khoảng trống trung tâm 9x9 cho biểu tượng VietQR / VISA
    for ($r = 16; $r <= 24; $r++) {
        for ($c = 16; $c <= 24; $c++) {
            $reserved[$r][$c] = true;
            $matrix[$r][$c] = false;
        }
    }

    // Điền các mô-đun dữ liệu mật độ cao nhất quán theo chuỗi seed
    $bytes = '';
    for ($round = 0; $round < 8; $round++) {
        $bytes .= hash('sha256', $seed . '|r' . $round, true);
    }
    $bitIndex = 0;
    $byteLen = strlen($bytes);
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if ($reserved[$r][$c]) {
                continue;
            }
            $byte = ord($bytes[intdiv($bitIndex, 8) % $byteLen]);
            $matrix[$r][$c] = (($byte >> ($bitIndex % 8)) & 1) === 1;
            $bitIndex++;
        }
    }

    return $matrix;
};

$renderQrPaths = static function (array $matrix): string {
    $d = '';
    foreach ($matrix as $r => $row) {
        $c = 0;
        $n = count($row);
        while ($c < $n) {
            if ($row[$c]) {
                $start = $c;
                while ($c < $n && $row[$c]) {
                    $c++;
                }
                $w = $c - $start;
                $d .= 'M' . ($start + 2) . ' ' . ($r + 2) . 'h' . $w . 'v1h-' . $w . 'z';
            } else {
                $c++;
            }
        }
    }
    return $d;
};

$bankQrPath = $renderQrPaths($buildQrMatrix($vietqrSeed . '|vcb|' . $randomVcbAccount));
$visaQrPath = $renderQrPaths($buildQrMatrix($vietqrSeed . '|visa|' . $randomVcbAccount . '|' . $randomVisaLast4));
?>
<div class="vietqr-box<?= $vietqrCompact ? ' is-compact' : '' ?>" data-vietqr-panel <?= $vietqrVisible ? '' : 'hidden' ?>>
    <!-- 1. MÃ QR VIETCOMBANK (VIETQR) CHO CHUYỂN KHOẢN NGÂN HÀNG -->
    <div class="vietqr-card" data-vietqr-mode="chuyen_khoan" <?= $vietqrMethod === 'chuyen_khoan' ? '' : 'hidden' ?>>
        <div class="vietqr-brand-top" aria-label="VietQR">
            <svg class="vietqr-wordmark-svg" viewBox="0 0 184 44" role="img" aria-label="VietQR">
                <defs>
                    <linearGradient id="vqrRedLeft" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#f05246"/>
                        <stop offset="100%" stop-color="#c91f28"/>
                    </linearGradient>
                    <linearGradient id="vqrRedRight" x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stop-color="#b81722"/>
                        <stop offset="100%" stop-color="#e53935"/>
                    </linearGradient>
                </defs>
                <path d="M18 7c2.4 0 4.2 1.3 5.3 3.5l10.2 19.6 8.3-17.6c1.1-2.3 2.9-3.5 5.2-3.5 2.7 0 4.5 2.5 3.3 5.2L37.4 39.2c-1.1 2.2-2.8 3.3-5.1 3.3s-4-1.1-5.1-3.3L14.6 12.2C13.5 9.6 15.2 7 18 7Z" fill="url(#vqrRedLeft)"/>
                <path d="M33.5 30.1 41.8 12.5c1.1-2.3 2.9-3.5 5.2-3.5 2.7 0 4.5 2.5 3.3 5.2L37.4 39.2c-1.1 2.2-2.8 3.3-5.1 3.3l1.2-12.4Z" fill="url(#vqrRedRight)" opacity=".88"/>
                <text x="53" y="34" font-family="'Be Vietnam Pro',system-ui,sans-serif" font-size="27" font-weight="800" letter-spacing="0.5">
                    <tspan fill="#d92d20">IET</tspan><tspan fill="#1d3f72">QR</tspan>
                </text>
            </svg>
        </div>

        <div class="vietqr-frame">
            <svg class="vietqr-matrix-svg" viewBox="0 0 45 45" role="img" aria-label="Mã QR Vietcombank VietQR mô phỏng">
                <rect width="45" height="45" fill="#ffffff"/>
                <path d="<?= $bankQrPath ?>" fill="#0b0f19" shape-rendering="crispEdges"/>
                <rect x="17.6" y="17.6" width="9.8" height="9.8" rx="1.6" fill="#ffffff"/>
                <path d="M19.8 19.8c.5 0 .9.3 1.2.8l1.5 3.3 1.5-3.3c.3-.5.7-.8 1.2-.8.6 0 1 .6.7 1.2l-2.4 5c-.3.5-.6.8-1 .8s-.7-.3-1-.8l-2.4-5c-.3-.6.1-1.2.7-1.2Z" fill="#d92d20"/>
            </svg>
        </div>

        <div class="vietqr-partners" aria-label="Đơn vị hỗ trợ napas 247 và Vietcombank">
            <span class="vietqr-napas">
                <span class="napas-text">napas</span><span class="napas-247">247</span>
            </span>
            <span class="vietqr-partner-divider" aria-hidden="true"></span>
            <span class="vietqr-vcb">
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2 22 9.5 16.5 21H7.5L2 9.5 12 2Z" fill="#006837"/>
                    <path d="M12 5.2 18.2 10 14.6 17.8H9.4L5.8 10 12 5.2Z" fill="#7ac143"/>
                    <path d="M12 8.2 14.8 10.6 13.1 15h-2.2L9.2 10.6 12 8.2Z" fill="#ffffff"/>
                </svg>
                <span>Vietcombank</span>
            </span>
        </div>

        <div class="vietqr-recipient-summary">
            <strong class="vietqr-recipient-name"><?= $qrEscape($vietqrRecipientName) ?></strong>
            <span class="vietqr-account-no"><?= $qrEscape($randomVcbAccount) ?></span>
            <span class="vietqr-amount-row">Số tiền: <strong data-vietqr-amount><?= $qrEscape($vietqrAmountText) ?></strong></span>
            <span class="vietqr-memo-row">Nội dung CK: <code><?= $qrEscape($vietqrTransferNote) ?></code></span>
        </div>

        <p class="vietqr-sim-warning">MÔ PHỎNG · KHÔNG CHUYỂN TIỀN THẬT</p>
    </div>

    <!-- 2. MÃ QR VISA / MASTERCARD (VIETQR · VIETCOMBANK) MÔ PHỎNG -->
    <div class="vietqr-card is-visa" data-vietqr-mode="the" <?= $vietqrMethod === 'the' ? '' : 'hidden' ?>>
        <div class="vietqr-brand-top vietqr-card-brands" aria-label="VISA và Mastercard">
            <svg class="visa-wordmark-svg" viewBox="0 0 92 30" role="img" aria-label="VISA">
                <path d="M6 6h9.5l1.2 2.4-10.7 1Z" fill="#f7b600"/>
                <text x="10" y="24" font-family="'Be Vietnam Pro',system-ui,sans-serif" font-size="22" font-weight="900" font-style="italic" letter-spacing="0.5" fill="#1a1f71">VISA</text>
            </svg>
            <svg class="mc-mark-svg" viewBox="0 0 46 28" role="img" aria-label="Mastercard">
                <circle cx="16" cy="14" r="10.5" fill="#eb001b"/>
                <circle cx="30" cy="14" r="10.5" fill="#f79e1b" fill-opacity=".9"/>
            </svg>
            <span class="vietqr-emv-pill">EMV QR</span>
        </div>

        <div class="vietqr-frame is-visa-frame">
            <svg class="vietqr-matrix-svg" viewBox="0 0 45 45" role="img" aria-label="Mã QR thanh toán thẻ quốc tế Visa / Mastercard mô phỏng">
                <rect width="45" height="45" fill="#ffffff"/>
                <path d="<?= $visaQrPath ?>" fill="#0f172a" shape-rendering="crispEdges"/>
                <rect x="17.2" y="17.8" width="10.6" height="9.4" rx="1.6" fill="#ffffff" stroke="#e2e8f0" stroke-width="0.35"/>
                <text x="22.5" y="23.1" text-anchor="middle" font-family="sans-serif" font-size="3.1" font-weight="900" font-style="italic" fill="#1a1f71">VISA</text>
                <rect x="19.3" y="24.2" width="6.4" height="0.85" rx="0.4" fill="#f7b600"/>
            </svg>
        </div>

        <div class="vietqr-partners" aria-label="Đối tác thanh toán thẻ quốc tế">
            <span class="vietqr-napas">
                <span class="napas-text">VISA</span><span class="napas-247">QR</span>
            </span>
            <span class="vietqr-partner-divider" aria-hidden="true"></span>
            <span class="vietqr-vcb">
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 2 22 9.5 16.5 21H7.5L2 9.5 12 2Z" fill="#006837"/>
                    <path d="M12 5.2 18.2 10 14.6 17.8H9.4L5.8 10 12 5.2Z" fill="#7ac143"/>
                    <path d="M12 8.2 14.8 10.6 13.1 15h-2.2L9.2 10.6 12 8.2Z" fill="#ffffff"/>
                </svg>
                <span>Vietcombank</span>
            </span>
        </div>

        <div class="vietqr-recipient-summary">
            <strong class="vietqr-recipient-name"><?= $qrEscape($vietqrRecipientName) ?></strong>
            <span class="vietqr-account-no"><?= $qrEscape($randomVcbAccount) ?> · VISA •••• <?= $qrEscape($randomVisaLast4) ?></span>
            <span class="vietqr-amount-row">Số tiền: <strong data-vietqr-amount><?= $qrEscape($vietqrAmountText) ?></strong></span>
            <span class="vietqr-memo-row">Nội dung: <code><?= $qrEscape($vietqrTransferNote) ?></code></span>
        </div>

        <p class="vietqr-sim-warning">MÔ PHỎNG · KHÔNG TRỪ TIỀN THẺ THẬT</p>
    </div>
</div>
