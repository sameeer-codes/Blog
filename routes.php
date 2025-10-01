<?php

use App\Controllers\HomeController;
use App\Core\Router;
use App\Controllers\Users\LoginController;
use App\Controllers\Users\RegisterController;
use App\Models\Users\RefreshTokenModel;
use App\Models\Users\UserModel;

$router = new Router();
$container = $GLOBALS['container'];
$database = $container->getService('Database');
$userModel = new UserModel($database);
$refreshTokenModel = new RefreshTokenModel($database);
$router->get('/api/test', [HomeController::class, 'Home'], [])->attachMiddleware(['admin']);

// Admin Routes 
$router->post('/api/user/register', [RegisterController::class, 'sendResponse'], [$userModel]);
$router->post('/api/user/login', [LoginController::class, 'login'], [$userModel, $refreshTokenModel]);