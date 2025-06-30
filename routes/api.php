<?php

use Weave\Controllers\LaceUpController;
use Weave\Controllers\DocsDemoController;
use Lacebox\Knots\RateLimitKnots;

$router = $router ?? null;
if (!$router) {
    throw new RuntimeException('Router instance is not injected into route file.');
}

$router->sewGet('/', [LaceUpController::class, 'hello']);
$router->get('/db', [LaceUpController::class, 'test']);
$router->get('/docs-demo', [DocsDemoController::class, 'index']);

$router->addRoute('GET', '/hi', [LaceUpController::class, 'hello'], [
        [ RateLimitKnots::class, [1, 60] ]
    ]
);

$router->addRoute('GET', '/secure-endpoint', [LaceUpController::class, 'hello'], [
    '_guard' => 'token',
]);

$router->group([
    'prefix'     => '/admin',
    'middleware' => [RateLimitKnots::class, [1, 60] ],
    //'namespace'  => 'Admin',           // auto‐prepended to Weave\Controllers\Admin\...
], function($r) {
    $r->get('/dashboard', ['LaceUpController','hello']);
    $r->post('/users',     ['UserController','store']);
});

// outside a group, nothing is prefixed or namespaced automatically
$router->get('/public', ['PublicController','index']);
