<?php

namespace App\Core\Middlewares;

class GuestMiddleware
{
    static public function handle()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']) || isset($_COOKIE['refreshToken'])) {
            sendResponse('error', 403, "Alredy logged In");
        }
        return;
    }
}