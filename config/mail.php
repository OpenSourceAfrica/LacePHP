<?php
// config/mail.php

return [
    // Which driver to use: smtp, php_mail, mailgun
    'driver' => env('MAIL_DRIVER', 'php_mail'),

    // Global "from" address
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name'    => env('MAIL_FROM_NAME',    'LacePHP'),
    ],

    // SMTP settings
    'smtp' => [
        'host'       => env('MAIL_SMTP_HOST', 'localhost'),
        'port'       => env('MAIL_SMTP_PORT', 25),
        'username'   => env('MAIL_SMTP_USER', ''),
        'password'   => env('MAIL_SMTP_PASS', ''),
        'encryption' => env('MAIL_SMTP_ENCRYPTION', ''), // '', tls or ssl
    ],

    // Mailgun settings
    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN', ''),
        'api_key'  => env('MAILGUN_API_KEY', ''),
        'endpoint' => env('MAILGUN_API_ENDPOINT', 'https://api.mailgun.net/v3'),
    ],
];
