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
        if ($validData === null) {
            sendResponse('success', 200, "Post Data is valid");
        }

        sendResponse("error", 400, "Required Data is not provided", $validData);
    }

    public function index()
    {
        $this->validatePost();
        // sendResponse("success", 200, "Test Post Data", $this->postData);
    }
}