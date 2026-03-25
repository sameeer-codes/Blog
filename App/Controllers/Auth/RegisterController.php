<?php

namespace App\Controllers\Auth;

use App\Models\Users\UserModel;
class RegisterController
{
    protected $input;
    protected $responseData;
    protected $errors = [];
    protected $userModel;
    protected $registrationData;
    protected $usernameRegex = '/^[a-zA-Z0-9-\._]{3,16}$/';
    protected $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,64}$/';

    public function __construct(UserModel $userModel)
    {
        $this->input = json_decode(file_get_contents('php://input'), true);
        $this->userModel = $userModel;
    }
    public function ValidateUser()
    {
        if (!is_array($this->input)) {
            sendResponse(422, "The registration payload is invalid.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $this->input) || trim((string) $this->input[$field]) === '') {
                $this->errors[$field] = "$field is required";
            }
        }

        foreach ($this->input as $valInput => $value) {
            if (!is_scalar($value) || empty(trim((string) $value))) {
                $this->errors[$valInput] = "$valInput  is required";
            }

            switch ($valInput) {
                case 'username': {
                    if (!preg_match($this->usernameRegex, (string) $value)) {
                        $this->errors[$valInput] = 'Please enter a valid username';
                    } else {
                        $this->registrationData['username'] = trim((string) $value);
                    }
                    break;
                }

                case 'email': {
                    if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$valInput] = 'Please enter a valid Email';
                    } else {
                        $this->registrationData['email'] = trim((string) $value);
                    }
                    break;
                }

                case 'password': {
                    if (!preg_match($this->passwordRegex, (string) $value)) {
                        $this->errors[$valInput] = 'Please enter a valid password';
                    } else {
                        $password = password_hash((string) $value, PASSWORD_ARGON2ID);
                        $this->registrationData['password'] = $password;
                    }
                    break;
                }
            }
        }
    }

    public function registerUser()
    {
        $this->ValidateUser();
        if (count($this->errors) > 0) {
            sendResponse(422, "The registration payload is invalid.", $this->errors);
        }
        $result = $this->userModel->registerUser($this->registrationData);
        if ($result) {
            return true;
        }
    }

    public function sendResponse()
    {
        if ($this->registerUser()) {
            sendResponse(201, 'Registration successful. You can now log in with your credentials.');
        }
    }
}
