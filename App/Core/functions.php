<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
function sendResponse($code, $message, $data = null)
{
    http_response_code($code);
    $response['success'] = $code < 400;
    $response['code'] = $code;
    $response['message'] = $message;

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit;
}

function generate_jwt($payload = [], $key = null, $algorithm = 'HS256')
{
    if($key === null) {
        $key = $_ENV['JWT_KEY'] ?? getenv('JWT_KEY');
    }
    $jwtKey = $key;
    $jwtPayload = $payload;
    $jwtAlgorithm = $algorithm;

    $token = JWT::encode($jwtPayload, $jwtKey, $jwtAlgorithm);
    return $token;
}

function decode_jwt($token, $key = null, $algorithm = 'HS256')
{
     if($key === null) {
        $key = $_ENV['JWT_KEY'] ?? getenv('JWT_KEY');
    }
    
    $data = JWT::decode($token, new Key($key, $algorithm));
    return (array) $data;
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

function report_error($message)
{
    error_log(print_r($message));
    exit;
}

function correctPath($givenPath)
{
    return rtrim(BASE_PATH, '/\\')
        . DIRECTORY_SEPARATOR
        . trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $givenPath), DIRECTORY_SEPARATOR);

}

function absoluteUrl($path)
{
    $scheme = 'http';
    if (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = '/' . ltrim((string) $path, '/');

    return $scheme . '://' . $host . $path;
}

function validImage($image, $imageSize, $extension = null)
{
    $acceptedImages = array('png', 'jpg', 'jpeg', 'webp', 'gif');
    $imageMaxSize = 1024 * 1024 * 20;

    $check = getimagesize($image);
    $isValid = in_array(strtolower($extension), $acceptedImages);

    if ($check && $isValid && $imageSize <= $imageMaxSize) {
        return true;
    }

    return false;
}

function human_filesize($bytes, $decimals = 2)
{
    $factor = floor((strlen($bytes) - 1) / 3);
    if ($factor > 0)
        $sz = 'KMGT';
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
}

function validatePost($postData)
{
    $requiredData = ['post_title', 'post_body', 'post_status'];
    $errors = [];
    if (!is_array($postData)) {
        return [
            'payload' => 'A valid JSON object is required'
        ];
    }

    for ($i = 0; $i < count($requiredData); $i++) {
        $field = $requiredData[$i];
        if (!array_key_exists($field, $postData)) {
            $errors[$field] = "$field is required";
        }
    }

    if (count($errors) === 0) {
        foreach ($postData as $key => $value) {
            switch ($key) {
                case 'post_title': {
                    $value = strip_tags($value);
                    if (strlen($value) < 30 || strlen($value) > 200) {
                        $errors[$key] = 'Post Title must be a minimum of 30 characters and maximum 200 characters';
                    }
                    break;
                }

                case 'post_body': {
                    $value = trim($value);
                    if (strlen($value) < 500 || strlen($value) >= 5000) {
                        $errors[$key] = "Post Content must be a minimum of 500 characters and maximum 5000 characters";
                    }
                    break;
                }

                case 'post_excerpt': {
                    $value = trim(strip_tags($value));
                    if (!empty($value) && (strlen($value) < 100 || strlen($value) >= 300)) {
                        $errors[$key] = "Excert can only be 100 characters minimum and 300 characters maximum";
                    }
                    break;
                }

                case 'featured_image': {
                    if ($value !== null && $value !== '' && (!is_int($value) || $value < 1)) {
                        $errors[$key] = "featured_image must be a valid upload id";
                    }
                    break;
                }

                case 'post_status': {
                    $value = trim($value);
                    $validStatuses = ['draft', 'published', 'archived'];
                    if (empty($value)) {
                        $errors[$key] = "post_status is required";
                    } else if (!in_array($value, $validStatuses, true)) {
                        $errors[$key] = "post_status must be one of: draft, published, archived";
                    }
                    break;
                }
            }
        }
    }


    return $errors;
}
