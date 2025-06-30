<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole;

use ArrayAccess;
use Lacebox\Insole\Stitching\SingletonTrait;

class Config implements ArrayAccess
{

    use SingletonTrait;

    /** @var array */
    private $data;

    /** @var self|null */
    private static $instance;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    // array‐style reads
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }
    #[\ReturnTypeWillChange]
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