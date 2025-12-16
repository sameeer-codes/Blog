<?php

namespace App\Controllers\Posts;

use App\Models\Posts\PostModel;
class CreatePostController
{
    private $postsModel;
    private $postdata;

    public function __construct(PostModel $postsModel)
    {
        $this->postsModel = $postsModel;
        $this->postdata = json_decode(file_get_contents('php://input'), true);
    }

    public function validatePost()
    {    
    }

    public function index()
    {
        sendResponse("success", 200, "Test Post Data", $this->postdata);
    }
}