<?php

namespace App\Controllers\Uploads;
use DateTime;

class AddUploadController
{
    private $input;
    private $files;
    private $uploadsModel;
    private $uploadDir;
    private $urls;
    private $errors = [];

    public function __construct()
    {
        $this->input = json_decode(file_get_contents('php://input'), true);
        $this->files = $_FILES['files'];
    }

    private function validate()
    {
        $this->uploadDir = correctPath('/public/uploads');

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $date = new DateTime();
        $date = $date->format("d-m-Y-H-i-s-v");

        for ($i = 0; $i < count($this->files['name']); $i++) {
            $filename = strtolower($this->files['name'][$i]);
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            $filepath = $this->uploadDir . '/' . basename($filename);
            $pathinfo = pathinfo($filepath);

            $filename = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . "-$date" . '.' . $pathinfo['extension'];
            $filesize = $this->files['size'][0];
            $temp = $this->files['tmp_name'][$i];

            $check = getimagesize($temp);

            if (!$check) {
                sendResponse("error", 415, "The Uploaded Media Files are not supported");
            }

            if ($this->moveFile($temp, $filename)) {
                $this->urls[] = $_SERVER['HTTP_HOST'] . '/' . $filename;
            } else {
                sendResponse("error", 500, "Unexpected Error Occured, Please try again later , or Contact the Site Admin 1");
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
        if (count($this->urls) > 0) {
            sendResponse("success", 200, "files Uploaded Successfully", $this->urls);
        }
        sendResponse("error", 500, 'Unexpected Error Occured, Please try again later , or Contact the Site Admin 2');
    }
}