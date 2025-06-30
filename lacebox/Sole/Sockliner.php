<?php

namespace Lacebox\Sole;

use GraphQL\GraphQL;
use Lacebox\Heel\Dashboard;
use Lacebox\Heel\Docs;
use Lacebox\Heel\GraphQLEndpoint;
use Lacebox\Heel\Health;
use Lacebox\Heel\Metrics;
use Lacebox\Insole\Lining\LiningLoader;
use Lacebox\Insole\Lining\Php8Lining;
use Lacebox\Knots\MagicDebugKnots;
use Lacebox\Knots\MetricsKnots;
use Lacebox\Knots\ShoeCacheKnots;
use Lacebox\Knots\ShoeGateKnots;
use Lacebox\Shoelace\EyeletDispatcherInterface;
use Lacebox\Shoelace\RouterInterface;
use Lacebox\Shoelace\DispatcherInterface;
use Lacebox\Shoelace\ContainerInterface;
use Lacebox\Sole\Heel\Tester;
use Lacebox\Strap\Guards\ShoeHmacGuard;
use Lacebox\Strap\Guards\ShoeSignatureGuard;
use Lacebox\Strap\Guards\ShoeTokenGuard;

/**
 * Sockliner acts as the core application kernel.
 * Implements singleton, loads config, boots settings, registers routes, and runs dispatch.
 */
class Sockliner
{
    /** @var self|null */
    protected static $instance = null;

    /** @var array */
    protected $config = [];

    /** @var \Lacebox\Shoelace\LiningInterface */
    protected $lining;

    /** @var Router */
    protected $router;

    /** @var DispatcherInterface */
    protected $dispatcher;

    /** @var ContainerInterface */
    protected $container;

    /**
     * Private constructor: bootstraps the application.
     */
    private function __construct()
    {
        // 1) Load merged configuration
        $this->config = Config::getInstance();

        // 2) Apply timezone and debug settings
        date_default_timezone_set($this->config['boot']['timezone'] ?? 'UTC');
        if (!empty($this->config['boot']['debug'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        // 3) Determine PHP major version
        $version = $this->config['sole_version'] ?? substr(PHP_VERSION, 0, 1);
        if (!in_array($version, ['7', '8'])) {
            trigger_error("[lacePHP] Warning: PHP version $version not officially supported", E_USER_WARNING);
        }

        // 4) Load version-specific lining
        $lining = LiningLoader::load($version);
        if ($version === '8' && $lining instanceof Php8Lining) {
            $lining->registerRoutesFromAttributes('Weave\\Controllers');
        }
        $this->lining = $lining;

        // 5) Instantiate the full-featured Sole Router
        $this->router     = new Router($lining);
        $this->dispatcher = $this->router;
        $this->container  = $lining;

        $base = dirname(__DIR__, 2);

        // Register EyeletDispatcher as a singleton in the container
        $this->container()->bind(EyeletDispatcherInterface::class, function() {
            return EyeletDispatcher::getInstance();
        });

        $secret = $this->config['security']['secret'] ?? 'default-secret';
        $defaultGuard = $this->config['auth']['guard'] ?? null;
        if ($defaultGuard) {
            $this->router->setGuardResolver(function(string $guardName) use ($defaultGuard, $secret) {
                // If the route didn’t override, use the default
                if ($guardName === null) {
                    $guardName = $defaultGuard;
                }
                switch ($guardName) {
                    case 'token':
                        $secrets = $this->config['auth']['tokens'] ?? [];
                        return new ShoeTokenGuard($secrets);
                    case 'hmac':
                        return new ShoeHmacGuard($secret);
                    case 'signature':
                        return new ShoeSignatureGuard($secret);
                    default:
                        return null;
                }
            });
        }

        // 7) Import routes from lining into the Sole Router
        $routing       = $this->config['routing'] ?? [];
        $routePaths    = $routing['route_paths'] ?? [];
        $autoDiscover  = isset($routing['auto_discover'])
            ? (bool)$routing['auto_discover']
            : true;      // default to true if not set

        if ($autoDiscover) {

            foreach ($routing['route_paths'] ?? [] as $dir) {
                $path = $base . '/' . rtrim($dir, '/');

                if (is_dir($path)) {
                    foreach (glob($path . '/*.php') as $file) {
                        /** @var Router $router */
                        $router = $this->router;
                        include $file;
                    }

                } elseif (is_file($path . '.php')) {
                    $router = $this->router;
                    include $path . '.php';

                } elseif (is_file($path)) {
                    $router = $this->router;
                    include $path;
                }
            }
        } else {

            // If auto_discover is false (or missing), we treat each entry as a file basename.
            foreach ($routePaths as $entry) {
                // Make sure it doesn't already have a .php
                $filename = preg_replace('/\.php$/', '', $entry) . '.php';
                $fullPath = $base . '/' . ltrim($filename, '/');

                if (! file_exists($fullPath) || ! is_file($fullPath)) {
                    throw new \RuntimeException(
                        "[lacePHP] Route file not found: {$fullPath}"
                    );
                }

                // Expose $router into the scope of that file
                /** @var \Lacebox\Shoelace\RouterInterface $router */
                $router = $this->router;
                include $fullPath;
            }
        }

        // also auto-attach MetricsKnots to every request in your Router
        $cacheKnots = (! empty(config()['cache']['enabled'] ?? false)) ? ShoeCacheKnots::class : '';

        $this->router->setGlobalMiddleware([
            ShoeGateKnots::class,
            MetricsKnots::class,
            MagicDebugKnots::class,
            $cacheKnots
        ]);

        $pm = new PluginManager();

        // now scans weave/Plugins instead of lacebox/Plugins
        if (!empty($this->config['plugins']['auto_discover_folder'] ?? true)) {
            $pm->discoverFromFolder($base);
        }

        if (!empty($this->config['plugins']['auto_discover_composer'] ?? false)) {
            $pm->discoverFromComposer($base . '/vendor');
        }

        $pm->registerAll($this->router, $this->config);
        $pm->bootAll($this->config);


        // pull in configured paths
        $eps = $this->config['endpoints'] ?? [];
        $guarded = !empty($eps['guarded']);  // true to apply token guard

        // If the user asked us to guard these endpoints, ensure a token secret exists:
        if ($guarded) {
            $secret = $this->config['auth']['token_secret']
                ?? $this->config['auth']['jwt_secret']
                ?? null;

            if (empty($secret)) {
                throw new \RuntimeException(
                    "[lacePHP] endpoints.guarded is true, but no auth.token_secret (or auth.jwt_secret) is configured in your config/env."
                );
            }

            // Register your token‐guard resolver now that we have a secret
            $this->router->setGuardResolver(function(string $guardName) use ($secret) {
                if ($guardName === 'token') {
                    // instantiate your ShoeTokenGuard with the secret
                    return new \Lacebox\Strap\Guards\ShoeTokenGuard($secret);
                }
                return null;
            });
        }

        $guardMw = $guarded ? ['_guard' => 'token'] : [];

        // 1) Swagger UI & spec
        $docsPath = trim($eps['docs'] ?? 'lace/docs', '/');
        $this->router->addRoute(
            'GET',
            "/{$docsPath}",
            [Docs::class, 'show'],
            $guardMw
        );

        // 2) Health-check
        $healthPath = trim($eps['health'] ?? 'lace/health', '/');
        $this->router->addRoute(
            'GET',
            "/{$healthPath}",
            [Health::class, 'show'],
            $guardMw
        );

        // 3) Dashboard
        $dashPath = trim($eps['dashboard'] ?? 'lace/dashboard', '/');
        $this->router->addRoute(
            'GET',
            "/{$dashPath}",
            [Dashboard::class, 'show'],
            $guardMw
        );

        // add metrics endpoint
        $prefix  = trim($eps['metrics'] ?? 'lace/metrics', '/');
        $this->router->addRoute(
            'GET',
            "/{$prefix}",
            [Metrics::class, 'show'],
            $guardMw
        );

        // 4) GraphQL
        $gqlPath = trim($eps['graphql'] ?? 'graphql', '/');
        if (!empty($this->config['graphql']['enabled'])) {
            // only if the library is installed
            if (class_exists(GraphQL::class)) {
                $this->router->addRoute(
                    'POST',
                    "/{$gqlPath}",
                    [GraphQLEndpoint::class, 'execute'],
                    $guardMw
                );
            } elseif (!empty($this->config['graphql']['enabled'])) {
                throw new \RuntimeException(
                    "[lacePHP] graphql.enabled is true but webonyx/graphql-php is not installed."
                );
            }
        }
    }

    /**
     * Retrieve the singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the merged configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the Sole Router instance.
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * Run the application: dispatch the HTTP request.
     */
    public function run(): void
    {
        $response = $this->router->dispatch();

        // If it’s an array or object (unlikely now, since the router formats JSON),
        // print it as JSON. Otherwise just echo the string.
        if (is_array($response) || is_object($response)) {
            echo json_encode($response, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } else {
            echo $response;
        }

        $this->terminate();
    }

    /**
     * Termination hook for cleanup or logging.
     */
    public function terminate(): void
    {
        // Future extension point
    }

    /**
     * Get the DI container (the lining instance).
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the dispatcher (alias to the router).
     *
     * @return DispatcherInterface
     */
    public function dispatcher(): DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function test(): Tester
    {
        return new Tester($this->router);
    }
}

