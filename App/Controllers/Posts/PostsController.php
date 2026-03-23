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
        sendResponse(501, "Post listing is not implemented yet.");
    }
}
