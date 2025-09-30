<?php

use App\Core\Middlewares\AdminMiddleware;

$container = require correctPath('/setContainers.php');
$middleware = $container->getService('Middleware');

$middleware->setMiddleware('admin', [AdminMiddleware::class, 'handle']);