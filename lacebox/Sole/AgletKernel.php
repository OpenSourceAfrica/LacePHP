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

class AgletKernel
{
    /** @var array[] */
    protected $codeTasks = [];

    /**
     * Register a task in code.
     *
     * @param string $name
     * @param string $cron        e.g. '0 * * * *'
     * @param string $handler     'Class@method' or shell
     */
    public function task(string $name, string $cron, string $handler): void
    {
        $this->codeTasks[] = compact('name','cron','handler');
    }

    /**
     * @return array[]  All code-registered tasks
     */
    public function getCodeTasks(): array
    {
        return $this->codeTasks;
    }
}