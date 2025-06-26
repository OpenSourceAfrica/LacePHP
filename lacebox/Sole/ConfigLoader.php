<?php
namespace Lacebox\Sole;

class ConfigLoader
{
    public static function load(): array
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
        $envName   = $json['lace_env'] ?? ($json['environment'] ?? 'production');
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
            'sole_version'  => $rawEnv['SOLE_VERSION']  ?? null,
            'lace_env'      => $rawEnv['LACE_ENV']      ?? null,
            'show_blisters' => $rawEnv['SHOW_BLISTERS'] ?? null,
            'brand_name'    => $rawEnv['BRAND_NAME']    ?? null,
            'base_url'      => $rawEnv['LACE_BASE_URL'] ?? null,
            'grip_level'    => $rawEnv['GRIP_LEVEL']    ?? null,
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