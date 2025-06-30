<?php
namespace Lacebox\Insole\Lining;

use Attribute;
use Lacebox\Insole\Stitching\LiningCoreTrait;
use Lacebox\Insole\Stitching\Php8\Php8ContainerTrait;
use Lacebox\Insole\Stitching\Php8\Php8DispatcherTrait;
use Lacebox\Shoelace\Attributes\Route;
use Lacebox\Shoelace\LiningInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

/**
 * PHP 8 lining: routes via #[Route(...)] + core routing, dispatching, container.
 */
class Php8Lining implements LiningInterface
{
    use LiningCoreTrait;
    use Php8DispatcherTrait;
    use Php8ContainerTrait;

    /**
     * Optionally accept framework config (e.g. middleware groups).
     */
    public function __construct(array $config = [])
    {
        if (! empty($config['middleware_groups']) && is_array($config['middleware_groups'])) {
            $this->middlewareGroups = $config['middleware_groups'];
        }
    }

    /**
     * Scan controllers for #[Route(method, uri, middleware?)] and register them.
     *
     * @param string      $controllersNamespace  e.g. 'Weave\\Controllers'
     * @param string|null $controllersPath       optional filesystem path
     */
    public function registerRoutesFromAttributes(
        string $controllersNamespace,
        ?string $controllersPath = null
    ): void {
        // Derive default path if not provided
        if ($controllersPath === null) {
            $base         = dirname(__DIR__, 4); // project root
            $relative     = str_replace('\\', '/', $controllersNamespace);
            $controllersPath = "{$base}/weave/{$relative}";
        }

        if (! is_dir($controllersPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($controllersPath)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Fully-qualified class name
            $class = $controllersNamespace . '\\' . $file->getBasename('.php');
            if (! class_exists($class)) {
                require_once $file->getRealPath();
            }
            if (! class_exists($class)) {
                continue;
            }

            $rc = new ReflectionClass($class);
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
                $attrs = $rm->getAttributes(Route::class, Attribute::IS_INSTANCEOF);
                foreach ($attrs as $attr) {
                    /** @var Route $routeAttr */
                    $routeAttr  = $attr->newInstance();
                    $method     = strtoupper($routeAttr->method);
                    $uri        = $routeAttr->uri;
                    $action     = [ $class, $rm->getName() ];
                    $middleware = $routeAttr->middleware ?? [];
                    $this->addRoute($method, $uri, $action, $middleware);
                }
            }
        }
    }
}