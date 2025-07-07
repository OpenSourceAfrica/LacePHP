<?php

// toebox.php
if (php_sapi_name() === 'cli-server') {

    $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $base   = __DIR__;
    $file   = $base . '/public' . $uri;

    //serve actual files directly
    if (is_file($file)) {
        return false;
    }

    //fallback to the front-controller
    require __DIR__ . '/public/index.php';
}