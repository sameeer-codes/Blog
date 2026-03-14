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

    public function addMedia($data)
    {
        $query = "INSERT INTO postsmodel (`user_id` , `uploaded_to` , `file_name` ,  `base_path` , `mime_type` ,  `file_size` ,  `alt_text` , `captions`) VALUES (:user_id , :uploaded_to , :file_name , :base_path , :mime_type , `:file_size`, :alt_text , :captions)";
        try {
            $sql = "INSERT INTO `users`(`username`, `email`, `password`, `userRole`) VALUES (:username ,  :email , :password , :userRole)";
            $this->connection->Query($sql, $data);
            return true;
        } catch (PDOException $e) {
            error_log('Failed to Register The User' . $e->getMessage());
            sendResponse("error", 500, 'Failed to Upload the media, please try again later');
        }
    }
}