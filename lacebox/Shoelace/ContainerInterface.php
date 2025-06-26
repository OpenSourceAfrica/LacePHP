<?php

namespace Lacebox\Shoelace;

interface ContainerInterface
{
    /**
     * Register a binding in the container.
     *
     * @param string $id
     * @param callable $concrete
     */
    public function bind(string $id, callable $concrete);

    /**
     * Resolve an instance by ID or class name.
     *
     * @param string $id
     * @return mixed
     */
    public function get(string $id);

    /**
     * Create or resolve a class instance.
     *
     * @param string $class
     * @return mixed
     */
    public function make(string $class);
}
