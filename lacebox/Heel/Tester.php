<?php

namespace Lacebox\Sole\Heel;

use Lacebox\Sole\Router;
use Lacebox\Sole\DebugCollector;

/**
 * Tester provides a fluent DSL for HTTP route testing.
 */
class Tester
{
    /** @var Router */
    protected $router;
    /** @var int */
    protected $status;
    /** @var mixed */
    protected $response;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Simulate a GET request.
     */
    public function get(string $uri): self
    {
        // Setup superglobals
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = $uri;

        // Reset debug entries if collector exists
        if (class_exists(DebugCollector::class)) {
            $ref  = new \ReflectionClass(DebugCollector::class);
            $prop = $ref->getProperty('entries');
            $prop->setAccessible(true);
            $prop->setValue([]);
        }

        // Dispatch and capture response
        $this->response = $this->router->dispatch();
        $this->status   = http_response_code();

        return $this;
    }

    /**
     * Assert expected HTTP status code.
     */
    public function expect(int $code): self
    {
        if ($this->status !== $code) {
            throw new \Exception("Expected status {$code}, got {$this->status}");
        }
        return $this;
    }

    /**
     * Assert JSON response matches exactly the expected array.
     */
    public function json(array $expected): self
    {
        $actual = is_string($this->response)
            ? json_decode($this->response, true)
            : $this->response;

        if ($actual !== $expected) {
            $exp = var_export($expected, true);
            $act = var_export($actual,   true);
            throw new \Exception("JSON mismatch:\nExpected: {\$exp}\nActual:   {\$act}");
        }
        return $this;
    }

    /**
     * Retrieve the raw response value.
     */
    public function raw()
    {
        return $this->response;
    }
}