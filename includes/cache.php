<?php

/**
 * Simple file-based JSON cache.
 *
 * Cache files are stored in CACHE_DIR as {sanitized_key}.json
 * Each file contains: { expires_at, created_at, key, payload }
 */

/**
 * Read a value from cache. Returns null on miss or expiry.
 */
function cache_get(string $key): mixed
{
    $file = _cache_filepath($key);
    if (!file_exists($file)) {
        return null;
    }

    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['expires_at'], $data['payload'])) {
        return null;
    }

    if (time() > $data['expires_at']) {
        @unlink($file);
        return null;
    }

    return $data['payload'];
}

/**
 * Write a value to cache with optional custom TTL.
 */
function cache_set(string $key, mixed $payload, ?int $ttl = null): bool
{
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0755, true);
    }

    $ttl  = $ttl ?? CACHE_TTL;
    $data = json_encode([
        'key'        => $key,
        'created_at' => time(),
        'expires_at' => time() + $ttl,
        'payload'    => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return file_put_contents(_cache_filepath($key), $data, LOCK_EX) !== false;
}

/**
 * Delete a single cache entry.
 */
function cache_clear(string $key): void
{
    $file = _cache_filepath($key);
    if (file_exists($file)) {
        @unlink($file);
    }
}

/**
 * Delete ALL cache entries.
 */
function cache_flush(): void
{
    if (!is_dir(CACHE_DIR)) {
        return;
    }
    foreach (glob(CACHE_DIR . '/*.json') ?: [] as $file) {
        @unlink($file);
    }
}

/**
 * Internal: resolve a cache key to an absolute file path.
 */
function _cache_filepath(string $key): string
{
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    return CACHE_DIR . '/' . $safe . '.json';
}
