<?php
require_once __DIR__ . '/includes/api.php';

$view = $_GET['page'] ?? 'dashboard';
$searchQuery = trim($_GET['search'] ?? '');
$selectedCategory = trim($_GET['cat'] ?? '');
$layout = $_GET['layout'] ?? 'grid';
$currentPage = max(1, intval($_GET['p'] ?? 1));
$perPage = defined('PER_PAGE') ? PER_PAGE : 6;

// Fetch articles from the configured API endpoint
$apiMeta = [];
$allArticles = get_all_articles([], $apiMeta);
$totalFetched = count($allArticles);

// Extract taxonomies for quick filter tabs
$taxData = extract_taxonomies($allArticles);
$categoriesList = $taxData['categories'] ?? [];
$tagsList = $taxData['tags'] ?? [];

// Filter articles by search keyword and category
$filteredArticles = $allArticles;

if (!empty($selectedCategory)) {
    $filteredArticles = array_filter($filteredArticles, function ($item) use ($selectedCategory) {
        $classList = $item['class_list'] ?? [];
        return in_array('category-' . $selectedCategory, $classList) || in_array($selectedCategory, $classList);
    });
}

if (!empty($searchQuery)) {
    $filteredArticles = array_filter($filteredArticles, function ($item) use ($searchQuery) {
        $title = $item['title']['rendered'] ?? '';
        $excerpt = $item['excerpt']['rendered'] ?? '';
        return (stripos($title, $searchQuery) !== false) || (stripos($excerpt, $searchQuery) !== false);
    });
}

$filteredArticles = array_values($filteredArticles);
$totalItems = count($filteredArticles);
$totalPages = max(1, ceil($totalItems / $perPage));
$offset = ($currentPage - 1) * $perPage;
$paginatedArticles = array_slice($filteredArticles, $offset, $perPage);

// API Health Stats
$apiHealth = get_api_health();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> — Enterprise REST API Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-header">
                <div class="brand-wrapper">
                    <div class="brand-icon">◈</div>
                    <div class="brand-meta">
                        <span class="brand-title"><?= htmlspecialchars(APP_NAME) ?></span>
                        <span class="brand-subtitle">WP-JSON Explorer</span>
                    </div>
                </div>
                <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Menu Navigasi">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="sidebar-content">
                <div class="sidebar-section">
                    <span class="sidebar-section-title">Navigasi Utama</span>
                    <a href="index.php?page=dashboard" class="sidebar-nav-item <?= $view === 'dashboard' ? 'active' : '' ?>">
                        <span class="sidebar-nav-icon-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            <span>Dashboard Overview</span>
                        </span>
                        <span class="sidebar-nav-badge"><?= $totalFetched ?></span>
                    </a>

                    <a href="index.php?page=categories" class="sidebar-nav-item <?= $view === 'categories' ? 'active' : '' ?>">
                        <span class="sidebar-nav-icon-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                <line x1="7" y1="7" x2="7.01" y2="7"></line>
                            </svg>
                            <span>Taksonomi & Kategori</span>
                        </span>
                        <span class="sidebar-nav-badge"><?= count($categoriesList) ?></span>
                    </a>

                    <a href="index.php?page=settings" class="sidebar-nav-item <?= $view === 'settings' ? 'active' : '' ?>">
                        <span class="sidebar-nav-icon-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            <span>API Inspector</span>
                        </span>
                        <span class="sidebar-nav-badge"><?= $apiHealth['http_code'] ?></span>
                    </a>
                </div>
            </div>

            <div class="sidebar-footer">
                <div class="endpoint-status-card">
                    <div class="endpoint-status-header">
                        <span>Backend Status</span>
                        <div class="live-indicator">
                            <span class="status-dot <?= $apiHealth['status'] === 'connected' ? '' : 'error' ?>"></span>
                            <span><?= $apiHealth['status'] === 'connected' ? 'ONLINE' : 'ERROR' ?></span>
                        </div>
                    </div>
                    <span class="endpoint-url-truncate" title="<?= htmlspecialchars(API_ENDPOINT_URL) ?>">
                        <?= htmlspecialchars(API_ENDPOINT_URL) ?>
                    </span>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Backdrop Overlay -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Main Workspace -->
        <div class="main-area">
            <!-- Top App Bar -->
            <header class="top-navbar">
                <div class="navbar-left-group">
                    <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Buka Menu Navigasi">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>

                    <nav class="navbar-breadcrumbs" aria-label="Breadcrumb">
                        <a href="index.php">Dashboard</a>
                        <span class="navbar-separator">/</span>
                        <span class="navbar-current">
                            <?= $view === 'categories' ? 'Taksonomi' : ($view === 'settings' ? 'API Inspector' : 'Semua Post') ?>
                        </span>
                    </nav>
                </div>

                <div class="navbar-actions">
                    <a href="<?= htmlspecialchars(API_ENDPOINT_URL) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline" title="Buka endpoint JSON asli di tab baru">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <span class="nav-action-text">Raw Endpoint</span>
                    </a>
                    <a href="index.php?page=<?= urlencode($view) ?>" class="btn btn-sm btn-secondary" title="Muat ulang data dari endpoint">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                        <span class="nav-action-text">Refresh</span>
                    </a>
                </div>
            </header>

            <!-- Main Content Container -->
            <main class="page-content">

                <?php if ($view === 'dashboard') : ?>
                    <!-- Dashboard Header -->
                    <section class="dashboard-header">
                        <h1 class="dashboard-heading">Content Explorer</h1>
                        <p class="dashboard-description">
                            Pemantauan dan visualisasi data REST API dari <code class="mono"><?= htmlspecialchars(API_ENDPOINT_URL) ?></code>.
                        </p>
                    </section>

                    <!-- Realtime Metrics Grid -->
                    <section class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-title">Total Post Ditemukan</span>
                                <div class="metric-icon-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value"><?= $totalFetched ?></div>
                            <span class="metric-sub"><?= $totalItems !== $totalFetched ? "Hasil Filter: {$totalItems} post" : "Semua item aktif" ?></span>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-title">Status REST API</span>
                                <div class="metric-icon-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value">
                                <span class="badge badge-success"><?= $apiHealth['http_code'] ?> OK</span>
                            </div>
                            <span class="metric-sub">Latensi ~<?= $apiHealth['latency_ms'] ?> ms</span>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-title">Kategori Taksonomi</span>
                                <div class="metric-icon-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value"><?= count($categoriesList) ?></div>
                            <span class="metric-sub"><?= count($tagsList) ?> tags terindeks</span>
                        </div>

                        <div class="metric-card">
                            <div class="metric-header">
                                <span class="metric-title">Protokol Backend</span>
                                <div class="metric-icon-wrap">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                                        <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                                        <line x1="6" y1="6" x2="6.01" y2="6"></line>
                                        <line x1="6" y1="18" x2="6.01" y2="18"></line>
                                    </svg>
                                </div>
                            </div>
                            <div class="metric-value">WordPress</div>
                            <span class="metric-sub">WP REST API v2</span>
                        </div>
                    </section>

                    <!-- Filter Toolbar -->
                    <section class="action-toolbar">
                        <form method="GET" action="index.php" class="toolbar-search-form" id="searchForm">
                            <input type="hidden" name="page" value="dashboard">
                            <input type="hidden" name="layout" value="<?= htmlspecialchars($layout) ?>">
                            <?php if (!empty($selectedCategory)) : ?>
                                <input type="hidden" name="cat" value="<?= htmlspecialchars($selectedCategory) ?>">
                            <?php endif; ?>

                            <span class="search-icon-adornment">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </span>
                            <input type="text" name="search" class="search-input-field" placeholder="Cari judul artikel atau konten..." value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off" id="searchInput">
                            <span class="search-kbd">/</span>
                        </form>

                        <div class="toolbar-controls">
                            <?php if (!empty($searchQuery) || !empty($selectedCategory)) : ?>
                                <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>" class="btn btn-outline btn-sm">
                                    Reset Filter
                                </a>
                            <?php endif; ?>

                            <!-- Layout Switcher (Grid vs Table) -->
                            <div class="view-toggle-group" role="group" aria-label="Layout toggle">
                                <a href="index.php?page=dashboard&layout=grid&search=<?= urlencode($searchQuery) ?>&cat=<?= urlencode($selectedCategory) ?>&p=<?= $currentPage ?>" class="view-toggle-btn <?= $layout === 'grid' ? 'active' : '' ?>" title="Tampilan Card Grid">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                    <span>Grid</span>
                                </a>
                                <a href="index.php?page=dashboard&layout=table&search=<?= urlencode($searchQuery) ?>&cat=<?= urlencode($selectedCategory) ?>&p=<?= $currentPage ?>" class="view-toggle-btn <?= $layout === 'table' ? 'active' : '' ?>" title="Tampilan Tabel Enterprise">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                    </svg>
                                    <span>Tabel</span>
                                </a>
                            </div>
                        </div>
                    </section>

                    <!-- Category Pills Horizontal Scroll -->
                    <?php if (!empty($categoriesList)) : ?>
                        <nav class="tax-filters" aria-label="Kategori artikel">
                            <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>&search=<?= urlencode($searchQuery) ?>" class="tax-chip <?= empty($selectedCategory) ? 'active' : '' ?>">
                                Semua Kategori (<?= $totalFetched ?>)
                            </a>
                            <?php foreach ($categoriesList as $cat) : ?>
                                <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>&cat=<?= urlencode($cat['slug']) ?>&search=<?= urlencode($searchQuery) ?>" class="tax-chip <?= $selectedCategory === $cat['slug'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?> (<?= $cat['count'] ?>)
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>

                    <!-- Articles Display -->
                    <?php if (!empty($paginatedArticles)) : ?>
                        <?php if ($layout === 'table') : ?>
                            <!-- Table View -->
                            <div class="data-table-wrapper">
                                <table class="enterprise-table">
                                    <thead>
                                        <tr>
                                            <th>ID Post</th>
                                            <th>Judul & Excerpt</th>
                                            <th>Penulis</th>
                                            <th>Status</th>
                                            <th>Tanggal Publish</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paginatedArticles as $item) :
                                            $id = $item['id'] ?? '-';
                                            $title = $item['title']['rendered'] ?? 'Tanpa Judul';
                                            $excerpt = clean_excerpt($item['excerpt']['rendered'] ?? '', 100);
                                            $author = $item['author'] ?? '29';
                                            $commentStatus = $item['comment_status'] ?? 'closed';
                                            $date = format_date($item['date'] ?? '');
                                        ?>
                                            <tr>
                                                <td><span class="table-id-pill">#<?= htmlspecialchars($id) ?></span></td>
                                                <td>
                                                    <div class="table-post-info">
                                                        <a href="detail.php?id=<?= urlencode($id) ?>" class="table-row-title">
                                                            <?= htmlspecialchars($title) ?>
                                                        </a>
                                                        <span class="table-row-excerpt"><?= htmlspecialchars($excerpt) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="table-author-cell">
                                                        <div class="avatar-mini">A</div>
                                                        <span class="mono text-muted">ID: <?= htmlspecialchars($author) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge"><?= htmlspecialchars(strtoupper($commentStatus)) ?></span>
                                                </td>
                                                <td>
                                                    <span class="mono text-muted"><?= htmlspecialchars($date) ?></span>
                                                </td>
                                                <td>
                                                    <a href="detail.php?id=<?= urlencode($id) ?>" class="btn btn-sm btn-outline">
                                                        Detail →
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <!-- Card Grid View -->
                            <div class="articles-grid-layout">
                                <?php foreach ($paginatedArticles as $item) :
                                    $id = $item['id'] ?? '-';
                                    $title = $item['title']['rendered'] ?? 'Tanpa Judul';
                                    $excerpt = clean_excerpt($item['excerpt']['rendered'] ?? '', 140);
                                    $date = format_date($item['date'] ?? '');
                                    $featuredImage = extract_featured_image($item);
                                    $classList = $item['class_list'] ?? [];
                                ?>
                                    <article class="article-post-card">
                                        <div class="card-media-banner">
                                            <span class="card-floating-id">#<?= htmlspecialchars($id) ?></span>
                                            <?php if (!empty($featuredImage)) : ?>
                                                <img src="<?= htmlspecialchars($featuredImage) ?>" alt="<?= htmlspecialchars($title) ?>" class="card-media-img" loading="lazy">
                                            <?php else : ?>
                                                <div class="card-media-placeholder">
                                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                                        <polyline points="21 15 16 10 5 21"></polyline>
                                                    </svg>
                                                    <span class="badge badge-mono">REST API Post</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body-content">
                                            <h2 class="card-article-title">
                                                <a href="detail.php?id=<?= urlencode($id) ?>">
                                                    <?= htmlspecialchars($title) ?>
                                                </a>
                                            </h2>

                                            <p class="card-article-excerpt">
                                                <?= htmlspecialchars($excerpt) ?>
                                            </p>

                                            <?php if (!empty($classList)) : ?>
                                                <div class="card-tag-row">
                                                    <?php 
                                                    $tagCount = 0;
                                                    foreach ($classList as $cls) : 
                                                        if (strpos($cls, 'tag-') === 0 && $tagCount < 3) : 
                                                            $tagName = str_replace('tag-', '', $cls);
                                                            $tagCount++;
                                                    ?>
                                                        <span class="badge badge-outline">#<?= htmlspecialchars($tagName) ?></span>
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-footer-meta">
                                            <div class="meta-time-text">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                </svg>
                                                <span><?= htmlspecialchars($date) ?></span>
                                            </div>
                                            <a href="detail.php?id=<?= urlencode($id) ?>" class="btn btn-sm btn-outline">
                                                Baca →
                                            </a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pagination Shelf -->
                        <?php if ($totalPages > 1) : ?>
                            <nav class="pagination-shelf" aria-label="Halaman navigasi">
                                <span class="pagination-entries-info">
                                    Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?> dari <?= $totalItems ?> artikel
                                </span>

                                <div class="pagination-button-cluster">
                                    <?php if ($currentPage > 1) : ?>
                                        <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>&search=<?= urlencode($searchQuery) ?>&cat=<?= urlencode($selectedCategory) ?>&p=<?= $currentPage - 1 ?>" class="pagination-nav-btn" title="Halaman Sebelumnya">
                                            ←
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                                        <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>&search=<?= urlencode($searchQuery) ?>&cat=<?= urlencode($selectedCategory) ?>&p=<?= $i ?>" class="pagination-nav-btn <?= $i === $currentPage ? 'active' : '' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($currentPage < $totalPages) : ?>
                                        <a href="index.php?page=dashboard&layout=<?= urlencode($layout) ?>&search=<?= urlencode($searchQuery) ?>&cat=<?= urlencode($selectedCategory) ?>&p=<?= $currentPage + 1 ?>" class="pagination-nav-btn" title="Halaman Selanjutnya">
                                            →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>

                    <?php else : ?>
                        <!-- Empty Search Results or API Unreachable -->
                        <div class="empty-data-state">
                            <div class="empty-state-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </div>
                            <h2 class="empty-state-title">Tidak Ada Artikel Ditemukan</h2>
                            <p class="empty-state-desc">
                                <?= !empty($searchQuery) || !empty($selectedCategory) 
                                    ? 'Tidak ada data artikel yang sesuai dengan filter pencarian Anda.' 
                                    : 'Tidak dapat memuat artikel dari endpoint backend. Pastikan URL endpoint valid dan server dapat diakses.' ?>
                            </p>
                            <div class="flex-row-center">
                                <a href="index.php?page=dashboard" class="btn btn-outline">
                                    Reset Semua Filter
                                </a>
                                <a href="index.php?page=settings" class="btn btn-secondary">
                                    Cek Pengaturan API
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($view === 'categories') : ?>
                    <!-- Categories & Taxonomy View -->
                    <section class="dashboard-header">
                        <h1 class="dashboard-heading">Taksonomi & Kategori</h1>
                        <p class="dashboard-description">
                            Klasifikasi kategori dan tag yang diekstrak secara otomatis dari respon REST API backend.
                        </p>
                    </section>

                    <div class="settings-grid">
                        <div class="config-card">
                            <div class="config-card-header">
                                <h2 class="config-card-title">Daftar Kategori Terdeteksi</h2>
                                <span class="badge"><?= count($categoriesList) ?> Kategori</span>
                            </div>

                            <div class="data-table-wrapper">
                                <table class="enterprise-table">
                                    <thead>
                                        <tr>
                                            <th>Nama Kategori</th>
                                            <th>Slug</th>
                                            <th>Jumlah Post</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categoriesList)) : ?>
                                            <?php foreach ($categoriesList as $cat) : ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                                    <td><code class="mono"><?= htmlspecialchars($cat['slug']) ?></code></td>
                                                    <td><span class="badge"><?= $cat['count'] ?> post</span></td>
                                                    <td>
                                                        <a href="index.php?page=dashboard&cat=<?= urlencode($cat['slug']) ?>" class="btn btn-sm btn-outline">
                                                            Filter Post →
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada taksonomi kategori terdeteksi.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="config-card">
                            <div class="config-card-header">
                                <h2 class="config-card-title">Daftar Tag Populer</h2>
                                <span class="badge"><?= count($tagsList) ?> Tags</span>
                            </div>

                            <div class="card-tag-row">
                                <?php if (!empty($tagsList)) : ?>
                                    <?php foreach ($tagsList as $tg) : ?>
                                        <a href="index.php?page=dashboard&search=<?= urlencode($tg['name']) ?>" class="badge badge-outline">
                                            #<?= htmlspecialchars($tg['name']) ?> (<?= $tg['count'] ?>)
                                        </a>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <span class="text-muted">Tidak ada tag.</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($view === 'settings') : ?>
                    <!-- Settings & API Inspector View -->
                    <section class="dashboard-header">
                        <h1 class="dashboard-heading">API Inspector & Konfigurasi</h1>
                        <p class="dashboard-description">
                            Status koneksi langsung, latensi respon, dan informasi teknis endpoint REST API.
                        </p>
                    </section>

                    <div class="settings-grid">
                        <div class="config-card">
                            <div class="config-card-header">
                                <h2 class="config-card-title">Konfigurasi Endpoint</h2>
                                <span class="badge badge-success"><?= htmlspecialchars(APP_ENV) ?></span>
                            </div>

                            <div class="form-group-block">
                                <label class="form-label-text">API Endpoint URL (Base)</label>
                                <input type="text" class="form-control-input" value="<?= htmlspecialchars(API_ENDPOINT_URL) ?>" readonly id="endpointUrlInput">
                            </div>

                            <div class="form-group-block">
                                <label class="form-label-text">Status Koneksi Server</label>
                                <div class="status-pill-row">
                                    <span class="status-dot <?= $apiHealth['status'] === 'connected' ? '' : 'error' ?>"></span>
                                    <span><strong><?= $apiHealth['http_code'] ?> OK</strong> — Terhubung ke backend</span>
                                </div>
                            </div>

                            <div class="form-group-block">
                                <label class="form-label-text">Response Time (Latency)</label>
                                <span class="mono text-muted">~<?= $apiHealth['latency_ms'] ?> milidetik</span>
                            </div>

                            <div class="flex-row-center">
                                <a href="<?= htmlspecialchars(API_ENDPOINT_URL) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline">
                                    Buka Endpoint Langsung ↗
                                </a>
                            </div>
                        </div>

                        <div class="config-card">
                            <div class="config-card-header">
                                <h2 class="config-card-title">Contoh Perintah cURL</h2>
                            </div>
                            <p class="text-muted">Gunakan perintah berikut di terminal untuk menguji endpoint secara manual:</p>
                            <pre class="prose-dark"><code class="mono">curl -X GET "<?= htmlspecialchars(API_ENDPOINT_URL) ?>" \
  -H "Accept: application/json"</code></pre>
                        </div>
                    </div>

                    <!-- Raw Response Inspector -->
                    <div class="raw-json-inspector-box">
                        <div class="json-inspector-header">
                            <span class="json-inspector-title">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="16 18 22 12 16 6"></polyline>
                                    <polyline points="8 6 2 12 8 18"></polyline>
                                </svg>
                                Respon JSON Mentah dari Endpoint (Sampel 1 Item Pertama)
                            </span>
                        </div>
                        <pre class="json-code-display"><?= htmlspecialchars(json_encode(!empty($allArticles[0]) ? $allArticles[0] : ['status' => 'no_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>

                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Scripts: Mobile Navigation & Keyboard Shortcuts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileNavToggle = document.getElementById('mobileNavToggle');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const appSidebar = document.getElementById('appSidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            if (appSidebar) appSidebar.classList.add('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
            document.body.classList.add('sidebar-locked');
        }

        function closeSidebar() {
            if (appSidebar) appSidebar.classList.remove('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
            document.body.classList.remove('sidebar-locked');
        }

        if (mobileNavToggle) mobileNavToggle.addEventListener('click', openSidebar);
        if (sidebarCloseBtn) sidebarCloseBtn.addEventListener('click', closeSidebar);
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function(e) {
            // Close mobile menu on Escape
            if (e.key === 'Escape') {
                closeSidebar();
            }

            // Quick focus search on '/'
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });
    });
    </script>
</body>

</html>