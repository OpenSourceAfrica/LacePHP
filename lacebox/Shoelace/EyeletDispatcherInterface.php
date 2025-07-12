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

namespace Lacebox\Shoelace;

/**
 * Simple pub/sub interface for framework events.
 */
interface EyeletDispatcherInterface
{
    /**
     * Register a listener for a named event.
     *
     * @param string   $eventName
     * @param callable $listener  function(Event $event): void
     */
    public function listen(string $eventName, callable $listener): void;

    /**
     * Fire an event, invoking all its listeners.
     *
     * @param string $eventName
     * @param mixed  $payload    any data passed to listeners
     */
    public function dispatch(string $eventName, $payload = null): void;
}