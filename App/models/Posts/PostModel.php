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
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.post_status = 'published' ORDER BY posts.post_id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->formatPosts($this->connection->Query($sql, $params)->fetchAll());
        } catch (PDOException $e) {
            error_log("Failed to fetch posts" . $e->getMessage());
            sendResponse(500, "Unable to fetch posts right now.");
        }
    }

    public function countPosts()
    {
        $sql = "SELECT COUNT(*) as total FROM posts WHERE post_status = 'published'";
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
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.post_id = :post_id AND posts.post_status = 'published'";
        try {
            return $this->formatPost($this->connection->Query($sql, $params)->fetch());
        } catch (PDOException $e) {
            error_log("Failed to fetch post" . $e->getMessage());
            sendResponse(500, "Unable to fetch the post right now.");
        }
    }

    public function getPostBySlug($params)
    {
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.post_slug = :post_slug AND posts.post_status = 'published'";
        try {
            return $this->formatPost($this->connection->Query($sql, $params)->fetch());
        } catch (PDOException $e) {
            error_log("Failed to fetch post by slug" . $e->getMessage());
            sendResponse(500, "Unable to fetch the post right now.");
        }
    }

    public function slugExists($params)
    {
        $sql = "SELECT post_id FROM posts WHERE post_slug = :post_slug";

        if (array_key_exists('post_id', $params)) {
            $sql .= " AND post_id != :post_id";
        }

        try {
            $result = $this->connection->Query($sql, $params)->fetch();
            return !empty($result);
        } catch (PDOException $e) {
            error_log("Failed to check post slug" . $e->getMessage());
            sendResponse(500, "Unable to validate the post slug right now.");
        }
    }

    public function updatePost($params)
    {
        $fields = [];
        $queryParams = [
            'post_id' => $params['post_id'],
            'author_id' => $params['author_id'],
        ];

        if (array_key_exists('post_title', $params)) {
            $fields[] = "post_title = :post_title";
            $queryParams['post_title'] = $params['post_title'];
        }

        if (array_key_exists('post_slug', $params)) {
            $fields[] = "post_slug = :post_slug";
            $queryParams['post_slug'] = $params['post_slug'];
        }

        if (array_key_exists('post_content', $params)) {
            $fields[] = "post_content = :post_content";
            $queryParams['post_content'] = $params['post_content'];
        }

        if (array_key_exists('post_excerpt', $params)) {
            $fields[] = "post_excerpt = :post_excerpt";
            $queryParams['post_excerpt'] = $params['post_excerpt'];
        }

        if (array_key_exists('post_featured_image', $params)) {
            $fields[] = "post_featured_image = :post_featured_image";
            $queryParams['post_featured_image'] = $params['post_featured_image'];
        }

        if (array_key_exists('post_status', $params)) {
            $fields[] = "post_status = :post_status";
            $queryParams['post_status'] = $params['post_status'];
        }

        if (empty($fields)) {
            return 0;
        }

        $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE post_id = :post_id AND author_id = :author_id";
        try {
            return $this->connection->Query($sql, $queryParams)->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to update post" . $e->getMessage());
            sendResponse(500, "Unable to update the post right now.");
        }
    }

    public function deletePost($params)
    {
        $sql = "DELETE FROM posts WHERE post_id = :post_id AND author_id = :author_id";
        try {
            return $this->connection->Query($sql, $params)->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to delete post" . $e->getMessage());
            sendResponse(500, "Unable to delete the post right now.");
        }
    }

    public function searchPosts($params)
    {
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.post_status = 'published' AND (posts.post_title LIKE :query OR posts.post_content LIKE :query OR posts.post_excerpt LIKE :query) ORDER BY posts.post_id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->formatPosts($this->connection->Query($sql, $params)->fetchAll());
        } catch (PDOException $e) {
            error_log("Failed to search posts" . $e->getMessage());
            sendResponse(500, "Unable to search posts right now.");
        }
    }

    public function countSearchPosts($params)
    {
        $sql = "SELECT COUNT(*) as total FROM posts WHERE post_status = 'published' AND (post_title LIKE :query OR post_content LIKE :query OR post_excerpt LIKE :query)";
        try {
            $result = $this->connection->Query($sql, $params)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Failed to count searched posts" . $e->getMessage());
            sendResponse(500, "Unable to count search results right now.");
        }
    }

    public function getAuthorPosts($params)
    {
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.author_id = :author_id ORDER BY posts.post_id DESC LIMIT :limit OFFSET :offset";
        try {
            return $this->formatPosts($this->connection->Query($sql, $params)->fetchAll());
        } catch (PDOException $e) {
            error_log("Failed to fetch author posts" . $e->getMessage());
            sendResponse(500, "Unable to fetch your posts right now.");
        }
    }

    public function countAuthorPosts($params)
    {
        $sql = "SELECT COUNT(*) as total FROM posts WHERE author_id = :author_id";
        try {
            $result = $this->connection->Query($sql, $params)->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Failed to count author posts" . $e->getMessage());
            sendResponse(500, "Unable to count your posts right now.");
        }
    }

    public function getAuthorPostById($params)
    {
        $sql = "SELECT posts.post_id, posts.post_title, posts.post_slug, posts.post_content, posts.post_excerpt, posts.post_featured_image, uploads.base_path AS featured_image_path, posts.author_id, posts.post_status, posts.created_at, posts.updated_at FROM posts LEFT JOIN uploads ON uploads.id = posts.post_featured_image WHERE posts.post_id = :post_id AND posts.author_id = :author_id";
        try {
            return $this->formatPost($this->connection->Query($sql, $params)->fetch());
        } catch (PDOException $e) {
            error_log("Failed to fetch author post" . $e->getMessage());
            sendResponse(500, "Unable to fetch your post right now.");
        }
    }

    protected function formatPosts($posts)
    {
        foreach ($posts as $index => $post) {
            $posts[$index] = $this->formatPost($post);
        }

        return $posts;
    }

    protected function formatPost($post)
    {
        if (!$post) {
            return $post;
        }

        $post['post_featured_image'] = !empty($post['featured_image_path'])
            ? absoluteUrl($post['featured_image_path'])
            : null;

        unset($post['featured_image_path']);

        return $post;
    }
}
