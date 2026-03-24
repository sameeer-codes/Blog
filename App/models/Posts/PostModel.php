<?php

namespace App\Models\Posts;

use App\Core\Database;
use PDOException;

class PostModel
{
    protected $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database;
        $this->connection->connect();
    }

    public function createPost($params)
    {
        $sql = "INSERT INTO posts (`post_title`, `post_slug`, `post_content`, `post_excerpt`, `post_featured_image`, `author_id`, `post_status`) VALUES (:post_title, :post_slug, :post_content, :post_excerpt, :post_featured_image, :author_id, :post_status)";
        try {
            $this->connection->Query($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to create the post" . $e->getMessage());
            sendResponse(500, "Unable to create the post right now.");
        }
    }
}
