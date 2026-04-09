<?php

namespace App\Controllers\Admin;

use App\Models\Users\UserModel;
use App\Models\Auth\RefreshTokenModel;
use App\Models\Posts\PostModel;
use App\Models\Uploads\UploadsModel;

class UsersController
{
    private $userModel;
    private $refreshTokenModel;
    private $postModel;
    private $uploadsModel;

    public function __construct(UserModel $userModel, RefreshTokenModel $refreshTokenModel, PostModel $postModel, UploadsModel $uploadsModel)
    {
        $this->userModel = $userModel;
        $this->refreshTokenModel = $refreshTokenModel;
        $this->postModel = $postModel;
        $this->uploadsModel = $uploadsModel;
    }

    public function index()
    {
        $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        if ($page < 1) {
            sendResponse(422, "The page value must be greater than 0.");
        }

        if ($limit < 1 || $limit > 100) {
            sendResponse(422, "The limit value must be between 1 and 100.");
        }

        $validStatuses = ['all', 'pending_approval', 'approved', 'blocked'];
        if (!in_array($status, $validStatuses, true)) {
            sendResponse(422, "Invalid status filter. Allowed: all, pending_approval, approved, blocked.");
        }

        $offset = ($page - 1) * $limit;

        $total = $this->userModel->countUsers(['status' => $status]);
        $users = $this->userModel->getUsers([
            'status' => $status,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        foreach ($users as $index => $user) {
            $users[$index]['index'] = $offset + $index + 1;
        }

        sendResponse(200, "Users fetched successfully.", [
            'items' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $limit) : 0,
                'has_next_page' => ($offset + $limit) < $total,
                'has_previous_page' => $page > 1,
            ],
        ]);
    }

    public function updateStatus()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $id = $input['id'] ?? null;
        $status = isset($input['status']) ? trim($input['status']) : null;

        if (!is_int($id) || $id < 1) {
            sendResponse(422, "The user id must be a positive integer.");
        }

        $validStatuses = ['pending_approval', 'approved', 'blocked'];
        if (!in_array($status, $validStatuses, true)) {
            sendResponse(422, "Invalid status. Allowed: pending_approval, approved, blocked.");
        }

        $user = $this->userModel->checkUserById($id);
        if (!$user) {
            sendResponse(404, "User not found.");
        }

        $changed = $this->userModel->updateUserStatus([
            'id' => $id,
            'status' => $status,
        ]);

        if ($status !== 'approved') {
            $this->refreshTokenModel->revokeRefreshTokensByUser($id);
        }

        if ($changed > 0) {
            sendResponse(200, "User status updated successfully.");
        }

        sendResponse(200, "No changes were made.");
    }

    public function updateRole()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $id = $input['id'] ?? null;
        $role = isset($input['user_role']) ? trim($input['user_role']) : null;

        if (!is_int($id) || $id < 1) {
            sendResponse(422, "The user id must be a positive integer.");
        }

        $validRoles = ['author', 'admin'];
        if (!in_array($role, $validRoles, true)) {
            sendResponse(422, "Invalid user_role. Allowed: author, admin.");
        }

        $user = $this->userModel->checkUserById($id);
        if (!$user) {
            sendResponse(404, "User not found.");
        }

        $changed = $this->userModel->updateUserRole([
            'id' => $id,
            'user_role' => $role,
        ]);

        if ($changed > 0) {
            sendResponse(200, "User role updated successfully.");
        }

        sendResponse(200, "No changes were made.");
    }

    public function show()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id === null || $id < 1) {
            sendResponse(422, "The user id must be a positive integer.");
        }

        $user = $this->userModel->getSafeUserById($id);
        if (!$user) {
            sendResponse(404, "User not found.");
        }

        $posts = $this->postModel->getPostsByAuthor([
            'author_id' => $id,
        ]);
        $uploads = $this->uploadsModel->getUploadsByUser([
            'user_id' => $id,
        ]);

        sendResponse(200, "User details fetched successfully.", [
            'user' => $user,
            'posts' => $posts,
            'uploads' => $uploads,
            'stats' => [
                'posts_count' => count($posts),
                'uploads_count' => count($uploads),
            ],
        ]);
    }
}
