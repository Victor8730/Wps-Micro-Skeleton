<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use WpsMicro\Core\Config;
use WpsMicro\Core\Kernel;
use WpsMicro\Core\Request;

final class ApplicationTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/wps-micro-app-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->storagePath);
    }

    public function testLoginPageRunsThroughTheInstalledFramework(): void
    {
        $rootPath = dirname(__DIR__, 2);
        $kernel = new Kernel(new Config([
            'app' => [
                'debug' => false,
                'url' => 'https://example.test',
            ],
            'router' => [
                'routes_path' => $rootPath . '/routes/web.php',
            ],
            'middleware' => [
                'global' => [],
                'route' => [],
            ],
            'session' => [
                'secure' => false,
            ],
            'logging' => [
                'path' => $this->storagePath . '/logs/app.log',
            ],
            'twig' => [
                'views_path' => $rootPath . '/resources/views',
                'cache_path' => $this->storagePath . '/cache/twig',
                'auto_reload' => false,
                'autoescape' => 'html',
            ],
            'vite' => [
                'manifest_path' => $rootPath . '/tests/Fixtures/vite-manifest.json',
                'build_path' => 'build',
            ],
        ]));
        $kernel->getContainer()->instance(\PDO::class, new \PDO('sqlite::memory:'));

        $response = $kernel->handle(new Request('GET', '/login'));

        self::assertSame(200, $response->getStatusCode(), $response->getContent());
        self::assertStringContainsString('<title>Log in | WPS Micro</title>', $response->getContent());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
