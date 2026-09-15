<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Console\CheckCommand;
use App\Console\SeedCommand;
use App\Console\SetupCommand;
use PDO;
use PHPUnit\Framework\TestCase;
use WpsMicro\Core\Config;
use WpsMicro\Core\Console\Commands\RouteListCommand;
use WpsMicro\Core\Container;
use WpsMicro\Core\Router;

final class ConsoleTest extends TestCase
{
    public function testSetupPreservesExistingEnvironmentAndDoesNotRequireADatabase(): void
    {
        $root = sys_get_temp_dir() . '/wps-setup-' . bin2hex(random_bytes(8));
        mkdir($root);
        file_put_contents($root . '/.env_example', "APP_ENV=local\n");
        try {
            $command = new SetupCommand($root);
            ob_start();
            self::assertSame(0, $command->handle([]));
            ob_end_clean();
            self::assertSame("APP_ENV=local\n", file_get_contents($root . '/.env'));
            self::assertDirectoryExists($root . '/storage/cache/twig');
            file_put_contents($root . '/.env', 'EXISTING_SECRET=keep');
            ob_start();
            self::assertSame(0, $command->handle([]));
            ob_end_clean();
            self::assertSame('EXISTING_SECRET=keep', file_get_contents($root . '/.env'));
        } finally {
            unlink($root . '/.env_example');
            unlink($root . '/.env');
            rmdir($root . '/storage/cache/twig');
            rmdir($root . '/storage/cache');
            rmdir($root . '/storage/logs');
            rmdir($root . '/storage');
            rmdir($root);
        }
    }

    public function testRouteListContainsNamedProtectedCrudRoutes(): void
    {
        $router = new Router();
        $routes = require dirname(__DIR__, 2) . '/routes/web.php';
        $routes($router);
        ob_start();
        $result = (new RouteListCommand($router))->handle([]);
        $output = ob_get_clean();
        self::assertSame(0, $result);
        self::assertStringContainsString('messages.update', $output);
        self::assertStringContainsString('PUT', $output);
        self::assertStringContainsString('AuthMiddleware', $output);
    }

    public function testProductionSeedRefusalDoesNotConnectToDatabase(): void
    {
        $container = new Container();
        $container->instance(Config::class, new Config(['app' => ['env' => 'production']]));
        $container->set(PDO::class, static function (): never {
            throw new \LogicException('Database should not be requested.');
        });
        ob_start();
        $result = (new SeedCommand($container))->handle(['--user=test@example.com']);
        $output = ob_get_clean();
        self::assertSame(1, $result);
        self::assertStringContainsString('--force', $output);
    }

    public function testDatabaseCheckDoesNotDiscloseConnectionSecrets(): void
    {
        $container = new Container();
        $container->instance(Config::class, new Config([
            'app' => ['env' => 'local', 'url' => 'http://localhost'],
            'twig' => ['cache_path' => sys_get_temp_dir()],
            'logging' => ['path' => sys_get_temp_dir() . '/app.log'],
            'vite' => ['dev_server_url' => 'http://localhost:5173'],
        ]));
        $container->set(PDO::class, static function (): never {
            throw new \RuntimeException('Password=do-not-print');
        });
        ob_start();
        $result = (new CheckCommand($container))->handle(['--database']);
        $output = ob_get_clean();
        self::assertSame(1, $result);
        self::assertStringContainsString('[FAIL] Database connection', $output);
        self::assertStringNotContainsString('do-not-print', $output);
    }
}
