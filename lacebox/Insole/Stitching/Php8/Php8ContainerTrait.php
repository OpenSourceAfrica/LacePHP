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

namespace Lacebox\Insole\Stitching\Php8;

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