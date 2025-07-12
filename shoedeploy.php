<?php

return [
    // default target if none specified on the command line
    'default' => 'staging',

    // define each environment
    'environments' => [
        'staging' => [
            'host'       => 'staging.example.com',
            'user'       => 'root',
            'path'       => '/var/www/myapp',
            'branch'     => 'main',
        ],
        'production' => [
            'host'       => 'prod.example.com',
            'user'       => 'deploy',
            'path'       => '/var/www/myapp',
            'branch'     => 'main',
        ],
    ],

    // optional hooks you can customize
    'hooks' => [
        'beforeDeploy' => function() {

            // e.g. run tests locally
            exec('ls -v');

            echo "🔎 Running local tests…\n";
            passthru('php vendor/bin/phpunit', $code);
            if ($code !== 0) {
                throw new \RuntimeException("Tests failed, aborting deploy.");
            }
        },
        'afterDeploy' => function() {
            echo "Restarting queue workers…\n";
            // note: adjust for your framework’s CLI if needed
            passthru("ssh {$this->user}@{$this->host} 'cd {$this->path} && php artisan queue:restart'");
        },
    ],
];