<?php
require_once __DIR__ . '/../includes/helpers.php';

$currentPage = max(1, intval($_GET['p'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');

// Build params
$params = ['per_page' => 24, 'page' => $currentPage, 'orderby' => 'count', 'order' => 'desc'];
if (!empty($searchQuery)) {
    $params['search'] = $searchQuery;
    // When searching, don't enforce hide_empty
    $params['hide_empty'] = 'false';
}

$totalPages = 0;
$totalItems = 0;
$categories = get_categories($params, $totalPages, $totalItems);
$currentPage = min($currentPage, max(1, $totalPages ?: 1));

render_site_header('Semua Kategori', 'categories');
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda', 'url' => home_url()],
            ['label' => 'Kategori'],
        ]); ?>

        <?php render_page_hero(
            'Kategori Berita',
            'Jelajahi ' . number_format($totalItems) . ' kategori dari seluruh topik berita ' . APP_NAME . '.',

        ); ?>

        <!-- Search bar -->
        <form method="GET" action="" class="search-feedback-banner" style="margin-bottom:1.75rem;">
            <div class="search-feedback-text">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>

                <input
                    type="text"
                    name="search"
                    placeholder="Cari kategori…"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    class="search-page-field"
                    style="max-width:20rem;height:2.25rem;">
            </div>

            <div style="display:flex;align-items:center;gap:0.5rem;">
                <?php if (!empty($searchQuery)) : ?>
                    <a href="<?= page_url('categories.php') ?>" class="btn-clear-search">
                        Reset ×
                    </a>
                <?php endif; ?>

                <button type="submit" class="search-page-submit" style="height:2.25rem;">
                    Cari
                </button>
            </div>
        </form>


        <?php if (!empty($categories)) : ?>

            <div class="categories-grid">
                <?php foreach ($categories as $cat) :
                    $desc   = strip_tags($cat['description'] ?? '');
                    $catUrl = home_url(['cat' => $cat['slug']]);
                ?>
                    <a href="<?= htmlspecialchars($catUrl) ?>" class="category-card">
                        <div class="cat-card-header">
                            <span class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></span>
                            <span class="cat-card-count"><?= number_format($cat['count']) ?> artikel</span>
                        </div>
                        <?php if (!empty($desc)) : ?>
                            <p class="cat-card-desc"><?= htmlspecialchars($desc) ?></p>
                        <?php endif; ?>
                        <span class="cat-card-cta">
                            Lihat Artikel
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12" />
                                <polyline points="12 5 19 12 12 19" />
                            </svg>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php render_pagination(
                $currentPage,
                $totalPages,
                $totalItems,
                count($categories),
                fn($p) => page_url('categories.php', array_filter(['p' => $p, 'search' => $searchQuery]))
            ); ?>

        <?php else : ?>
            <?php render_empty_state('Tidak Ada Kategori', 'Belum ada kategori yang tersedia.', 'Kembali ke Beranda', home_url()); ?>
        <?php endif; ?>

    </div>
</main>

<?php render_site_footer(); ?>