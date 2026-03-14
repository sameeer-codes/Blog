<?php

namespace App\Core\Middlewares;

class GuestMiddleware
{
    static public function handle()
    {
        if (isset($_COOKIE['refreshToken'])) {
            sendResponse("success", 200, "Already Logged In");
        }
    }
}