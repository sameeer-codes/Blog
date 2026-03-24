<?php

namespace App\Controllers\Posts;

use App\Models\Posts\PostModel;

class SinglePostController
{
    private $postModel;
    private $postId;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
    }

    public function index()
    {
        if (isset($_GET['id'])) {
            $this->postId = (int) $_GET['id'];
        } else {
            sendResponse(400, "The post id is required.");
        }

        if ($this->postId < 1) {
            sendResponse(422, "The post id must be a valid integer.");
        }

        $post = $this->postModel->getPostById([
            'post_id' => $this->postId,
        ]);

        if ($post) {
            sendResponse(200, "Post found.", $post);
        }

        sendResponse(404, "No post was found for the provided id.");
    }
}
