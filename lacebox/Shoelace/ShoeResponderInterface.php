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

interface ShoeResponderInterface
{
    public function json($data, int $statusCode = 200): string;

    public function html(string $html, int $statusCode = 200): string;

    public function text(string $text, int $statusCode = 200): string;

    public function error(string $message, int $statusCode = 500): string;

    public function unauthorized(string $message = 'Unauthorized'): string;

    public function notFound(string $message = 'Not Found'): string;

    public function serverError(string $message = 'Internal Server Error'): string;

    public function setHeader(string $name, string $value): void;

    public function getHeaders(): array;
}
