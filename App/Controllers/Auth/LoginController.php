<?php

namespace App\Controllers\Auth;

use App\Models\Auth\RefreshTokenModel;
use App\Models\Users\UserModel;

class LoginController
{
    protected $input;
    protected $errors = [];
    protected $userModel;
    protected $refreshTokenModel;

    public function __construct(UserModel $userModel, RefreshTokenModel $refreshTokenModel)
    {
        $this->input = json_decode(file_get_contents('php://input'), true);
        $this->userModel = $userModel;
        $this->refreshTokenModel = $refreshTokenModel;
    }

    protected function validateUser()
    {
        foreach ($this->input as $field => $value) {
            if (empty(trim($value))) {
                $this->errors[$field] = "$field is required";
            }
        }

        if (!empty($this->errors)) {
            sendResponse("error", 400, "Validation Failed", $this->errors);
        }
    }

    protected function checkUser()
    {
        $this->validateUser();

        $user = $this->userModel->checkUser($this->input['email']);
        if ($user && password_verify($this->input['password'], $user['password'])) {
            return $user;
        }

        sendResponse("error", 404, "Invalid email or password");
    }

    public function login()
    {
        $user = $this->checkUser();

        // Generate tokens
        $jwtToken = generate_jwt([
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'userRole' => $user['userRole'],
            'issuedAt' => time(),
            'expiresAt' => time() + 3600,
        ]);

        $refreshToken = generate_refresh_token();

        // Save refresh token in DB
        $this->refreshTokenModel->saveRefreshToken([
            'refreshtoken' => $refreshToken,
            'userid' => $user['id'],
            'issued_at' => time(),
            'expires_at' => time() + 3600 * 24 * 30,
        ]);

        // Optionally: set refresh token as HTTP-only cookie
        setcookie('refreshToken', $refreshToken, time() + 3600 * 24 * 30, "/", "", false, true);

        sendResponse("success", 200, "Login successful", [
            'jwt' => $jwtToken
        ]);
    }
}
