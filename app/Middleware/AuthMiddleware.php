<?php

declare(strict_types=1);

namespace App\Middleware;

use WpsMicro\Core\JsonResponse;
use WpsMicro\Core\Middleware;
use WpsMicro\Core\RedirectResponse;
use WpsMicro\Core\Request;
use WpsMicro\Core\Response;
use WpsMicro\Core\Session;

class AuthMiddleware implements Middleware
{
    /**
     * Session storage used to resolve the current user.
     */
    private Session $session;

    /**
     * Create the authentication middleware.
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Allow authenticated requests to continue through the pipeline.
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->session->has('user_id')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        return new RedirectResponse('/login');
    }
}
