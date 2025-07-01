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

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Sole\Http\ShoeResponder;

class ConfigLoader
{
    use SingletonTrait;

    public function load(): array
    {
        $base = dirname(__DIR__, 2);

        //
        // 1) lace.json
        //
        $jsonPath = $base . '/lace.json';
        $json     = file_exists($jsonPath)
            ? json_decode(file_get_contents($jsonPath), true) ?: []
            : [];

        // Decide which “app config” file to load
        $appConfigName = 'lace';
        $appConfigFile = "{$base}/config/{$appConfigName}.php";

        $config_error = "Missing application config file: config{$appConfigName}.php";
        if (! file_exists($appConfigFile)) {
            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, '❌ ' . $config_error . PHP_EOL);
                exit(1);
            } else {
                // in a web request
                echo ShoeResponder::getInstance()->serverError($config_error);
                exit;
            }
        }

        $appConfig = include $appConfigFile;

        //
        // 2) environment-specific overrides
        //
        //   lace.json may name an env (e.g. “production”), or fallback to json->lace_env
        $envName   = $json['lace_config'] ?? 'config_production';
        $envConfigFile = "{$base}/config/{$envName}.php";

        $envConfig = [];
        if (file_exists($envConfigFile)) {
            $envConfig = include $envConfigFile;
            if (! is_array($envConfig)) {

                $config_error = "Config file did not return an array: config/{$envName}.php";

                if (! file_exists($appConfigFile)) {
                    if (php_sapi_name() === 'cli') {
                        fwrite(STDERR, '❌ ' . $config_error . PHP_EOL);
                        exit(1);
                    } else {
                        // in a web request
                        echo ShoeResponder::getInstance()->serverError($config_error);
                        exit;
                    }
                }
            }
        }

        //
        // 3) .env overrides
        //
        $rawEnv = [];
        $envPath = $base . '/.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $m)) {
                    $rawEnv[$m[1]] = $m[2];
                }
            }
        }

        // Map only the well-known ones into our structure; leave the rest as pass-through
        $mappedEnv = [
            'sole_version'  => $rawEnv['SOLE_VERSION']  ?? $appConfig['sole_version'] ?? null,
            'lace_env'      => $rawEnv['LACE_ENV']      ?? $json['lace_env'] ?? null,
            'show_blisters' => $rawEnv['SHOW_BLISTERS'] ?? $appConfig['boot']['show_blisters'] ?? null,
            'brand_name'    => $rawEnv['BRAND_NAME']    ?? $json['brand_name'] ?? null,
            'base_url'      => $rawEnv['LACE_BASE_URL'] ?? $appConfig['base_url'] ?? null,
            'grip_level'    => $rawEnv['GRIP_LEVEL']    ?? $appConfig['grip_level'] ?? null,
        ];
        foreach ($rawEnv as $k => $v) {
            if (! in_array($k, [
                'SOLE_VERSION','LACE_ENV','SHOW_BLISTERS',
                'BRAND_NAME','LACE_BASE_URL','GRIP_LEVEL',
            ], true)) {
                $mappedEnv[$k] = $v;
            }
        }

        //
        // 4) Merge everything:
        //     lace.json  <-  config/lace.php  <-  config/{env}.php  <-  .env
        //
        return array_replace_recursive(
            $json,
            $appConfig,
            $envConfig,
            $mappedEnv
        );
    }
}