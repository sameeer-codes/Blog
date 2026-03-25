<?php

namespace App\Controllers\Posts;

use App\Core\Auth;
use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModal;

class EditPostController
{
    private $postModel;
    private $uploadsModel;
    private $inputs;
    private $postId;
    private $post;
    private $updateData = [];

    public function __construct(PostModel $postModel, UploadsModal $uploadsModel)
    {
        $this->postModel = $postModel;
        $this->uploadsModel = $uploadsModel;
        $this->inputs = json_decode(file_get_contents('php://input'), true);
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
        if (!is_array($this->inputs)) {
            sendResponse(422, "The post payload is invalid.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        if (array_key_exists('postId', $this->inputs)) {
            $this->postId = $this->inputs['postId'];
        } else {
            sendResponse(400, "The postId field is required.");
        }

        if (!is_int($this->postId) || $this->postId < 1) {
            sendResponse(422, "The postId field must be a valid integer.");
        }

        $this->post = $this->postModel->getAuthorPostById([
            'post_id' => $this->postId,
            'author_id' => Auth::user(),
        ]);

        if (!$this->post) {
            sendResponse(404, "No post was found for the provided id.");
        }

        if (array_key_exists('postTitle', $this->inputs) && trim((string) $this->inputs['postTitle']) !== '') {
            $postTitle = trim(strip_tags((string) $this->inputs['postTitle']));
            if (strlen($postTitle) < 30 || strlen($postTitle) > 200) {
                sendResponse(422, "postTitle must be between 30 and 200 characters.");
            }
            $this->updateData['post_title'] = $postTitle;
            $this->updateData['post_slug'] = $this->resolveUniqueSlug($postTitle, $this->postId);
        }

        if (array_key_exists('postBody', $this->inputs) && trim((string) $this->inputs['postBody']) !== '') {
            $postBody = trim((string) $this->inputs['postBody']);
            if (strlen($postBody) < 500 || strlen($postBody) >= 5000) {
                sendResponse(422, "postBody must be between 500 and 4999 characters.");
            }
            $this->updateData['post_content'] = $postBody;
        }

        if (array_key_exists('postExcerpt', $this->inputs)) {
            $postExcerpt = trim(strip_tags((string) $this->inputs['postExcerpt']));
            if ($postExcerpt === '') {
                if (array_key_exists('post_content', $this->updateData)) {
                    $postExcerpt = $this->generateExcerpt($this->updateData['post_content']);
                } else {
                    $postExcerpt = null;
                }
            } else if (strlen($postExcerpt) < 100 || strlen($postExcerpt) >= 300) {
                sendResponse(422, "postExcerpt must be between 100 and 299 characters.");
            }
            $this->updateData['post_excerpt'] = $postExcerpt;
        }

        if (array_key_exists('featuredImage', $this->inputs)) {
            if ($this->inputs['featuredImage'] === null || $this->inputs['featuredImage'] === '') {
                $this->updateData['post_featured_image'] = null;
            } else {
                $featuredImage = $this->inputs['featuredImage'];
                if (!is_int($featuredImage) || $featuredImage < 1) {
                    sendResponse(422, "featuredImage must be a valid upload id.");
                }

                $upload = $this->uploadsModel->getUploadById([
                    'id' => $featuredImage,
                ]);
                if (!$upload) {
                    sendResponse(404, "The featured image upload was not found.");
                }

                if ((int) $upload['user_id'] !== (int) Auth::user()) {
                    sendResponse(403, "You do not have permission to use this upload as the featured image.");
                }

                $this->updateData['post_featured_image'] = (string) $featuredImage;
            }
        }

        if (array_key_exists('postStatus', $this->inputs) && trim((string) $this->inputs['postStatus']) !== '') {
            $postStatus = trim((string) $this->inputs['postStatus']);
            $validStatuses = ['draft', 'published', 'archived'];
            if (!in_array($postStatus, $validStatuses, true)) {
                sendResponse(422, "postStatus must be one of: draft, published, archived");
            }
            $this->updateData['post_status'] = $postStatus;
        }

        if (empty($this->updateData)) {
            sendResponse(400, "No fields were provided to update.");
        }

        $result = $this->postModel->updatePost([
            'post_id' => $this->postId,
            'author_id' => Auth::user(),
        ] + $this->updateData);

        if ($result > 0) {
            sendResponse(200, "Post updated successfully.");
        }

        sendResponse(200, "No post changes were made.");
    }
}
