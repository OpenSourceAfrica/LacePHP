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

use Lacebox\Insole\Stitching\LiningCoreTrait;
use Lacebox\Insole\Stitching\Php7\Php7ContainerTrait;
use Lacebox\Insole\Stitching\Php7\Php7DispatcherTrait;
use Lacebox\Shoelace\LiningInterface;

/**
 * PHP7 lining implementation: routing, dispatching, and basic container.
 */
class Php7Lining implements LiningInterface
{
    use LiningCoreTrait;
    use Php7DispatcherTrait;
    use Php7ContainerTrait;

    /**
     * Optionally load middleware groups from config.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['middleware_groups']) && is_array($config['middleware_groups'])) {
            $this->middlewareGroups = $config['middleware_groups'];
        }
    }
}
