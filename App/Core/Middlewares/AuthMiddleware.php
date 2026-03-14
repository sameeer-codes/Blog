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
            }
            try {
                $jwt = decode_jwt($token);
            } catch (Exception $e) {
                error_log("Error Decoding JWT token , Error" . $e->getMessage(), $e->getCode());
                sendResponse("error", 401, "Unauthorized Access");
            }
            $jwt = decode_jwt($token);
            Auth::setUser($jwt['id']);
            $expiry = $jwt['expiresAt'];
            if ($expiry <= time()) {
                sendResponse("error", 453, "The provided JWT token has expired. Please log in again to obtain a new token.");
            }
            return;
        }

        sendResponse('error', 403, "Unauthorized Access, No Authorization header was found");
    }
}