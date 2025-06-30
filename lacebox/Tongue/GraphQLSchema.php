<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Tongue;

use GraphQL\Type\Schema;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

class GraphQLSchema
{
    /**
     * Build & return the GraphQL schema.
     *
     * @return Schema
     */
    public static function build(): Schema
    {
        // Pull the configured PHP major version
        $config = config();
        $version = isset($config['php_version'])
            ? (int)$config['php_version']
            : (int)substr(PHP_VERSION, 0, 1);

        // Start with your always-available fields:
        $fields = [
            'hello' => [
                'type'    => Type::string(),
                'resolve' => function () {
                    return 'Hello from lacePHP GraphQL!';
                },
            ],
        ];

        // If PHP 8 or configured for 8, load any attribute-driven fields
        if ($version >= 8 && defined('Attribute')) {
            $fields = array_merge($fields, self::loadAttributeFields());
        }

        // Create the Query type
        $queryType = new ObjectType([
            'name'   => 'Query',
            'fields' => $fields,
        ]);

        // (Optionally add Mutation here in future)
        return new Schema([
            'query' => $queryType,
        ]);
    }

    /**
     * Scan for #[GraphQLField] attributes under weave/GraphQL (PHP 8 only).
     *
     * @return array
     */
    protected static function loadAttributeFields(): array
    {
        $out = [];

        // Only run if the Attribute class exists
        if (! class_exists(\Attribute::class)) {
            return $out;
        }

        $dir = dirname(__DIR__, 2) . '/weave/GraphQL';
        foreach ((array) glob($dir . '/*.php') as $file) {
            require_once $file;
            $class = 'Weave\\GraphQL\\' . basename($file, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $rc = new ReflectionClass($class);
            foreach ($rc->getAttributes(\Lacebox\Tongue\Attributes\GraphQLField::class) as $attr) {
                $meta = $attr->newInstance();
                $out[$meta->name] = [
                    'type'    => $meta->type,
                    'resolve' => $meta->resolver,
                ];
            }
        }

        return $out;
    }
}