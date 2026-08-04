<?php
// Entry point for Vercel Serverless Function (vercel-php)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = ltrim($uri, '/');

// Default to index.php if root path requested
if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/../index.php';
} elseif (file_exists(__DIR__ . '/../' . $path) && is_file(__DIR__ . '/../' . $path)) {
    require __DIR__ . '/../' . $path;
} else {
    http_response_code(404);
    echo "404 Not Found";
}
