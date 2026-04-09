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
    }

    public function registerUser($data = [])
    {
        if ($this->checkUser($data['email'])) {
            sendResponse(409, "An account with this email already exists.");
        } else if ($this->checkUser($data['username'])) {
            sendResponse(409, "This username is already taken.");
        }
        try {
            $data['user_role'] = 'author';
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

    public function getUsers($params)
    {
        $conditions = [];
        $queryParams = [
            'limit' => $params['limit'],
            'offset' => $params['offset'],
        ];

        if (!empty($params['status']) && $params['status'] !== 'all') {
            $conditions[] = 'status = :status';
            $queryParams['status'] = $params['status'];
        }

        $where = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';
        $sql = "SELECT id, username, email, user_role, status, created_at, updated_at FROM users $where ORDER BY id DESC LIMIT :limit OFFSET :offset";

        try {
            return $this->connection->Query($sql, $queryParams)->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch users" . $e->getMessage());
            sendResponse(500, "Unable to fetch users right now.");
        }
    }

    public function countUsers($params)
    {
        $conditions = [];
        $queryParams = [];

        if (!empty($params['status']) && $params['status'] !== 'all') {
            $conditions[] = 'status = :status';
            $queryParams['status'] = $params['status'];
        }

        $where = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';
        $sql = "SELECT COUNT(*) as total FROM users $where";

        try {
            $result = $this->connection->Query($sql, $queryParams)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Failed to count users" . $e->getMessage());
            sendResponse(500, "Unable to count users right now.");
        }
    }

    public function updateUserStatus($params)
    {
        $sql = "UPDATE users SET status = :status WHERE id = :id";
        try {
            return $this->connection->Query($sql, $params)->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to update user status" . $e->getMessage());
            sendResponse(500, "Unable to update user status right now.");
        }
    }

    public function updateUserRole($params)
    {
        $sql = "UPDATE users SET user_role = :user_role WHERE id = :id";
        try {
            return $this->connection->Query($sql, $params)->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to update user role" . $e->getMessage());
            sendResponse(500, "Unable to update user role right now.");
        }
    }

    public function getSafeUserById($id)
    {
        try {
            $sql = "SELECT id, username, email, user_role, status, created_at, updated_at FROM users WHERE id = :id";
            $user = $this->connection->Query($sql, ['id' => $id])->fetch();
        } catch (PDOException $e) {
            error_log("Failed to fetch safe user record" . $e->getMessage());
            sendResponse(500, "Unable to fetch the user record.");
        }

        return $user ?: false;
    }
}
