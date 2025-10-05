<?php

use App\Controllers\Auth\RefreshTokenController;
use App\Controllers\HomeController;
use App\Core\Router;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Users\UserModel;

$router = new Router();
$container = $GLOBALS['container'];

// Models for dependency injection
$database = $container->getService('Database'); // Database Model
$userModel = new UserModel($database); // User Model
$refreshTokenModel = new RefreshTokenModel($database); // Refresh Token Model


// Routes Declaration
$router->get('/api/test', [HomeController::class, 'Home'], [])->attachMiddleware(['admin']);

// Admin Routes 
$router->post('/api/user/register', [RegisterController::class, 'sendResponse'], [$userModel]); // Register Model
$router->post('/api/user/login', [LoginController::class, 'login'], [$userModel, $refreshTokenModel]);
$router->get('/api/refresh-token', [RefreshTokenController::class, 'handle'], [$refreshTokenModel]);