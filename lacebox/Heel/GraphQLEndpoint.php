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

namespace Lacebox\Heel;

use GraphQL\GraphQL as GraphQLCore;
use GraphQL\Error\DebugFlag;
use GraphQL\Error\FormattedError;
use Lacebox\Tongue\GraphQLSchema;

class GraphQLEndpoint
{
    public function execute(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $query         = $input['query']         ?? null;
        $variables     = $input['variables']     ?? [];
        $operationName = $input['operationName'] ?? null;

        try {
            $schema = GraphQLSchema::build();
            $result = GraphQLCore::executeQuery(
                $schema,
                $query,
                null,    // rootValue
                null,    // context
                $variables,
                $operationName
            );
            $output = $result->toArray(DebugFlag::INCLUDE_DEBUG_MESSAGE);

        } catch (\Throwable $e) {
            $output = [
                'errors' => [
                    FormattedError::createFromException($e, DebugFlag::INCLUDE_DEBUG_MESSAGE)
                ]
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($output, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        exit;
    }
}