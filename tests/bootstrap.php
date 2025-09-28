<?php
//declare(strict_types=1);
//
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
//
//$projectRoot = realpath(__DIR__ . '/');
//if ($projectRoot === false) {
//    fwrite(STDERR, "[bootstrap] Project root not found\n");
//    exit(1);
//}
//chdir($projectRoot);
//
//// If your app has its own bootstrap, include it here, e.g.:
//$bootstrap = $projectRoot . '/bootstrap.php';
//if (file_exists($bootstrap)) {
//    require_once $bootstrap;
//}
//
//// Base TestCase for app tests
//require_once __DIR__ . '/app_TestCase.php';
//
//if (!function_exists('str_contains')) {
//    function str_contains($haystack, $needle) { return $needle === '' || strpos($haystack, $needle) !== false; }
//}


declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/** 1) Resolve project root and cd there */
$projectRoot = realpath(__DIR__ . '/');
if ($projectRoot === false) {
    fwrite(STDERR, "[bootstrap] Project root not found\n");
    exit(1);
}
chdir($projectRoot);

/** 2) Prefer Lace helper autoloader if available */
$helpers = $projectRoot . '/lacebox/Sole/Helpers.php';
if (is_file($helpers)) {
    require_once $helpers;
    if (function_exists('enable_lace_autoloading')) {
        enable_lace_autoloading();
    }
}

/**
 * 3) Case-insensitive PSR-4-ish autoloader for Lacebox\
 *    Handles folder segments whose real names are lower-case.
 */
spl_autoload_register(function ($class) use ($projectRoot) {
    $prefix = 'Lacebox\\';
    if (stripos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));            // e.g. Sole\Http\ShoeRequest
    $segments = explode('\\', $relative);                   // ['Sole','Http','ShoeRequest']

    $dir = $projectRoot . DIRECTORY_SEPARATOR . 'lacebox';  // base: ./lacebox
    for ($i = 0; $i < count($segments) - 1; $i++) {
        $want = $segments[$i];

        // Try exact match first
        $candidate = $dir . DIRECTORY_SEPARATOR . $want;
        if (is_dir($candidate)) {
            $dir = $candidate;
            continue;
        }

        // Fallback: find a case-insensitive directory name
        $found = false;
        $dh = @opendir($dir);
        if ($dh) {
            while (($entry = readdir($dh)) !== false) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (is_dir($dir . DIRECTORY_SEPARATOR . $entry) && strcasecmp($entry, $want) === 0) {
                    $dir = $dir . DIRECTORY_SEPARATOR . $entry;
                    $found = true;
                    break;
                }
            }
            closedir($dh);
        }
        if (!$found) {
            return; // bail: path segment not found
        }
    }

    $fileBase = end($segments) . '.php';
    $file = $dir . DIRECTORY_SEPARATOR . $fileBase;

    // Try exact file name, else try a case-insensitive file lookup
    if (!is_file($file)) {
        $dh = @opendir($dir);
        if ($dh) {
            $target = end($segments) . '.php';
            while (($entry = readdir($dh)) !== false) {
                if (is_file($dir . DIRECTORY_SEPARATOR . $entry) && strcasecmp($entry, $target) === 0) {
                    $file = $dir . DIRECTORY_SEPARATOR . $entry;
                    break;
                }
            }
            closedir($dh);
        }
    }

    if (is_file($file)) {
        require_once $file;
    }
});

/** 4) Load the framework test base class (namespace: LacePHP\Framework\Tests) */
require_once __DIR__ . '/app_TestCase.php';

/** 5) Small polyfills for PHP 7.4 */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}