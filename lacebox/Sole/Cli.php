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

/**
 * A minimal console registry for `php lace ...`
 */
class Cli
{
    /** @var array<string, array{description:string,callback:callable}> */
    protected $commands = [];

    /**
     * Register a new console command.
     *
     * @param  string   $name        e.g. "ai:status"
     * @param  string   $description
     * @param  callable $callback    fn(array $argv): void
     */
    public function register(string $name, string $description, callable $callback): void
    {
        $this->commands[$name] = [
            'description' => $description,
            'callback'    => $callback,
        ];
    }


    /**
     * Run the CLI: dispatch $argv to the matching callback.
     */
    public function run(array $argv): void
    {
        // nothing provided or explicit help
        $cmd0 = $argv[1] ?? '';
        if ($cmd0 === '' || $cmd0 === 'help' || $cmd0 === 'list') {
            $this->printHelp();
            return;
        }

        // try exact match on first token (for colon commands)
        if (isset($this->commands[$cmd0])) {
            ($this->commands[$cmd0]['callback'])($argv);
            return;
        }

        // try two‐token match (for space‐separated commands)
        $cmd1 = $argv[2] ?? '';
        if ($cmd1 !== '') {
            $two = $cmd0 . ' ' . $cmd1;
            if (isset($this->commands[$two])) {
                ($this->commands[$two]['callback'])($argv);
                return;
            }
        }

        // unknown command
        fwrite(STDERR, "Unknown command: “{$cmd0}”\n\n");
        $this->printHelp();
    }

    protected function printHelp(): void
    {
        echo "\n👟 lacePHP CLI\n";
        echo "Available commands:\n";

        printf("  %-20s %s\n", "list", "list all commands available");
        foreach ($this->commands as $name => $cmd) {
            printf("  %-20s %s\n", $name, $cmd['description']);
        }
        echo "\n";
    }
}