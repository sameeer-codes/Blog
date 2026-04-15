<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Composer Autoload
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Schema\RequiredTables;
use App\Core\Schema\SchemaManager;

if (file_exists(__DIR__ . '/.env')) {
$dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$autoCreateSchema = filter_var($_ENV['AUTO_CREATE_SCHEMA'] ?? false, FILTER_VALIDATE_BOOLEAN);

// CORS headers
$allowedOrigins = array_filter(
    array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS'] ?? ''))
);
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$requestOrigin}");
    header('Vary: Origin');
}

header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Handle preflight request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

const BASE_PATH = __DIR__;
require_once BASE_PATH . '/App/Core/functions.php';
require_once correctPath('/Container.php');

if ($autoCreateSchema) {
    $database = $container->getService('Database');
    $schemaManager = new SchemaManager($database, RequiredTables::definitions());
    $schemaManager->ensureAll();
}

require_once correctPath('/App.php');
require_once correctPath('/routes.php');


//Route to Controller
$url = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
error_log('Application bootstrap completed successfully.');
$router->routeToController($url, $_SERVER['REQUEST_METHOD'] ?? 'GET');
