<?php

use Weave\Controllers\LaceUpController;
use Weave\Controllers\DocsDemoController;

$router = $router ?? null;
if (!$router) {
    throw new RuntimeException('Router instance is not injected into route file.');
}

$router->sewGet('/', [LaceUpController::class, 'hello']);
$router->get('/docs-demo', [DocsDemoController::class, 'index']);