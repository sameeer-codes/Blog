<?php

namespace App\Controllers\Uploads;

use App\Core\Auth;
use App\Models\Uploads\UploadsModal;

class DeleteUploadController
{
    private $inputs;
    private $id;
    private $userId;
    private $image;
    private $uploadsModel;
    public function __construct(UploadsModal $uploadsModel)
    {
        $this->uploadsModel = $uploadsModel;
        $this->inputs = json_decode(file_get_contents("php://input"), true);
        $this->userId = Auth::user();

    }

    public function deleteUpload()
    {
        if (array_key_exists('id', $this->inputs)) {
            $this->id = $this->inputs["id"];
        } else {
            sendResponse(400, "The upload id is required.");
        }
        if (!is_int($this->id)) {
            sendResponse(422, "The upload id must be an integer.");
        }

        $this->image = $this->uploadsModel->getUploadById(["id" => $this->id]);
        if (!$this->image) {
            sendResponse(404, "No upload was found for the provided id.");
        }

        if ((int) $this->image['user_id'] !== (int) $this->userId) {
            sendResponse(403, "You do not have permission to delete this upload.");
        }

        $filePath = correctPath('/public' . $this->image['base_path']);
        if (!file_exists($filePath)) {
            sendResponse(404, "The uploaded file was not found in storage.");
        }

        if (!unlink($filePath)) {
            sendResponse(500, "The uploaded file could not be deleted from storage.");
        }

        $result = $this->uploadsModel->deleteUpload(['id' => $this->id, 'user_id' => $this->userId]);
        if ($result > 0) {
            sendResponse(200, "Upload deleted successfully.");
        }
        sendResponse(500, "The upload could not be deleted.");
    }
}
