<?php

namespace App\Controllers\Admin;

use App\Models\Uploads\UploadsModel;

class UploadsController
{
    private $uploadsModel;

    public function __construct(UploadsModel $uploadsModel)
    {
        $this->uploadsModel = $uploadsModel;
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

        if ($page < 1) {
            sendResponse(422, "The page value must be greater than 0.");
        }

        if ($limit < 1 || $limit > 100) {
            sendResponse(422, "The limit value must be between 1 and 100.");
        }

        $offset = ($page - 1) * $limit;

        $total = $this->uploadsModel->countAllUploads();
        $uploads = $this->uploadsModel->getAllUploads([
            'limit' => $limit,
            'offset' => $offset,
        ]);

        foreach ($uploads as $index => $upload) {
            $uploads[$index]['index'] = $offset + $index + 1;
        }

        sendResponse(200, "Uploads fetched successfully.", [
            'items' => $uploads,
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

    public function delete()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $id = $input['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            sendResponse(422, "The upload id must be a positive integer.");
        }

        $upload = $this->uploadsModel->getUploadById(['id' => $id]);
        if (!$upload) {
            sendResponse(404, "Upload not found.");
        }

        $filePath = correctPath('/public' . $upload['base_path']);
        if (file_exists($filePath) && !unlink($filePath)) {
            sendResponse(500, "The uploaded file could not be deleted from storage.");
        }

        $deleted = $this->uploadsModel->deleteAnyUpload(['id' => $id]);
        if ($deleted > 0) {
            sendResponse(200, "Upload deleted successfully.");
        }

        sendResponse(500, "The upload could not be deleted.");
    }

    public function update()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!is_array($input)) {
            sendResponse(422, "A valid JSON payload is required.", [
                'payload' => 'A valid JSON object is required'
            ]);
        }

        $id = $input['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            sendResponse(422, "The upload id must be a positive integer.");
        }

        $upload = $this->uploadsModel->getUploadById(['id' => $id]);
        if (!$upload) {
            sendResponse(404, "Upload not found.");
        }

        $updateData = [];

        if (array_key_exists('alt_text', $input) && trim((string) $input['alt_text']) !== '') {
            $altText = trim(strip_tags((string) $input['alt_text']));
            if (strlen($altText) > 200) {
                sendResponse(422, "The alt_text field must not exceed 200 characters.");
            }
            $updateData['alt_text'] = $altText;
        }

        if (array_key_exists('captions', $input) && trim((string) $input['captions']) !== '') {
            $captions = trim(strip_tags((string) $input['captions']));
            if (strlen($captions) > 200) {
                sendResponse(422, "The captions field must not exceed 200 characters.");
            }
            $updateData['captions'] = $captions;
        }

        if (empty($updateData)) {
            sendResponse(400, "No fields were provided to update.");
        }

        $result = $this->uploadsModel->adminUpdateUpload([
            'id' => $id,
        ] + $updateData);

        if ($result > 0) {
            sendResponse(200, "Upload updated successfully.");
        }

        sendResponse(200, "No upload changes were made.");
    }
}
