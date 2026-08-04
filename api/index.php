<?php
// Entry point for Vercel Serverless Function (vercel-php)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = urldecode(ltrim($path, '/'));

// 1. Root path or index requests
if ($path === '' || $path === 'index' || $path === 'index.php') {
    require_once __DIR__ . '/main_index.php';
    exit;
}

// 2. Direct match for PHP file inside api/
$fileInApi = __DIR__ . '/' . $path;
if (file_exists($fileInApi) && is_file($fileInApi) && pathinfo($fileInApi, PATHINFO_EXTENSION) === 'php') {
    require_once $fileInApi;
    exit;
}

// 3. Extensionless match inside api/ (e.g. /results -> results.php)
$phpInApi = __DIR__ . '/' . $path . '.php';
if (file_exists($phpInApi) && is_file($phpInApi)) {
    require_once $phpInApi;
    exit;
}

// 4. Check root directory for static assets (like uploads/...)
$rootDir = realpath(__DIR__ . '/..');
$staticFile = $rootDir . '/' . $path;
if (file_exists($staticFile) && is_file($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'css'  => 'text/css',
        'js'   => 'text/javascript'
    ];
    $contentType = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($staticFile) : 'application/octet-stream');
    header('Content-Type: ' . $contentType);
    readfile($staticFile);
    exit;
}

// 5. 404 Fallback
http_response_code(404);
echo "404 Not Found";
