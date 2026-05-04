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

class LaceTimer
{
    /**
     * Where to find schedule.json
     */
    protected static function scheduleDir(): string
    {
        return dirname(__DIR__, 2) . '/aglet';
    }

    protected static function scheduleFile(): string
    {
        return self::scheduleDir() . '/schedule.json';
    }

    /**
     * Load raw JSON schedule definitions.
     *
     * @return array{ name:string, cron:string, handler:string }[]
     */
    public function loadSchedule(): array
    {
        echo $dir  = self::scheduleDir();
        $file = self::scheduleFile();

        // ensure the folder exists
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // *** load code‐based tasks automatically ***
        $kernel = $dir . '/kernel.php';
        if (file_exists($kernel)) {
            require_once $kernel;
        }

        if (! file_exists($file)) {
            return [];
        }
        $json = json_decode(file_get_contents($file), true);
        return is_array($json) ? $json : [];
    }


    /**
     * Which tasks are due at this tick?
     */
    public function dueTasks(): array
    {
        $now = time();
        $due = [];

        foreach ($this->loadSchedule() as $task) {
            if ($this->isDue($task['cron'], $now)) {
                $due[] = $task;
            }
        }

        // also include any code-based registrations
        if (function_exists('schedule')) {
            foreach (schedule()->getCodeTasks() as $task) {
                if ($this->isDue($task['cron'], $now)) {
                    $due[] = $task;
                }
            }
        }

        return $due;
    }

    /**
     * Cron-match against a timestamp.
     */
    protected function isDue(string $expr, int $ts): bool
    {
        $timestamp = $timestamp ?? time();

        [$min, $hour, $mday, $mon, $wday] = preg_split('/\s+/', trim($expr));

        return $this->matchCron($min, (int) date('i', $timestamp))
            && $this->matchCron($hour, (int) date('G', $timestamp))
            && $this->matchCron($mday, (int) date('j', $timestamp))
            && $this->matchCron($mon, (int) date('n', $timestamp))
            && $this->matchCron($wday, (int) date('w', $timestamp));
    }

    protected function matchCron(string $field, int $value): bool
    {
        $field = trim($field);

        // Any value
        if ($field === '*') {
            return true;
        }

        // Step values like */5
        if (preg_match('/^\*\/(\d+)$/', $field, $m)) {
            $step = (int) $m[1];
            return $step > 0 && $value % $step === 0;
        }

        // List values like 1,5,10
        if (str_contains($field, ',')) {
            foreach (explode(',', $field) as $part) {
                if ($this->matchCron(trim($part), $value)) {
                    return true;
                }
            }
            return false;
        }

        // Range values like 10-20
        if (preg_match('/^(\d+)-(\d+)$/', $field, $m)) {
            $start = (int) $m[1];
            $end   = (int) $m[2];
            return $value >= $start && $value <= $end;
        }

        // Exact numeric value
        if (ctype_digit($field)) {
            return (int) $field === $value;
        }

        return false;
    }

    /**
     * Run all due tasks.
     */
    public function runDue(): void
    {
        $due = $this->dueTasks();
        if (empty($due)) {
            echo " No tasks due right now.\n";
            return;
        }

        foreach ($due as $task) {
            echo "Running “{$task['name']}”… ";
            $this->invokeHandler($task['handler']);
            echo "\n";
        }
    }

    protected function invokeHandler(string $handler): void
    {
        if (strpos($handler, '@') !== false) {
            list($class, $method) = explode('@', $handler, 2);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                throw new \RuntimeException("Invalid task handler: {$handler}");
            }
            (new $class())->{$method}();

        } else {
            // shell command
            passthru($handler, $rc);
            if ($rc !== 0) {
                throw new \RuntimeException("Command failed: {$handler} (exit {$rc})");
            }
        }
    }
}