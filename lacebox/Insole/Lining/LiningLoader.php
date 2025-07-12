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

namespace Lacebox\Insole\Lining;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Shoelace\LiningInterface;

class LiningLoader {
    use SingletonTrait;

    private function __construct(){}
    private function __clone(){}
    public function load(string $version): LiningInterface {

        if ($version === '8' && class_exists(Php8Lining::class)) {echo "hello";
            return new Php8Lining();
        }

        if (class_exists(Php7Lining::class)) {
            return new Php7Lining();
        }

        throw new \RuntimeException("No valid PHP Lining class available for version: $version");
    }
}