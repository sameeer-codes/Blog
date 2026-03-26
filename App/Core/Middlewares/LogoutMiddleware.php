<?php

namespace App\Core\Middlewares;

use Exception;

class LogoutMiddleware
{
    public static function handle()
    {
        if (!isset($_COOKIE['refreshToken']) || empty($_COOKIE['refreshToken'])) {
            sendResponse(401, "A refresh token cookie is required.");
        }

        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['Authorization'] ?? null);
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            $token = $matches[1];
        } else {
            sendResponse(401, "A valid bearer token is required.");
        }

        try {
            decode_jwt($token);
        } catch (Exception $e) {
            error_log("Error decoding logout JWT token: " . $e->getMessage());
            sendResponse(401, "The access token is invalid.");
        }
    }
}
