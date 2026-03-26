<?php

namespace App\Controllers\Posts;

use App\Core\Auth;
use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModel;
class CreatePostController
{
    private $postsModel;
    private $uploadsModel;
    private $postData;
    private $postParams;

    public function __construct(PostModel $postsModel, UploadsModel $uploadsModel)
    {
        $this->postsModel = $postsModel;
        $this->uploadsModel = $uploadsModel;
        $this->postData = json_decode(file_get_contents('php://input'), true);
    }

    public function validatePost()
    {
        $validData = validatePost($this->postData);
        if (empty($validData)) {
            return;
        }

        sendResponse(422, "The post payload is invalid.", $validData);
    }

    private function generateSlug($title)
    {
        $slug = strtolower(trim(strip_tags($title)));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function resolveUniqueSlug($title)
    {
        $baseSlug = $this->generateSlug($title);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->postsModel->slugExists(['post_slug' => $slug])) {
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
        $this->validatePost();
        $postExcerpt = array_key_exists('post_excerpt', $this->postData) ? trim(strip_tags($this->postData['post_excerpt'])) : '';
        $featuredImageUrl = null;
        if (empty($postExcerpt)) {
            $postExcerpt = $this->generateExcerpt($this->postData['post_body']);
        }

        $postFeaturedImage = null;
        if (array_key_exists('featured_image', $this->postData) && $this->postData['featured_image'] !== null && $this->postData['featured_image'] !== '') {
            $postFeaturedImage = (int) $this->postData['featured_image'];
            $featuredImage = $this->uploadsModel->getUploadById(['id' => $postFeaturedImage]);
            if (!$featuredImage) {
                sendResponse(404, "The featured image upload was not found.");
            }

            if ((int) $featuredImage['user_id'] !== (int) Auth::id()) {
                sendResponse(403, "You do not have permission to use this upload as the featured image.");
            }

            $featuredImageUrl = absoluteUrl($featuredImage['base_path']);
        }

        $this->postParams = [
            'post_title' => trim(strip_tags($this->postData['post_title'])),
            'post_slug' => $this->resolveUniqueSlug($this->postData['post_title']),
            'post_content' => trim($this->postData['post_body']),
            'post_excerpt' => $postExcerpt,
            'post_featured_image' => $postFeaturedImage !== null ? (string) $postFeaturedImage : null,
            'author_id' => Auth::id(),
            'post_status' => trim($this->postData['post_status']),
        ];

        if ($this->postsModel->createPost($this->postParams)) {
            $responseData = $this->postParams;
            $responseData['post_featured_image'] = $featuredImageUrl;
            sendResponse(201, "Post created successfully.", $responseData);
        }
    }
}
