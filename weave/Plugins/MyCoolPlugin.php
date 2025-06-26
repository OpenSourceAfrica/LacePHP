<?php
namespace Weave\Plugins;

use Lacebox\Shoelace\AbstractPlugin;
use Lacebox\Shoelace\RouterInterface;

class MyCoolPlugin extends AbstractPlugin
{
    public function register(RouterInterface $router, array $config): void
    {
        // add a new global route
        $router->addRoute('GET', '/cool', [\Weave\Controllers\LaceUpController::class, 'hello']);
    }

    public function boot(array $config): void
    {
        // application-specific event listener
        on('user.registered', function($user) {
            // …
        });
    }
}
