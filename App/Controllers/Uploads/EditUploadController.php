<?php

namespace App\Controllers\Uploads;

use App\Core\Auth;
use App\Models\Uploads\UploadsModal;

class EditUploadController
{
    private $inputs;
    private $id;
    private $userId;
    private $image;
    private $uploadsModel;
    private $altText;
    private $captions;
    private $updateData = [];

    public function __construct(UploadsModal $uploadsModel)
    {
        $this->uploadsModel = $uploadsModel;
        $this->inputs = json_decode(file_get_contents("php://input"), true);
        $this->userId = Auth::user();
    }

    public function editUpload()
    {
        if (!is_array($this->inputs)) {
            sendResponse(422, "The upload payload is invalid.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        if (array_key_exists('id', $this->inputs)) {
            $this->id = $this->inputs["id"];
        } else {
            sendResponse(400, "The upload id is required.");
        }

        if (!is_int($this->id)) {
            sendResponse(422, "The upload id must be an integer.");
        }

        if (array_key_exists('alt_text', $this->inputs) && trim((string) $this->inputs['alt_text']) !== '') {
            $this->altText = trim(strip_tags((string) $this->inputs['alt_text']));
            if (strlen($this->altText) > 200) {
                sendResponse(422, "The alt_text field must not exceed 200 characters.");
            }
            $this->updateData['alt_text'] = $this->altText;
        }

        if (array_key_exists('captions', $this->inputs) && trim((string) $this->inputs['captions']) !== '') {
            $this->captions = trim(strip_tags((string) $this->inputs['captions']));
            if (strlen($this->captions) > 200) {
                sendResponse(422, "The captions field must not exceed 200 characters.");
            }
            $this->updateData['captions'] = $this->captions;
        }

        if (empty($this->updateData)) {
            sendResponse(400, "No fields were provided to update.");
        }

        $this->image = $this->uploadsModel->getUploadById(['id' => $this->id]);
        if (!$this->image) {
            sendResponse(404, "No upload was found for the provided id.");
        }

        if ((int) $this->image['user_id'] !== (int) $this->userId) {
            sendResponse(403, "You do not have permission to edit this upload.");
        }

        $result = $this->uploadsModel->updateUpload([
            'id' => $this->id,
            'user_id' => $this->userId,
        ] + $this->updateData);

        if ($result > 0) {
            sendResponse(200, "Upload updated successfully.");
        }

        sendResponse(200, "No upload changes were made.");
    }
}
