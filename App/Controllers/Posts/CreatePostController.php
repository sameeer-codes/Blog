<?php

namespace App\Controllers\Posts;

use App\Core\Auth;
use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModal;
class CreatePostController
{
    private $postsModel;
    private $uploadsModel;
    private $postData;
    private $postParams;

    public function __construct(PostModel $postsModel, UploadsModal $uploadsModel)
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
        $postExcerpt = array_key_exists('postExcerpt', $this->postData) ? trim(strip_tags($this->postData['postExcerpt'])) : '';
        if (empty($postExcerpt)) {
            $postExcerpt = $this->generateExcerpt($this->postData['postBody']);
        }

        $postFeaturedImage = null;
        if (array_key_exists('featuredImage', $this->postData) && $this->postData['featuredImage'] !== null && $this->postData['featuredImage'] !== '') {
            $postFeaturedImage = (int) $this->postData['featuredImage'];
            $featuredImage = $this->uploadsModel->getUploadById(['id' => $postFeaturedImage]);
            if (!$featuredImage) {
                sendResponse(404, "The featured image upload was not found.");
            }

            if ((int) $featuredImage['user_id'] !== (int) Auth::user()) {
                sendResponse(403, "You do not have permission to use this upload as the featured image.");
            }
        }

        $this->postParams = [
            'post_title' => trim(strip_tags($this->postData['postTitle'])),
            'post_slug' => $this->generateSlug($this->postData['postTitle']),
            'post_content' => trim($this->postData['postBody']),
            'post_excerpt' => $postExcerpt,
            'post_featured_image' => $postFeaturedImage !== null ? (string) $postFeaturedImage : null,
            'author_id' => Auth::user(),
            'post_status' => trim($this->postData['postStatus']),
        ];

        if ($this->postsModel->createPost($this->postParams)) {
            sendResponse(201, "Post created successfully.", $this->postParams);
        }
    }
}
