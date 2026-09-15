<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Router;

final class AuthMiddlewareTest extends TestCase
{
    public function testItAllowsAuthenticatedRequests(): void
    {
        $middleware = $this->middleware(true);

        $response = $middleware->handle(
            new Request('GET', '/account'),
            static fn (): Response => new Response('account')
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('account', $response->getContent());
    }

    public function testItReturnsJsonForUnauthenticatedApiRequests(): void
    {
        $middleware = $this->middleware(false);

        $response = $middleware->handle(
            new Request('GET', '/account', [], [], [], ['Accept' => 'application/json']),
            static fn (): Response => new Response('not reached')
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(
            ['message' => 'Unauthenticated.'],
            json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testItRedirectsUnauthenticatedBrowserRequests(): void
    {
        $middleware = $this->middleware(false);

        $response = $middleware->handle(
            new Request('GET', '/account'),
            static fn (): Response => new Response('not reached')
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaders()['Location'] ?? null);
    }

    private function middleware(bool $authenticated): AuthMiddleware
    {
        $router = new Router();
        $router->get('/login', 'Controller@login')->name('login');

        return new AuthMiddleware(new StubAuth($authenticated), $router);
    }
}

final class StubAuth extends AuthService
{
    public function __construct(private readonly bool $authenticated)
    {
    }

    public function check(): bool
    {
        return $this->authenticated;
    }
}
