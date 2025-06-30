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

namespace Lacebox\Strap\Guards;

use Lacebox\Shoelace\Guards\ShoeGuardInterface;

class ShoeTokenGuard implements ShoeGuardInterface
{
    /** @var string */
    protected $token;

    /** @var string[] */
    protected $secrets;

    /**
     * @param string[] $secrets  List of valid bearer tokens
     */
    public function __construct(array $secrets)
    {
        $this->secrets = $secrets;

        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? ($_SERVER['Authorization'] ?? '');

        if (preg_match('/Bearer\s+(\S+)/', $header, $m)) {
            $this->token = $m[1];
        } else {
            $this->token = '';
        }
    }

    public function check(): bool
    {
        if (empty($this->token) || empty($this->secrets)) {
            return false;
        }

        // Return true as soon as *any* secret matches exactly:
        foreach ($this->secrets as $secret) {
            if (hash_equals((string)$secret, $this->token)) {
                return true;
            }
        }

        return false;
    }
}