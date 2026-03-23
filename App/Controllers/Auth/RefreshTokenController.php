<?php

namespace App\Controllers\Auth;

use App\Models\Auth\RefreshTokenModel;
use App\Models\Users\UserModel;

class RefreshTokenController
{
    private $refreshTokenModel;
    private $userModel;
    private $refreshToken;
    public function __construct(RefreshTokenModel $refreshTokenModel, UserModel $userModel)
    {
        $this->refreshTokenModel = $refreshTokenModel;
        $this->userModel = $userModel;
    }

    public function vaildateToken()
    {
        if (isset($_COOKIE['refreshToken'])) {
            $this->refreshToken = $_COOKIE['refreshToken'];
            $this->refreshToken = $this->refreshTokenModel->getRefreshToken($this->refreshToken);
            if ($this->refreshToken) {
                if (!$this->refreshToken['expires_at'] >= time() or $this->refreshToken['is_revoked'] === true) {
                    sendResponse(401, "The refresh token has expired. Please log in again.");
                }
                $userid = $this->refreshToken['userid'];
                $user = $this->userModel->checkUserById($userid);
                if ($user) {
                    $jwtToken = generate_jwt([
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'userRole' => $user['userRole'],
                        'issuedAt' => time(),
                        'expiresAt' => time() + 3600,
                    ]);
                    sendResponse(200, "Access token refreshed successfully.", ['token' => $jwtToken]);
                } else {
                    sendResponse(404, "The user for this refresh token was not found.");
                }
            }
        } else {
            sendResponse(401, "A refresh token cookie is required.");
        }
    }
    public function handle()
    {
        $this->vaildateToken();
    }
}
