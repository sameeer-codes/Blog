<?php

namespace App\Controllers\Uploads;
use App\Core\Auth;
use App\Models\Uploads\UploadsModal;
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

    public function __construct(UploadsModal $uploadsModel)
    {
        $this->files = $_FILES['files'];
        $this->uploadsModel = $uploadsModel;
    }

    private function validate()
    {
        $this->uploadspath = '/uploads';
        $this->uploadDir = correctPath("/public/uploads");

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
            $filesize = $this->files['size'][0];
            $temp = $this->files['tmp_name'][$i];
            $mimeType = $finfo->file($temp);
            $isvalid = validImage($temp, $filesize, $fileExtension);
            if (!$isvalid) {
                $this->uploads[] = [
                    "filename" => $filename,
                    "success" => false,
                    'response' => "Please upload a valid image and less then 20MB , Image files accepted are png, jpg, jpeg and webp"
                ];
            } else if ($this->moveFile($temp, $updatedFilePath)) {
                $params = [
                    'user_id' => Auth::user(),
                    'uploaded_to' => null,
                    'file_name' => $filename,
                    'base_path' => $this->uploadspath . $updatedFileName,
                    'mime_type' => $mimeType,
                    'file_size' => $filesize,
                    'alt_text' => null,
                    'captions' => null
                ];
                $this->uploads[] = [
                    "filename" => $filename,
                    "success" => true,
                    'response' => $_SERVER['HTTP_HOST'] . "/" . $this->uploadspath . "/" . $updatedFileName
                ];
            } else {
                sendResponse("error", 500, "Unexpected Error Occured, Please try again later , or Contact the Site Admin");
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
            sendResponse("success", 200, "files Uploaded Successfully", $this->uploads);
        }
        sendResponse("error", 500, 'Unexpected Error Occured, Please try again later , or Contact the Site Admin');
    }
}