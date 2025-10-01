<?php

use App\Core\Container;
use App\Core\Database;
use App\Core\Middlewares\MiddlewareKernal;

$container = new Container();

$container->setService('Database', function () {
    $database = new Database();
    return $database;
});

$container->setService('Middleware', function () {
    $middleware = new MiddlewareKernal();
    return $middleware;
});


$GLOBALS['container'] = $container;
return $container;