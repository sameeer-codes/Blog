<?php

namespace App\Core\Middlewares;

use App\Core\Auth;
use Exception;

class AuthMiddleware
{
    static public function handle()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && isset($_COOKIE['refreshToken'])) {
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
            Auth::setUser($jwt['id']);
            $expiry = $jwt['expiresAt'];
            if ($expiry <= time()) {
                sendResponse(401, "The access token has expired. Please log in again.");
            }
            return;
        }

        sendResponse(401, "Authentication is required for this endpoint.");
    }
}
