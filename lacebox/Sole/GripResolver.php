<?php

namespace Lacebox\Sole;

class GripResolver
{
    const LOW = 'low';
    const MODERATE = 'moderate';
    const HIGH = 'high';

    protected $level;
    protected $environment;
    protected $profile = [];

    public function __construct(array $config = [])
    {
        $this->environment = $this->resolveEnvironment($config);
        $this->level = $this->resolveLevel($config, $this->environment);
        $this->profile = $this->buildProfile($this->level, $this->environment);
    }

    public static function make(array $config = [])
    {
        return new static($config);
    }

    public function level()
    {
        return $this->level;
    }

    public function environment()
    {
        return $this->environment;
    }

    public function profile()
    {
        return $this->profile;
    }

    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->profile)
            ? $this->profile[$key]
            : $default;
    }

    public function apply()
    {
        error_reporting($this->get('error_reporting', E_ALL));

        ini_set('display_errors', $this->get('display_errors') ? '1' : '0');
        ini_set('display_startup_errors', $this->get('display_startup_errors') ? '1' : '0');
        ini_set('log_errors', $this->get('log_errors') ? '1' : '0');
    }

    protected function resolveEnvironment(array $config)
    {
        if (!empty($config['lace_env'])) {
            return strtolower(trim($config['lace_env']));
        }

        if (!empty($config['app']['env'])) {
            return strtolower(trim($config['app']['env']));
        }

        if (!empty($config['boot']['env'])) {
            return strtolower(trim($config['boot']['env']));
        }

        return 'production';
    }

    protected function resolveLevel(array $config, $environment)
    {
        if (!empty($config['grip_level'])) {
            return $this->normaliseLevel($config['grip_level']);
        }

        if (!empty($config['boot']['grip_level'])) {
            return $this->normaliseLevel($config['boot']['grip_level']);
        }

        return $this->levelFromEnvironment($environment);
    }

    protected function normaliseLevel($level)
    {
        $level = strtolower(trim((string) $level));

        if (in_array($level, [self::LOW, self::MODERATE, self::HIGH], true)) {
            return $level;
        }

        return self::MODERATE;
    }

    protected function levelFromEnvironment($environment)
    {
        switch ($environment) {
            case 'local':
            case 'development':
            case 'dev':
                return self::LOW;

            case 'testing':
            case 'test':
            case 'staging':
                return self::MODERATE;

            case 'production':
            case 'prod':
            default:
                return self::HIGH;
        }
    }

    protected function buildProfile($level, $environment)
    {
        $profiles = [
            self::LOW => [
                'display_errors' => true,
                'display_startup_errors' => true,
                'log_errors' => true,
                'error_detail_mode' => 'detailed',
                'error_reporting' => E_ALL,

                'log_level' => 'debug',
                'show_trace' => true,
                'strict_routing' => false,
                'strict_validation' => false,
                'enforce_middleware' => false,
                'security_headers' => false,
                'audit_logging' => false,

                'router_rethrow_exceptions' => false,
                'router_log_trace' => true,
            ],

            self::MODERATE => [
                'display_errors' => false,
                'display_startup_errors' => false,
                'log_errors' => true,
                'error_detail_mode' => 'safe',
                'error_reporting' => E_ALL,

                'log_level' => 'warning',
                'show_trace' => false,
                'strict_routing' => true,
                'strict_validation' => true,
                'enforce_middleware' => false,
                'security_headers' => true,
                'audit_logging' => false,

                'router_rethrow_exceptions' => false,
                'router_log_trace' => false,
            ],

            self::HIGH => [
                'display_errors' => false,
                'display_startup_errors' => false,
                'log_errors' => true,
                'error_detail_mode' => 'generic',
                'error_reporting' => E_ALL,

                'log_level' => 'error',
                'show_trace' => false,
                'strict_routing' => true,
                'strict_validation' => true,
                'enforce_middleware' => true,
                'security_headers' => true,
                'audit_logging' => true,

                'router_rethrow_exceptions' => false,
                'router_log_trace' => false,
            ],
        ];

        $profile = $profiles[$level];
        $profile['level'] = $level;
        $profile['environment'] = $environment;

        return $profile;
    }
}