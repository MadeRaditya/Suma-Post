<?php
require_once __DIR__ . '/includes/api.php';

$articleId = isset($_GET['id']) ? intval($_GET['id']) : null;
$article = null;

if ($articleId) {
    $article = get_article_by_id($articleId);
}

$pageTitle = isset($article['title']['rendered']) 
    ? htmlspecialchars($article['title']['rendered']) 
    : 'Detail Artikel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-navbar">
        <nav class="navbar-breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Dashboard</a>
            <span class="navbar-separator">/</span>
            <a href="index.php?page=articles">Artikel</a>
            <span class="navbar-separator">/</span>
            <span class="navbar-current">Detail #<?= htmlspecialchars($articleId ?? 'N/A') ?></span>
        </nav>

        <div class="navbar-actions">
            <a href="index.php" class="btn btn-sm btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span class="nav-action-text">Kembali ke Beranda</span>
                <span class="nav-action-mobile-text">Kembali</span>
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="page-content">
        <?php include __DIR__ . '/views/article-detail.php'; ?>
    </main>

</body>
</html>