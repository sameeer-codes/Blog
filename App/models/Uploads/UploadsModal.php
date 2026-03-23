<?php

namespace App\Models\Uploads;

use App\Core\Database;
use PDOException;

class UploadsModal
{
    protected $connection;
    protected $requestParams;

    public function __construct(Database $database)
    {
        $this->connection = $database;
        $this->connection->connect();
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
}
