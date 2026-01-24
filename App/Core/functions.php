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

function validImage($image, $imageSize, $extension = null)
{
    $acceptedImages = array('png', 'jpg', 'jpeg ', 'webp', 'gif');
    $imageMaxSize = 1024 * 1024 * 5;

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
    $requiredData = ['postTitle', 'postContent', 'postExcerpt', 'postFeaturedImage', 'postStatus'];
    $errors = [];
    for ($i = 0; $i < count($requiredData); $i++) {
        $field = $requiredData[$i];
        if (!array_key_exists($field, $postData)) {
            $errors[$field] = "$field is required";
        }
    }

    if (count($errors) === 0) {
        foreach ($postData as $key => $value) {
            switch ($key) {
                case 'postTitle': {
                    $value = strip_tags($value);
                    if (strlen($value) < 30 || strlen($value) > 200) {
                        $errors[$key] = 'Post Title must be a minimum of 30 characters and maximum 200 characters';
                    }
                    break;
                }

                case 'postBody': {
                    $value = strip_tags($value);
                    if (strlen($value) < 500 || strlen($value) >= 5000) {
                        $errors[$key] = "Post Content must be a minimum of 500 characters and maximum 5000 characters";
                    }
                    break;
                }

                case 'postExcerpt': {
                    $value = strip_tags($value);
                    if (strlen($value) < 100 || strlen($value) >= 500) {
                        $errors[$key] = "Post Excerpt must be a minimum of 300 characters and maximum of 5000 characters";
                    }
                    break;
                }
            }
        }
    }


    return $errors;
}