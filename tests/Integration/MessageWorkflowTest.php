<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Home;
use App\Models\User;
use App\Services\DemoSeeder;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\BrowserSession;
use WpsMicro\Core\Config;
use WpsMicro\Core\Container;
use WpsMicro\Core\Csrf;
use WpsMicro\Core\Kernel;
use WpsMicro\Core\Middleware\CsrfMiddleware;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Session;

final class MessageWorkflowTest extends TestCase
{
    private PDO $db;
    private BrowserSession $session;
    private string $storage;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT UNIQUE, password TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $this->db->exec('CREATE TABLE home_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, body TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $migration = require dirname(__DIR__, 2) . '/database/migrations/2026_09_15_000003_add_message_ownership.php';
        $migration->up($this->db);
        $this->session = new BrowserSession();
        $this->storage = sys_get_temp_dir() . '/wps-browser-' . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->storage)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->storage, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->storage);
    }

    public function testRegistrationLoginCrudAndLogout(): void
    {
        self::assertSame(200, $this->send('GET', '/register')->getStatusCode());
        $response = $this->send('POST', '/register', [
            'name' => 'Victor', 'email' => 'victor@example.com',
            'password' => 'password-123', 'password_confirmation' => 'password-123',
        ]);
        self::assertSame('/account', $response->getHeaders()['Location']);
        self::assertSame(1, $this->session->regenerations);
        self::assertStringContainsString('Victor', $this->send('GET', '/account')->getContent());
        self::assertSame(200, $this->send('GET', '/messages/create')->getStatusCode());
        $response = $this->send('POST', '/messages', ['title' => 'Private title', 'body' => '<script>alert(1)</script>', 'user_id' => 999]);
        self::assertSame('/messages', $response->getHeaders()['Location']);
        $id = (int) $this->db->lastInsertId();
        $listing = $this->send('GET', '/messages')->getContent();
        self::assertStringContainsString('Private title', $listing);
        self::assertStringContainsString('&lt;script&gt;', $listing);
        self::assertStringNotContainsString('Private title', $this->send('GET', '/')->getContent());
        self::assertStringContainsString('Private title', $this->send('GET', '/messages/' . $id . '/edit')->getContent());
        self::assertSame(302, $this->send('POST', '/messages/' . $id, ['_method' => 'PUT', 'title' => 'Edited', 'body' => 'Saved'])->getStatusCode());
        self::assertSame('Edited', $this->db->query('SELECT title FROM home_messages')->fetchColumn());
        self::assertSame(302, $this->send('POST', '/messages/' . $id, ['_method' => 'DELETE'])->getStatusCode());
        self::assertSame(0, (int) $this->db->query('SELECT COUNT(*) FROM home_messages')->fetchColumn());
        self::assertSame(302, $this->send('POST', '/logout')->getStatusCode());
        self::assertSame('/login', $this->send('GET', '/messages')->getHeaders()['Location']);
        self::assertSame(302, $this->send('POST', '/login', ['email' => 'victor@example.com', 'password' => 'password-123'])->getStatusCode());
        self::assertSame(200, $this->send('GET', '/account')->getStatusCode());
    }

    public function testForeignRecordsCannotBeReadEditedOrDeleted(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');
        $id = (new Home($this->db))->createForUser($owner, 'Owner secret', 'Private');
        $this->session->set('user_id', $other);
        self::assertStringNotContainsString('Owner secret', $this->send('GET', '/messages')->getContent());
        foreach ([['GET', '/edit'], ['PUT', ''], ['DELETE', '']] as [$method, $suffix]) {
            self::assertSame(404, $this->send($method, '/messages/' . $id . $suffix, ['title' => 'Hacked', 'body' => 'Hacked'], ['Accept' => 'application/json'])->getStatusCode());
        }
        self::assertSame('Owner secret', (new Home($this->db))->findForUser($id, $owner)['title']);
    }

    public function testValidationPreservesSafeInputAndRendersErrors(): void
    {
        $this->session->set('user_id', $this->user('writer@example.com'));
        $response = $this->send('POST', '/messages', ['title' => '', 'body' => 'Keep this text'], ['Referer' => 'https://example.test/messages/create']);
        self::assertSame('/messages/create', $response->getHeaders()['Location']);
        $form = $this->send('GET', '/messages/create')->getContent();
        self::assertStringContainsString('title is required.', $form);
        self::assertStringContainsString('Keep this text', $form);
        self::assertStringContainsString('aria-describedby="title-error"', $form);
        self::assertSame(0, (new Home($this->db))->countForUser((int) $this->session->get('user_id')));
        self::assertSame(422, $this->send('POST', '/messages', ['title' => [], 'body' => 'Text'], ['Accept' => 'application/json'])->getStatusCode());
    }

    public function testCsrfAndStaleSessionsAreRejected(): void
    {
        $id = $this->user('csrf@example.com');
        $this->session->set('user_id', $id);
        self::assertSame(419, $this->send('POST', '/messages', ['title' => 'No token', 'body' => 'No token'], [], false)->getStatusCode());
        $this->db->exec('DELETE FROM users');
        self::assertSame(401, $this->send('GET', '/messages', [], ['Accept' => 'application/json'])->getStatusCode());
        self::assertFalse($this->session->has('user_id'));
    }

    public function testPaginationAndDemoSeeding(): void
    {
        $id = $this->user('demo@example.com');
        $this->session->set('user_id', $id);
        $seeder = new DemoSeeder($this->db);
        self::assertSame(3, $seeder->seedForUser($id));
        self::assertSame(0, $seeder->seedForUser($id));
        $messages = new Home($this->db);
        for ($i = 0; $i < 12; ++$i) {
            $messages->createForUser($id, 'Message ' . $i, 'Content');
        }
        $response = $this->send('GET', '/messages', [], [], true, ['page' => 2]);
        self::assertStringContainsString('Page 2 of 2', $response->getContent());
        self::assertSame(5, substr_count($response->getContent(), '<article'));
        self::assertSame(200, $this->send('GET', '/messages', [], [], true, ['page' => ['bad']])->getStatusCode());
    }

    public function testOwnershipMigrationCanRollBackWithoutLosingLegacyRows(): void
    {
        $this->db->exec("INSERT INTO home_messages (title, body) VALUES ('Legacy', 'Keep me')");
        $migration = require dirname(__DIR__, 2) . '/database/migrations/2026_09_15_000003_add_message_ownership.php';
        $migration->down($this->db);
        self::assertSame('Legacy', $this->db->query('SELECT title FROM home_messages')->fetchColumn());
        $migration->up($this->db);
        self::assertCount(1, (new Home($this->db))->latestMessages());
    }

    private function user(string $email): int
    {
        return (int) (new User($this->db))->create('Writer', $email, password_hash('password-123', PASSWORD_DEFAULT))['id'];
    }

    private function send(string $method, string $path, array $body = [], array $headers = [], bool $csrf = true, array $query = []): Response
    {
        $root = dirname(__DIR__, 2);
        $this->session->nextRequest();
        if ($csrf && !in_array($method, ['GET', 'HEAD'], true)) {
            $body['_token'] = (new Csrf($this->session))->token();
        }
        $container = new Container();
        $container->instance(PDO::class, $this->db);
        $container->instance(Session::class, $this->session);
        $kernel = new Kernel(new Config([
            'app' => ['debug' => true, 'url' => 'https://example.test'],
            'router' => ['routes_path' => $root . '/routes/web.php'],
            'middleware' => ['route' => [CsrfMiddleware::class]],
            'logging' => ['path' => $this->storage . '/logs/app.log'],
            'twig' => ['views_path' => $root . '/resources/views', 'cache_path' => $this->storage . '/twig', 'autoescape' => 'html'],
            'vite' => ['manifest_path' => $root . '/tests/Fixtures/vite-manifest.json', 'build_path' => 'build'],
        ]), $container);

        return $kernel->handle(new Request($method, $path, $query, $body, [], $headers));
    }
}
