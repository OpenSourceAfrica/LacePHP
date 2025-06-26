<?php

namespace Lacebox\Insole\Lining;

use Lacebox\Shoelace\LiningInterface;

class LiningLoader {
    public static function load(string $version): LiningInterface {

        if ($version === '8' && class_exists(Php8Lining::class)) {
            return new Php8Lining();
        }

        if (class_exists(Php7Lining::class)) {
            return new Php7Lining();
        }

        throw new \RuntimeException("No valid PHP Lining class available for version: $version");
    }
}