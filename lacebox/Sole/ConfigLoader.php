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
                fwrite(STDERR,$config_error . PHP_EOL);
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
        $envName   = $json['lace_config'] ?? 'config_local';
        $envConfigFile = "{$base}/config/{$envName}.php";

        $envConfig = [];
        if (file_exists($envConfigFile)) {
            $envConfig = include $envConfigFile;
            if (! is_array($envConfig)) {

                $config_error = "Config file did not return an array: config/{$envName}.php";

                if (! file_exists($appConfigFile)) {
                    if (php_sapi_name() === 'cli') {
                        fwrite(STDERR,$config_error . PHP_EOL);
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
        $rawEnv = Env::all();

        // Map only the well-known ones into our structure; leave the rest as pass-through
        $mappedEnv = [
            'sole_version'  => $rawEnv['SOLE_VERSION']  ?? $appConfig['sole_version'] ?? null,
            'lace_env'      => $rawEnv['LACE_ENV']      ?? $json['lace_env'] ?? null,
            'show_blisters' => $rawEnv['LACE_APP_SHOW_BLISTERS'] ?? $appConfig['boot']['show_blisters'] ?? null,
            'brand_name'    => $rawEnv['LACE_APP_BRAND_NAME']    ?? $json['brand_name'] ?? null,
            'base_url'      => $rawEnv['LACE_APP_BASE_URL'] ?? $appConfig['base_url'] ?? null,
            'grip_level'    => $rawEnv['LACE_APP_GRIP_LEVEL']    ?? $appConfig['grip_level'] ?? null,
        ];
        foreach ($rawEnv as $k => $v) {
            if (! in_array($k, [
                'SOLE_VERSION','LACE_ENV','LACE_APP_SHOW_BLISTERS',
                'LACE_APP_BRAND_NAME','LACE_APP_BASE_URL','LACE_APP_GRIP_LEVEL',
            ], true)) {
                $mappedEnv[$k] = $v;
            }
        }

        //
        // 4) Merge everything:
        //     lace.json  <-  config/lace.php  <-  config/{env}.php  <-  .env
        //
        $core = array_replace_recursive(
            $json,
            $appConfig,
            $envConfig,
            $mappedEnv
        );

        //
        // 5) now pull in *every* other file in config/*.php
        //
        foreach (glob($base . '/config/*.php') as $file) {
            $name = basename($file, '.php');

            // skip the two we’ve already loaded
            if ($name === 'lace' || $name === $envName) {
                continue;
            }

            // include it under its filename as a key
            $core[$name] = include $file;
        }

        return $core;
    }
}