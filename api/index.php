<?php
// Entry point for Vercel Serverless Function (vercel-php)
$rootDir = realpath(__DIR__ . '/..');
if ($rootDir) {
    chdir($rootDir);
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = urldecode(ltrim($path, '/'));

// 1. Root path or index requests
if ($path === '' || $path === 'index' || $path === 'index.php') {
    require_once 'index.php';
    exit;
}

// 2. Direct match for file (e.g. /results.php, /uploads/...)
if (file_exists($path) && is_file($path)) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        require_once $path;
        exit;
    } else {
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
        $contentType = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream');
        header('Content-Type: ' . $contentType);
        readfile($path);
        exit;
    }
}

// 3. Extensionless URL match (e.g. /results -> results.php)
$phpFile = $path . '.php';
if (file_exists($phpFile) && is_file($phpFile)) {
    require_once $phpFile;
    exit;
}

// 4. 404 Fallback
http_response_code(404);
echo "404 Not Found";

