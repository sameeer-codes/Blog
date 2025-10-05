<?php

namespace App\Core\Middlewares;

class AdminMiddleware
{
    static public function handle()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? null);
            if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m))
                $token = $m[1];
            $jwt = decode_jwt($token);
            $expiry = $jwt['expiresAt'];
            if ($expiry <= time()) {
                sendResponse("error", 453, "The provided JWT token has expired. Please log in again to obtain a new token.");
            }
            return;
        }
        sendResponse('error', 403, "Unauthorized Access, No Authorization header was found");
    }
}