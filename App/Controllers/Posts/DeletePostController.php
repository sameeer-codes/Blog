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
            'author_id' => Auth::id(),
        ]);

        if (!$this->post) {
            sendResponse(404, "No post was found for the provided id.");
        }

        $result = $this->postModel->deletePost([
            'post_id' => $this->postId,
            'author_id' => Auth::id(),
        ]);

        if ($result > 0) {
            sendResponse(200, "Post deleted successfully.");
        }

        sendResponse(500, "The post could not be deleted.");
    }
}
