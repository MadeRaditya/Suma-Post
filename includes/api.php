<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/cache.php';

/* ==========================================================================
   CORE HTTP LAYER
   ========================================================================== */

/**
 * Generic GET request to the WP REST API.
 *
 * Endpoint is relative to API_BASE_URL (e.g. 'posts', 'categories').
 * Response headers (x-wp-total, x-wp-totalpages, etc.) are captured into
 * $responseHeaders so callers can read pagination metadata.
 *
 * @param string $endpoint   Relative endpoint path, e.g. 'posts' or 'posts/42'
 * @param array  $params     Query parameters to append
 * @param array  $responseHeaders  Out-param: captured HTTP response headers (lowercase keys)
 * @return mixed Decoded JSON (array|int|string) or null on failure
 */
function wp_api_get(string $endpoint, array $params = [], array &$responseHeaders = []): mixed
{
    $url = rtrim(API_BASE_URL, '/') . '/' . ltrim($endpoint, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $responseHeaders = [];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SumaPortal/2.0)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Cache-Control: no-cache'],
            // Capture response headers into $responseHeaders
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                if (strpos($header, ':') !== false) {
                    [$name, $value]                    = explode(':', $header, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }
                return $len;
            },
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $body) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        // Non-200 from WP (e.g. 400 with error detail): still try to decode
        if ($body) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['code'])) {
                return null; // WP error object
            }
        }
    }

    // Fallback: file_get_contents (no header capture, but at least gets data)
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => 10,
            'header'        => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
            'ignore_errors' => true,
        ],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body !== false) {
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && !isset($data['code'])) {
            return $data;
        }
    }

    return null;
}

/* ==========================================================================
   SLUG → ID RESOLUTION  (with 24h cache)
   ========================================================================== */

/**
 * Resolve a taxonomy slug to its numeric WP term ID.
 *
 * @param string $taxonomy  'categories' | 'tags'
 * @param string $slug
 * @return int|null
 */
function resolve_slug_to_id(string $taxonomy, string $slug): ?int
{
    $cacheKey = "slug_{$taxonomy}_{$slug}";
    $cached   = cache_get($cacheKey);
    if ($cached !== null) {
        return (int) $cached;
    }

    $result = wp_api_get($taxonomy, ['slug' => $slug, 'per_page' => 1]);
    if (is_array($result) && !empty($result[0]['id'])) {
        $id = (int) $result[0]['id'];
        cache_set($cacheKey, $id, 86400); // 24 h
        return $id;
    }

    return null;
}

/* ==========================================================================
   POSTS
   ========================================================================== */

/**
 * Fetch paginated posts from the WP REST API.
 *
 * Supported $params (all optional):
 *   page, per_page, search, categories (int[]), tags (int[]),
 *   author (int[]), orderby, order, after, before,
 *   modified_after, modified_before, sticky, slug,
 *   exclude (int[]), include (int[]),
 *   categories_exclude, tags_exclude
 *
 * @param array $params
 * @param int   $totalPages  Out-param: X-WP-TotalPages header value
 * @param int   $totalItems  Out-param: X-WP-Total header value
 * @return array
 */
function get_posts(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = [
        'per_page' => PER_PAGE,
        'page'     => 1,
        '_embed'   => '1',
    ];
    $params = array_merge($defaults, $params);

    // Normalise array params → comma-separated string
    foreach (
        [
            'categories',
            'tags',
            'author',
            'exclude',
            'include',
            'categories_exclude',
            'tags_exclude'
        ] as $key
    ) {
        if (isset($params[$key]) && is_array($params[$key])) {
            $params[$key] = implode(',', array_filter(array_map('intval', $params[$key])));
        }
    }

    $headers = [];
    $result  = wp_api_get('posts', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/**
 * Fetch a single post by numeric ID.
 *
 * @param int $id
 * @return array|null
 */
function get_post_by_id(int $id): ?array
{
    $result = wp_api_get("posts/{$id}", ['_embed' => '1']);
    return (is_array($result) && !empty($result['id'])) ? $result : null;
}

/**
 * Fetch a single post by its slug.
 *
 * @param string $slug
 * @return array|null
 */
function get_post_by_slug(string $slug): ?array
{
    $result = wp_api_get('posts', ['slug' => $slug, '_embed' => '1', 'per_page' => 1]);
    if (is_array($result) && !empty($result[0])) {
        return $result[0];
    }
    return null;
}

/* ==========================================================================
   PAGES
   ========================================================================== */

/**
 * Fetch WP pages list.
 *
 * @param array $params  page, per_page, search, orderby, order, slug
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_pages_list(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => PER_PAGE, 'page' => 1, '_embed' => '1'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('pages', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/* ==========================================================================
   CATEGORIES
   ========================================================================== */

/**
 * Fetch categories list.
 *
 * Common $params: per_page, page, search, slug, orderby (count|name|id),
 *                 order (asc|desc), hide_empty (true|false), parent
 *
 * @param array $params
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_categories(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => 100, 'hide_empty' => 'true', 'orderby' => 'count', 'order' => 'desc'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('categories', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/**
 * Fetch a single category by ID.
 *
 * @param int $id
 * @return array|null
 */
function get_category_by_id(int $id): ?array
{
    $result = wp_api_get("categories/{$id}");
    return (is_array($result) && !empty($result['id'])) ? $result : null;
}

/**
 * Get categories for site navigation (cached, top 50 by post count).
 *
 * @return array  Each item: ['id', 'name', 'slug', 'count', 'description']
 */
function get_nav_categories(): array
{
    $cacheKey = 'nav_categories';
    $cached   = cache_get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }

    $cats = get_categories(['per_page' => 50, 'hide_empty' => 'true', 'orderby' => 'count', 'order' => 'desc']);
    $nav  = array_map(fn($c) => [
        'id'          => $c['id'],
        'name'        => $c['name'],
        'slug'        => $c['slug'],
        'count'       => $c['count'],
        'description' => $c['description'] ?? '',
    ], $cats);

    cache_set($cacheKey, $nav, 3600); // 1 h
    return $nav;
}

/* ==========================================================================
   TAGS
   ========================================================================== */

/**
 * Fetch tags list.
 *
 * Common $params: per_page, page, search, slug, orderby (count|name|id), hide_empty
 *
 * @param array $params
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_tags(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => 100, 'hide_empty' => 'true', 'orderby' => 'count', 'order' => 'desc'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('tags', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/* ==========================================================================
   AUTHORS / USERS
   ========================================================================== */

/**
 * Fetch public authors list.
 *
 * Common $params: per_page, page, search, orderby (name|id|registered_date), order
 *
 * @param array $params
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_authors(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => 50, 'has_published_posts' => 'true'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('users', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/**
 * Fetch a single author by ID.
 *
 * @param int $id
 * @return array|null
 */
function get_author_by_id(int $id): ?array
{
    $result = wp_api_get("users/{$id}");
    return (is_array($result) && !empty($result['id'])) ? $result : null;
}

/* ==========================================================================
   MEDIA
   ========================================================================== */

/**
 * Fetch media library items.
 *
 * Common $params: per_page, page, search, media_type (image|video|text|application|audio),
 *                 mime_type (image/jpeg|image/png|...), orderby, order, parent
 *
 * @param array $params
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_media_list(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => 24, 'media_type' => 'image', 'orderby' => 'date', 'order' => 'desc'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('media', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/* ==========================================================================
   COMMENTS
   ========================================================================== */

/**
 * Fetch comments.
 *
 * Common $params: per_page, page, post (post ID), search, status (approved),
 *                 orderby, order, after, before
 *
 * @param array $params
 * @param int   $totalPages
 * @param int   $totalItems
 * @return array
 */
function get_comments_list(array $params = [], int &$totalPages = 0, int &$totalItems = 0): array
{
    $defaults = ['per_page' => 20, 'status' => 'approved', 'order' => 'desc', 'orderby' => 'date'];
    $params   = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('comments', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/* ==========================================================================
   GLOBAL SEARCH  (/wp/v2/search)
   ========================================================================== */

/**
 * Search across post types using the WP REST search endpoint.
 *
 * @param string $query    Search keyword
 * @param string $type     'post' | 'term' | 'post-format' (default: 'post')
 * @param string $subtype  For type=post: 'post' | 'page' | 'any'
 * @param array  $params   Extra params: per_page, page
 * @param int    $totalPages
 * @param int    $totalItems
 * @return array  Each item: { id, title, url, type, subtype }
 */
function wp_search(
    string $query,
    string $type = 'post',
    string $subtype = 'any',
    array  $params = [],
    int   &$totalPages = 0,
    int   &$totalItems = 0
): array {
    $defaults = [
        'search'   => $query,
        'type'     => $type,
        'subtype'  => $subtype,
        'per_page' => PER_PAGE,
        'page'     => 1,
    ];
    $params = array_merge($defaults, $params);

    $headers = [];
    $result  = wp_api_get('search', $params, $headers);

    $totalItems = intval($headers['x-wp-total']      ?? 0);
    $totalPages = intval($headers['x-wp-totalpages'] ?? 0);

    return is_array($result) ? $result : [];
}

/* ==========================================================================
   LEGACY COMPATIBILITY  (used by existing index.php before refactor)
   ========================================================================== */

/**
 * Perform HTTP GET request using cURL with stream fallback.
 * Kept for backward compatibility.
 *
 * @param string     $url
 * @param array|null $meta  Reference variable to capture request metadata
 * @return array|null
 */
function fetch_api_data(string $url, ?array &$meta = null): mixed
{
    $meta = ['url' => $url, 'http_code' => 0, 'total_time' => 0, 'error' => null];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Cache-Control: no-cache'],
        ]);

        $start               = microtime(true);
        $response            = curl_exec($ch);
        $meta['total_time']  = round((microtime(true) - $start) * 1000, 1);
        $meta['http_code']   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $meta['error'] = curl_error($ch);
        }
        curl_close($ch);

        if ($meta['http_code'] === 200 && $response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
    }

    // Fallback
    if ($meta['http_code'] !== 200) {
        $ctx      = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
                'ignore_errors' => true
            ],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $start    = microtime(true);
        $response = @file_get_contents($url, false, $ctx);
        if ($response !== false) {
            $meta['total_time'] = round((microtime(true) - $start) * 1000, 1);
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $meta['http_code'] = 200;
                return $data;
            }
        }
    }

    return null;
}

/**
 * Fetch all articles (legacy wrapper — now delegates to get_posts).
 *
 * @param array      $params
 * @param array|null $meta
 * @return array
 */
function get_all_articles(array $params = [], ?array &$meta = null): array
{
    $meta = ['url' => API_ENDPOINT_URL, 'http_code' => 0, 'total_time' => 0, 'error' => null];
    $t    = microtime(true);
    $res  = get_posts(array_merge(['per_page' => 100], $params));
    $meta['http_code']  = empty($res) ? 500 : 200;
    $meta['total_time'] = round((microtime(true) - $t) * 1000, 1);
    return $res;
}

/**
 * Fetch a single article by ID (legacy wrapper).
 *
 * @param int        $id
 * @param array|null $meta
 * @return array|null
 */
function get_article_by_id(int $id, ?array &$meta = null): ?array
{
    $meta = [];
    return get_post_by_id($id);
}

/**
 * API health check.
 *
 * @return array
 */
function get_api_health(): array
{
    $rh   = [];
    $t    = microtime(true);
    $data = wp_api_get('posts', ['per_page' => 1], $rh);
    $ms   = round((microtime(true) - $t) * 1000, 1);
    $ok   = is_array($data);
    return [
        'status'     => $ok ? 'connected' : 'error',
        'http_code'  => $ok ? 200 : 500,
        'latency_ms' => $ms,
        'endpoint'   => API_BASE_URL,
        'item_count' => is_array($data) ? count($data) : 0,
        'error'      => $ok ? null : 'API unreachable',
    ];
}

/* ==========================================================================
   UTILITY / FORMATTERS  (all previously existing helpers kept)
   ========================================================================== */

/**
 * Format ISO datetime into readable Indonesian date string.
 *
 * @param string $dateString
 * @return string
 */
function format_date(string $dateString): string
{
    if (empty($dateString)) {
        return '-';
    }
    $ts = strtotime($dateString);
    if (!$ts) {
        return htmlspecialchars($dateString);
    }
    static $months = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ags',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];
    $d = date('j', $ts);
    $m = $months[(int) date('n', $ts)] ?? date('M', $ts);
    $y = date('Y', $ts);
    return "{$d} {$m} {$y}";
}

/**
 * Format date in long Indonesian form.
 */
function format_date_long(string $dateString): string
{
    if (empty($dateString)) return '-';
    $ts = strtotime($dateString);
    if (!$ts) return htmlspecialchars($dateString);
    static $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
    return date('j', $ts) . ' ' . ($months[(int) date('n', $ts)] ?? date('M', $ts)) . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts) . ' WIB';
}

/**
 * Strip HTML and truncate an excerpt.
 *
 * @param string $html
 * @param int    $limit  Character limit
 * @return string
 */
function clean_excerpt(string $html, int $limit = 130): string
{
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', trim($text));
    if (mb_strlen($text) > $limit) {
        return mb_substr($text, 0, $limit) . '…';
    }
    return $text;
}

/** Legacy alias */
function format_lampost_date(string $dateString): string
{
    if (empty($dateString)) return date('M d');
    $ts = strtotime($dateString);
    return $ts ? date('M d', $ts) : htmlspecialchars($dateString);
}

/**
 * Extract primary category name from a post array.
 *
 * @param array $article
 * @return string
 */
function extract_post_category(array $article): string
{
    if (!empty($article['_embedded']['wp:term'][0])) {
        foreach ($article['_embedded']['wp:term'][0] as $term) {
            if (!empty($term['name'])) {
                return $term['name'];
            }
        }
    }
    if (!empty($article['class_list'])) {
        foreach ($article['class_list'] as $cls) {
            if (str_starts_with($cls, 'category-')) {
                return ucwords(str_replace(['category-', '-'], ['', ' '], $cls));
            }
        }
    }
    return 'Berita';
}

/**
 * Extract primary category slug from a post array.
 */
function extract_post_category_slug(array $article): string
{
    if (!empty($article['_embedded']['wp:term'][0])) {
        foreach ($article['_embedded']['wp:term'][0] as $term) {
            if (!empty($term['slug'])) {
                return $term['slug'];
            }
        }
    }
    return '';
}

/**
 * Extract author display name from a post array.
 *
 * @param array $article
 * @return string
 */
function extract_post_author(array $article): string
{
    return $article['_embedded']['author'][0]['name'] ?? 'Redaksi';
}

/**
 * Extract featured image URL from a post array.
 *
 * @param array $article
 * @return string|null
 */
function extract_featured_image(array $article): ?string
{
    if (!empty($article['_embedded']['wp:featuredmedia'][0]['source_url'])) {
        return $article['_embedded']['wp:featuredmedia'][0]['source_url'];
    }
    $content = $article['content']['rendered'] ?? '';
    if ($content && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Extract taxonomy categories and tags from a post collection (legacy).
 *
 * @param array $articles
 * @return array ['categories' => [...], 'tags' => [...]]
 */
function extract_taxonomies(array $articles): array
{
    $categories = [];
    $tags       = [];
    foreach ($articles as $post) {
        foreach ($post['class_list'] ?? [] as $cls) {
            if (str_starts_with($cls, 'category-')) {
                $name = ucwords(str_replace(['category-', '-'], ['', ' '], $cls));
                $slug = str_replace('category-', '', $cls);
                $categories[$slug] = [
                    'name'  => $name,
                    'slug'  => $slug,
                    'count' => ($categories[$slug]['count'] ?? 0) + 1,
                ];
            } elseif (str_starts_with($cls, 'tag-')) {
                $name = str_replace(['tag-', '-'], ['', ' '], $cls);
                $slug = str_replace('tag-', '', $cls);
                $tags[$slug] = [
                    'name'  => $name,
                    'slug'  => $slug,
                    'count' => ($tags[$slug]['count'] ?? 0) + 1,
                ];
            }
        }
    }
    return ['categories' => array_values($categories), 'tags' => array_values($tags)];
}
