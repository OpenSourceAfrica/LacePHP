<?php
//declare(strict_types=1);
//
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
//
//// Resolve project root
//$projectRoot = realpath(__DIR__ . '/..');
//if ($projectRoot === false) {
//    fwrite(STDERR, "[bootstrap] Project root not found\n");
//    exit(1);
//}
//chdir($projectRoot);
//
//// Try Lace helper autoloader first
//$helpers = $projectRoot . '/lacebox/Sole/Helpers.php';
//if (file_exists($helpers)) {
//    require_once $helpers;
//    if (function_exists('enable_lace_autoloading')) {
//        enable_lace_autoloading();
//    }
//}
//
//// Fallback: minimal PSR-4-ish autoloader for Lacebox\
//spl_autoload_register(function ($class) use ($projectRoot) {
//    $prefix = 'Lacebox\\';
//    if (strpos($class, $prefix) === 0) {
//        $relative = substr($class, strlen($prefix));
//        $file = $projectRoot . '/lacebox/' . str_replace('\\', '/', $relative) . '.php';
//        if (file_exists($file)) {
//            require_once $file;
//        }
//    }
//});
//
//// Load this suite's base TestCase
//require_once __DIR__ . '/lacebox_TestCase.php';
//
//// Polyfills for PHP 7.4 if needed
//if (!function_exists('str_contains')) {
//    function str_contains($haystack, $needle) { return $needle === '' || strpos($haystack, $needle) !== false; }
//}

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

// project root is the parent of lacebox/
$projectRoot = realpath(__DIR__ . '/..'); // lacebox/
if ($projectRoot === false) {
    fwrite(STDERR, "[bootstrap] lacebox/ not found\n");
    exit(1);
}
$projectRoot = dirname($projectRoot);     // project root
chdir($projectRoot);

// prefer Lace helper autoloader
$helpers = $projectRoot . '/lacebox/Sole/Helpers.php';
if (is_file($helpers)) {
    require_once $helpers;
    if (function_exists('enable_lace_autoloading')) enable_lace_autoloading();
}

// same case-insensitive autoloader as above…
spl_autoload_register(function ($class) use ($projectRoot) {
    $prefix = 'Lacebox\\';
    if (stripos($class, $prefix) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $segments = explode('\\', $relative);

    $dir = $projectRoot . '/lacebox';
    for ($i = 0; $i < count($segments) - 1; $i++) {
        $want = $segments[$i];
        $cand = $dir . '/' . $want;
        if (is_dir($cand)) {
            $dir = $cand;
            continue;
        }
        $found = false;
        if ($dh = @opendir($dir)) {
            while (($e = readdir($dh)) !== false) {
                if ($e === '.' || $e === '..') continue;
                if (is_dir($dir . '/' . $e) && strcasecmp($e, $want) === 0) {
                    $dir .= '/' . $e;
                    $found = true;
                    break;
                }
            }
            closedir($dh);
        }
        if (!$found) return;
    }

    $target = end($segments) . '.php';
    $file = $dir . '/' . $target;
    if (!is_file($file) && ($dh = @opendir($dir))) {
        while (($e = readdir($dh)) !== false) {
            if (is_file($dir . '/' . $e) && strcasecmp($e, $target) === 0) {
                $file = $dir . '/' . $e;
                break;
            }
        }
        closedir($dh);
    }
    if (is_file($file)) require_once $file;
});

// load your test base (whatever namespace you used for these tests)
require_once __DIR__ . '/lacebox_TestCase.php';

if (!function_exists('str_contains')) {
    function str_contains($h, $n)
    {
        return $n === '' || strpos($h, $n) !== false;
    }
}
