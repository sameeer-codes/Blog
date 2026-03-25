<?php

namespace App\Controllers\Uploads;

use App\Core\Auth;
use App\Models\Uploads\UploadsModal;

class GetUploadsController
{
    private $uploadsModel;
    private $userId;
    private $page;
    private $limit;
    private $offset;

    public function __construct(UploadsModal $uploadsModel)
    {
        $this->uploadsModel = $uploadsModel;
        $this->userId = Auth::user();
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

        $totalUploads = $this->uploadsModel->countUploads([
            'user_id' => $this->userId,
        ]);

        $uploads = $this->uploadsModel->getUploads([
            'user_id' => $this->userId,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ]);

        foreach ($uploads as $index => $upload) {
            $uploads[$index]['base_path'] = absoluteUrl($upload['base_path']);
            $uploads[$index]['index'] = $this->offset + $index + 1;
        }

        sendResponse(200, "Uploads fetched successfully.", [
            'items' => $uploads,
            'pagination' => [
                'page' => $this->page,
                'limit' => $this->limit,
                'total' => $totalUploads,
                'total_pages' => $totalUploads > 0 ? (int) ceil($totalUploads / $this->limit) : 0,
                'has_next_page' => ($this->offset + $this->limit) < $totalUploads,
                'has_previous_page' => $this->page > 1,
            ],
        ]);
    }
}
