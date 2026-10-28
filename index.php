<?php
require_once __DIR__ . '/includes/helpers.php';

// ── Request parameters
$searchQuery      = trim($_GET['search'] ?? '');
$selectedCatSlug  = trim($_GET['cat'] ?? '');
$selectedTagId    = max(0, intval($_GET['tag_id'] ?? 0));
$selectedAuthorId = max(0, intval($_GET['author_id'] ?? 0));
$currentPage      = max(1, intval($_GET['p'] ?? 1));

// ── Resolve category slug → ID  (2-step: slug → API → ID)
$selectedCatId   = null;
$selectedCatName = '';
if (!empty($selectedCatSlug)) {
    $selectedCatId = resolve_slug_to_id('categories', $selectedCatSlug);
    // Fetch category name for display
    if ($selectedCatId) {
        $catObj          = get_category_by_id($selectedCatId);
        $selectedCatName = $catObj['name'] ?? ucwords(str_replace('-', ' ', $selectedCatSlug));
    }
}

// ── Determine if hero slider should show
$showSlider = ($currentPage === 1 && empty($searchQuery) && empty($selectedCatSlug) && $selectedTagId === 0 && $selectedAuthorId === 0);

// ── Fetch slider posts (first 4 posts, separate call on page 1)
$sliderArticles = [];
$sliderIds      = [];
if ($showSlider) {
    [$sliderPages, $sliderItems] = [0, 0];
    $sliderArticles = get_posts(['per_page' => 4, 'page' => 1], $sliderPages, $sliderItems);
    $sliderIds      = array_column($sliderArticles, 'id');
}

// ── Build feed parameters
$feedParams = ['per_page' => PER_PAGE, 'page' => $currentPage];

if (!empty($selectedCatId)) {
    $feedParams['categories'] = $selectedCatId;
}

if ($selectedTagId > 0) {
    $feedParams['tags'] = $selectedTagId;
}

if ($selectedAuthorId > 0) {
    $feedParams['author'] = $selectedAuthorId;
}

if (!empty($searchQuery)) {
    $feedParams['search'] = $searchQuery;
}
// Exclude slider posts from feed to prevent duplicates (page 1 only)
if (!empty($sliderIds)) {
    $feedParams['exclude'] = implode(',', $sliderIds);
}

// ── Fetch paginated feed posts
$totalPages      = 0;
$totalItems      = 0;
$paginatedArticles = get_posts($feedParams, $totalPages, $totalItems);
$currentPage       = min($currentPage, max(1, $totalPages ?: 1));

// ── Helper: build URL for pagination
function build_page_url(int $pageNum, string $cat = '', string $search = '', int $tagId = 0, int $authorId = 0): string
{
    $p = ['p' => $pageNum];
    if (!empty($cat))    $p['cat']    = $cat;
    if (!empty($search)) $p['search'] = $search;
    if ($tagId > 0) $p['tag_id'] = $tagId;
    if ($authorId > 0) $p['author_id'] = $authorId;
    return 'index.php?' . http_build_query($p);
}

// ── Render page
$pageTitle = '';
if (!empty($selectedCatName)) {
    $pageTitle = 'Kategori: ' . $selectedCatName;
} elseif (!empty($searchQuery)) {
    $pageTitle = 'Hasil Pencarian: "' . $searchQuery . '"';
}

if ($selectedAuthorId > 0) {
    $authorObj = get_author_by_id($selectedAuthorId);
    $selectedAuthorName = $authorObj['name'] ?? 'Penulis #' . $selectedAuthorId;
}

render_site_header($pageTitle, 'home', $selectedCatSlug, $searchQuery);
?>

<main class="main-content-layout">
    <div class="news-container">
        <!-- Author Active Banner -->
        <?php if ($selectedAuthorId > 0) : ?>
            <div class="search-feedback-banner">
                <div class="search-feedback-text">
                    <span>
                        Artikel oleh:
                        <strong>
                            <?= htmlspecialchars($selectedAuthorName) ?>
                        </strong>
                        · <?= number_format($totalItems) ?> berita ditemukan
                    </span>
                </div>

                <a href="<?= htmlspecialchars(home_url()) ?>"
                    class="btn-clear-search">
                    Hapus Filter
                </a>
            </div>
        <?php endif; ?>

        <!-- Tag Active Banner -->
        <?php if ($selectedTagId > 0) : ?>
            <?php
            $activeTag = get_tags([
                'include'   => [$selectedTagId],
                'hide_empty' => 'false',
                'per_page'  => 1
            ]);
            $activeTagName = $activeTag[0]['name'] ?? 'Tag #' . $selectedTagId;
            ?>

            <div class="search-feedback-banner">
                <div class="search-feedback-text">
                    <span>
                        Artikel dengan tag:
                        <strong># <?= htmlspecialchars($activeTagName) ?></strong>
                        &nbsp;·&nbsp;
                        <?= number_format($totalItems) ?> berita ditemukan
                    </span>
                </div>

                <a href="<?= htmlspecialchars(home_url()) ?>"
                    class="btn-clear-search">
                    Hapus Filter
                </a>
            </div>
        <?php endif; ?>

        <!-- Search / Category Active Banner -->
        <?php if (!empty($searchQuery)) : ?>
            <div class="search-feedback-banner">
                <div class="search-feedback-text">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <span>Hasil untuk: <strong>&ldquo;<?= htmlspecialchars($searchQuery) ?>&rdquo;</strong>
                        &nbsp;·&nbsp; <?= number_format($totalItems) ?> berita ditemukan</span>
                </div>
                <a href="index.php<?= !empty($selectedCatSlug) ? '?cat=' . urlencode($selectedCatSlug) : '' ?>"
                    class="btn-clear-search">
                    Reset <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($sliderArticles) || !empty($paginatedArticles)) : ?>

            <!-- ── Headline Slider (page 1, no filters) -->
            <?php if (!empty($sliderArticles)) : ?>
                <section class="slider-headline-section" id="headlineSliderArea" aria-label="Berita Utama">
                    <div class="slider-viewport" id="sliderViewport">
                        <?php foreach ($sliderArticles as $idx => $slide) :
                            $slideImg    = extract_featured_image($slide) ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1200&auto=format&fit=crop&q=80';
                            $slideTitle  = $slide['title']['rendered'] ?? 'Tanpa Judul';
                            $slideCat    = extract_post_category($slide);
                            $slideCatSlug = extract_post_category_slug($slide);
                            $slideAuthor = extract_post_author($slide);
                            $slideDate   = format_date($slide['date'] ?? '');
                            $slideLink   = page_url('post-detail.php', ['id' => $slide['id']]);
                        ?>
                            <article class="slider-slide<?= $idx === 0 ? ' active' : '' ?>" data-index="<?= $idx ?>">
                                <a href="<?= htmlspecialchars($slideLink) ?>" aria-label="<?= htmlspecialchars($slideTitle) ?>">
                                    <img src="<?= htmlspecialchars($slideImg) ?>"
                                        alt="<?= htmlspecialchars($slideTitle) ?>"
                                        class="slide-cover-img"
                                        loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>">
                                    <div class="slide-overlay"></div>
                                    <div class="slide-caption">
                                        <span class="slide-cat-badge"><?= htmlspecialchars($slideCat) ?></span>
                                        <h2 class="slide-title"><?= htmlspecialchars($slideTitle) ?></h2>
                                        <div class="slide-meta-row">
                                            <span class="slide-meta-item">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                                    <circle cx="12" cy="7" r="4" />
                                                </svg>
                                                <?= htmlspecialchars($slideAuthor) ?>
                                            </span>
                                            <span aria-hidden="true">·</span>
                                            <span class="slide-meta-item">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                                <?= htmlspecialchars($slideDate) ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>

                        <?php if (count($sliderArticles) > 1) : ?>
                            <button type="button" class="slider-arrow-btn prev" id="sliderPrevBtn" aria-label="Sebelumnya">&#10094;</button>
                            <button type="button" class="slider-arrow-btn next" id="sliderNextBtn" aria-label="Selanjutnya">&#10095;</button>
                            <div class="slider-dots-shelf" id="sliderDotsShelf">
                                <?php for ($i = 0; $i < count($sliderArticles); $i++) : ?>
                                    <button type="button" class="slider-dot<?= $i === 0 ? ' active' : '' ?>"
                                        data-slide="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ── Feed Section -->
            <?php if (!empty($paginatedArticles)) : ?>
                <section class="news-feed-section">
                    <header class="section-headline-bar">
                        <div class="section-title-mark">
                            <span class="section-red-bar"></span>
                            <span><?= !empty($selectedCatName)
                                        ? 'Berita ' . htmlspecialchars(ucwords($selectedCatName))
                                        : (!empty($searchQuery) ? 'Hasil Pencarian' : 'Berita Terbaru') ?></span>
                        </div>
                        <span class="section-items-count"><?= number_format($totalItems) ?> Berita</span>
                    </header>

                    <div class="news-articles-grid">
                        <?php foreach ($paginatedArticles as $post) :
                            $postTitle   = $post['title']['rendered'] ?? 'Tanpa Judul';
                            $postImg     = extract_featured_image($post) ?: 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=400&auto=format&fit=crop&q=80';
                            $postCat     = extract_post_category($post);
                            $postAuthor  = extract_post_author($post);
                            $postDate    = format_date($post['date'] ?? '');
                            $postExcerpt = clean_excerpt($post['excerpt']['rendered'] ?? '', 110);
                            $postLink    = page_url('post-detail.php', ['id' => $post['id']]);
                        ?>
                            <article class="news-article-card">
                                <a href="<?= htmlspecialchars($postLink) ?>"
                                    class="card-thumbnail-link"
                                    aria-label="<?= htmlspecialchars($postTitle) ?>">
                                    <img src="<?= htmlspecialchars($postImg) ?>"
                                        alt="<?= htmlspecialchars($postTitle) ?>"
                                        class="card-thumbnail-img" loading="lazy">
                                </a>
                                <div class="card-content-col">
                                    <div class="card-top-content">
                                        <div class="card-meta-line">
                                            <span class="card-cat-name"><?= htmlspecialchars($postCat) ?></span>
                                            <span class="card-date-indicator">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                </svg>
                                                <?= htmlspecialchars($postDate) ?>
                                            </span>
                                        </div>
                                        <h2 class="card-post-title">
                                            <a href="<?= htmlspecialchars($postLink) ?>">
                                                <?= htmlspecialchars($postTitle) ?>
                                            </a>
                                        </h2>
                                        <?php if ($postExcerpt) : ?>
                                            <p class="card-excerpt-text"><?= htmlspecialchars($postExcerpt) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-bottom-row">
                                        <span>Oleh <?= htmlspecialchars($postAuthor) ?></span>
                                        <span class="card-read-action">
                                            Baca
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                                <polyline points="12 5 19 12 12 19" />
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php
                    render_pagination(
                        $currentPage,
                        $totalPages,
                        $totalItems,
                        count($paginatedArticles),
                        fn($p) => build_page_url($p, $selectedCatSlug, $searchQuery, $selectedTagId, $selectedAuthorId)
                    );
                    ?>
                </section>
            <?php else : ?>
                <?php render_empty_state(
                    'Tidak Ada Berita',
                    'Tidak ada berita yang cocok dengan filter yang dipilih.',
                    'Lihat Semua Berita',
                    home_url()
                ); ?>
            <?php endif; ?>

        <?php else : ?>
            <?php render_empty_state(
                'Tidak Ada Berita Ditemukan',
                'Belum ada data berita yang dapat dimuat dari backend.',
                'Coba Lagi',
                home_url()
            ); ?>
        <?php endif; ?>

    </div>
</main>

<?php render_site_footer(); ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.slider-slide');
        const dots = document.querySelectorAll('.slider-dot');
        const prevBtn = document.getElementById('sliderPrevBtn');
        const nextBtn = document.getElementById('sliderNextBtn');
        const area = document.getElementById('headlineSliderArea');
        if (!slides.length) return;

        let current = 0,
            timer = null;

        function goTo(i) {
            slides.forEach((s, idx) => s.classList.toggle('active', idx === i));
            dots.forEach((d, idx) => d.classList.toggle('active', idx === i));
            current = i;
        }

        function next() {
            goTo((current + 1) % slides.length);
        }

        function prev() {
            goTo((current - 1 + slides.length) % slides.length);
        }

        function startAuto() {
            stopAuto();
            timer = setInterval(next, 5000);
        }

        function stopAuto() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        if (nextBtn) nextBtn.addEventListener('click', () => {
            next();
            startAuto();
        });
        if (prevBtn) prevBtn.addEventListener('click', () => {
            prev();
            startAuto();
        });
        dots.forEach(d => d.addEventListener('click', function() {
            goTo(parseInt(this.dataset.slide, 10));
            startAuto();
        }));
        if (area) {
            area.addEventListener('mouseenter', stopAuto);
            area.addEventListener('mouseleave', startAuto);
        }
        if (slides.length > 1) startAuto();
    });
</script>