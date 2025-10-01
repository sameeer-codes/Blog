<?php

use App\Core\Middlewares\AdminMiddleware;

$container = $GLOBALS['container'];
$middleware = $container->getService('Middleware');

$middleware->setMiddleware('admin', [AdminMiddleware::class, 'handle']);