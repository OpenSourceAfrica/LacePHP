<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.lacephp.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Heel;

class Docs
{
    public function show(): void
    {
        $specPath = '/public/outsole/docs/openapi.json';
        $fullSpec = dirname(__DIR__, 2) . $specPath;
        if (! file_exists($fullSpec)) {
            http_response_code(500);
            echo "<h1>500 Internal Server Error</h1>";
            echo "<p>OpenAPI spec not found at {$specPath}</p>";
            exit;
        }

        // Compute URLs via shoe_* helpers
        $cssUrl    = shoe_asset('outsole/swagger-ui/swagger-ui.css');
        $bundleJs  = shoe_asset('outsole/swagger-ui/swagger-ui-bundle.js');
        $presetJs  = shoe_asset('outsole/swagger-ui/swagger-ui-standalone-preset.js');
        $jsonUrl   = shoe_asset('outsole/docs/openapi.json');

        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>API Docs</title>
  <link rel="stylesheet" href="{$cssUrl}" />
  <style>body{margin:0;}#swagger-ui{height:100vh;}</style>
</head>
<body>
  <div id="swagger-ui"></div>
  <script src="{$bundleJs}"></script>
  <script src="{$presetJs}"></script>
  <script>
    window.ui = SwaggerUIBundle({
      url: '{$jsonUrl}',
      dom_id: '#swagger-ui',
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIStandalonePreset
      ],
      layout: 'StandaloneLayout'
    });
  </script>
</body>
</html>
HTML;
        exit;
    }
}