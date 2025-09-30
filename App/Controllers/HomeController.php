<?php

namespace App\Controllers;

use App\Models\Users\RefreshTokenModel;
class HomeController
{
    protected $data = [
        "success" => true,
        "message" => "Welcome to the API of sameer's code lab"
    ];
    public function Home()
    {
        echo json_encode($this->data);
        return;
    }
}