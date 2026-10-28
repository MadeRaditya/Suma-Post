<?php
require_once __DIR__ . '/../includes/helpers.php';

// Accept ?id=123 or ?slug=nama-artikel
$postId   = intval($_GET['id'] ?? 0);
$postSlug = trim($_GET['slug'] ?? '');

$post = null;
if ($postId > 0) {
    $post = get_post_by_id($postId);
} elseif (!empty($postSlug)) {
    $post = get_post_by_slug($postSlug);
}

// ── 404 if not found
if (!$post) {
    http_response_code(404);
    render_site_header('Artikel Tidak Ditemukan', 'home');
    echo '<main class="main-content-layout"><div class="news-container">';
    render_empty_state(
        'Artikel Tidak Ditemukan',
        'Artikel yang kamu cari tidak tersedia atau telah dihapus.',
        'Kembali ke Beranda',
        home_url()
    );
    echo '</div></main>';
    render_site_footer();
    exit;
}

// ── Extract post data
$postTitle    = html_entity_decode($post['title']['rendered'] ?? 'Tanpa Judul', ENT_QUOTES, 'UTF-8');
$postContent  = $post['content']['rendered'] ?? '';
$postExcerpt  = clean_excerpt($post['excerpt']['rendered'] ?? '', 160);
$postDate     = format_date_long($post['date'] ?? '');
$postModified = format_date($post['modified'] ?? '');
$postAuthor   = extract_post_author($post);
$postCat      = extract_post_category($post);
$postCatSlug  = extract_post_category_slug($post);
$featuredImg  = extract_featured_image($post);
$originalUrl  = $post['link'] ?? '#';

// Author avatar
$authorAvatar = $post['_embedded']['author'][0]['avatar_urls'][96]
    ?? $post['_embedded']['author'][0]['avatar_urls'][48]
    ?? null;
if (!$authorAvatar) {
    $authorAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($postAuthor) . '&size=80&background=dbeafe&color=2563eb&bold=true';
}

// Tags from embedded wp:term[1]
$postTags = [];
if (!empty($post['_embedded']['wp:term'][1])) {
    foreach ($post['_embedded']['wp:term'][1] as $tag) {
        if (!empty($tag['name'])) {
            $postTags[] = $tag;
        }
    }
}

// ── Related posts (same category, exclude current)
$relatedPosts = [];
if (!empty($postCatSlug)) {
    $catId = resolve_slug_to_id('categories', $postCatSlug);
    if ($catId) {
        $relatedPosts = get_posts([
            'categories' => $catId,
            'exclude'    => $post['id'],
            'per_page'   => 5,
            '_embed'     => '1',
        ]);
    }
}

// ── Sidebar categories (top 10)
$sidebarCats = get_nav_categories();
$sidebarCats = array_slice($sidebarCats, 0, 10);

render_site_header($postTitle, 'home', $postCatSlug);
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda',          'url' => home_url()],
            ['label' => $postCat,           'url' => home_url(['cat' => $postCatSlug])],
            ['label' => mb_substr($postTitle, 0, 40) . (mb_strlen($postTitle) > 40 ? '…' : '')],
        ]); ?>

        <div class="post-detail-layout">

            <!-- ── Main Article -->
            <article class="post-detail-main">

                <!-- Back button -->
                <a href="javascript:history.back()" class="btn-back">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12" />
                        <polyline points="12 19 5 12 12 5" />
                    </svg>
                    Kembali
                </a>

                <!-- Category + Meta -->
                <div class="post-detail-meta">
                    <?php if (!empty($postCat)) : ?>
                        <a href="<?= home_url(['cat' => $postCatSlug]) ?>" class="post-cat-badge">
                            <?= htmlspecialchars($postCat) ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Title -->
                <h1 class="post-detail-title"><?= htmlspecialchars($postTitle) ?></h1>

                <!-- Byline -->
                <div class="post-detail-byline">
                    <div class="byline-author">
                        <img src="<?= htmlspecialchars($authorAvatar) ?>"
                            alt="Avatar <?= htmlspecialchars($postAuthor) ?>"
                            class="byline-avatar"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($postAuthor) ?>&size=80&background=dbeafe&color=2563eb'">
                        <div>
                            <div class="byline-name"><?= htmlspecialchars($postAuthor) ?></div>
                            <div class="byline-date"><?= htmlspecialchars($postDate) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <?php if ($featuredImg) : ?>
                    <img src="<?= htmlspecialchars($featuredImg) ?>"
                        alt="<?= htmlspecialchars($postTitle) ?>"
                        class="post-detail-feat-img"
                        loading="eager">
                <?php endif; ?>

                <!-- Post Content -->
                <div class="post-content">
                    <?= $postContent ?>
                </div>

                <!-- Tags + Footer Meta -->
                <?php if (!empty($postTags) || !empty($postModified)) : ?>
                    <div class="post-footer-meta">
                        <?php if (!empty($postTags)) : ?>
                            <div class="post-tags-row">
                                <span style="font-size:.8rem;font-weight:600;color:var(--ink-500);margin-right:.25rem;">Tag:</span>
                                <?php foreach ($postTags as $tag) : ?>
                                    <a href="<?= home_url(['tag_id' => (int) $tag['id']]) ?>"
                                        class="post-tag-link">
                                        # <?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($postModified)) : ?>
                            <span style="font-size:.75rem;color:var(--ink-400);white-space:nowrap;">
                                Diperbarui: <?= htmlspecialchars($postModified) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </article>

            <!-- ── Sidebar -->
            <aside class="post-detail-sidebar">

                <!-- Related Posts Widget -->
                <?php if (!empty($relatedPosts)) : ?>
                    <div class="sidebar-widget">
                        <div class="sidebar-widget-header">Artikel Terkait</div>
                        <div class="sidebar-widget-body">
                            <div class="related-posts-list">
                                <?php foreach ($relatedPosts as $rel) :
                                    $relTitle = html_entity_decode($rel['title']['rendered'] ?? '', ENT_QUOTES, 'UTF-8');
                                    $relImg   = extract_featured_image($rel) ?: '';
                                    $relDate  = format_date($rel['date'] ?? '');
                                    $relUrl   = page_url('post-detail.php', ['id' => $rel['id']]);
                                ?>
                                    <a href="<?= htmlspecialchars($relUrl) ?>" class="related-post-item">
                                        <?php if ($relImg) : ?>
                                            <img src="<?= htmlspecialchars($relImg) ?>"
                                                alt="<?= htmlspecialchars($relTitle) ?>"
                                                class="related-post-img" loading="lazy">
                                        <?php else : ?>
                                            <div class="related-post-img"
                                                style="background:var(--blue-50);display:flex;align-items:center;justify-content:center;color:var(--blue-400);font-size:1.25rem;">📰</div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="related-post-title"><?= htmlspecialchars($relTitle) ?></div>
                                            <div class="related-post-date"><?= htmlspecialchars($relDate) ?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Categories Widget -->
                <?php if (!empty($sidebarCats)) : ?>
                    <div class="sidebar-widget">
                        <div class="sidebar-widget-header">Kategori</div>
                        <div class="sidebar-widget-body">
                            <ul class="sidebar-cat-list">
                                <?php foreach ($sidebarCats as $cat) : ?>
                                    <li class="sidebar-cat-item">
                                        <a href="<?= home_url(['cat' => $cat['slug']]) ?>">
                                            <span><?= htmlspecialchars($cat['name']) ?></span>
                                            <span class="sidebar-cat-count"><?= $cat['count'] ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Info Widget -->
                <div class="sidebar-widget">
                    <div class="sidebar-widget-header">Info Artikel</div>
                    <div class="sidebar-widget-body" style="display:flex;flex-direction:column;gap:.75rem;">
                        <div style="font-size:.8rem;display:flex;flex-direction:column;gap:.375rem;">
                            <span style="color:var(--ink-400);font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Penulis</span>
                            <span style="color:var(--ink-800);font-weight:600;"><?= htmlspecialchars($postAuthor) ?></span>
                        </div>
                        <div style="font-size:.8rem;display:flex;flex-direction:column;gap:.375rem;">
                            <span style="color:var(--ink-400);font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Kategori</span>
                            <a href="<?= home_url(['cat' => $postCatSlug]) ?>"
                                style="color:var(--blue-600);font-weight:600;"><?= htmlspecialchars($postCat) ?></a>
                        </div>
                        <div style="font-size:.8rem;display:flex;flex-direction:column;gap:.375rem;">
                            <span style="color:var(--ink-400);font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Tanggal Publikasi</span>
                            <span style="color:var(--ink-700);"><?= htmlspecialchars($postDate) ?></span>
                        </div>
                        <?php if (!empty($postTags)) : ?>
                            <div style="font-size:.8rem;display:flex;flex-direction:column;gap:.375rem;">
                                <span style="color:var(--ink-400);font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;">Tag</span>
                                <div style="display:flex;flex-wrap:wrap;gap:.375rem;">
                                    <?php foreach (array_slice($postTags, 0, 5) as $tag) : ?>
                                        <a href="<?= home_url(['tag_id' => (int) $tag['id']]) ?>"
                                            class="post-tag-link">
                                            # <?= htmlspecialchars($tag['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </aside>
        </div>

    </div>
</main>

<?php render_site_footer(); ?>