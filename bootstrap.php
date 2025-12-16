<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Composer Autoload
require_once 'vendor/autoload.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

const BASE_PATH = __DIR__;
require_once BASE_PATH . '/App/Core/functions.php';
require_once correctPath('/config.php');
require_once correctPath('/Container.php');
require_once correctPath('/App.php');
require_once correctPath('/routes.php');

//Route to Controller
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$router->routeToController($url, $_SERVER['REQUEST_METHOD']);