<?php

declare(strict_types=1);

use App\Controllers\ControllerAuth;
use App\Controllers\ControllerHome;
use App\Middleware\AuthMiddleware;
use WpsMicro\Core\Router;

return static function (Router $router): void {
    $router->get('/', [ControllerHome::class, 'actionIndex']);

    $router->get('/login', [ControllerAuth::class, 'actionLogin']);
    $router->post('/login', [ControllerAuth::class, 'actionAuthenticate']);
    $router->get('/register', [ControllerAuth::class, 'actionRegister']);
    $router->post('/register', [ControllerAuth::class, 'actionStore']);

    $router->get('/account', [ControllerAuth::class, 'actionAccount'])
        ->middleware(AuthMiddleware::class);
    $router->post('/logout', [ControllerAuth::class, 'actionLogout'])
        ->middleware(AuthMiddleware::class);
};
