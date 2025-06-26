<?php
namespace Lacebox\Shoelace;

/**
 * Optional base class you can extend to reduce boilerplate.
 */
abstract class AbstractPlugin implements PluginInterface
{
    public function register(RouterInterface $router, array $config): void {}
    public function boot(array $config): void {}
}