<?php

namespace App\Controllers\Posts;

use App\Models\Posts\PostModel;

class PostsController
{

    protected $postModel;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
    }

    public function index()
    {
        sendResponse("success", 200, "These are all the posts");
    }
}