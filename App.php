<?php

use App\Core\Middlewares\AuthMiddleware;
use App\Core\Middlewares\AuthorMiddleware;
use App\Core\Middlewares\AdminMiddleware;
use App\Core\Middlewares\GuestMiddleware;
use App\Core\Middlewares\LogoutMiddleware;

$container = $GLOBALS['container'];
$middleware = $container->getService('Middleware');

$middleware->setMiddleware('auth', [AuthMiddleware::class, 'handle']);
$middleware->setMiddleware('author', [AuthorMiddleware::class, 'handle']);
$middleware->setMiddleware('admin', [AdminMiddleware::class, 'handle']);
$middleware->setMiddleware('guest', [GuestMiddleware::class, 'handle']);
$middleware->setMiddleware('logout', [LogoutMiddleware::class, 'handle']);
