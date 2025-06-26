<?php
namespace Lacebox\Insole\Stitching;

/**
 * Provides IoC container capabilities using PHP8 features.
 */
trait Php8ContainerTrait
{
    protected array $bindings = [];

    /**
     * Bind an abstract name to a concrete factory.
     */
    public function bind(string $id, callable $concrete): void
    {
        $this->bindings[$id] = $concrete;
    }

    /**
     * Resolve an abstract from the container.
     */
    public function make(string $id): object
    {
        return isset($this->bindings[$id])
            ? ($this->bindings[$id])()
            : new $id();
    }

    /**
     * Alias for make().
     */
    public function get(string $id): object
    {
        return $this->make($id);
    }
}