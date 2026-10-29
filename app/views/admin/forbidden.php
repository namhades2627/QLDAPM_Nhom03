<?php
/** @var string $errorTitle */
/** @var string $errorMessage */
$errorTitle = $errorTitle ?? 'Không có quyền truy cập';
$errorMessage = $errorMessage ?? 'Phiên truy cập không hợp lệ hoặc tài khoản không còn quyền quản trị.';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Không có quyền truy cập | OnTrip</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body class="auth-page">
<main class="auth-main">
    <section class="auth-shell auth-panel">
        <h1><?= htmlspecialchars($errorTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="button button-primary" href="index.php?action=login">Về đăng nhập quản trị</a>
    </section>
</main>
</body>
</html>
