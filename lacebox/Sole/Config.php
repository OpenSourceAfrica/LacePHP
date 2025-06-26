<?php
namespace Lacebox\Sole;

use ArrayAccess;

class Config implements ArrayAccess
{
    /** @var array */
    private $data;

    /** @var self|null */
    private static $instance;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $raw = ConfigLoader::load();
            self::$instance = new self($raw);
        }

        return self::$instance;
    }

    // array‐style reads
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    // optionally support writes if you need them
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    // explicit getters also still work
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }
}