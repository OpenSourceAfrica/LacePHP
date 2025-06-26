<?php

namespace Lacebox\Shoelace;

interface ApiDocInterface
{
    /**
     * Return OpenAPI metadata for this controller's methods.
     *
     * @return array
     */
    public static function openApiSpec(): array;
}