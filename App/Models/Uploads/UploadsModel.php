<?php

namespace App\Models\Uploads;

use App\Core\Database;
use PDOException;

class UploadsModel
{
    protected $connection;
    protected $requestParams;

    public function __construct(Database $database)
    {
        $this->connection = $database;
    }

    public function addUpload($data)
    {
        $query = "INSERT INTO uploads (`user_id` , `uploaded_to` , `file_name` ,  `base_path` , `mime_type` ,  `file_size` ,  `alt_text` , `captions`) VALUES (:user_id , :uploaded_to , :file_name , :base_path , :mime_type , :file_size, :alt_text , :captions)";
        try {
            $this->connection->Query($query, $data);
            return true;
        } catch (PDOException $e) {
            error_log('Failed to Upload the file' . $e->getMessage(), $e->getCode());
            sendResponse(500, 'Unable to save the uploaded file metadata.');
        }
    }

    public function deleteUpload($data)
    {
        $sql = "DELETE FROM uploads WHERE  id = :id AND user_id = :user_id";
        try {
            return $this->connection->Query($sql, $data)->rowCount();
        } catch (PDOException $e) {
            error_log("Error Deleting the file" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to delete the upload record.");
        }
    }

    public function updateUpload($data)
    {
        $fields = [];
        $params = [
            'id' => $data['id'],
            'user_id' => $data['user_id'],
        ];

        if (array_key_exists('alt_text', $data)) {
            $fields[] = "alt_text = :alt_text";
            $params['alt_text'] = $data['alt_text'];
        }

        if (array_key_exists('captions', $data)) {
            $fields[] = "captions = :captions";
            $params['captions'] = $data['captions'];
        }

        if (empty($fields)) {
            return 0;
        }

        $sql = "UPDATE uploads SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id";
        try {
            return $this->connection->Query($sql, $params)->rowCount();
        } catch (PDOException $e) {
            error_log("Error Updating the file" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to update the upload record.");
        }
    }

    public function getUploadById($data)
    {
        $sql = "SELECT * FROM uploads WHERE id = :id";
        try {
            return $this->connection->Query($sql, $data)->fetch();
        } catch (PDOException $e) {
            error_log("Error Deleting the file" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to fetch the upload record.");
        }
    }

    public function getUploads($data)
    {
        $sql = "SELECT id, uploaded_to, file_name, base_path, mime_type, file_size, alt_text, captions FROM uploads WHERE user_id = :user_id ORDER BY id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->connection->Query($sql, $data)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching uploads" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to fetch uploads.");
        }
    }

    public function countUploads($data)
    {
        $sql = "SELECT COUNT(*) as total FROM uploads WHERE user_id = :user_id";
        try {
            $result = $this->connection->Query($sql, $data)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting uploads" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to count uploads.");
        }
    }

    public function getAllUploads($data)
    {
        $sql = "SELECT id, user_id, uploaded_to, file_name, base_path, mime_type, file_size, alt_text, captions, created_at, updated_at FROM uploads ORDER BY id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->connection->Query($sql, $data)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching all uploads" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to fetch uploads.");
        }
    }

    public function countAllUploads()
    {
        $sql = "SELECT COUNT(*) as total FROM uploads";
        try {
            $result = $this->connection->Query($sql)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting all uploads" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to count uploads.");
        }
    }

    public function deleteAnyUpload($data)
    {
        $sql = "DELETE FROM uploads WHERE id = :id";
        try {
            return $this->connection->Query($sql, $data)->rowCount();
        } catch (PDOException $e) {
            error_log("Error deleting upload" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to delete the upload record.");
        }
    }

    public function adminUpdateUpload($data)
    {
        $fields = [];
        $params = [
            'id' => $data['id'],
        ];

        if (array_key_exists('alt_text', $data)) {
            $fields[] = "alt_text = :alt_text";
            $params['alt_text'] = $data['alt_text'];
        }

        if (array_key_exists('captions', $data)) {
            $fields[] = "captions = :captions";
            $params['captions'] = $data['captions'];
        }

        if (empty($fields)) {
            return 0;
        }

        $sql = "UPDATE uploads SET " . implode(', ', $fields) . " WHERE id = :id";
        try {
            return $this->connection->Query($sql, $params)->rowCount();
        } catch (PDOException $e) {
            error_log("Error updating admin upload" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to update the upload record.");
        }
    }

    public function getUploadsByUser($data)
    {
        $sql = "SELECT id, user_id, uploaded_to, file_name, base_path, mime_type, file_size, alt_text, captions, created_at, updated_at FROM uploads WHERE user_id = :user_id ORDER BY id DESC";
        try {
            return $this->connection->Query($sql, $data)->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching uploads by user" . $e->getMessage(), $e->getCode());
            sendResponse(500, "Unable to fetch uploads.");
        }
    }
}
