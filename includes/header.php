<?php
require_once __DIR__ . '/auth.php';

$page = currentPage();

$allowedThemes = ['default', 'pink', 'blue', 'yellow', 'purple'];
$theme = $_COOKIE['gymty-theme'] ?? 'default';

if (!in_array($theme, $allowedThemes, true)) {
    $theme = 'default';
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="GYMTY - Profesyonel fitness üyelik sistemi. Kişisel antrenör, grup dersleri ve modern spor salonu.">
    <meta name="csrf-token" content="<?php echo sanitize(csrfToken()); ?>">
    <title>GYMTY Fitness
        <?php echo isset($pageTitle) ? '| ' . sanitize($pageTitle) : ''; ?>
    </title>
    <link rel="icon" type="image/png" href="<?php echo sanitize(BASE_URL); ?>/assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet"
        href="<?php echo sanitize(BASE_URL); ?>/assets/css/style.css?v=<?php echo (int) @filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>

<body data-theme="<?php echo sanitize($theme); ?>">

    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <a href="<?php echo sanitize(BASE_URL); ?>/" class="logo">
                <span class="logo-icon"><i class="fas fa-dumbbell"></i></span>
                <span class="logo-text">GYM<span class="accent">TY</span></span>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Menü">
                <span></span><span></span><span></span>
            </button>

            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo sanitize(BASE_URL); ?>/index"
                        class="<?php echo $page === 'index' ? 'active' : ''; ?>">Anasayfa</a></li>
                <li><a href="<?php echo sanitize(BASE_URL); ?>/paketler"
                        class="<?php echo $page === 'paketler' ? 'active' : ''; ?>">Paketler</a>
                </li>

                <li><a href="<?php echo sanitize(BASE_URL); ?>/iletisim"
                        class="<?php echo $page === 'iletisim' ? 'active' : ''; ?>">İletişim</a></li>

                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo sanitize(BASE_URL); ?>/admin/" class="nav-btn nav-btn-outline">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo sanitize(BASE_URL); ?>/panel" class="nav-btn nav-btn-outline">Panelim</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="<?php echo sanitize(BASE_URL); ?>/giris" class="nav-btn nav-btn-outline">Giriş</a></li>
                    <li><a href="<?php echo sanitize(BASE_URL); ?>/kayit" class="nav-btn">Üye Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash-message flash-<?php echo sanitize($flash['type']); ?>" id="flashMsg" role="alert">
            <div class="container">
                <span>
                    <?php echo sanitize($flash['message']); ?>
                </span>
                <button onclick="this.parentElement.parentElement.remove()" class="flash-close"
                    aria-label="Kapat">&times;</button>
            </div>
        </div>
    <?php endif; ?>

    <main>
