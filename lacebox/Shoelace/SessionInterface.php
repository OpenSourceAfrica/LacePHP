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

interface SessionInterface
{
    /**
     * Ensure the session is started.
     *
     * @return bool true if started or already active
     */
    public function start(): bool;

    /**
     * Has the session been started for this request?
     */
    public function isStarted(): bool;

    /**
     * Get current session ID (empty string if none).
     */
    public function id(): string;

    /**
     * Check if a key exists in the session.
     *
     * @param string $key
     */
    public function has($key): bool;

    /**
     * Get a value from the session.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Store a value in the session.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function put($key, $value): void;

    /**
     * Forget a key from the session.
     *
     * @param string $key
     */
    public function forget($key): void;

    /**
     * Get all session data.
     *
     * @return array
     */
    public function all(): array;

    /**
     * Remove all session data (but keep session itself).
     */
    public function flush(): void;

    /**
     * Regenerate session ID.
     *
     * @param bool $deleteOld
     */
    public function regenerate(bool $deleteOld = true): void;

    /**
     * Destroy the session completely.
     */
    public function destroy(): void;
}