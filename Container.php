<?php

use App\Core\Container;
use App\Core\Database;
use App\Core\Middlewares\MiddlewareKernal;
use App\Core\Router;

$container = new Container();

$container->setService('Database', function () {
    $database = new Database();
    return $database;
});

$container->setService('Middleware', function () {
    $middleware = new MiddlewareKernal();
    return $middleware;
});

$container->setService('Router', function () {
    $router = new Router();
    return $router;
});


$GLOBALS['container'] = $container;
return $container;