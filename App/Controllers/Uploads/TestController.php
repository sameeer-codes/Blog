<?php

namespace App\Controllers\Uploads;

use App\Models\Uploads\UploadsModal;

class TestController
{
    private $uploadsModel;
    private $inputs;
    private $id;

    public function __construct(UploadsModal $uploadsModel)
    {
        $this->uploadsModel = $uploadsModel;
        $this->inputs = json_decode(file_get_contents("php://input"), true);
    }

    public function getUpload()
    {
        if (array_key_exists('id', $this->inputs)) {
            $this->id = $this->inputs["id"];
        } else {
            sendResponse(400, "The upload id is required.");
        }
        if (!is_int($this->id)) {
            sendResponse(422, "The upload id must be an integer.");
        }

        $result = $this->uploadsModel->getUploadById(['id' => $this->id]);
        if ($result) {
            sendResponse(200, "Upload found.", $result);
        }
        sendResponse(404, "No upload was found for the provided id.");
    }
}
