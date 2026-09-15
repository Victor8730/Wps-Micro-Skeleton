<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Home;
use App\Models\User;
use App\Services\DemoSeeder;
use PDO;
use PHPUnit\Framework\TestCase;
use WpsMicro\Core\Config;
use WpsMicro\Core\Migrator;

final class MariaDbMigrationTest extends TestCase
{
    public function testMigrationAndRollbackWithMariaDb(): void
    {
        $host = getenv('WPS_TEST_MARIADB_HOST');
        if ($host === false || $host === '') {
            self::markTestSkipped('A dedicated MariaDB test database is not configured.');
        }
        $name = getenv('WPS_TEST_MARIADB_DATABASE');
        if ($name !== 'wps_skeleton_test') {
            self::fail('Use only the disposable wps_skeleton_test database.');
        }
        $db = new PDO(
            'mysql:host=' . $host . ';port=' . (getenv('WPS_TEST_MARIADB_PORT') ?: '3306') . ';dbname=' . $name,
            getenv('WPS_TEST_MARIADB_USERNAME'),
            getenv('WPS_TEST_MARIADB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $migrator = new Migrator($db, new Config(['database' => ['migrations_path' => dirname(__DIR__, 2) . '/database/migrations']]));
        self::assertSame([], $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN), 'The test database must be empty.');
        try {
            self::assertCount(3, $migrator->migrate());
            self::assertSame([], $migrator->migrate());
            $user = (new User($db))->create('Demo', 'demo@example.com', password_hash('password-123', PASSWORD_DEFAULT));
            $id = (int) $user['id'];
            self::assertSame(3, (new DemoSeeder($db))->seedForUser($id));
            self::assertSame(0, (new DemoSeeder($db))->seedForUser($id));
            self::assertCount(1, (new Home($db))->latestMessages());
            self::assertSame(3, (new Home($db))->countForUser($id));
            self::assertCount(1, $migrator->rollback());
            self::assertSame(4, (int) $db->query('SELECT COUNT(*) FROM home_messages')->fetchColumn());
            self::assertCount(2, $migrator->rollback(2));
        } finally {
            $db->exec('DROP TABLE IF EXISTS home_messages');
            $db->exec('DROP TABLE IF EXISTS users');
            $db->exec('DROP TABLE IF EXISTS migrations');
        }
    }
}
