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

namespace Lacebox\Shoelace;

interface CommandInterface
{
    /** e.g. “route:list” or “stitch controller” */
    public function name(): string;

    /** Short description for “--help” */
    public function description(): string;

    /**
     * Return true if this command wants to handle the given argv.
     */
    public function matches(array $argv): bool;

    /**
     * Execute the command. Should echo/exit as needed.
     */
    public function run(array $argv): void;
}