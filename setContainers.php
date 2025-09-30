<?php

use App\Core\Container;
use App\Core\Database;
use App\Core\Middleware;

$container = new Container();

$container->setService('Database', function () {
    $database = new Database();
    return $database;
});

$container->setService('Middleware', function () {
    $middleware = new Middleware();
    return $middleware;
});


return $container;