<?php

namespace App\Controllers\Posts;

use App\Core\Auth;
use App\Models\Posts\PostModel;

class AuthorPostsController
{
    private $postModel;
    private $page;
    private $limit;
    private $offset;

    public function __construct(PostModel $postModel)
    {
        $this->postModel = $postModel;
    }

    public function index()
    {
        $this->page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $this->limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

        if ($this->page < 1) {
            sendResponse(422, "The page value must be greater than 0.");
        }

        if ($this->limit < 1 || $this->limit > 50) {
            sendResponse(422, "The limit value must be between 1 and 50.");
        }

        $this->offset = ($this->page - 1) * $this->limit;
        $totalPosts = $this->postModel->countAuthorPosts([
            'author_id' => Auth::id(),
        ]);

        $posts = $this->postModel->getAuthorPosts([
            'author_id' => Auth::id(),
            'limit' => $this->limit,
            'offset' => $this->offset,
        ]);

        foreach ($posts as $index => $post) {
            $posts[$index]['index'] = $this->offset + $index + 1;
        }

        sendResponse(200, "Your posts fetched successfully.", [
            'items' => $posts,
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $totalPosts,
                'total_pages' => $totalPosts > 0 ? (int) ceil($totalPosts / $this->limit) : 0,
                'has_next_page' => ($this->offset + $this->limit) < $totalPosts,
                'has_previous_page' => $this->page > 1,
            ],
        ]);
    }
}
