<?php

namespace App\Models\Users;

use App\Core\Database;
use PDOException;
class UserModel
{
    protected $connection;
    protected $requestParams;

    public function __construct(Database $connection)
    {
        $this->connection = $connection;
        $this->connection->connect();
    }

    public function registerUser($data = [])
    {
        if ($this->checkUser($data['email'])) {
            sendResponse(409, "An account with this email already exists.");
        } else if ($this->checkUser($data['username'])) {
            sendResponse(409, "This username is already taken.");
        }
        try {
            $data['user_role'] = 'admin';
            $data['status'] = 'pending_approval';
            $sql = "INSERT INTO `users`(`username`, `email`, `password`, `user_role`, `status`) VALUES (:username ,  :email , :password , :user_role, :status)";
            $this->connection->Query($sql, $data);
            return true;
        } catch (PDOException $e) {
            error_log('Failed to Register The User' . $e->getMessage());
            sendResponse(500, 'Unable to register the user right now.');
        }
    }

    public function checkUser($parameter)
    {
        try {
            $sql = "SELECT * FROM `users` WHERE email = :parameter OR username = :parameter";
            $params['parameter'] = $parameter;
            $user = $this->connection->Query($sql, $params)->fetch();
        } catch (PDOException $e) {
            error_log("Failed to Failed to find the user" . $e->getMessage());
            sendResponse(500, "Unable to fetch the user record.");
        }
        if (!empty($user)) {
            return $user;
        }
        return false;
    }
    public function checkUserById($parameter)
    {
        try {
            $sql = "SELECT * FROM `users` WHERE id = :id";
            $user = $this->connection->Query($sql, ['id' => $parameter])->fetch();
        } catch (PDOException $e) {
            error_log("Failed to Failed to find the user" . $e->getMessage());
            sendResponse(500, "Unable to fetch the user record.");
        }
        if (!empty($user)) {
            return $user;
        }
        return false;
    }
}
