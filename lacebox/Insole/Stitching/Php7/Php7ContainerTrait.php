<?php

namespace Lacebox\Insole\Stitching\Php7;

/**
 * Provides basic IoC container capabilities for PHP7 lining.
 */
trait Php7ContainerTrait
{
    /** @var array */
    protected $bindings = [];

    /**
     * Bind an abstract name to a concrete factory.
     */
    public function bind(string $id, callable $concrete)
    {
        $this->bindings[$id] = $concrete;
    }

    /**
     * Resolve an abstract from the container.
     */
    public function make(string $id)
    {
        if (isset($this->bindings[$id])) {
            return call_user_func($this->bindings[$id]);
        }
        return new $id();
    }

    /**
     * Alias for make().
     */
    public function get(string $id)
    {
        return $this->make($id);
    }
}