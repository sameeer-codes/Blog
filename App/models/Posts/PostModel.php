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

    public function getPosts($params)
    {
        $sql = "SELECT post_id, post_title, post_slug, post_content, post_excerpt, post_featured_image, author_id, post_status, created_at, updated_at FROM posts ORDER BY post_id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->connection->Query($sql, $params)->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch posts" . $e->getMessage());
            sendResponse(500, "Unable to fetch posts right now.");
        }
    }

    public function countPosts()
    {
        $sql = "SELECT COUNT(*) as total FROM posts";
        try {
            $result = $this->connection->Query($sql)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Failed to count posts" . $e->getMessage());
            sendResponse(500, "Unable to count posts right now.");
        }
    }

    public function getPostById($params)
    {
        $sql = "SELECT post_id, post_title, post_slug, post_content, post_excerpt, post_featured_image, author_id, post_status, created_at, updated_at FROM posts WHERE post_id = :post_id";
        try {
            return $this->connection->Query($sql, $params)->fetch();
        } catch (PDOException $e) {
            error_log("Failed to fetch post" . $e->getMessage());
            sendResponse(500, "Unable to fetch the post right now.");
        }
    }
}
