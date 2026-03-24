<?php

namespace App\Controllers\Posts;

use App\Core\Auth;
use App\Models\Posts\PostModel;

class DeletePostController
{
    private $postModel;
    private $inputs;
    private $postId;
    private $post;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
        $this->inputs = json_decode(file_get_contents('php://input'), true);
    }

    public function index()
    {
        if (array_key_exists('postId', $this->inputs)) {
            $this->postId = $this->inputs['postId'];
        } else {
            sendResponse(400, "The postId field is required.");
        }

        if (!is_int($this->postId) || $this->postId < 1) {
            sendResponse(422, "The postId field must be a valid integer.");
        }

        $this->post = $this->postModel->getPostById([
            'post_id' => $this->postId,
        ]);

        if (!$this->post) {
            sendResponse(404, "No post was found for the provided id.");
        }

        if ((int) $this->post['author_id'] !== (int) Auth::user()) {
            sendResponse(403, "You do not have permission to delete this post.");
        }

        $result = $this->postModel->deletePost([
            'post_id' => $this->postId,
            'author_id' => Auth::user(),
        ]);

        if ($result > 0) {
            sendResponse(200, "Post deleted successfully.");
        }

        sendResponse(500, "The post could not be deleted.");
    }
}
