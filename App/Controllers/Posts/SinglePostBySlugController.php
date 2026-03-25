<?php

namespace App\Controllers\Posts;

use App\Models\Posts\PostModel;

class SinglePostBySlugController
{
    private $postModel;
    private $slug;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
    }

    public function index()
    {
        if (isset($_GET['slug'])) {
            $this->slug = trim(strip_tags($_GET['slug']));
        } else {
            sendResponse(400, "The post slug is required.");
        }

        if (empty($this->slug)) {
            sendResponse(422, "The post slug must be a non-empty string.");
        }

        $post = $this->postModel->getPostBySlug([
            'post_slug' => $this->slug,
        ]);

        if ($post) {
            sendResponse(200, "Post found.", $post);
        }

        sendResponse(404, "No post was found for the provided slug.");
    }
}
