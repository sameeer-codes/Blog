<?php

use App\Controllers\Auth\RefreshTokenController;
use App\Controllers\HomeController;
use App\Controllers\Posts\CreatePostController;
use App\Controllers\Posts\PostsController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Posts\PostModel;
use App\Models\Users\UserModel;

$container = $GLOBALS['container'];
$router = $container->getService('Router');

// Models for dependency injection
$database = $container->getService('Database'); // Database Model
$userModel = new UserModel($database); // User Model
$postModel = new PostModel($database); // User Model
$refreshTokenModel = new RefreshTokenModel($database); // Refresh Token Model


$router->get(
    '/api/test',
    [
        HomeController::class,
        'Home'
    ]
);

$router->post(
    '/api/auth/register',
    [
        RegisterController::class,
        'sendResponse'
    ],
    [$userModel]
)->attachMiddleware(['guest']);

$router->post(
    '/api/auth/login',
    [LoginController::class, 'login'],
    [$userModel, $refreshTokenModel]
)->attachMiddleware(['guest']);


$router->get(
    '/api/posts',
    [PostsController::class, 'index'],
    [$postModel]
);

$router->post(
    '/api/post/create',
    [CreatePostController::class, 'index'],
    [$postModel]
)->attachMiddleware(['auth']);

$router->get(
    '/api/refresh-token',
    [RefreshTokenController::class, 'handle'],
    [$refreshTokenModel, $userModel]
);