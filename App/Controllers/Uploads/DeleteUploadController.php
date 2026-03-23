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

        // Check if the file exists before attempting deletion
        // if (file_exists($file_path)) {
        //     // Attempt to delete the file
        //     if (unlink($file_path)) {
        //         echo "The file " . basename($file_path) . " has been deleted successfully.";
        //     } else {
        //         echo "Error deleting the file " . basename($file_path) . ". Check file permissions.";
        //     }
        // } else {
        //     echo "File not found: " . basename($file_path);
        // }

        $result = $this->uploadsModel->deleteUpload(['id' => $this->id, 'user_id' => $this->userId]);
        if ($result > 0) {
            sendResponse(200, "Upload deleted successfully.");
        }
        sendResponse(500, "The upload could not be deleted.");
    }
}
