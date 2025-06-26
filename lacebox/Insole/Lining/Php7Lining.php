<?php

namespace Lacebox\Insole\Lining;

use Lacebox\Shoelace\LiningInterface;
use Lacebox\Insole\Stitching\LiningCoreTrait;
use Lacebox\Insole\Stitching\Php7ContainerTrait;
use Lacebox\Insole\Stitching\Php7DispatcherTrait;

/**
 * PHP7 lining implementation: routing, dispatching, and basic container.
 */
class Php7Lining implements LiningInterface
{
    use LiningCoreTrait;
    use Php7DispatcherTrait;
    use Php7ContainerTrait;

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
