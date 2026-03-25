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
        if (!is_array($this->input)) {
            sendResponse(422, "The login payload is invalid.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $requiredFields = ['email', 'password'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $this->input) || trim((string) $this->input[$field]) === '') {
                $this->errors[$field] = "$field is required";
            }
        }

        foreach ($this->input as $field => $value) {
            if (!is_scalar($value) || empty(trim((string) $value))) {
                $this->errors[$field] = "$field is required";
            }
        }

        if (!empty($this->errors)) {
            sendResponse(422, "The login payload is invalid.", $this->errors);
        }
    }

    protected function checkUser()
    {
        $this->validateUser();

        $user = $this->userModel->checkUser($this->input['email']);
        if ($user && password_verify($this->input['password'], $user['password'])) {
            return $user;
        }

        sendResponse(401, "The email or password is incorrect.");
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

        // Set refresh token cookie (HttpOnly)
        setcookie(
            'refreshToken',
            $refreshToken,
            [
                'expires' => time() + 3600 * 24 * 30,
                'path' => '/',
                'secure' => false,   // true if using HTTPS
                'httponly' => true,
                'samesite' => 'LAX' // important for cross-origin!
            ]
        );

        sendResponse(200, "Login successful.", [
            'jwt' => $jwtToken
        ]);
    }
}
