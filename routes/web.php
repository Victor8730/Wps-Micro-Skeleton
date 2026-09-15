<?php

declare(strict_types=1);

use App\Controllers\ControllerAuth;
use App\Controllers\ControllerHome;
use App\Controllers\ControllerMessage;
use App\Middleware\AuthMiddleware;
use WpsMicro\Core\Router;

return static function (Router $router): void {
    $router->get('/', [ControllerHome::class, 'actionIndex'])->name('home');

    $router->get('/login', [ControllerAuth::class, 'actionLogin'])->name('login');
    $router->post('/login', [ControllerAuth::class, 'actionAuthenticate'])->name('login.store');
    $router->get('/register', [ControllerAuth::class, 'actionRegister'])->name('register');
    $router->post('/register', [ControllerAuth::class, 'actionStore'])->name('register.store');

    $router->group(['middleware' => AuthMiddleware::class], static function (Router $router): void {
        $router->get('/account', [ControllerAuth::class, 'actionAccount'])->name('account');
        $router->post('/logout', [ControllerAuth::class, 'actionLogout'])->name('logout');
        $router->group(['prefix' => '/messages', 'name' => 'messages.'], static function (Router $router): void {
            $router->get('/', [ControllerMessage::class, 'actionIndex'])->name('index');
            $router->get('/create', [ControllerMessage::class, 'actionCreate'])->name('create');
            $router->post('/', [ControllerMessage::class, 'actionStore'])->name('store');
            $router->get('/{id}/edit', [ControllerMessage::class, 'actionEdit'])->whereNumber('id')->name('edit');
            $router->put('/{id}', [ControllerMessage::class, 'actionUpdate'])->whereNumber('id')->name('update');
            $router->delete('/{id}', [ControllerMessage::class, 'actionDelete'])->whereNumber('id')->name('delete');
        });
    });
};
