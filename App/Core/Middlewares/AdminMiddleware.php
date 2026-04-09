<?php

namespace App\Core\Middlewares;

use App\Core\Auth;

class AdminMiddleware
{
    public static function handle()
    {
        if (!Auth::check()) {
            sendResponse(401, "Authentication is required for this endpoint.");
        }

        $user = Auth::user();
        if ($user['status'] !== 'approved') {
            sendResponse(403, "Your account is not active.");
        }

        if ($user['user_role'] !== 'admin') {
            sendResponse(403, "Admin privileges are required for this endpoint.");
        }
    }
}
