<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.lacephp.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Sole;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Shoelace\EyeletDispatcherInterface;

class EyeletDispatcher implements EyeletDispatcherInterface
{
    use SingletonTrait;

    /** @var EyeletDispatcher|null */
    private static $instance = null;

    /** @var array<string, callable[]> */
    protected $listeners = [];

    public function listen(string $eventName, callable $listener): void
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(string $eventName, $payload = null): void
    {
        $toNotify = $this->listeners[$eventName] ?? [];
        $wildcard = $this->listeners['*'] ?? [];

        foreach (array_merge($toNotify, $wildcard) as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                log_error(
                    '500',
                    "Eyelet listener for {$eventName} failed: " . $e->getMessage()
                );
            }
        }
    }
}
