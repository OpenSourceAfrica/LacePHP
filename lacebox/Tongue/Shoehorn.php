<?php

// Shoehorn.php - The primary bootstrapper for lacePHP

// Load environment config (minimalistic)
if (file_exists(__DIR__ . '/../../shoebox/env/.env')) {
    foreach (file(__DIR__ . '/../../shoebox/env/.env') as $line) {
        if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $matches)) {
            $_ENV[$matches[1]] = $matches[2];
        }
    }
}

// Include any preloading tasks (e.g., helpers, functions)
require_once __DIR__ . '/../Sole/Helpers.php';