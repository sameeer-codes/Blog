<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
function sendResponse($status, $code, $message, $data = [])
{
    http_response_code($code);
    $response['status'] = $status;
    $response['code'] = $code;
    $response['message'] = $message;

    if (!empty($data)) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit;
}

function generate_jwt($payload = [], $key = JWT_KEY, $algorithm = 'HS256')
{
    $jwtKey = $key;
    $jwtPayload = $payload;
    $jwtAlgorithm = $algorithm;

    $token = JWT::encode($jwtPayload, $jwtKey, $jwtAlgorithm);
    return $token;
}

function decode_jwt($token, $key = JWT_KEY, $algorithm = 'HS256')
{
    $data = JWT::decode($token, new Key($key, $algorithm));
    $data = (array) $data;
    return $data;
}

function generate_refresh_token()
{
    $refreshToken = bin2hex(random_bytes(64));
    return $refreshToken;
}

function dd($var)
{
    echo json_encode($var);
    exit;
}

function correctPath($givenPath)
{
    $path = BASE_PATH . $givenPath;
    if (str_contains($path, '\\')) {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
    } else {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
    return $path;
}
