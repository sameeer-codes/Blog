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
            sendResponse('error', 409, "Email alredy exists");
        } else if ($this->checkUser($data['username'])) {
            sendResponse("error", 409, "Username Alredy Exists");
        }
        try {
            $data['userRole'] = 'admin';
            $sql = "INSERT INTO `users`(`username`, `email`, `password`, `userRole`) VALUES (:username ,  :email , :password , :userRole)";
            $this->connection->Query($sql, $data);
            return true;
        } catch (PDOException $e) {
            error_log('Failed to Register The User' . $e->getMessage());
            sendResponse("error", 500, 'Failed to Registet the User, please try again later');
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
            sendResponse("error", 500, "Some Error occured while trying to fetch the user");
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
            sendResponse("error", 500, "Some Error occured while trying to fetch the user");
        }
        if (!empty($user)) {
            return $user;
        }
        return false;
    }
}