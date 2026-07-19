<?php

declare(strict_types=1);

use App\Controllers\Controller404;
use WpsMicro\Core\Env;
use WpsMicro\Core\Middleware\CsrfMiddleware;

$rootPath = dirname(__DIR__);
$environment = (string) Env::get('APP_ENV', 'production');
$debug = Env::bool('APP_DEBUG', false);
$isProduction = $environment === 'production';
$path = static function (string $key, string $default) use ($rootPath): string {
    $value = (string) Env::get($key, $default);

    if (strpos($value, '/') === 0) {
        return $value;
    }

    return $rootPath . '/' . ltrim($value, '/');
};

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'WPS Micro'),
        'env' => $environment,
        'debug' => $debug,
        'url' => Env::get('APP_URL', 'http://localhost:8000'),
    ],
    'router' => [
        'routes_path' => $path('ROUTES_PATH', 'routes/web.php'),
    ],
    'middleware' => [
        'global' => [],
        'route' => [
            CsrfMiddleware::class,
        ],
    ],
    'session' => [
        'name' => Env::get('SESSION_NAME', 'WPSMICROSESSID'),
        'lifetime' => (int) Env::get('SESSION_LIFETIME', 0),
        'path' => Env::get('SESSION_PATH', '/'),
        'domain' => Env::get('SESSION_DOMAIN', ''),
        'secure' => Env::bool('SESSION_SECURE', $isProduction),
        'http_only' => Env::bool('SESSION_HTTP_ONLY', true),
        'same_site' => Env::get('SESSION_SAME_SITE', 'Lax'),
    ],
    'logging' => [
        'path' => $path('LOG_PATH', 'storage/logs/app.log'),
    ],
    'errors' => [
        'not_found' => [Controller404::class, 'actionIndex'],
    ],
    'vite' => [
        'dev_server_url' => Env::get('VITE_DEV_SERVER_URL', ''),
        'manifest_path' => $path('VITE_MANIFEST_PATH', 'public/build/.vite/manifest.json'),
        'build_path' => Env::get('VITE_BUILD_PATH', 'build'),
    ],
    'twig' => [
        'views_path' => $path('TWIG_VIEWS_PATH', 'resources/views'),
        'cache_path' => $path('TWIG_CACHE_PATH', 'storage/cache/twig'),
        'auto_reload' => Env::bool('TWIG_AUTO_RELOAD', $debug),
        'autoescape' => Env::get('TWIG_AUTOESCAPE', 'html'),
    ],
    'database' => [
        'driver' => Env::get('DB_DRIVER', 'mysql'),
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'wps_micro'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
        'migrations_path' => $path('DB_MIGRATIONS_PATH', 'database/migrations'),
    ],
];
