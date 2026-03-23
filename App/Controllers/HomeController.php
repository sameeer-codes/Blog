<?php

namespace App\Controllers;
class HomeController
{
    public function Home()
    {
        sendResponse(200, "Welcome to Sameer's Code Lab.");
        return;
    }
}
