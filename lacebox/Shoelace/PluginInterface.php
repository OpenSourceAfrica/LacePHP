<?php
namespace Lacebox\Shoelace;

/**
 * A Shoe-Plugin is any class that wants to extend LacePHP.
 */
interface PluginInterface
{
    /**
     * Called very early—register routes, middleware, commands here.
     *
     * @param \Lacebox\Sole\RouterInterface $router
     * @param array                         $config The merged app config
     */
    public function register(RouterInterface $router, array $config): void;

    /**
     * Called after all plugins are registered—bootstrap, event listeners, etc.
     *
     * @param array $config
     */
    public function boot(array $config): void;
}