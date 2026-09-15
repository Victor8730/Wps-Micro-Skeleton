<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use WpsMicro\Core\JsonResponse;
use WpsMicro\Core\Middleware;
use WpsMicro\Core\RedirectResponse;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Router;

class AuthMiddleware implements Middleware
{
    /**
     * Session storage used to resolve the current user.
     */
    private AuthService $auth;

    /**
     * Create the authentication middleware.
     */
    public function __construct(AuthService $auth, private Router $router)
    {
        $this->auth = $auth;
    }

    /**
     * Allow authenticated requests to continue through the pipeline.
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->auth->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        return new RedirectResponse($this->router->url('login'));
    }
}
