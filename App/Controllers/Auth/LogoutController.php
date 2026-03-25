<?php

namespace App\Controllers\Auth;

use App\Models\Auth\RefreshTokenModel;

class LogoutController
{
    private $refreshTokenModel;

    public function __construct(RefreshTokenModel $refreshTokenModel)
    {
        $this->refreshTokenModel = $refreshTokenModel;
    }

    public function handle()
    {
        if (!isset($_COOKIE['refreshToken']) || empty($_COOKIE['refreshToken'])) {
            sendResponse(401, "A refresh token cookie is required.");
        }

        $refreshToken = $_COOKIE['refreshToken'];
        $token = $this->refreshTokenModel->getRefreshToken($refreshToken);

        setcookie('refreshToken', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => 'localhost',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'LAX'
        ]);

        if (!$token) {
            sendResponse(404, "The refresh token was not found.");
        }

        $this->refreshTokenModel->revokeRefreshToken($refreshToken);

        sendResponse(200, "Logout successful.");
    }
}
