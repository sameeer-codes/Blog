<?php

namespace App\Core\Middlewares;

class GuestMiddleware
{
    static public function handle()
    {
        if (isset($_COOKIE['refreshToken'])) {
            sendResponse(409, "You are already logged in.");
        }
    }
}
