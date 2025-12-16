<?php

use App\Core\Middlewares\AuthMiddleware;
use App\Core\Middlewares\GuestMiddleware;

$container = $GLOBALS['container'];
$middleware = $container->getService('Middleware');

$middleware->setMiddleware('auth', [AuthMiddleware::class, 'handle']);
$middleware->setMiddleware('guest', [GuestMiddleware::class, 'handle']);