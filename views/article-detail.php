<?php
/**
 * Single Article Detail View (PHP SSR)
 * Strict Shadcn Dark Mode styling - Zero inline CSS
 */

if (!$article || isset($article['code'])) : ?>
    <div class="empty-data-state">
        <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <h2 class="empty-state-title">Artikel Tidak Ditemukan</h2>
        <p class="empty-state-desc">Data artikel dengan ID tersebut tidak tersedia di endpoint backend atau telah dihapus.</p>
        <div class="flex-row-center">
            <a href="index.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
<?php return; endif;

$title = $article['title']['rendered'] ?? 'Tanpa Judul';
$content = $article['content']['rendered'] ?? '';
$date = format_date($article['date'] ?? '');
$modified = format_date($article['modified'] ?? '');
$slug = $article['slug'] ?? '-';
$type = $article['type'] ?? 'post';
$status = $article['status'] ?? 'publish';
$commentStatus = $article['comment_status'] ?? 'closed';
$classList = $article['class_list'] ?? [];
$link = $article['link'] ?? '#';
$guid = $article['guid']['rendered'] ?? '#';
$author = $article['author'] ?? '29';
$featuredImg = extract_featured_image($article);
?>

<div class="detail-article-container">
    <!-- Top Action Nav -->
    <div class="detail-top-nav-bar">
        <a href="index.php" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Kembali
        </a>

        <div class="flex-row-center">
            <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline" title="Buka URL asli di tab baru">
                <span>Buka Link Asli</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
            </a>
        </div>
    </div>

    <!-- Article Content Card -->
    <article class="detail-main-card">
        <header class="detail-post-header">
            <div class="detail-badges-row">
                <span class="badge badge-mono">ID: #<?= htmlspecialchars($article['id'] ?? '') ?></span>
                <span class="badge badge-success"><?= htmlspecialchars(strtoupper($status)) ?></span>
                <span class="badge badge-outline">Komentar: <?= htmlspecialchars(strtoupper($commentStatus)) ?></span>
                <span class="badge badge-outline">Tipe: <?= htmlspecialchars($type) ?></span>
            </div>

            <h1 class="detail-title-heading"><?= htmlspecialchars($title) ?></h1>

            <div class="detail-meta-horizontal">
                <div class="detail-meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Dipublikasikan: <strong><?= htmlspecialchars($date) ?></strong></span>
                </div>
                <div class="detail-meta-item">
                    <span>Diperbarui: <strong><?= htmlspecialchars($modified) ?></strong></span>
                </div>
                <div class="detail-meta-item">
                    <span>Penulis ID: <strong><?= htmlspecialchars($author) ?></strong></span>
                </div>
                <div class="detail-meta-item">
                    <span>Slug: <code class="mono"><?= htmlspecialchars($slug) ?></code></span>
                </div>
            </div>
        </header>

        <?php if (!empty($featuredImg)) : ?>
            <div class="detail-featured-media-box">
                <img src="<?= htmlspecialchars($featuredImg) ?>" alt="<?= htmlspecialchars($title) ?>" class="detail-featured-img" loading="lazy">
            </div>
        <?php endif; ?>

        <!-- Rendered Post HTML Content with Clean Dark Typography -->
        <div class="prose-dark">
            <?= $content ?>
        </div>

        <footer class="detail-footer-shelf">
            <?php if (!empty($classList)) : ?>
                <div class="detail-tax-section">
                    <span class="detail-tax-label">Kategori & Tags:</span>
                    <div class="card-tag-row">
                        <?php foreach ($classList as $cls) : ?>
                            <?php if (strpos($cls, 'category-') === 0 || strpos($cls, 'tag-') === 0) : 
                                $isTag = strpos($cls, 'tag-') === 0;
                                $displayLabel = str_replace(['category-', 'tag-'], '', $cls);
                            ?>
                                <span class="badge <?= $isTag ? 'badge-outline' : '' ?>">
                                    <?= $isTag ? '#' : '📂 ' ?><?= htmlspecialchars($displayLabel) ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Raw JSON Inspector Accordion for API transparency -->
            <div class="raw-json-inspector-box">
                <div class="json-inspector-header">
                    <span class="json-inspector-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                        Raw WP-JSON Payload (Post #<?= htmlspecialchars($article['id'] ?? '') ?>)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline" id="copyJsonBtn">Salin JSON</button>
                </div>
                <pre class="json-code-display" id="rawJsonCode"><?= htmlspecialchars(json_encode($article, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </footer>
    </article>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyBtn = document.getElementById('copyJsonBtn');
    const jsonBlock = document.getElementById('rawJsonCode');
    if (copyBtn && jsonBlock) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(jsonBlock.innerText).then(function () {
                const originalText = copyBtn.innerText;
                copyBtn.innerText = 'Tersalin!';
                setTimeout(function () {
                    copyBtn.innerText = originalText;
                }, 1800);
            });
        });
    }
});
</script>