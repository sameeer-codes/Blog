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
            dd($expiry);
        } else if ($_COOKIE) {
            dd('Cookie Found');
        }
        sendResponse('error', 403, "Unauthorized Access, No Authorization header was found");
    }
}