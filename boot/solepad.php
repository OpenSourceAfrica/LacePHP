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

use Lacebox\Sole\Http\ShoeResponder;
use Lacebox\Sole\Sockliner;

try {

    if (!file_exists(__DIR__ . '/../lacebox/Sole/Helpers.php')) {
        http_response_code(500);
        echo "This LacePHP cannot boot because an essential resources is broken. [LacePHP001]\n";
        exit;
    }

    // 1) Bring in your helper (defines enable_lace_autoloading() and config())
    require_once __DIR__ . '/../lacebox/Sole/Helpers.php';

    enable_lace_autoloading();

    // 2) Read merged config (lace.json + env/.env)
    $config = config();

    // 3) Then conditionally load Composer from the user-configured directory:
    if (! empty($config['cli']['allow_composer'])) {
        $vendorDir = rtrim($config['paths']['vendor'], '/');
        $autoload  = __DIR__ . "/../{$vendorDir}/autoload.php";
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
            throw new \RuntimeException("Composer autoloader not found at {$autoload}");
        }
    }

    if (! empty($config['boot']['timezone'])) {
        // 1) Tells all date functions to use this zone:
        date_default_timezone_set($config['boot']['timezone']);

        // 2) Also set the ini, so any internal or extension calls (like Intl) respect it:
        ini_set('date.timezone', $config['boot']['timezone']);
    }

    // 5) Now that autoloading is live, pull in your kernel
    $app = Sockliner::getInstance();
    $app->run();
    return $app;

} catch (Throwable $e) {

    http_response_code(500);

    // if debug is on, show full error
    if (config('boot.debug', false)) {
        echo "<h1>Internal Server Error</h1>";
        echo "<pre>" . htmlspecialchars($e->getMessage(), ENT_QUOTES) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES) . "</pre>";
    } else {
       echo (ShoeResponder::getInstance())->serverError();
    }
    exit;
}

