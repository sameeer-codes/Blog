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
        $this->input = file_get_contents('php://input');
        $this->input = json_decode($this->input);
        $this->userModel = $userModel;
    }
    public function ValidateUser()
    {
        foreach ($this->input as $valInput => $value) {
            if (empty(trim($value))) {
                $this->errors[$valInput] = "$valInput  is required";
            }

            switch ($valInput) {
                case 'username': {
                    if (!preg_match($this->usernameRegex, $value)) {
                        $this->errors[$valInput] = 'Please enter a valid username';
                    } else {
                        $this->registrationData['username'] = $value;
                    }
                    break;
                }

                case 'email': {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$valInput] = 'Please enter a valid Email';
                    } else {
                        $this->registrationData['email'] = $value;
                    }
                    break;
                }

                case 'password': {
                    if (!preg_match($this->passwordRegex, $value)) {
                        $this->errors[$valInput] = 'Please enter a valid password';
                    } else {
                        $password = password_hash($value, PASSWORD_ARGON2ID);
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
            sendResponse("error", 400, "Vaildation Failed, please recheck the given values and try again.", $this->errors);
        }
        $result = $this->userModel->registerUser($this->registrationData);
        if ($result) {
            return true;
        }
    }

    public function sendResponse()
    {
        if ($this->registerUser()) {
            sendResponse("success", 200, 'Registration Successfull, Please Login with your provided credentials');
        }
    }
}
