<?php

namespace App\Controllers\Posts;

use App\Models\Posts\PostModel;
class CreatePostController
{
    private $postsModel;

    private $requiredData;
    private $postData;
    private $errors;

    public function __construct(PostModel $postsModel)
    {
        $this->postsModel = $postsModel;
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

    public function index()
    {
        $this->validatePost();
        sendResponse(501, "Post creation is not implemented yet.", $this->postData);
    }
}
