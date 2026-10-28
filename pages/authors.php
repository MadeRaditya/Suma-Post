<?php
require_once __DIR__ . '/../includes/helpers.php';

$currentPage = max(1, intval($_GET['p'] ?? 1));
$searchQuery = trim($_GET['search'] ?? '');

$params = ['per_page' => 18, 'page' => $currentPage];
if (!empty($searchQuery)) {
    $params['search'] = $searchQuery;
}

$totalPages = 0;
$totalItems = 0;
$authors    = get_authors($params, $totalPages, $totalItems);
$currentPage = min($currentPage, max(1, $totalPages ?: 1));

/**
 * Generate a Gravatar URL from an email or WP avatar_urls.
 */
function get_author_avatar(array $user, int $size = 80): string
{
    // WP REST API provides avatar_urls
    if (!empty($user['avatar_urls'][$size])) return $user['avatar_urls'][$size];
    if (!empty($user['avatar_urls'][96]))  return $user['avatar_urls'][96];
    if (!empty($user['avatar_urls'][48]))  return $user['avatar_urls'][48];
    // Fallback: letter avatar via UI Avatars
    $name = urlencode($user['name'] ?? 'U');
    return "https://ui-avatars.com/api/?name={$name}&size={$size}&background=dbeafe&color=2563eb&bold=true";
}

render_site_header('Penulis', 'authors');
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda', 'url' => home_url()],
            ['label' => 'Penulis'],
        ]); ?>

        <?php render_page_hero(
            'Tim Penulis',
            'Kenali ' . number_format($totalItems) . ' kontributor di balik artikel-artikel ' . APP_NAME . '.',
        ); ?>

        <!-- Search -->
        <form method="GET" action="" style="display:flex;gap:0.5rem;margin-bottom:1.75rem;">
            <input type="text" name="search" placeholder="Cari penulis…"
                value="<?= htmlspecialchars($searchQuery) ?>"
                class="search-page-field" style="max-width:22rem;">
            <button type="submit" class="search-page-submit">Cari</button>
            <?php if (!empty($searchQuery)) : ?>
                <a href="<?= page_url('authors.php') ?>" class="btn-clear-search">Reset</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($authors)) : ?>

            <div class="authors-grid">
                <?php foreach ($authors as $author) :
                    $avatarUrl   = get_author_avatar($author, 80);
                    $bio         = strip_tags($author['description'] ?? '');
                    $postCount   = $author['post_count'] ?? ($author['count'] ?? 0);
                ?>
                    <div class="author-card">
                        <img src="<?= htmlspecialchars($avatarUrl) ?>"
                            alt="Avatar <?= htmlspecialchars($author['name']) ?>"
                            class="author-avatar"
                            loading="lazy"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($author['name'] ?? 'U') ?>&size=80&background=dbeafe&color=2563eb&bold=true'">
                        <div class="author-info">
                            <div class="author-name"><?= htmlspecialchars($author['name']) ?></div>
                            <div class="author-meta-row">
                                <?php if ($postCount > 0) : ?>
                                    <span class="author-post-count"><?= $postCount ?> Artikel</span>
                                <?php endif; ?>
                                <?php if (!empty($author['slug'])) : ?>
                                    <span class="author-slug">@<?= htmlspecialchars($author['slug']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($bio)) : ?>
                                <p class="author-bio"><?= htmlspecialchars($bio) ?></p>
                            <?php endif; ?>
                            <div class="author-link-row">
                                <a href="<?= home_url(['author_id' => (int) $author['id']]) ?>"
                                    style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;color:var(--ink-500);">
                                    Lihat Artikel →
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php render_pagination(
                $currentPage,
                $totalPages,
                $totalItems,
                count($authors),
                fn($p) => page_url('authors.php', array_filter(['p' => $p, 'search' => $searchQuery]))
            ); ?>

        <?php else : ?>
            <?php render_empty_state('Tidak Ada Penulis', 'Belum ada penulis yang tersedia.', 'Kembali ke Beranda', home_url()); ?>
        <?php endif; ?>

    </div>
</main>

<?php render_site_footer(); ?>