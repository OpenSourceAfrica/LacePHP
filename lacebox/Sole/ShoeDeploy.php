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

namespace Lacebox\Sole;

class ShoeDeploy
{
    public static function run(?string $envName): void
    {
        $configPath = getcwd() . '/shoedeploy.php';
        if (! file_exists($configPath)) {
            fwrite(STDERR, "shoedeploy.php not found in project root\n");
            exit(1);
        }

        $config = include $configPath;
        $envName = $envName ?? ($config['default'] ?? null);
        if (! $envName || ! isset($config['environments'][$envName])) {
            fwrite(STDERR, "❌ Unknown environment “{$envName}”\n");
            exit(1);
        }
        $env = $config['environments'][$envName];

        echo "🚀 Deploying to [{$envName}] {$env['user']}@{$env['host']}:{$env['path']}\n";

        // 1) beforeDeploy hook
        if (! empty($config['hooks']['beforeDeploy'])) {
            call_user_func($config['hooks']['beforeDeploy']);
        }

        // 2) remote commands via ssh
        $releaseDir = $env['path'] . '/releases/' . date('YmdHis');
        $commands = [
            // create release dir
            "ssh {$env['user']}@{$env['host']} 'mkdir -p {$releaseDir}'",
            // clone or fetch repo
            "ssh {$env['user']}@{$env['host']} 'git clone --branch {$env['branch']} . {$releaseDir}'",
            // update current symlink
            "ssh {$env['user']}@{$env['host']} 'ln -nfs {$releaseDir} {$env['path']}/current'",
            // install dependencies
            "ssh {$env['user']}@{$env['host']} 'cd {$env['path']}/current && composer install --no-dev --optimize-autoloader'",
        ];

        foreach ($commands as $cmd) {
            echo "▶️  $cmd\n";
            passthru($cmd, $ret);
            if ($ret !== 0) {
                fwrite(STDERR, "❌ Command failed: $cmd\n");
                exit(1);
            }
        }

        // 3) afterDeploy hook
        if (! empty($config['hooks']['afterDeploy'])) {
            call_user_func($config['hooks']['afterDeploy']);
        }

        echo "✅ Deployment to {$envName} succeeded!\n";
    }
}
