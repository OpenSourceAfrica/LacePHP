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

use Lacebox\Sole\Cobble\MigrationManager;

class Migrations
{
    public function show(): string
    {
        header('Content-Type: text/html; charset=utf-8');

        $all     = MigrationManager::listAllMigrations();
        $ran     = MigrationManager::getRan();
        $pending = MigrationManager::pending();

        $html = [];
        $html[] = "<h2>LacePHP Migrations</h2>";
        $html[] = "<p><b>Total:</b> " . count($all) . " | <b>Ran:</b> " . count($ran) . " | <b>Pending:</b> " . count($pending) . "</p>";

        $html[] = "<hr>";
        $html[] = "<form method='POST' action=". shoe_base_url() . "/lace/migrate/run>";
        $html[] = "<button type='submit'>Run Pending Migrations</button>";
        $html[] = "</form>";

        $html[] = "<hr>";
        $html[] = "<h3>Pending</h3>";
        $html[] = $this->renderList($pending);

        $html[] = "<h3>Ran</h3>";
        $html[] = $this->renderList(array_reverse($ran));

        return implode("\n", $html);
    }

    public function run()
    {
        // returns array => your Router will JSON it
        $report = MigrationManager::runAll(false);

        return [
            'ok'      => empty($report['errors']),
            'message' => empty($report['errors']) ? 'Migrations completed' : 'Migrations completed with errors',
            'report'  => $report,
            'pending_after' => MigrationManager::pending(),
        ];
    }

    private function renderList(array $items): string
    {
        if (!$items) return "<p><i>None</i></p>";

        $out = ["<ul>"];
        foreach ($items as $i) {
            $out[] = "<li>" . htmlspecialchars((string)$i, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        $out[] = "</ul>";
        return implode("\n", $out);
    }
}