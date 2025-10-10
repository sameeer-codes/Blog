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
                    sendResponse("error", 403, "Refresh Token has expired, please login again");
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
                    sendResponse("success", 200, "User logged in successfully", ['jwt' => $jwtToken]);
                } else {
                    sendResponse('error', 403, "User not found for the given refresh token");
                }
            }
        } else {
            sendResponse('error', 403, "No Refresh Token was Found");
        }
    }
    public function handle()
    {
        $this->vaildateToken();
    }
}