<?php

namespace App\Controllers\Admin;

use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModel;

class PostsController
{
    private $postModel;
    private $uploadsModel;

    public function __construct(PostModel $postModel, UploadsModel $uploadsModel)
    {
        $this->postModel = $postModel;
        $this->uploadsModel = $uploadsModel;
    }

    private function generateSlug($title)
    {
        $slug = strtolower(trim(strip_tags($title)));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function resolveUniqueSlug($title, $postId)
    {
        $baseSlug = $this->generateSlug($title);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->postModel->slugExists([
            'post_slug' => $slug,
            'post_id' => $postId,
        ])) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function generateExcerpt($content, $limit = 299)
    {
        $content = trim(strip_tags($content));
        if (strlen($content) <= $limit) {
            return $content;
        }

        $trimLimit = $limit - 3;
        $excerpt = substr($content, 0, $trimLimit + 1);
        $lastSpace = strrpos($excerpt, ' ');
        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        } else {
            $excerpt = substr($excerpt, 0, $trimLimit);
        }

        return rtrim($excerpt) . '...';
    }

    public function index()
    {
        $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        if ($page < 1) {
            sendResponse(422, "The page value must be greater than 0.");
        }

        if ($limit < 1 || $limit > 100) {
            sendResponse(422, "The limit value must be between 1 and 100.");
        }

        $validStatuses = ['all', 'draft', 'published', 'archived'];
        if (!in_array($status, $validStatuses, true)) {
            sendResponse(422, "Invalid status filter. Allowed: all, draft, published, archived.");
        }

        $offset = ($page - 1) * $limit;

        $total = $this->postModel->countAllPosts(['status' => $status]);
        $posts = $this->postModel->getAllPosts([
            'status' => $status,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        foreach ($posts as $index => $post) {
            $posts[$index]['index'] = $offset + $index + 1;
        }

        sendResponse(200, "Posts fetched successfully.", [
            'items' => $posts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
                'has_next_page' => ($offset + $limit) < $total,
                'has_previous_page' => $page > 1,
            ],
        ]);
    }

    public function updateStatus()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $postId = $input['post_id'] ?? null;
        $status = isset($input['post_status']) ? trim($input['post_status']) : null;

        if (!is_int($postId) || $postId < 1) {
            sendResponse(422, "The post_id must be a positive integer.");
        }

        $validStatuses = ['draft', 'published', 'archived'];
        if (!in_array($status, $validStatuses, true)) {
            sendResponse(422, "Invalid post_status. Allowed: draft, published, archived.");
        }

        $changed = $this->postModel->adminUpdateStatus([
            'post_id' => $postId,
            'post_status' => $status,
        ]);

        if ($changed > 0) {
            sendResponse(200, "Post status updated successfully.");
        }

        sendResponse(200, "No changes were made.");
    }

    public function delete()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $postId = $input['post_id'] ?? null;
        if (!is_int($postId) || $postId < 1) {
            sendResponse(422, "The post_id must be a positive integer.");
        }

        $deleted = $this->postModel->adminDeletePost(['post_id' => $postId]);
        if ($deleted > 0) {
            sendResponse(200, "Post deleted successfully.");
        }

        sendResponse(404, "Post not found.");
    }

    public function update()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $postId = $input['post_id'] ?? null;
        if (!is_int($postId) || $postId < 1) {
            sendResponse(422, "The post_id must be a positive integer.");
        }

        $post = $this->postModel->getAnyPostById([
            'post_id' => $postId,
        ]);
        if (!$post) {
            sendResponse(404, "Post not found.");
        }

        $updateData = [];

        if (array_key_exists('post_title', $input) && trim((string) $input['post_title']) !== '') {
            $postTitle = trim(strip_tags((string) $input['post_title']));
            if (strlen($postTitle) < 30 || strlen($postTitle) > 200) {
                sendResponse(422, "post_title must be between 30 and 200 characters.");
            }
            $updateData['post_title'] = $postTitle;
            $updateData['post_slug'] = $this->resolveUniqueSlug($postTitle, $postId);
        }

        if (array_key_exists('post_body', $input) && trim((string) $input['post_body']) !== '') {
            $postBody = trim((string) $input['post_body']);
            if (strlen($postBody) < 500 || strlen($postBody) >= 5000) {
                sendResponse(422, "post_body must be between 500 and 4999 characters.");
            }
            $updateData['post_content'] = $postBody;
        }

        if (array_key_exists('post_excerpt', $input)) {
            $postExcerpt = trim(strip_tags((string) $input['post_excerpt']));
            if ($postExcerpt === '') {
                if (array_key_exists('post_content', $updateData)) {
                    $postExcerpt = $this->generateExcerpt($updateData['post_content']);
                } else {
                    $postExcerpt = null;
                }
            } else if (strlen($postExcerpt) < 100 || strlen($postExcerpt) >= 300) {
                sendResponse(422, "post_excerpt must be between 100 and 299 characters.");
            }
            $updateData['post_excerpt'] = $postExcerpt;
        }

        if (array_key_exists('featured_image', $input)) {
            if ($input['featured_image'] === null || $input['featured_image'] === '') {
                $updateData['post_featured_image'] = null;
            } else {
                $featuredImage = $input['featured_image'];
                if (!is_int($featuredImage) || $featuredImage < 1) {
                    sendResponse(422, "featured_image must be a valid upload id.");
                }

                $upload = $this->uploadsModel->getUploadById([
                    'id' => $featuredImage,
                ]);
                if (!$upload) {
                    sendResponse(404, "The featured image upload was not found.");
                }

                if ((int) $upload['user_id'] !== (int) $post['author_id']) {
                    sendResponse(403, "The featured image must belong to the post author.");
                }

                $updateData['post_featured_image'] = (string) $featuredImage;
            }
        }

        if (array_key_exists('post_status', $input) && trim((string) $input['post_status']) !== '') {
            $postStatus = trim((string) $input['post_status']);
            $validStatuses = ['draft', 'published', 'archived'];
            if (!in_array($postStatus, $validStatuses, true)) {
                sendResponse(422, "post_status must be one of: draft, published, archived");
            }
            $updateData['post_status'] = $postStatus;
        }

        if (empty($updateData)) {
            sendResponse(400, "No fields were provided to update.");
        }

        $result = $this->postModel->adminUpdatePost([
            'post_id' => $postId,
        ] + $updateData);

        if ($result > 0) {
            sendResponse(200, "Post updated successfully.");
        }

        sendResponse(200, "No post changes were made.");
    }
}
