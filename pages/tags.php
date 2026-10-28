<?php
require_once __DIR__ . '/../includes/helpers.php';

$currentPage = max(1, intval($_GET['p'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');
$viewMode    = in_array($_GET['view'] ?? 'cloud', ['cloud', 'list']) ? ($_GET['view'] ?? 'cloud') : 'cloud';

$params = ['per_page' => 100, 'page' => $currentPage, 'hide_empty' => 'true', 'orderby' => 'count', 'order' => 'desc'];
if (!empty($searchQuery)) {
    $params['search']     = $searchQuery;
    $params['hide_empty'] = 'false';
}

$totalPages = 0;
$totalItems = 0;
$tags       = get_tags($params, $totalPages, $totalItems);
$currentPage = min($currentPage, max(1, $totalPages ?: 1));

// Compute size scale (1–5) for tag cloud based on count
$maxCount = max(array_column($tags, 'count') ?: [1]);
$minCount = min(array_column($tags, 'count') ?: [1]);

function tag_size(int $count, int $min, int $max): int
{
    if ($max === $min) return 3;
    return (int) round(1 + (($count - $min) / ($max - $min)) * 4);
}

render_site_header('Tag Berita', 'tags');
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda', 'url' => home_url()],
            ['label' => 'Tag'],
        ]); ?>

        <?php render_page_hero(
            'Tag Berita',
            number_format($totalItems) . ' tag tersedia di ' . APP_NAME . '. Klik tag untuk menemukan artikel terkait.',
        ); ?>

        <!-- Controls bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <!-- Search -->
            <form method="GET" action="" style="display:flex;gap:0.5rem;align-items:center;">
                <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
                <input type="text" name="search" placeholder="Cari tag…"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    class="search-page-field" style="max-width:16rem;height:2.25rem;">
                <button type="submit" class="search-page-submit" style="height:2.25rem;">Cari</button>
                <?php if (!empty($searchQuery)) : ?>
                    <a href="<?= page_url('tags.php', ['view' => $viewMode]) ?>" class="btn-clear-search">Reset</a>
                <?php endif; ?>
            </form>

            <!-- View toggle -->
            <div class="media-filter-bar" style="margin-bottom:0;">
                <a href="<?= page_url('tags.php', array_filter(['search' => $searchQuery, 'view' => 'cloud'])) ?>"
                    class="media-filter-btn<?= $viewMode === 'cloud' ? ' active' : '' ?>">Cloud</a>
                <a href="<?= page_url('tags.php', array_filter(['search' => $searchQuery, 'view' => 'list'])) ?>"
                    class="media-filter-btn<?= $viewMode === 'list' ? ' active' : '' ?>">Daftar</a>
            </div>
        </div>

        <?php if (!empty($tags)) : ?>

            <?php if ($viewMode === 'cloud') : ?>
                <div class="tags-cloud-wrapper">
                    <?php foreach ($tags as $tag) :
                        $size    = tag_size((int) $tag['count'], $minCount, $maxCount);

                        $tagUrl = home_url(['tag_id' => (int) $tag['id']]);
                    ?>
                        <a href="<?= htmlspecialchars($tagUrl) ?>"
                            data-size="<?= $size ?>"
                            class="tag-cloud-item"
                            title="<?= htmlspecialchars($tag['name']) ?> (<?= $tag['count'] ?> artikel)"
                            style="font-size:<?= ['.75rem', '.825rem', '.9375rem', '1.0625rem', '1.25rem'][$size - 1] ?>">
                            # <?= htmlspecialchars($tag['name']) ?>
                            <span class="tag-count-badge"><?= $tag['count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>
                <div class="tags-list-grid">
                    <?php foreach ($tags as $tag) :
                        $tagUrl = home_url([
                            'tag_id' => (int) $tag['id']
                        ]);
                    ?>
                        <a href="<?= htmlspecialchars($tagUrl) ?>" class="tag-list-card">
                            <span class="tag-list-name"># <?= htmlspecialchars($tag['name']) ?></span>
                            <span class="cat-card-count"><?= $tag['count'] ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php render_pagination(
                $currentPage,
                $totalPages,
                $totalItems,
                count($tags),
                fn($p) => page_url('tags.php', array_filter(['p' => $p, 'search' => $searchQuery, 'view' => $viewMode]))
            ); ?>

        <?php else : ?>
            <?php render_empty_state('Tidak Ada Tag', 'Belum ada tag yang tersedia.', 'Kembali ke Beranda', home_url()); ?>
        <?php endif; ?>

    </div>
</main>

<?php render_site_footer(); ?>