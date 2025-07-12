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
 * A Shoe-Plugin is any class that wants to extend LacePHP.
 */
interface PluginInterface
{
    /**
     * Called very early—register routes, middleware, commands here.
     *
     * @param RouterInterface $router
     * @param array                         $config The merged app config
     */
    public function register(RouterInterface $router, array $config): void;

    /**
     * Called after all plugins are registered—bootstrap, event listeners, etc.
     *
     * @param array $config
     */
    public function boot(array $config): void;
}