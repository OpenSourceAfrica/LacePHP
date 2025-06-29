<?php

use Lacebox\Sole\EyeletDispatcher;
use Lacebox\Sole\Config;
use Lacebox\Sole\ShoeResponder;
use Lacebox\Sole\ConfigLoader;
use Lacebox\Sole\Http\Request;

if (!function_exists('enable_lace_autoloading')) {
    function enable_lace_autoloading(): void
    {
        spl_autoload_register(function ($class) {
            $prefixes = [
                'Lacebox\\' => __DIR__ . '/../',
                'Weave\\' => __DIR__ . '/../../weave/',
                'Shoebox\\' => __DIR__ . '/../../shoebox/',
                'Weave\\Plugins\\' => __DIR__ . '/../../weave/Plugins/',
            ];

            foreach ($prefixes as $prefix => $base_dir) {
                if (strpos($class, $prefix) === 0) {
                    $relative_class = substr($class, strlen($prefix));
                    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                        return;
                    }
                }
            }
        });
    }
}

/**
 * Get the full merged configuration, or a specific key+subkeys via dot syntax.
 *
 * @param  string|null  $path    Optional “dot” path, e.g. "database.mysql.host"
 * @param  mixed        $default Default if not set
 * @return mixed        Full config array or the requested value
 */
if (! function_exists('config')) {
    function config(string $path = null, $default = null)
    {
        // Grab the merged config singleton
        $cfg = Config::getInstance( ConfigLoader::getInstance()->load() )->all();

        if ($path === null) {
            return $cfg;
        }

        // Traverse dot-notation
        $segments = explode('.', $path);
        $current = $cfg;
        foreach ($segments as $seg) {
            if (is_array($current) && array_key_exists($seg, $current)) {
                $current = $current[$seg];
            } else {
                return $default;
            }
        }
        return $current;
    }
}

/**
 * Shortcut to pull a single value from system config.
 * Identical to config($path, $default).
 */
if (! function_exists('config_get')) {
    function config_get(string $path, $default = null)
    {
        return config($path, $default);
    }
}


if (!function_exists('response')) {
    function response(array $data = [], int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log errors (404, 401, 500) when enabled in config.
     */
    function log_error(string $type, string $message): void
    {
        $config = config();
        $logging = $config['logging'] ?? ['enabled' => true];

        if (!($logging['enabled'] ?? true)) {
            return;
        }

        $levels = $logging['levels'] ?? ['404', '401', '500'];
        if (!in_array($type, $levels, true)) {
            return;
        }

        // 1) Determine log file path from config or default
        $rawPath = $logging['path']
            ?? 'shoebox/logs/lace.log';

        // 2) Make absolute if relative
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows: check for X:\ or \\server\
            $isAbsolute = preg_match('#^[A-Za-z]:\\\\#', $rawPath)
                || strpos($rawPath, '\\\\') === 0;
        } else {
            // Unix: absolute if starts with /
            $isAbsolute = strpos($rawPath, '/') === 0;
        }

        if (!$isAbsolute) {
            $base = dirname(__DIR__, 2);    // project root
            $logFile = $base . DIRECTORY_SEPARATOR . ltrim($rawPath, '/\\');
        } else {
            $logFile = $rawPath;
        }

        // 3) Ensure directory exists
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log("lacePHP: could not create log directory {$dir}");
                return;
            }
        }

        // 4) Append the error line
        $date = date('Y-m-d H:i:s');
        file_put_contents(
            $logFile,
            "[$date] [$type] $message\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        return $value === false || $value === null ? $default : $value;
    }
}

if (!function_exists('kickback')) {
    function kickback(): ShoeResponder
    {
        return ShoeResponder::getInstance();
    }
}

if (! function_exists('shoe_base_url')) {
    /**
     * Return the application’s base URL (from config or auto-detect).
     */
    function shoe_base_url(string $path = ''): string
    {
        $cfg = config();
        $url = rtrim($cfg['base_url'] ?? '', '/');
        if (empty($url)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $url    = "{$scheme}://{$host}";
        }
        if ($path !== '') {
            $path = ltrim($path, '/');
            $url .= "/{$path}";
        }
        return $url;
    }
}

if (! function_exists('shoe_asset')) {
    /**
     * Return the full URL for a public asset under /public/.
     */
    function shoe_asset(string $publicPath): string
    {
        // publicPath is relative to the web‐root
        return shoe_base_url($publicPath);
    }
}

if (! function_exists('fire')) {
    /**
     * Shortcut to dispatch an event.
     */
    function fire(string $eventName, $payload = null): void
    {
        EyeletDispatcher::getInstance()->dispatch($eventName, $payload);
    }
}

if (! function_exists('on')) {
    /**
     * Shortcut to listen for an event.
     */
    function on(string $eventName, callable $listener): void
    {
        EyeletDispatcher::getInstance()->listen($eventName, $listener);
    }
}

/**
 * Return a scheduler instance for code-based task registration.
 */
if (! function_exists('schedule')) {
    function schedule(): \Lacebox\Sole\AgletKernel
    {
        static $kernel;
        if (!$kernel) {
            $kernel = new \Lacebox\Sole\AgletKernel();
        }
        return $kernel;
    }
}


// (other helpers above…)

if (!function_exists('lace_now')) {
    /**
     * Get a DateTime in your app’s configured timezone.
     *
     * @return \DateTime
     */
    function lace_now(): \DateTime
    {
        $tz = config('boot.timezone', 'UTC');
        return new \DateTime('now', new \DateTimeZone($tz));
    }
}

if (!function_exists('lace_now_str')) {
    /**
     * Get the current time as a formatted string.
     *
     * @param string $format any format accepted by DateTime::format
     * @return string
     */
    function lace_now_str(string $format = 'Y-m-d H:i:s'): string
    {
        return lace_now()->format($format);
    }
}

if (! function_exists('sole_request')) {
    /**
     * Shoe-themed request accessor.
     * @return \Lacebox\Sole\Http\Request
     */
    function sole_request(): Request
    {
        return Request::grab();
    }
}

include 'HwidProvider.php';

if (! function_exists('view')) {
    /**
     * Render a PHP template into a string.
     *
     * Usage:
     *   echo view('emails.welcome', ['name'=>'Foo']);
     *   // will include: weave/Views/emails/welcome.php
     *
     * @param string $template  Dot-notation path under weave/Views (no “.php”)
     * @param array  $data      Variables to extract into scope
     * @return string           Rendered HTML
     * @throws \RuntimeException
     */
    function view(string $template, array $data = []): string
    {
        // Convert dots to directory separators
        $relPath = str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';
        // Look first in app’s weave/Views/, then fallback to shoebox/views/
        $baseApp = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'weave' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR;
        $baseCore = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'shoebox' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
        $file = null;

        if (file_exists($baseApp . $relPath)) {
            $file = $baseApp . $relPath;
        } elseif (file_exists($baseCore . $relPath)) {
            $file = $baseCore . $relPath;
        }

        if (! $file) {
            throw new \RuntimeException("View template not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}

/**
 * Derive an encryption key from salt + license key.
 */
function lace_derive_key(string $salt, string $licenseKey): string
{
    // Use HMAC-SHA256, output raw bytes
    return hash_hmac('sha256', $licenseKey, $salt, true);
}

if (! function_exists('prompt')) {
    function prompt(string $label): string {
        fwrite(STDOUT, "{$label}: ");
        $in = trim(fgets(STDIN));
        return $in;
    }
}

if (! function_exists('encryptPayload')) {
    function encryptPayload(array $payload, string $salt, string $license): string {
        $iv  = substr($salt,0,16);
        $key = hash_hkdf('sha256',$salt,32,'lacephp-plugin',$license);
        $plain = json_encode($payload);
        return openssl_encrypt($plain,'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
    }
}

if (! function_exists('decryptPayload')) {
    function decryptPayload(string $enc, string $salt, string $license): array {
        $iv  = substr($salt,0,16);
        $key = hash_hkdf('sha256',$salt,32,'lacephp-plugin',$license);
        $json = openssl_decrypt($enc,'aes-256-cbc',$key,OPENSSL_RAW_DATA,$iv);
        return json_decode($json,true);
    }
}
