<?php
require_once __DIR__ . '/../includes/helpers.php';

// ============================================================
// REQUEST PARAMETERS
// ============================================================
// Menggunakan 'search' sebagai parameter utama agar selaras dengan form global di header.
// Fallback ke 'q' untuk backward compatibility jika ada link lama yang menggunakan ?q=
$searchQuery = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));

$allowedTypes = ['post', 'page', 'any'];
$postType = (string) ($_GET['type'] ?? 'post');

if (!in_array($postType, $allowedTypes, true)) {
    $postType = 'post';
}

$currentPage = max(1, (int) ($_GET['p'] ?? 1));

// ============================================================
// SEARCH STATE
// ============================================================
$results       = [];
$totalPages    = 0;
$totalItems    = 0;
$searchFailed  = false;

if ($searchQuery !== '') {
    $subtype = $postType === 'any' ? 'any' : $postType;

    $results = wp_search(
        $searchQuery,
        'post',
        $subtype,
        [
            'per_page' => PER_PAGE,
            'page'     => $currentPage,
        ],
        $totalPages,
        $totalItems
    );

    /*
     * Catatan:
     * wp_search() saat ini mengembalikan [] baik ketika tidak ada
     * hasil maupun ketika request API gagal.
     *
     * Karena itu, kondisi [] tidak boleh langsung dianggap error.
     * Untuk deteksi error yang akurat, helper API perlu menyediakan
     * status HTTP/error terpisah.
     */
    $currentPage = min(
        $currentPage,
        max(1, $totalPages ?: 1)
    );
}

// ============================================================
// DISPLAY CONFIGURATION
// ============================================================
$typeOptions = [
    'post' => 'Artikel',
    'page' => 'Halaman',
    'any'  => 'Semua Tipe',
];

// Kata kunci populer statis untuk state awal (kosong)
$popularSearches = ['Teknologi', 'Pendidikan', 'Otomotif', 'Beasiswa', 'Tutorial'];

// SVG icon per result type
function result_icon(string $subtype): string
{
    return match ($subtype) {
        'page' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
        default => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    };
}

render_site_header(
    $searchQuery !== ''
        ? 'Pencarian: "' . $searchQuery . '"'
        : 'Pencarian Global',
    'search',
    '',
    $searchQuery
);
?>

<main class="main-content-layout">
    <div class="news-container">

        <?php render_breadcrumbs([
            ['label' => 'Beranda', 'url' => home_url()],
            ['label' => 'Pencarian Global'],
        ]); ?>

        <!-- Search Form Card (Enhanced Hero Look) -->
        <div class="search-page-form">
            <div class="search-page-form-header">
                <h1 class="search-page-title">Pencarian Terpadu</h1>
                <p class="search-page-subtitle">Temukan artikel, halaman, atau topik spesifik di <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <form method="GET" action="">
                <div class="search-page-input-row">
                    <div class="search-input-wrapper">
                        <svg class="search-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input
                            type="text"
                            name="search"
                            placeholder="Cari sesuatu..."
                            value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>"
                            class="search-page-field"
                            autocomplete="off"
                            autofocus>
                        
                        <?php if ($searchQuery !== '') : ?>
                            <a href="<?= page_url('search.php') ?>" class="search-clear-inline" aria-label="Hapus Pencarian">×</a>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="type" value="<?= htmlspecialchars($postType, ENT_QUOTES, 'UTF-8') ?>">

                    <button type="submit" class="search-page-submit">
                        Telusuri
                    </button>
                </div>

                <div class="search-type-filter">
                    <span class="search-type-label">Filter Tipe:</span>

                    <?php foreach ($typeOptions as $value => $label) : ?>
                        <button
                            type="submit"
                            name="type"
                            value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                            class="search-type-btn<?= $postType === $value ? ' active' : '' ?>">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <?php if ($searchQuery !== '') : ?>

            <!-- Search Results Header -->
            <div class="search-results-header">
                <span class="search-results-count">
                    Ditemukan
                    <strong><?= number_format($totalItems) ?></strong>
                    hasil untuk
                    <strong>&ldquo;<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>&rdquo;</strong>

                    <?php if ($postType !== 'any') : ?>
                        dalam kategori <strong><?= htmlspecialchars($typeOptions[$postType], ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php endif; ?>
                </span>

                <?php if (!empty($results)) : ?>
                    <span class="search-results-pagination-meta">
                        Halaman <?= $currentPage ?> dari <?= $totalPages ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($results)) : ?>

                <!-- Results List -->
                <div class="search-results-list">

                    <?php foreach ($results as $item) :
                        $itemId = (int) ($item['id'] ?? 0);

                        $itemTitle = html_entity_decode(
                            (string) ($item['title'] ?? 'Tanpa Judul'),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8'
                        );

                        $itemType = (string) (
                            $item['subtype'] ?? $item['type'] ?? 'post'
                        );

                        $itemUrl = (string) ($item['url'] ?? '');

                        // Build destination URL
                        if ($itemType === 'post' && $itemId > 0) {
                            $localUrl = page_url(
                                'post-detail.php',
                                ['id' => $itemId]
                            );
                            $isExternal = false;
                        } elseif (
                            in_array($itemType, ['page', 'post'], true)
                            && $itemUrl !== ''
                        ) {
                            $localUrl = $itemUrl;
                            $isExternal = true;
                        } else {
                            $localUrl = '';
                            $isExternal = false;
                        }

                        $typeLabel = $itemType === 'page' ? 'Halaman' : 'Artikel';
                        // Berikan kelas warna berbeda antara post dan page
                        $typeClass = $itemType === 'page' ? 'type-page' : 'type-post';

                        $safeTitle = htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8');
                        $safeOriginalUrl = htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8');
                    ?>

                        <a
                            href="<?= htmlspecialchars($localUrl, ENT_QUOTES, 'UTF-8') ?: '#' ?>"
                            <?= $isExternal ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                            class="search-result-item">
                            
                            <div class="search-result-icon <?= $typeClass ?>">
                                <?= result_icon($itemType) ?>
                            </div>

                            <div class="search-result-body">
                                <div class="search-result-type <?= $typeClass ?>">
                                    <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                </div>

                                <h3 class="search-result-title">
                                    <?= $safeTitle ?>
                                </h3>

                                <div class="search-result-url">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                                    <?= $safeOriginalUrl !== '' ? $safeOriginalUrl : 'URL tidak tersedia' ?>
                                </div>
                            </div>

                            <div class="search-result-action">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </div>
                        </a>

                    <?php endforeach; ?>

                </div>

                <!-- Pagination -->
                <?php render_pagination(
                    $currentPage,
                    $totalPages,
                    $totalItems,
                    count($results),
                    fn($p) => page_url(
                        'search.php',
                        array_filter([
                            'p'    => $p,
                            'search' => $searchQuery, // Disinkronkan dengan name="search"
                            'type' => $postType,
                        ], fn($value) => $value !== '' && $value !== null)
                    )
                ); ?>

            <?php else : ?>

                <?php render_empty_state(
                    'Pencarian Tidak Ditemukan',
                    'Maaf, kami tidak dapat menemukan hasil untuk "' .
                        htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') .
                        '". Coba gunakan kata kunci yang lebih umum atau periksa ejaan Anda.',
                    'Kembali ke Beranda',
                    home_url()
                ); ?>

            <?php endif; ?>

        <?php else : ?>

            <!-- Initial Empty State -->
            <div class="search-initial-state">
                <h2 class="initial-title">Mulai Pencarian Anda</h2>
                <p class="initial-desc">Ketik kata kunci di atas untuk mencari artikel, panduan, atau berita terbaru.</p>
                
                <div class="popular-searches">
                    <span class="popular-label">Saran Pencarian:</span>
                    <div class="popular-tags-list">
                        <?php foreach($popularSearches as $popSearch): ?>
                            <a href="<?= page_url('search.php', ['search' => $popSearch]) ?>" class="popular-tag-btn">
                                <?= htmlspecialchars($popSearch, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<?php render_site_footer(); ?>