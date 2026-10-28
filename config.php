<?php

/**
 * Application Configuration
 * Loads .env and defines all runtime constants.
 */

// ── Load .env file (vanilla parser, no external deps)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$name, $value] = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// ── WP REST API Base URL  (no trailing slash, no /posts suffix)
if (!defined('API_BASE_URL')) {
    $envBase = getenv('API_BASE_URL') ?: '';
    if ($envBase) {
        define('API_BASE_URL', rtrim($envBase, '/'));
    } else {
        // Derive from legacy API_ENDPOINT_URL by stripping known resource paths
        $envLegacy = getenv('API_ENDPOINT_URL') ?: 'https://suma.id/wp-json/wp/v2/posts';
        $base = preg_replace(
            '#/(posts|pages|categories|tags|users|media|comments|search)(/.*)?$#',
            '',
            rtrim($envLegacy, '/')
        );
        define('API_BASE_URL', $base ?: 'https://suma.id/wp-json/wp/v2');
    }
}

// ── Legacy compat: direct posts endpoint
if (!defined('API_ENDPOINT_URL')) {
    define('API_ENDPOINT_URL', API_BASE_URL . '/posts');
}

// ── Application name
if (!defined('APP_NAME')) {
    define('APP_NAME', getenv('APP_NAME') ?: 'Suma.id');
}

// ── Environment (production | development)
if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

// ── Default posts per page
if (!defined('PER_PAGE')) {
    define('PER_PAGE', intval(getenv('PER_PAGE') ?: 10));
}

// ── Public base URL  (auto-detected from server env)
if (!defined('APP_URL')) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $projDir = str_replace('\\', '/', __DIR__);
    $subPath = '';
    if ($docRoot && str_starts_with($projDir, $docRoot)) {
        $subPath = substr($projDir, strlen($docRoot));
    }
    define('APP_URL', $scheme . '://' . $host . $subPath);
}

// ── Cache directory (absolute filesystem path)
if (!defined('CACHE_DIR')) {
    define('CACHE_DIR', __DIR__ . '/cache');
}

// ── Cache TTL in seconds (default 1 hour)
if (!defined('CACHE_TTL')) {
    define('CACHE_TTL', intval(getenv('CACHE_TTL') ?: 3600));
}
