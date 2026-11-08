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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../public/assets/css/style.css?v=20261108-ivory">
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
