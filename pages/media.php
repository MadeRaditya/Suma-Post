<?php
require_once __DIR__ . '/../includes/helpers.php';

$currentPage = max(1, intval($_GET['p'] ?? 1));
$mediaType   = in_array($_GET['type'] ?? 'image', ['image', 'video', 'audio', 'application']) ? ($_GET['type'] ?? 'image') : 'image';
$searchQuery = trim($_GET['search'] ?? '');

$params = [
    'per_page'   => 24,
    'page'       => $currentPage,
    'media_type' => $mediaType,
    'order'      => 'desc',
    'orderby'    => 'date',
];
if (!empty($searchQuery)) {
    $params['search'] = $searchQuery;
}

$totalPages = 0;
$totalItems = 0;
$mediaItems = get_media_list($params, $totalPages, $totalItems);
$currentPage = min($currentPage, max(1, $totalPages ?: 1));

$mediaTypes = [
    'image'       => 'Gambar',
    'video'       => 'Video',
    'audio'       => 'Audio',
    'application' => 'Dokumen',
];

render_site_header('Galeri Media', 'media');
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda', 'url' => home_url()],
            ['label' => 'Media'],
        ]); ?>

        <?php render_page_hero(
            'Galeri Media',
            'Jelajahi ' . number_format($totalItems) . ' item media dari arsip ' . APP_NAME . '.',
        ); ?>

        <!-- Filter bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div class="media-filter-bar" style="margin-bottom:0;">
                <?php foreach ($mediaTypes as $type => $label) : ?>
                    <a href="<?= page_url('media.php', array_filter(['type' => $type, 'search' => $searchQuery])) ?>"
                        class="media-filter-btn<?= $mediaType === $type ? ' active' : '' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="" style="display:flex;gap:0.5rem;align-items:center;">
                <input type="hidden" name="type" value="<?= htmlspecialchars($mediaType) ?>">
                <input type="text" name="search" placeholder="Cari media…"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    class="search-page-field" style="max-width:14rem;height:2.25rem;">
                <button type="submit" class="search-page-submit" style="height:2.25rem;">Cari</button>
                <?php if (!empty($searchQuery)) : ?>
                    <a href="<?= page_url('media.php', ['type' => $mediaType]) ?>" class="btn-clear-search">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($mediaItems)) : ?>

            <div class="media-gallery-grid">
                <?php foreach ($mediaItems as $item) :
                    $srcUrl  = $item['source_url'] ?? ($item['guid']['rendered'] ?? '');
                    $altText = $item['alt_text'] ?? ($item['title']['rendered'] ?? 'Media');
                    $caption = strip_tags($item['caption']['rendered'] ?? '');
                    $mime    = $item['mime_type'] ?? '';
                    $date    = format_date($item['date'] ?? '');
                    $origUrl = $item['source_url'] ?? '#';
                    $relatedPostId = (int) ($item['post'] ?? 0);
                    $postUrl = $relatedPostId > 0 ? page_url('post-detail.php', ['id' => $relatedPostId]) : '';
                    $mediaId = (int) ($item['id'] ?? 0);
                    $mediaTitle = trim(
                        strip_tags($item['title']['rendered'] ?? '')
                    );
                    $displayTitle = $altText ?: ($mediaTitle ?: $caption ?: 'Media');
                    $mediaData = [
                        'id'      => $mediaId,
                        'title'   => $displayTitle,
                        'url'     => $srcUrl,
                        'mime'    => $mime,
                        'date'    => $date,
                        'caption' => $caption,
                    ];

                    // Pick thumbnail (medium size preferred)
                    $thumbUrl = $item['media_details']['sizes']['medium']['source_url']
                        ?? $item['media_details']['sizes']['thumbnail']['source_url']
                        ?? $srcUrl;
                ?>
                    <div class="media-item">
                        <?php if (str_starts_with($mime, 'image/')) : ?>
                            <img src="<?= htmlspecialchars($thumbUrl) ?>"
                                alt="<?= htmlspecialchars($altText) ?>"
                                class="media-item-img" loading="lazy"
                                onerror="this.src='https://images.unsplash.com/photo-1508739773434-c26b3d09e071?w=400&fit=crop&q=60'">
                        <?php elseif (str_starts_with($mime, 'video/')) : ?>
                            <video class="media-item-img" muted preload="metadata"
                                style="object-fit:cover;width:100%;height:100%;">
                                <source src="<?= htmlspecialchars($srcUrl) ?>" type="<?= htmlspecialchars($mime) ?>">
                            </video>
                        <?php else : ?>
                            <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:var(--ink-100);font-size:3rem;">
                                <?= str_starts_with($mime, 'audio/') ? '🎵' : '📄' ?>
                            </div>
                        <?php endif; ?>

                        <div class="media-item-overlay">
                            <p class="media-item-title"><?= htmlspecialchars($altText ?: $caption ?: 'Media') ?></p>
                            <p class="media-item-meta"><?= htmlspecialchars($date) ?> · <?= htmlspecialchars($mime) ?></p>
                        </div>

                        <?php if ($relatedPostId > 0) : ?>
                            <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') ?>"
                                class="media-item-link"
                                aria-label="Baca berita terkait: <?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?>">
                            </a>
                        <?php else : ?>
                            <button
                                type="button"
                                class="media-item-link media-preview-trigger"
                                data-media="<?= htmlspecialchars(
                                                json_encode($mediaData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                aria-label="Pratinjau media: <?= htmlspecialchars($displayTitle, ENT_QUOTES, 'UTF-8') ?>">
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php render_pagination(
                $currentPage,
                $totalPages,
                $totalItems,
                count($mediaItems),
                fn($p) => page_url('media.php', array_filter(['p' => $p, 'type' => $mediaType, 'search' => $searchQuery]))
            ); ?>

        <?php else : ?>
            <?php render_empty_state(
                'Tidak Ada Media',
                'Tidak ada item media ditemukan untuk tipe "' . ($mediaTypes[$mediaType] ?? $mediaType) . '".',
                'Lihat Semua Gambar',
                page_url('media.php')
            ); ?>
        <?php endif; ?>

    </div>

    <div id="mediaPreviewModal"
        class="media-preview-modal"
        aria-hidden="true">

        <div class="media-preview-backdrop"
            data-close-modal></div>

        <section class="media-preview-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="mediaPreviewTitle">

            <button type="button"
                class="media-preview-close"
                id="mediaPreviewClose"
                aria-label="Tutup pratinjau">
                &times;
            </button>

            <div id="mediaPreviewContent"
                class="media-preview-content"></div>

            <div class="media-preview-info">
                <h2 id="mediaPreviewTitle">Pratinjau Media</h2>
                <p id="mediaPreviewDate"></p>
                <p id="mediaPreviewMime"></p>
                <p id="mediaPreviewCaption"></p>

                <div class="media-preview-actions">
                    <a id="mediaPreviewOpen"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer">
                        Buka file
                    </a>

                    <a id="mediaPreviewDownload"
                        href="#"
                        download>
                        Unduh media
                    </a>
                </div>
            </div>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('mediaPreviewModal');
            const content = document.getElementById('mediaPreviewContent');
            const title = document.getElementById('mediaPreviewTitle');
            const date = document.getElementById('mediaPreviewDate');
            const mime = document.getElementById('mediaPreviewMime');
            const caption = document.getElementById('mediaPreviewCaption');
            const openLink = document.getElementById('mediaPreviewOpen');
            const downloadLink = document.getElementById('mediaPreviewDownload');
            const closeButton = document.getElementById('mediaPreviewClose');
    
            if (!modal || !content) return;
    
            let previousFocus = null;
    
            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
    
                // Hentikan audio/video ketika modal ditutup.
                content.replaceChildren();
    
                if (previousFocus) previousFocus.focus();
            }
    
            function openModal(data, trigger) {
                previousFocus = trigger;
    
                content.replaceChildren();
    
                title.textContent = data.title || 'Media';
                date.textContent = 'Tanggal: ' + (data.date || '-');
                mime.textContent = 'Tipe: ' + (data.mime || 'Tidak diketahui');
                caption.textContent = data.caption || '';
    
                openLink.href = data.url || '#';
                downloadLink.href = data.url || '#';
                downloadLink.download = '';
    
                const mediaUrl = data.url || '';
                const mediaMime = data.mime || '';
    
                if (mediaMime.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = mediaUrl;
                    img.alt = data.title || 'Pratinjau gambar';
                    img.onerror = function() {
                        content.textContent = 'Gambar tidak dapat ditampilkan.';
                    };
                    content.appendChild(img);
    
                } else if (mediaMime.startsWith('video/')) {
                    const video = document.createElement('video');
                    video.src = mediaUrl;
                    video.controls = true;
                    video.preload = 'metadata';
                    content.appendChild(video);
    
                } else if (mediaMime.startsWith('audio/')) {
                    const audio = document.createElement('audio');
                    audio.src = mediaUrl;
                    audio.controls = true;
                    audio.preload = 'metadata';
                    content.appendChild(audio);
    
                } else {
                    const icon = document.createElement('div');
                    icon.className = 'media-preview-file-icon';
                    icon.textContent = '📄';
                    content.appendChild(icon);
                }
    
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                closeButton.focus();
            }
    
            document.querySelectorAll('.media-preview-trigger').forEach(function(button) {
                button.addEventListener('click', function() {
                    try {
                        const data = JSON.parse(this.dataset.media);
                        openModal(data, this);
                    } catch (error) {
                        console.error('Data media tidak valid:', error);
                    }
                });
            });
    
            closeButton.addEventListener('click', closeModal);
    
            modal.querySelectorAll('[data-close-modal]').forEach(function(element) {
                element.addEventListener('click', closeModal);
            });
    
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>
</main>


<?php render_site_footer(); ?>