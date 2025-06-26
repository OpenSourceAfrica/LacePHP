<?php

namespace Weave\Controllers;

use Lacebox\Shoelace\ApiDocInterface;

class DocsDemoController implements ApiDocInterface
{
    public function index()
    {
        return response(['message' => 'This is documented via Swagger!']);
    }

    public static function openApiSpec(): array
    {
        return [
            '/docs-demo' => [
                'get' => [
                    'summary' => 'Get a Swagger-documented response',
                    'responses' => [
                        '200' => [
                            'description' => 'Successful response',
                            'content' => [
                                'application/json' => [
                                    'example' => ['message' => 'This is documented via Swagger!']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}