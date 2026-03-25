<?php

use App\Controllers\Auth\RefreshTokenController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\HomeController;
use App\Controllers\Posts\AuthorPostsController;
use App\Controllers\Posts\AuthorSinglePostController;
use App\Controllers\Posts\CreatePostController;
use App\Controllers\Posts\DeletePostController;
use App\Controllers\Posts\EditPostController;
use App\Controllers\Posts\PostsController;
use App\Controllers\Posts\SearchPostsController;
use App\Controllers\Posts\SinglePostController;
use App\Controllers\Posts\SinglePostBySlugController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Uploads\AddUploadController;
use App\Controllers\Uploads\DeleteUploadController;
use App\Controllers\Uploads\EditUploadController;
use App\Controllers\Uploads\GetUploadsController;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModal;
use App\Models\Users\UserModel;

$container = $GLOBALS['container'];
$router = $container->getService('Router');

// Models for dependency injection
$database = $container->getService('Database'); // Database Model
$userModel = new UserModel($database); // User Model
$postModel = new PostModel($database); // Post Model
$refreshTokenModel = new RefreshTokenModel($database); // Refresh Token Model
$uploadsModel = new UploadsModal($database);

$router->get(
    '/',
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

$router->get(
    '/api/posts/single',
    [SinglePostController::class, 'index'],
    [$postModel]
);

$router->get(
    '/api/posts/slug',
    [SinglePostBySlugController::class, 'index'],
    [$postModel]
);

$router->get(
    '/api/posts/search',
    [SearchPostsController::class, 'index'],
    [$postModel]
);

$router->get(
    '/api/posts/me',
    [AuthorPostsController::class, 'index'],
    [$postModel]
)->attachMiddleware(['auth']);

$router->get(
    '/api/posts/me/single',
    [AuthorSinglePostController::class, 'index'],
    [$postModel]
)->attachMiddleware(['auth']);

$router->post(
    '/api/posts',
    [CreatePostController::class, 'index'],
    [$postModel, $uploadsModel]
)->attachMiddleware(['auth']);

$router->patch(
    '/api/posts',
    [EditPostController::class, 'index'],
    [$postModel, $uploadsModel]
)->attachMiddleware(['auth']);

$router->delete(
    '/api/posts',
    [DeletePostController::class, 'index'],
    [$postModel]
)->attachMiddleware(['auth']);

$router->post(
    '/api/refresh-token',
    [RefreshTokenController::class, 'handle'],
    [$refreshTokenModel, $userModel]
);

$router->post(
    '/api/auth/logout',
    [LogoutController::class, 'handle'],
    [$refreshTokenModel]
);

$router->post(
    '/api/uploads',
    [AddUploadController::class, 'upload'],
    [$uploadsModel]
)->attachMiddleware(['auth']);

$router->get(
    '/api/uploads',
    [GetUploadsController::class, 'index'],
    [$uploadsModel]
)->attachMiddleware(['auth']);

$router->delete(
    '/api/uploads',
    [DeleteUploadController::class, 'deleteUpload'],
    [$uploadsModel]
)->attachMiddleware(['auth']);

$router->patch(
    '/api/uploads',
    [EditUploadController::class, 'editUpload'],
    [$uploadsModel]
)->attachMiddleware(['auth']);
