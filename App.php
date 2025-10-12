<?php

use App\Core\Middlewares\AdminMiddleware;
use App\Core\Middlewares\GuestMiddleware;

$container = $GLOBALS['container'];
$middleware = $container->getService('Middleware');

$middleware->setMiddleware('admin', [AdminMiddleware::class, 'handle']);
$middleware->setMiddleware('guest', [GuestMiddleware::class, 'handle']);