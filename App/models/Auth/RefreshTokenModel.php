<?php

namespace App\Models\Auth;
use PDOException;
class RefreshTokenModel
{
    private $connection;
    public function __construct($db)
    {
        $this->connection = $db;
        $this->connection->connect();
    }

    public function saveRefreshToken($data)
    {
        try {
            $sql = "INSERT INTO `refreshtokens`(`refreshtoken`, `userid`, `issued_at`, `expires_at`) VALUES (:refreshtoken ,  :userid , :issued_at , :expires_at)";
            $result = $this->connection->Query($sql, $data);
        } catch (PDOException $e) {
            error_log('There was an error creating a Refresh Token.' . $e->getMessage());
            sendResponse(500, "Unable to save the refresh token.");
        }
        if ($result) {
            return $result;
        }
        return false;
    }

    public function getRefreshToken($token)
    {

        try {
            $sql = 'SELECT * FROM `refreshTokens` Where refreshtoken = :token';
            $result = $this->connection->Query($sql, ['token' => $token])->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching the refresh Token" . $e->getMessage());
            sendResponse(500, "Unable to fetch the refresh token.");
        }

        if (!empty($result)) {
            return $result;
        }

        return false;
    }

    public function revokeRefreshToken($token)
    {
        try {
            $sql = "UPDATE `refreshTokens` SET `is_revoked` = 1 WHERE `refreshtoken` = :token";
            return $this->connection->Query($sql, ['token' => $token])->rowCount();
        } catch (PDOException $e) {
            error_log("Error revoking the refresh token" . $e->getMessage());
            sendResponse(500, "Unable to revoke the refresh token.");
        }
    }
}
