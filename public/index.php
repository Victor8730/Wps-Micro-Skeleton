<?php

declare(strict_types=1);

// Let PHP's development server serve public assets without booting the kernel.
if (PHP_SAPI === 'cli-server') {
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    $file = realpath(__DIR__ . '/' . ltrim($path, '/'));
    $extensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
    if ($file !== false && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
        && is_file($file) && !preg_match('~(?:^|/)\.~', $path)
        && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) {
        return false;
    }
}

try {
    /** @var \WpsMicro\Core\Kernel $kernel */
    $kernel = require dirname(__DIR__) . '/bootstrap/app.php';
    $kernel->handleGlobals()->send();
} catch (\Throwable $exception) {
    error_log(sprintf(
        '%s in %s:%d',
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));

    http_response_code(500);
    echo 'Internal Server Error';
}
