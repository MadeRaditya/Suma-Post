<?php

require_once __DIR__ . '/../config.php';

/**
 * Perform HTTP GET request to API Endpoint using cURL with stream fallback.
 *
 * @param string $url
 * @param array|null $meta Reference variable to capture request metadata
 * @return array|null
 */
function fetch_api_data(string $url, ?array &$meta = null) {
    $meta = [
        'url' => $url,
        'http_code' => 0,
        'total_time' => 0,
        'error' => null,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Cache-Control: no-cache',
        ]);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $meta['total_time'] = round((microtime(true) - $startTime) * 1000, 1);
        $meta['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $meta['error'] = curl_error($ch);
        }
        curl_close($ch);

        if ($meta['http_code'] === 200 && !empty($response)) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
    }

    // Fallback using file_get_contents if curl unavailable or returned non-200
    if ($meta['http_code'] !== 200) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $startTime = microtime(true);
        $response = @file_get_contents($url, false, $context);
        if ($response !== false) {
            $meta['total_time'] = round((microtime(true) - $startTime) * 1000, 1);
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
 * Fetch all articles from the configured API endpoint.
 *
 * @param array $params Optional query parameters
 * @param array|null $meta
 * @return array
 */
function get_all_articles(array $params = [], ?array &$meta = null): array {
    $url = API_ENDPOINT_URL;

    // Attach _embed so featured media and terms are included by WordPress REST API
    $defaultParams = ['_embed' => '1'];
    $queryParams = array_merge($defaultParams, $params);

    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    $fullUrl = $url . $separator . http_build_query($queryParams);

    $result = fetch_api_data($fullUrl, $meta);
    return is_array($result) ? $result : [];
}

/**
 * Fetch a single article by ID.
 *
 * @param int $id
 * @param array|null $meta
 * @return array|null
 */
function get_article_by_id(int $id, ?array &$meta = null): ?array {
    $baseUrl = rtrim(API_ENDPOINT_URL, '/');
    $url = "{$baseUrl}/" . intval($id) . '?_embed=1';

    $result = fetch_api_data($url, $meta);
    if (is_array($result) && !isset($result['code'])) {
        return $result;
    }
    return null;
}

/**
 * Inspect API Health status.
 *
 * @return array
 */
function get_api_health(): array {
    $meta = [];
    $data = fetch_api_data(API_ENDPOINT_URL, $meta);

    $isOk = ($meta['http_code'] === 200 && is_array($data));
    return [
        'status' => $isOk ? 'connected' : 'error',
        'http_code' => $meta['http_code'] ?: 500,
        'latency_ms' => $meta['total_time'] ?: 0,
        'endpoint' => API_ENDPOINT_URL,
        'item_count' => is_array($data) ? count($data) : 0,
        'error' => $meta['error'] ?? null,
    ];
}

/**
 * Format ISO datetime into clean Indonesian readable date.
 *
 * @param string $dateString
 * @return string
 */
function format_date(string $dateString): string {
    if (empty($dateString)) {
        return '-';
    }

    $timestamp = strtotime($dateString);
    if (!$timestamp) {
        return htmlspecialchars($dateString);
    }

    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $d = date('j', $timestamp);
    $m = $months[(int)date('n', $timestamp)] ?? date('M', $timestamp);
    $y = date('Y', $timestamp);
    $time = date('H:i', $timestamp);

    return "{$d} {$m} {$y}, {$time} WIB";
}

/**
 * Clean HTML excerpt string.
 *
 * @param string $html
 * @param int $limit
 * @return string
 */
function clean_excerpt(string $html, int $limit = 130): string {
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', trim($text));

    if (mb_strlen($text) > $limit) {
        return mb_substr($text, 0, $limit) . '...';
    }
    return $text;
}

/**
 * Extract image URL from embedded media or HTML content.
 *
 * @param array $article
 * @return string|null
 */
function extract_featured_image(array $article): ?string {
    // 1. Check WP _embedded featured media
    if (!empty($article['_embedded']['wp:featuredmedia'][0]['source_url'])) {
        return $article['_embedded']['wp:featuredmedia'][0]['source_url'];
    }

    // 2. Fallback: regex search first <img> in content
    $content = $article['content']['rendered'] ?? '';
    if (!empty($content) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $match)) {
        return $match[1];
    }

    return null;
}

/**
 * Extract taxonomy categories and tags from post collection.
 *
 * @param array $articles
 * @return array ['categories' => [...], 'tags' => [...]]
 */
function extract_taxonomies(array $articles): array {
    $categories = [];
    $tags = [];

    foreach ($articles as $post) {
        $classList = $post['class_list'] ?? [];
        foreach ($classList as $cls) {
            if (strpos($cls, 'category-') === 0) {
                $name = ucwords(str_replace(['category-', '-'], ['', ' '], $cls));
                $slug = str_replace('category-', '', $cls);
                $categories[$slug] = [
                    'name' => $name,
                    'slug' => $slug,
                    'count' => ($categories[$slug]['count'] ?? 0) + 1,
                ];
            } elseif (strpos($cls, 'tag-') === 0) {
                $name = str_replace(['tag-', '-'], ['', ' '], $cls);
                $slug = str_replace('tag-', '', $cls);
                $tags[$slug] = [
                    'name' => $name,
                    'slug' => $slug,
                    'count' => ($tags[$slug]['count'] ?? 0) + 1,
                ];
            }
        }
    }

    return [
        'categories' => array_values($categories),
        'tags' => array_values($tags),
    ];
}