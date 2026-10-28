<?php

/**
 * Shared template helpers — header, footer, pagination, breadcrumbs, etc.
 * All pages include this file after api.php.
 */

require_once __DIR__ . '/api.php';

/* ==========================================================================
   URL HELPERS
   ========================================================================== */

/**
 * Build an absolute URL relative to the app root.
 *
 * @param string $path  e.g. 'pages/categories.php' or 'assets/css/style.css'
 * @return string
 */
function app_url(string $path = ''): string
{
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Build URL to a page file (e.g. 'categories.php' → APP_URL/pages/categories.php).
 *
 * @param string $page    filename inside /pages, e.g. 'categories.php'
 * @param array  $params  Query parameters
 * @return string
 */
function page_url(string $page, array $params = []): string
{
    $base = APP_URL . '/pages/' . ltrim($page, '/');
    return empty($params) ? $base : $base . '?' . http_build_query($params);
}

/**
 * Build URL to the main index page with optional params.
 *
 * @param array $params
 * @return string
 */
function home_url(array $params = []): string
{
    $base = APP_URL . '/index.php';
    return empty($params) ? $base : $base . '?' . http_build_query($params);
}

/* ==========================================================================
   SITE HEADER
   ========================================================================== */

/**
 * Render the shared site header.
 *
 * @param string $pageTitle         Browser <title> suffix
 * @param string $activePage        'home' | 'categories' | 'tags' | 'authors' | 'media' | 'search'
 * @param string $selectedCategory  Slug of the currently active category chip
 * @param string $searchQuery       Pre-fill the search field
 */
function render_site_header(
    string $pageTitle        = '',
    string $activePage       = 'home',
    string $selectedCategory = '',
    string $searchQuery      = ''
): void {
    $navCategories = get_nav_categories();
    $logoSrc       = app_url('assets/images/logosuma_600.webp');
    $searchAction  = home_url();
    $fullTitle     = $pageTitle ? htmlspecialchars($pageTitle) . ' — ' . htmlspecialchars(APP_NAME)
        : htmlspecialchars(APP_NAME) . ' — Portal Berita Modern';

    $navLinks = [
        'home'       => ['label' => 'Beranda',  'url' => home_url()],
        'categories' => ['label' => 'Kategori', 'url' => page_url('categories.php')],
        'tags'       => ['label' => 'Tag',      'url' => page_url('tags.php')],
        'authors'    => ['label' => 'Penulis',  'url' => page_url('authors.php')],
        'media'      => ['label' => 'Media',    'url' => page_url('media.php')],
        'search'     => ['label' => 'Pencarian', 'url' => page_url('search.php')],
    ];
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $fullTitle ?></title>
        <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
        <link rel="stylesheet" href="<?= app_url('assets/css/pages.css') ?>">
    </head>

    <body>

        <header class="site-header">
            <div class="news-container">
                <div class="header-main-row">
                    <!-- Logo -->
                    <a href="<?= home_url() ?>" class="brand-logomark" aria-label="Beranda <?= htmlspecialchars(APP_NAME) ?>">
                        <img src="<?= $logoSrc ?>" alt="Logo <?= htmlspecialchars(APP_NAME) ?>" width="120" height="40">
                    </a>

                    <!-- Primary Navigation -->
                    <nav class="primary-nav" aria-label="Navigasi Utama">
                        <?php foreach ($navLinks as $key => $link) : ?>
                            <a href="<?= $link['url'] ?>"
                                class="primary-nav-link<?= $activePage === $key ? ' active' : '' ?>">
                                <?= $link['label'] ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <!-- Search Form -->
                    <form method="GET" action="<?= $searchAction ?>" class="news-search-form" role="search">
                        <input type="text" name="search" class="search-field-input"
                            placeholder="Cari berita…"
                            value="<?= htmlspecialchars($searchQuery) ?>"
                            autocomplete="off" aria-label="Kata kunci pencarian">
                        <?php if (!empty($searchQuery)) : ?>
                            <a href="<?= home_url(!empty($selectedCategory) ? ['cat' => $selectedCategory] : []) ?>"
                                class="search-clear-btn" title="Hapus pencarian" aria-label="Hapus">×</a>
                        <?php endif; ?>
                        <button type="submit" class="search-btn-icon" aria-label="Cari">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- Category Chip Bar -->
                <?php if (!empty($navCategories)) : ?>
                    <nav class="category-nav-bar" aria-label="Filter Kategori">
                        <a href="<?= home_url() ?>"
                            class="cat-nav-chip<?= (empty($selectedCategory) && $activePage === 'home') ? ' active' : '' ?>">
                            Semua
                        </a>
                        <?php foreach ($navCategories as $cat) : ?>
                            <a href="<?= home_url(['cat' => $cat['slug']]) ?>"
                                class="cat-nav-chip<?= $selectedCategory === $cat['slug'] ? ' active' : '' ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                                <span class="chip-count"><?= $cat['count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </header>
    <?php
}

/* ==========================================================================
   SITE FOOTER
   ========================================================================== */

/**
 * Render the shared site footer and close HTML tags.
 */
function render_site_footer(): void
{
    ?>
        <footer class="site-footer">
            <div class="news-container">
                <div class="footer-inner-row">
                    <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. Hak Cipta Dilindungi.</span>
                    <nav class="footer-nav" aria-label="Footer Navigation">
                        <a href="<?= home_url() ?>">Beranda</a>
                        <a href="<?= page_url('categories.php') ?>">Kategori</a>
                        <a href="<?= page_url('tags.php') ?>">Tag</a>
                        <a href="<?= page_url('authors.php') ?>">Penulis</a>
                        <a href="<?= page_url('media.php') ?>">Media</a>
                        <a href="<?= page_url('search.php') ?>">Pencarian</a>
                    </nav>
                    <span>Portal Berita Digital Terpercaya</span>
                </div>
            </div>
        </footer>
    </body>

    </html>
<?php
}

/* ==========================================================================
   PAGINATION
   ========================================================================== */

/**
 * Render pagination controls.
 *
 * @param int      $currentPage
 * @param int      $totalPages
 * @param int      $totalItems
 * @param int      $shownCount    Number of items on current page
 * @param callable $buildUrl      fn(int $page): string — returns page URL
 */
function render_pagination(
    int      $currentPage,
    int      $totalPages,
    int      $totalItems,
    int      $shownCount,
    callable $buildUrl
): void {
    if ($totalPages <= 1) return;
    $currentPage = max(1, min($currentPage, $totalPages));
?>
    <nav class="pagination-shelf" aria-label="Navigasi Halaman">
        <span class="pagination-meta-info">
            Halaman <?= $currentPage ?> dari <?= $totalPages ?>
            &nbsp;·&nbsp; <?= number_format($totalItems) ?> item
        </span>
        <div class="pagination-cluster">
            <!-- Prev -->
            <a href="<?= $currentPage > 1 ? htmlspecialchars($buildUrl($currentPage - 1)) : '#' ?>"
                class="pagination-nav-btn<?= $currentPage <= 1 ? ' disabled' : '' ?>"
                aria-label="Halaman Sebelumnya">&#8249;</a>

            <!-- Page numbers with ellipsis -->
            <?php
            $range = 2;
            $pages = [];
            for ($p = 1; $p <= $totalPages; $p++) {
                if (
                    $p === 1 || $p === $totalPages ||
                    ($p >= $currentPage - $range && $p <= $currentPage + $range)
                ) {
                    $pages[] = $p;
                }
            }
            $prev = null;
            foreach ($pages as $p) {
                if ($prev !== null && $p - $prev > 1) {
                    echo '<span class="pagination-ellipsis">…</span>';
                }
                $cls = 'pagination-nav-btn' . ($p === $currentPage ? ' active' : '');
                echo '<a href="' . htmlspecialchars($buildUrl($p)) . '" class="' . $cls . '">' . $p . '</a>';
                $prev = $p;
            }
            ?>

            <!-- Next -->
            <a href="<?= $currentPage < $totalPages ? htmlspecialchars($buildUrl($currentPage + 1)) : '#' ?>"
                class="pagination-nav-btn<?= $currentPage >= $totalPages ? ' disabled' : '' ?>"
                aria-label="Halaman Selanjutnya">&#8250;</a>
        </div>
    </nav>
<?php
}

/* ==========================================================================
   BREADCRUMBS
   ========================================================================== */

/**
 * Render breadcrumb trail.
 *
 * @param array $crumbs  [['label' => 'Beranda', 'url' => '...'], ..., ['label' => 'Current']]
 */
function render_breadcrumbs(array $crumbs): void
{
    if (empty($crumbs)) return;
?>
    <nav class="breadcrumb-trail" aria-label="Breadcrumb">
        <?php foreach ($crumbs as $i => $crumb) :
            $isLast = ($i === count($crumbs) - 1);
        ?>
            <?php if (!$isLast && !empty($crumb['url'])) : ?>
                <a href="<?= htmlspecialchars($crumb['url']) ?>" class="bc-link">
                    <?= htmlspecialchars($crumb['label']) ?>
                </a>
                <span class="bc-sep" aria-hidden="true">/</span>
            <?php else : ?>
                <span class="bc-current" aria-current="page">
                    <?= htmlspecialchars($crumb['label']) ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
<?php
}

/* ==========================================================================
   EMPTY STATE
   ========================================================================== */

/**
 * Render a centered empty / no-results state box.
 *
 * @param string $title
 * @param string $desc
 * @param string $btnText
 * @param string $btnUrl
 */
function render_empty_state(
    string $title   = 'Tidak Ada Data',
    string $desc    = '',
    string $btnText = '',
    string $btnUrl  = ''
): void {
?>
    <div class="empty-news-box">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--ink-300)"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
        <h2 class="empty-title"><?= htmlspecialchars($title) ?></h2>
        <?php if ($desc) : ?>
            <p class="empty-desc"><?= htmlspecialchars($desc) ?></p>
        <?php endif; ?>
        <?php if ($btnText && $btnUrl) : ?>
            <a href="<?= htmlspecialchars($btnUrl) ?>" class="btn-reset-filter"><?= htmlspecialchars($btnText) ?></a>
        <?php endif; ?>
    </div>
<?php
}

/* ==========================================================================
   PAGE HERO BANNER
   ========================================================================== */

/**
 * Render a simple page hero/intro banner.
 *
 * @param string $title
 * @param string $subtitle
 * @param string $icon     SVG string or emoji
 */
function render_page_hero(string $title, string $subtitle = '', string $icon = ''): void
{
?>
    <div class="page-hero">
        <?php if ($icon) : ?><div class="page-hero-icon"><?= $icon ?></div><?php endif; ?>
        <h1 class="page-hero-title"><?= htmlspecialchars($title) ?></h1>
        <?php if ($subtitle) : ?>
            <p class="page-hero-subtitle"><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
    </div>
<?php
}
