<?php

use Weave\Controllers\LaceUpController;
use Weave\Controllers\DocsDemoController;
use Lacebox\Knots\RateLimitKnots;

$router = $router ?? null;
if (!$router) {
    throw new RuntimeException('Router instance is not injected into route file.');
}

$router->get('/test', function() {
    return "Hello!";
});

$router->sewGet('/', [LaceUpController::class, 'hello']);
$router->sewGet('/hello', [LaceUpController::class, 'html']);

$router->get('/docs-demo', [DocsDemoController::class, 'index']);

$router->addRoute('GET', '/hi', [LaceUpController::class, 'hello'], [
        [ RateLimitKnots::class, [1, 60] ]
    ]
);

$router->addRoute('GET', '/secure-endpoint', [LaceUpController::class, 'hello'], [
    '_guard' => 'token',
]);