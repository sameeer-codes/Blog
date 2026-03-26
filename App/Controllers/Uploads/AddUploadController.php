<?php

namespace App\Controllers\Uploads;
use App\Core\Auth;
use App\Models\Uploads\UploadsModel;
use DateTime;
use finfo;

class AddUploadController
{
    private $input;
    private $files;
    private $uploadsModel;
    private $uploadDir;
    private $uploads;
    private $uploadspath;
    private $errors = [];

    public function __construct(UploadsModel $uploadsModel)
    {
        $this->files = $_FILES['files'] ?? null;
        $this->uploadsModel = $uploadsModel;
        $this->uploads = [];
    }

    private function validate()
    {
        $this->uploadspath = '/uploads';
        $this->uploadDir = correctPath("/public/uploads");

        if (
            !is_array($this->files)
            || !isset($this->files['name'])
            || !is_array($this->files['name'])
            || count($this->files['name']) === 0
        ) {
            sendResponse(422, "At least one file is required.");
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        for ($i = 0; $i < count($this->files['name']); $i++) {

            $date = new DateTime();
            $date = $date->format("d-m-Y-H-i-s-v");

            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', strtolower($this->files['name'][$i])); // safe file name
            $filepath = pathinfo($this->uploadDir . '/' . basename($filename)); // Exact path to the file
            $fileExtension = $filepath['extension']; //File Extension
            $updatedFileName = $filepath['filename'] . "-$date" . '.' . $fileExtension; // Updated Filename
            $updatedFilePath = $filepath['dirname'] . "/" . $updatedFileName; // Updated Filename
            $filesize = $this->files['size'][$i];
            $temp = $this->files['tmp_name'][$i];
            $mimeType = $finfo->file($temp);
            $isvalid = validImage($temp, $filesize, $fileExtension);
            if (!$isvalid) {
                $this->uploads[] = [
                    "filename" => $filename,
                    "success" => false,
                    'base_path' => null,
                    'message' => "Upload a valid image under 20MB. Accepted types: png, jpg, jpeg, webp, gif."
                ];
            } else if ($this->moveFile($temp, $updatedFilePath)) {
                $params = [
                    'user_id' => Auth::id(),
                    'uploaded_to' => null,
                    'file_name' => $filename,
                    'base_path' => $this->uploadspath . "/" . $updatedFileName,
                    'mime_type' => $mimeType,
                    'file_size' => $filesize,
                    'alt_text' => null,
                    'captions' => null
                ];
                if ($this->uploadsModel->addUpload($params)) {
                    $this->uploads[] = [
                        "filename" => $filename,
                        "success" => true,
                        'base_path' => absoluteUrl($this->uploadspath . "/" . $updatedFileName),
                        'message' => "File uploaded successfully."
                    ];
                } else {
                    $this->uploads[] = [
                        "filename" => $filename,
                        "success" => false,
                        'base_path' => null,
                        'message' => "The file upload could not be saved. Please try again."
                    ];
                }
            } else {
                sendResponse(500, "The uploaded file could not be stored.");
            }
        }
    }

    private function moveFile($from, $to)
    {
        return move_uploaded_file($from, $to);
    }
    public function upload()
    {
        $this->validate();
        if (count($this->uploads) > 0) {
            sendResponse(200, "File upload completed.", $this->uploads);
        }
        sendResponse(500, 'No upload result could be produced.');
    }
}
