<?php

require_once __DIR__ . '/../lacebox/Sole/helpers.php';

$configPath = __DIR__ . '/../lace.json';
$config = file_exists($configPath)
    ? json_decode(file_get_contents($configPath), true)
    : ['use_autoloader' => true];

if ($config['use_autoloader'] ?? true) {
    enable_lace_autoloading();
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    echo "❌ No autoloader available.\n";
    exit(1);
}

use Lacebox\Tongue\Socklinerddd;

return new Socklinerddd();