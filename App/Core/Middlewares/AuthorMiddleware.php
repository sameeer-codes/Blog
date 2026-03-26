<?php

namespace App\Core\Middlewares;

use App\Core\Auth;

class AuthorMiddleware
{
    static public function handle()
    {
        if (!Auth::check()) {
            sendResponse(401, "Authentication is required for this endpoint.");
        }

        $user = Auth::user();
        if ($user['status'] !== 'approved') {
            sendResponse(403, "Your account is not active.");
        }

        if (!in_array($user['user_role'], ['author', 'admin'], true)) {
            sendResponse(403, "You do not have permission to access this endpoint.");
        }
    }
}
