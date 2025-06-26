<?php

namespace Lacebox\Shoelace;

interface DispatcherInterface
{
    /**
     * Dispatch the current HTTP request.
     *
     * Should resolve the route, apply middleware, and invoke the handler.
     */
    public function dispatch();
}