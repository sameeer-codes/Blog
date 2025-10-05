<?php

namespace App\Controllers\Auth;

use App\Models\Auth\RefreshTokenModel;

class RefreshTokenController
{
    private $refreshTokenModel;
    private $refreshToken;
    public function __construct(RefreshTokenModel $refreshTokenModel)
    {
        $this->refreshTokenModel = $refreshTokenModel;
    }

    public function vaildateToken()
    {
        if (isset($_COOKIE['refreshToken'])) {
            $this->refreshToken = $_COOKIE['refreshToken'];
            $this->refreshToken = $this->refreshTokenModel->getRefreshToken($this->refreshToken);
            if ($this->refreshToken) {

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