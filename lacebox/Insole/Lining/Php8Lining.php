<?php

namespace Lacebox\Insole\Lining;

use Lacebox\Shoelace\LiningInterface;
use Lacebox\Insole\Stitching\LiningCoreTrait;
use Lacebox\Insole\Stitching\Php8DispatcherTrait;
use Lacebox\Insole\Stitching\Php8ContainerTrait;

/**
 * PHP8 lining implementation: routing, dispatching, and IoC container.
 */
class Php8Lining implements LiningInterface
{
    use LiningCoreTrait;
    use Php8DispatcherTrait;
    use Php8ContainerTrait;

    /**
     * Optionally load middleware groups from config.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['middleware_groups']) && is_array($config['middleware_groups'])) {
            $this->middlewareGroups = $config['middleware_groups'];
        }
    }
}
