<?php

// Load .env file if it exists (Vanilla PHP parser without external dependencies)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Configuration Constants
if (!defined('API_ENDPOINT_URL')) {
    define('API_ENDPOINT_URL', getenv('API_ENDPOINT_URL') ?: 'https://suma.id/wp-json/wp/v2/posts');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', getenv('APP_NAME') ?: 'SUMA Post Viewer');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
}

if (!defined('PER_PAGE')) {
    define('PER_PAGE', intval(getenv('PER_PAGE') ?: 6));
}