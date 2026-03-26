<?php

namespace App\Core\Middlewares;

use App\Core\Database;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Users\UserModel;

class GuestMiddleware
{
    static public function handle()
    {
        if (!isset($_COOKIE['refreshToken']) || empty($_COOKIE['refreshToken'])) {
            return;
        }

        $database = new Database();
        $refreshTokenModel = new RefreshTokenModel($database);
        $userModel = new UserModel($database);
        $refreshToken = $refreshTokenModel->getRefreshToken($_COOKIE['refreshToken']);

        if (!$refreshToken) {
            return;
        }

        if ((int) $refreshToken['expires_at'] < time() || (int) $refreshToken['is_revoked'] === 1) {
            return;
        }

        $user = $userModel->checkUserById($refreshToken['userid']);
        if (!$user) {
            return;
        }

        if ($user['status'] !== 'approved') {
            $refreshTokenModel->revokeRefreshTokensByUser($user['id']);
            return;
        }

        sendResponse(409, "You are already logged in.");
    }
}
