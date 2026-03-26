# Debug Log

This file records the major logic and structural issues found during review, the affected files, and the fix applied.

## 1. Shared-path router returned 405 too early

- Issue:
  - Routes that shared the same URL with different HTTP methods could fail before the router reached the correct handler.
- Affected files:
  - `D:\Blog\App\Core\Router.php:52`
  - `D:\Blog\routes.php:61`
  - `D:\Blog\routes.php:97`
  - `D:\Blog\routes.php:103`
  - `D:\Blog\routes.php:109`
  - `D:\Blog\routes.php:127`
  - `D:\Blog\routes.php:133`
  - `D:\Blog\routes.php:139`
  - `D:\Blog\routes.php:145`
- Fix:
  - Updated router dispatch to scan all routes first, track whether the path matched, and only return `405` after no handler with the requested method was found.

## 2. Authors could not edit or delete draft or archived posts

- Issue:
  - Post edit and delete flows were loading posts through the public published-only lookup.
- Affected files:
  - `D:\Blog\App\Controllers\Posts\EditPostController.php:63`
  - `D:\Blog\App\Controllers\Posts\DeletePostController.php:33`
  - `D:\Blog\App\Models\Posts\PostModel.php:55`
- Fix:
  - Switched edit and delete flows to the author-owned post lookup so authenticated authors can modify their own `draft` and `archived` posts.

## 3. Refresh-token validation logic was incorrect

- Issue:
  - Expiration checking used the wrong operator precedence and missing DB token rows could fall through without a response.
- Affected files:
  - `D:\Blog\App\Controllers\Auth\RefreshTokenController.php:21`
- Fix:
  - Added explicit checks for missing cookie, missing DB record, expiration, and revocation before issuing a new JWT.

## 4. Upload file-size validation used the first file size for every file

- Issue:
  - Multi-file uploads validated all files against `size[0]`.
- Affected files:
  - `D:\Blog\App\Controllers\Uploads\AddUploadController.php:45`
- Fix:
  - Changed validation to use the current file index size.

## 5. Upload URLs were inconsistent across endpoints

- Issue:
  - Upload create responses and upload list responses used different URL styles and different keys.
- Affected files:
  - `D:\Blog\App\Controllers\Uploads\AddUploadController.php:67`
  - `D:\Blog\App\Controllers\Uploads\GetUploadsController.php:47`
  - `D:\Blog\App\Core\functions.php:62`
- Fix:
  - Added a shared `absoluteUrl()` helper and updated upload responses to return absolute host-prefixed URLs.
  - Upload create success items now return `base_path` instead of `response` for URL data.

## 6. CORS method list did not match the router

- Issue:
  - Browser preflight requests could fail for supported methods such as `PATCH` and `DELETE`.
- Affected files:
  - `D:\Blog\bootstrap.php:9`
- Fix:
  - Expanded `Access-Control-Allow-Methods` to `GET, POST, PUT, PATCH, DELETE, OPTIONS`.

## 7. Guest middleware trusted any refreshToken cookie

- Issue:
  - Login and register could be blocked by stale or revoked cookies because guest middleware only checked cookie presence.
- Affected files:
  - `D:\Blog\App\Core\Middlewares\GuestMiddleware.php:12`
- Fix:
  - Guest middleware now loads the token from the database and only blocks when the token exists and is still valid.

## 8. Auth payload validation was brittle on malformed or incomplete JSON

- Issue:
  - Login and register controllers could iterate invalid payloads or miss required keys.
- Affected files:
  - `D:\Blog\App\Controllers\Auth\LoginController.php:17`
  - `D:\Blog\App\Controllers\Auth\RegisterController.php:18`
- Fix:
  - Added explicit JSON-object validation and required-field checks before field-level validation.

## 9. Cookie domain was hardcoded to localhost

- Issue:
  - Login and logout cookie handling was environment-specific and could break outside localhost.
- Affected files:
  - `D:\Blog\App\Controllers\Auth\LoginController.php:85`
  - `D:\Blog\App\Controllers\Auth\LogoutController.php:25`
- Fix:
  - Removed the hardcoded `domain` option so cookie behavior follows the active host.

## 10. Auth middleware did not validate refresh-token state

- Issue:
  - Protected routes only checked that a refresh-token cookie existed, not that it still existed in the database or was valid.
- Affected files:
  - `D:\Blog\App\Core\Middlewares\AuthMiddleware.php:12`
- Fix:
  - Auth middleware now loads the refresh token from the database and rejects revoked, expired, or missing tokens.

## 11. Post creation did not guard against malformed JSON

- Issue:
  - Post validation assumed the request body decoded into an associative array.
- Affected files:
  - `D:\Blog\App\Controllers\Posts\CreatePostController.php:19`
  - `D:\Blog\App\Core\functions.php:102`
- Fix:
  - Added a JSON-object guard in `validatePost()` so invalid request bodies return a clean validation error.

## 12. Upload creation did not validate missing file input cleanly

- Issue:
  - Upload creation assumed `$_FILES['files']` existed and was well-formed.
- Affected files:
  - `D:\Blog\App\Controllers\Uploads\AddUploadController.php:21`
- Fix:
  - Added request-shape validation and a `422` response when no files are provided.

## 13. Post slugs could collide for duplicate titles

- Issue:
  - Public slug lookups depended on `post_slug`, but create and edit flows generated slugs directly from the title without resolving collisions.
- Affected files:
  - `D:\Blog\App\Controllers\Posts\CreatePostController.php:39`
  - `D:\Blog\App\Controllers\Posts\EditPostController.php:32`
  - `D:\Blog\App\Models\Posts\PostModel.php:75`
- Fix:
  - Added a slug existence check in the post model.
  - Create flow now appends numeric suffixes such as `-2`, `-3`, and so on until a free slug is found.
  - Edit flow now regenerates a unique slug while ignoring the current post id.

## 14. Post edit and delete did not guard malformed JSON bodies

- Issue:
  - Edit and delete post controllers decoded JSON bodies and immediately accessed keys without verifying that the payload was a valid JSON object.
- Affected files:
  - `D:\Blog\App\Controllers\Posts\EditPostController.php:61`
  - `D:\Blog\App\Controllers\Posts\DeletePostController.php:21`
- Fix:
  - Added JSON-object validation at the start of both controllers and return `422` when the payload is malformed.

## 15. Upload create item shapes were inconsistent

- Issue:
  - Per-file upload results used mixed keys such as `response`, `message`, and `base_path`, making frontend parsing inconsistent.
- Affected files:
  - `D:\Blog\App\Controllers\Uploads\AddUploadController.php:51`
- Fix:
  - Normalized each upload item to always return:
    - `filename`
    - `success`
    - `base_path`
    - `message`

## 16. Upload edit and delete did not guard malformed JSON bodies

- Issue:
  - Upload edit and delete controllers decoded JSON bodies and immediately accessed keys without verifying that the payload was a valid JSON object.
- Affected files:
  - `D:\Blog\App\Controllers\Uploads\DeleteUploadController.php:23`
  - `D:\Blog\App\Controllers\Uploads\EditUploadController.php:26`
- Fix:
  - Added JSON-object validation at the start of both controllers and return `422` when the payload is malformed.

## 17. README public single-post example showed the wrong status

- Issue:
  - The documented example for `GET /api/posts/single` showed a `draft` post even though the endpoint only returns `published` posts.
- Affected files:
  - `D:\Blog\README.md:395`
- Fix:
  - Updated the example response so `post_status` is `published`.

## Current status

- PHP syntax check result: no syntax errors across the project at the time of this log.
- Remaining lower-priority items:
  - naming inconsistencies such as `MiddlewareKernal`, `UploadsModal`, and `vaildateToken`
  - there is still no public or protected single-upload GET endpoint exposed through `routes.php`
