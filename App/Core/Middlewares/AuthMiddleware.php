<?php

namespace App\Core\Middlewares;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Users\UserModel;
use Exception;

class AuthMiddleware
{
    static public function handle()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && isset($_COOKIE['refreshToken'])) {
            $database = new Database();
            $refreshTokenModel = new RefreshTokenModel($database);
            $refreshToken = $refreshTokenModel->getRefreshToken($_COOKIE['refreshToken']);
            if (
                !$refreshToken
                || (int) $refreshToken['expires_at'] < time()
                || (int) $refreshToken['is_revoked'] === 1
            ) {
                sendResponse(401, "Authentication is required for this endpoint.");
            }

            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? null);
            if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
                $token = $m[1];
            } else {
                sendResponse(401, "A valid bearer token is required.");
            }
            try {
                $jwt = decode_jwt($token);
            } catch (Exception $e) {
                error_log("Error Decoding JWT token , Error" . $e->getMessage(), $e->getCode());
                sendResponse(401, "The access token is invalid.");
            }
            $expiry = $jwt['expiresAt'];
            if ($expiry <= time()) {
                sendResponse(401, "The access token has expired. Please log in again.");
            }

            if ((int) $refreshToken['userid'] !== (int) $jwt['id']) {
                sendResponse(401, "Authentication is required for this endpoint.");
            }

            $userModel = new UserModel($database);
            $user = $userModel->checkUserById($jwt['id']);
            if (!$user) {
                sendResponse(401, "Authentication is required for this endpoint.");
            }

            if ($user['status'] !== 'approved') {
                $refreshTokenModel->revokeRefreshTokensByUser($user['id']);
                sendResponse(403, "Your account is not active.");
            }

            Auth::setUser($user);
            return;
        }

        sendResponse(401, "Authentication is required for this endpoint.");
    }
}
