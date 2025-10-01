<?php

namespace App\Controllers;
class HomeController
{
    protected $data = [
        "status" => "success",
        "message" => "Welcome to the API of sameer's code lab"
    ];
    public function Home()
    {
        echo json_encode($this->data);
        return;
    }
}