<?php
define('FUZZYWIRE', true); // Security check
require_once 'config.php';
require_once 'helpers.php';

 $page = $_GET['page'] ?? 'home';
 $section = $_GET['section'] ?? 'dashboard';
 $allowedAdminSections = ['dashboard', 'inventory', 'customization', 'wrappers', 'ribbons', 'presets', 'orders', 'sales'];
 if (!in_array($section, $allowedAdminSections, true)) $section = 'dashboard';
 $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

 $pageTitles = ['home' => 'Crafty Fuzzy', 'bouquets' => 'Bouquets | Crafty Fuzzy', 'customize' => 'Customize | Crafty Fuzzy', 'about' => 'About & Care | Crafty Fuzzy'];
 $pageTitle = $pageTitles[$page] ?? 'Crafty Fuzzy';

// Fetch only what each page actually needs
 $flowers = getFlowers($db);
 $builderFlowers = getFlowers($db, true);
 $wrappers = getWrappers($db);
 $ribbons = getRibbons($db);
 $baseSizes = getBaseSizes($db);
 $builderBaseSizes = getBaseSizes($db, true);
 $bouquets = getBouquets($db);
 $reviews = getReviews($db);

if ($page === 'admin') {
    $orders = getOrders($db);
    if (empty($_SESSION['admin_user'])) {
        include 'views/admin/login.php';
        exit;
    }
    include 'partials/admin_header.php';
    $viewFile = "views/admin/$section.php";
    if (file_exists($viewFile)) include $viewFile;
    include 'partials/admin_footer.php';
} else {
    if (!$isAjax) {
        include 'partials/header.php';
    } else {
        echo '<title>' . htmlspecialchars($pageTitle) . '</title>';
    }
    echo '<main id="page-content" data-page="' . htmlspecialchars($page) . '">';
    $viewFile = "views/$page.php";
    if (file_exists($viewFile)) include $viewFile;
    echo '</main>';
    if (!$isAjax) {
        include 'partials/footer.php';
    }
}
?>
