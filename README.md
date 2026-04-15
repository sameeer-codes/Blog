# Blog Backend API

This repository contains a custom PHP backend for a blog application. It exposes a small JSON API for authentication, posts, and media uploads using a lightweight in-project router, middleware layer, and service container.

## Stack

- PHP
- Composer
- MySQL via PDO
- `firebase/php-jwt` for JWT handling

## Project Structure

- `public/index.php`: web entry point
- `bootstrap.php`: bootstraps the application, loads config, registers routes, and dispatches the request
- `routes.php`: defines all HTTP routes and their controller bindings
- `Container.php`: registers core services (`Database`, `Router`, `Middleware`)
- `App.php`: registers named middleware aliases
- `App/Controllers`: request handlers
- `App/Models`: database access layer
- `App/Core`: router, database wrapper, auth state, middleware, and helper functions

## Runtime Behavior

- All responses are JSON and use the shared `sendResponse()` helper.
- CORS is resolved from `ALLOWED_ORIGINS` and matched against the incoming `Origin` header.
- The custom router supports `GET`, `POST`, `PUT`, `PATCH`, and `DELETE`.
- The currently registered routes use `GET`, `POST`, `PATCH`, and `DELETE`.
- The API sets `Content-Type: application/json` globally.
- The login flow sets a `refreshToken` cookie with `HttpOnly`.
- The app logs a successful bootstrap message and a successful database connection message during healthy requests.

## Configuration

The application is now configured through environment variables.

- `ALLOWED_ORIGINS`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_CHARSET`
- `DB_SSL_CA`
- `JWT_KEY`

Example local `.env` values:

```env
ALLOWED_ORIGINS=http://localhost:5173
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=blog
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
JWT_KEY=testSecretKey
```

## Local Setup

1. Install PHP and MySQL.
2. Create a MySQL database named `blog`.
3. Create a `.env` file with your local database credentials and JWT key.
4. Install Composer dependencies:

```bash
composer install
```

5. Serve the `public` directory through Apache, Nginx, or PHP's built-in server.

Example with PHP built-in server:

```bash
php -S localhost:8000 -t public
```

With that setup, the API base URL is typically:

```text
http://localhost:8000
```

## Render + Aiven Deployment Notes

For the current production setup, the backend runs on Render through Docker and connects to Aiven MySQL over SSL.

Recommended environment variables on Render:

```env
ALLOWED_ORIGINS=https://your-frontend-domain.com,http://localhost:5173
DB_HOST=your-aiven-host
DB_PORT=your-aiven-port
DB_NAME=sameer-ali-blog-db
DB_USER=your-aiven-username
DB_PASSWORD=your-aiven-password
DB_CHARSET=utf8mb4
DB_SSL_CA=/etc/secrets/ca.pem
JWT_KEY=your-production-jwt-key
```

Render secret-file setup:

- Upload the Aiven CA certificate as a Render Secret File named `ca.pem`
- Render exposes it at:
  - `/etc/secrets/ca.pem`
- Set:
  - `DB_SSL_CA=/etc/secrets/ca.pem`

Docker note:

- The Docker image adds `www-data` to group `1000` so Apache/PHP can read Render-mounted secret files correctly.

Deployment troubleshooting summary:

- Windows local development hid a case-sensitive PSR-4 path issue that broke Linux/Render deployment
- Aiven MySQL required:
  - host
  - port
  - db name
  - charset
  - SSL CA file
- Render secret files existed at runtime but were not initially readable by Apache/PHP until the Docker permission fix was applied

## Architecture Notes

The backend is structured around a few custom primitives:

- A service container that lazily creates shared instances
- A router that maps routes to controller class/method pairs
- Route-level dependency injection
- Named middleware (`auth`, `guest`)
- Controller and model separation

### Database connection lifecycle

The database wrapper now behaves like a shared request-scoped connection service:

- `Container.php` registers one shared `Database` service for the request lifecycle
- models receive that shared `Database` instance through dependency injection
- `AuthMiddleware` and `GuestMiddleware` now also use the shared container `Database` service instead of constructing a new one directly
- `Database::connect()` is idempotent and returns the existing PDO connection if one has already been created
- `Database::Query()` lazily ensures the connection exists before preparing and executing SQL

In practice, that means the first DB-backed operation in a request opens the PDO connection, and later models/middleware in the same request reuse it instead of reconnecting.

## Authentication Model

Authentication is implemented with two pieces:

1. A JWT sent by the backend in the login response body
2. A `refreshToken` cookie stored by the browser

### Protected routes

Routes behind `auth` middleware require:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie present
- the refresh token must belong to the same user as the JWT
- the current database user record must still exist and have `status = approved`

Routes behind `author` middleware additionally require:

- `user_role` to be either `author` or `admin`

### Guest routes

Routes behind `guest` middleware are intended for unauthenticated users.

If an already-authenticated user hits a guest-only route, the API returns:

- `409`: `You are already logged in.`
- guest middleware only blocks when the refresh token is still valid and the current user record is still `approved`

## Request and Response Format

### Standard response shape

Most endpoints return JSON in this format:

```json
{
  "success": true,
  "code": 200,
  "message": "Message text",
  "data": {}
}
```

`success` is generated from the HTTP status code, and `data` is included only when the handler sends additional payload.

### Content types used by this API

- `application/json` for auth, post, refresh-token, and upload metadata requests
- `multipart/form-data` for file uploads

### How to pass parameters

This API uses three parameter locations. Each endpoint section below states which one it expects.

- Query string: append parameters to the URL, for example `/api/posts?page=1&limit=10`
- JSON body: send `Content-Type: application/json` and pass a JSON object in the request body
- Multipart form data: send `Content-Type: multipart/form-data` and pass files through fields such as `files[]`

Rules used in this codebase:

- `GET` routes read parameters from the query string
- `POST`, `PUT`, `PATCH`, and `DELETE` routes that accept structured data read parameters from the JSON body unless the endpoint is a file upload
- Upload creation uses `multipart/form-data`
- Authenticated routes require the `Authorization: Bearer <jwt>` header and the `refreshToken` cookie unless the route documentation says otherwise
- JSON field naming is `snake_case`

## API Endpoints

The following endpoints are currently registered in `routes.php`.

### `GET /`

Simple health/welcome endpoint.

Auth: none

Expected input: none

Example response:

```json
{
  "success": true,
  "code": 200,
  "message": "Welcome to Sameer's Code Lab."
}
```

### `POST /api/auth/register`

Registers a new user.

Auth: `guest` middleware

Request body:

```json
{
  "username": "sameer_01",
  "email": "sameer@example.com",
  "password": "StrongPassword1!"
}
```

Validation extracted from the controller:

- `username`: required, regex `^[a-zA-Z0-9-._]{3,16}$`
- `email`: required, must be a valid email
- `password`: required, regex `^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[\\W_]).{8,64}$`

Behavior:

- Hashes password using `PASSWORD_ARGON2ID`
- Rejects duplicate email or username
- Stores `user_role` as `author`
- Stores `status` as `pending_approval`

Success response:

```json
{
  "success": true,
  "code": 201,
  "message": "Registration successful. You can now log in with your credentials."
}
```

Possible error cases:

- `422`: validation failed
- `409`: email already exists
- `409`: username already exists
- `500`: DB or server error

### `POST /api/auth/login`

Logs in an existing user.

Auth: `guest` middleware

Request body:

```json
{
  "email": "sameer@example.com",
  "password": "StrongPassword1!"
}
```

Validation extracted from the controller:

- `email`: required, non-empty
- `password`: required, non-empty

Behavior:

- Verifies credentials against stored password hash
- Allows login only when `status = approved`
- Returns a JWT in the response body
- Sets a `refreshToken` cookie
- Stores refresh token metadata in the database

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Login successful.",
  "data": {
    "jwt": "<token>"
  }
}
```

Possible error cases:

- `422`: invalid request payload
- `401`: invalid email or password
- `403`: account pending approval
- `403`: account not active
- `409`: user is already logged in
- `500`: refresh token save or DB error

### `POST /api/auth/logout`

Logs out the current browser session by revoking the stored refresh token and clearing the `refreshToken` cookie.

Auth: `logout` middleware

How parameters must be passed:

- Send `Authorization: Bearer <jwt>`
- Requires the `refreshToken` cookie
- The bearer token may be expired, but it must still be present and validly signed

Behavior:

- Looks up the current `refreshToken` cookie in the database
- Marks the token as revoked
- Clears the `refreshToken` cookie from the browser

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Logout successful."
}
```

Possible error cases:

- `401`: missing refresh token cookie
- `404`: refresh token not found
- `500`: revoke failed

### `POST /api/refresh-token`

Uses the `refreshToken` cookie to mint a fresh JWT.

Auth: none at route level, but requires cookie-based refresh token flow

Expected input:

- No JSON body required by the controller
- Requires `refreshToken` cookie

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Access token refreshed successfully.",
  "data": {
    "token": "<new-jwt>"
  }
}
```

Possible error cases:

- `401`: missing refresh token cookie
- `401`: expired or revoked refresh token
- `404`: refresh token not found
- `404`: user not found for token
- `403`: account pending approval
- `403`: account not active
- `500`: DB error

### `GET /api/posts`

Fetches paginated published posts.

Auth: none

Query parameters:

- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `10`, must be between `1` and `50`

Behavior:

- Orders posts by `post_id DESC`
- Returns only posts with `post_status = published`
- Returns paginated post results
- Adds an `index` field to each returned item based on the current page offset

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Posts fetched successfully.",
  "data": {
    "items": [
      {
        "post_id": 3,
        "post_title": "My third post",
        "post_slug": "my-third-post",
        "post_content": "Post body here...",
        "post_excerpt": "Post excerpt here...",
        "post_featured_image": "http://localhost:8000/uploads/example-3.png",
        "author_id": 1,
        "post_status": "published",
        "created_at": "2026-03-24 10:00:00",
        "updated_at": "2026-03-24 10:00:00",
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 3,
      "total_pages": 1,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `422`: invalid `page`
- `422`: invalid `limit`
- `500`: query or count failed

### `GET /api/posts/single`

Fetches a single published post by id.

Auth: none

Query parameters:

- `id`: required, must be a valid integer greater than `0`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post found.",
  "data": {
    "post_id": 1,
    "post_title": "My first post",
    "post_slug": "my-first-post",
    "post_content": "Post body here...",
    "post_excerpt": "Post excerpt here...",
    "post_featured_image": "http://localhost:8000/uploads/example-1.png",
    "author_id": 1,
    "post_status": "published",
    "created_at": "2026-03-24 10:00:00",
    "updated_at": "2026-03-24 10:00:00"
  }
}
```

Possible error cases:

- `400`: missing post id
- `422`: invalid post id
- `404`: post not found
- `500`: query failed

### `GET /api/posts/slug`

Fetches a single published post by `post_slug`. This is the public endpoint intended to support frontend routes such as `/blog/:slug`.

Auth: none

How parameters must be passed:

- Pass `slug` in the query string
- Example: `/api/posts/slug?slug=my-first-post`

Query parameters:

- `slug`: required, non-empty string

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post found.",
  "data": {
    "post_id": 1,
    "post_title": "My first post",
    "post_slug": "my-first-post",
    "post_content": "Post body here...",
    "post_excerpt": "Post excerpt here...",
    "post_featured_image": "http://localhost:8000/uploads/example-1.png",
    "author_id": 1,
    "post_status": "published",
    "created_at": "2026-03-24 10:00:00",
    "updated_at": "2026-03-24 10:00:00"
  }
}
```

Possible error cases:

- `400`: missing post slug
- `422`: empty or invalid post slug
- `404`: post not found
- `500`: query failed

### `GET /api/posts/search`

Searches published posts by title, content, or excerpt.

Auth: none

Query parameters:

- `query`: required, search text
- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `10`, must be between `1` and `50`

Behavior:

- Searches only posts with `post_status = published`
- Looks in `post_title`, `post_content`, and `post_excerpt`
- Returns paginated search results with `index`

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Posts fetched successfully.",
  "data": {
    "items": [
      {
        "post_id": 1,
        "post_title": "Custom PHP Authentication Guide",
        "post_slug": "custom-php-authentication-guide",
        "post_content": "Post body here...",
        "post_excerpt": "Excerpt here...",
        "post_featured_image": "http://localhost:8000/uploads/example-1.png",
        "author_id": 1,
        "post_status": "published",
        "created_at": "2026-03-25 10:00:00",
        "updated_at": "2026-03-25 10:00:00",
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 1,
      "total_pages": 1,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `400`: missing search query
- `422`: invalid `page`
- `422`: invalid `limit`
- `500`: query or count failed

### `GET /api/posts/me`

Fetches paginated posts for the authenticated author, including `draft`, `published`, and `archived`.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`

Query parameters:

- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `10`, must be between `1` and `50`

Behavior:

- Returns only posts where `author_id` is the authenticated user
- Includes all statuses
- Adds an `index` field to each returned item based on the current page offset

Possible error cases:

- `422`: invalid `page`
- `422`: invalid `limit`
- `500`: query or count failed

### `GET /api/posts/me/single`

Fetches a single post by id for the authenticated author, including drafts and archived posts.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`

Query parameters:

- `id`: required, must be a valid integer greater than `0`

Possible error cases:

- `400`: missing post id
- `422`: invalid post id
- `404`: post not found for the authenticated author
- `500`: query failed

### `POST /api/posts`

Creates a new post.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass the post fields in the JSON request body

Request body:

```json
{
  "post_title": "A sufficiently long blog title...",
  "post_body": "Main post content...",
  "post_excerpt": "Optional summary...",
  "featured_image": 12,
  "post_status": "draft"
}
```

Required fields:

- `post_title`
- `post_body`
- `post_status`

Optional fields:

- `post_excerpt`
- `featured_image`

Validation:

- `post_title`: required, `30` to `200` characters
- `post_body`: required, `500` to `4999` characters
- `post_excerpt`: optional, `100` to `299` characters when provided
- `featured_image`: optional, must be a valid positive upload id when provided
- `post_status`: required, must be one of `draft`, `published`, `archived`

Behavior:

- Generates `post_slug` from `post_title`
- If the generated slug already exists, appends a numeric suffix such as `-2`, `-3`, and so on until a unique slug is found
- Uses the authenticated user as `author_id`
- If `post_excerpt` is not provided, it is generated from the first part of `post_body`
- Auto-generated excerpts that are trimmed end with `...`
- If `featured_image` is provided, the upload must exist and belong to the authenticated user
- Stores post data in the `posts` table using the real table columns
- Returns `post_featured_image` as an absolute URL in the response when a featured image is attached

Success response:

```json
{
  "success": true,
  "code": 201,
  "message": "Post created successfully.",
  "data": {
    "post_title": "A sufficiently long blog title...",
    "post_slug": "a-sufficiently-long-blog-title",
    "post_content": "Main post content...",
    "post_excerpt": "Optional summary...",
    "post_featured_image": "http://localhost:8000/uploads/example-12.png",
    "author_id": 1,
    "post_status": "draft"
  }
}
```

Possible error cases:

- `422`: invalid post payload
- `404`: featured image upload not found
- `403`: featured image upload does not belong to the authenticated user
- `500`: insert failed

### `PATCH /api/posts`

Updates an existing post owned by the authenticated author.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `post_id` and any fields to change in the JSON request body

Request body:

```json
{
  "post_id": 1,
  "post_title": "Updated Custom PHP Blog Architecture for Better Maintainability",
  "post_body": "Updated post body content...",
  "post_excerpt": "Updated summary...",
  "featured_image": 1,
  "post_status": "published"
}
```

Behavior:

- `post_id` is required
- Only the authenticated author can update the post
- All editable fields are optional except `post_id`
- If `post_title` changes, `post_slug` is regenerated and resolved to a unique slug if needed
- If `post_excerpt` is empty and `post_body` is updated, an excerpt is generated automatically
- `featured_image` may be set to `null` or empty to remove it

Validation:

- `post_id`: required, valid integer greater than `0`
- `post_title`: optional, `30` to `200` characters
- `post_body`: optional, `500` to `4999` characters
- `post_excerpt`: optional, `100` to `299` characters when provided as text
- `featured_image`: optional, valid upload id when provided
- `post_status`: optional, one of `draft`, `published`, `archived`

Possible error cases:

- `400`: missing `post_id`
- `400`: no fields were provided to update
- `422`: invalid field values
- `403`: post does not belong to the authenticated user
- `404`: post not found
- `404`: featured image upload not found
- `500`: update failed

### `DELETE /api/posts`

Deletes a post owned by the authenticated author.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `post_id` in the JSON request body

Request body:

```json
{
  "post_id": 1
}
```

Possible error cases:

- `400`: missing `post_id`
- `422`: invalid `post_id`
- `403`: post does not belong to the authenticated user
- `404`: post not found
- `500`: delete failed

### `POST /api/uploads`

Uploads one or more image files and stores metadata in the `uploads` table.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`
- `user_role` must be `author` or `admin`

Request content type:

```text
multipart/form-data
```

Expected form fields:

- `files[]`: one or more uploaded image files

Accepted image extensions from helper code:

- `png`
- `jpg`
- `jpeg`
- `webp`
- `gif`

Current max file size from helper code:

- `20MB`

Behavior:

- Creates `public/uploads` if missing
- Renames files using a timestamp suffix
- Detects MIME type with `finfo`
- Moves files to `public/uploads`
- Stores metadata in the `uploads` table
- Returns a per-file success/failure array

Example success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "File upload completed.",
  "data": [
    {
      "filename": "example.png",
      "success": true,
      "base_path": "http://localhost:8000/uploads/example-23-03-2026-10-10-10-123.png",
      "message": "File uploaded successfully."
    }
  ]
}
```

Example failed item inside the upload response:

```json
{
  "filename": "bad-file.txt",
  "success": false,
  "base_path": null,
  "message": "Upload a valid image under 20MB. Accepted types: png, jpg, jpeg, webp, gif."
}
```

### `GET /api/uploads`

Fetches paginated uploads for the authenticated user.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- `user_role` must be `author` or `admin`
- `user_role` must be `author` or `admin`

Query parameters:

- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `10`, must be between `1` and `50`

Behavior:

- Returns only uploads that belong to the authenticated user
- Orders uploads by `id DESC`
- Excludes `user_id` from the response items
- Adds an `index` field to each returned item based on the current page offset

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Uploads fetched successfully.",
  "data": {
    "items": [
      {
        "id": 12,
        "uploaded_to": null,
        "file_name": "example.png",
        "base_path": "http://localhost:8000/uploads/example.png",
        "mime_type": "image/png",
        "file_size": 102400,
        "alt_text": null,
        "captions": null,
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 24,
      "total_pages": 3,
      "has_next_page": true,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `422`: invalid `page`
- `422`: invalid `limit`
- `500`: query or count failed

### `DELETE /api/uploads`

Deletes an upload record owned by the authenticated user.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` in the JSON request body

Request body:

```json
{
  "id": 1
}
```

Validation:

- `id` is required
- `id` must be an integer

Behavior:

- Fetches the upload record by id
- Verifies that the upload belongs to the authenticated user
- Deletes the physical file from `public/uploads`
- Deletes the matching upload record from the `uploads` table

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload deleted successfully."
}
```

Possible error cases:

- `400`: missing upload id
- `422`: invalid upload id
- `403`: upload does not belong to the authenticated user
- `404`: upload not found
- `404`: uploaded file not found in storage
- `500`: delete failed

### `PATCH /api/uploads`

Updates the `alt_text` and/or `captions` fields of a single upload record owned by the authenticated user.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` and any fields to update in the JSON request body

Request body:

```json
{
  "id": 1,
  "alt_text": "Updated alt text",
  "captions": "Updated caption"
}
```

Behavior:

- `id` is required and must be an integer
- `alt_text` is optional
- `captions` is optional
- Both text fields are sanitized with trimming and tag removal
- If both optional fields are empty or missing, the API returns an error instead of updating
- Only the provided fields are updated in the database

Validation:

- `alt_text`: maximum `200` characters
- `captions`: maximum `200` characters

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No upload changes were made."
}
```

Possible error cases:

- `400`: missing upload id
- `400`: no fields were provided to update
- `422`: invalid upload id
- `422`: `alt_text` exceeds `200` characters
- `422`: `captions` exceeds `200` characters
- `403`: upload does not belong to the authenticated user
- `404`: upload not found
- `500`: update failed

## Admin-only endpoints

Auth: `auth` + `admin` middleware (JWT + refresh cookie; requires `user_role = admin` and `status = approved`).

These admin controllers intentionally follow the same request-handling pattern already used across the rest of the codebase:

- `GET` list endpoints read filters and pagination from `$_GET`
- `PATCH` and `DELETE` admin endpoints decode JSON from `php://input`
- All responses are returned through the shared `sendResponse()` helper

### `GET /api/admin/users`

Fetches paginated users for the admin dashboard.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Pass filters and pagination values in the query string

Query parameters:

- `status`: optional, default `all`, allowed values: `all`, `pending_approval`, `approved`, `blocked`
- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `20`, must be between `1` and `100`

Behavior:

- Returns users across the whole website, not just the current admin
- Applies an optional `status` filter
- Orders users by `id DESC`
- Adds an `index` field to each returned item based on the current page offset
- Returns a standard pagination object in `data.pagination`

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Users fetched successfully.",
  "data": {
    "items": [
      {
        "id": 12,
        "username": "sameer_01",
        "email": "sameer@example.com",
        "user_role": "author",
        "status": "pending_approval",
        "created_at": "2026-04-01 10:00:00",
        "updated_at": "2026-04-01 10:00:00",
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `422`: invalid `status` filter
- `422`: invalid `page`
- `422`: invalid `limit`
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: query or count failed

### `PATCH /api/admin/users/status`

Updates the status of a user account.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` and `status` in the JSON request body

Request body:

```json
{
  "id": 12,
  "status": "approved"
}
```

Validation:

- `id`: required, must be a positive integer
- `status`: required, must be one of `pending_approval`, `approved`, `blocked`

Behavior:

- Looks up the target user by id
- Updates the user's `status`
- If the new status is anything other than `approved`, all refresh tokens for that user are revoked
- Returns a success message even when the payload is valid but no database row changes

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "User status updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Possible error cases:

- `422`: invalid JSON payload
- `422`: invalid user id
- `422`: invalid status
- `404`: user not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: update failed

### `PATCH /api/admin/users/role`

Updates a user's role.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` and `user_role` in the JSON request body

Request body:

```json
{
  "id": 12,
  "user_role": "admin"
}
```

Validation:

- `id`: required, must be a positive integer
- `user_role`: required, must be one of `author`, `admin`

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "User role updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Possible error cases:

- `422`: invalid JSON payload
- `422`: invalid user id
- `422`: invalid user role
- `404`: user not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: update failed

### `GET /api/admin/users/single`

Fetches a safe detailed view of a user without exposing the password, along with that user's posts and uploads.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Pass `id` in the query string

Query parameters:

- `id`: required, must be a positive integer

Behavior:

- Returns safe user fields only
- Does not include the password hash
- Returns all posts created by that user
- Returns all uploads owned by that user
- Returns simple counts for posts and uploads

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "User details fetched successfully.",
  "data": {
    "user": {
      "id": 12,
      "username": "sameer_01",
      "email": "sameer@example.com",
      "user_role": "author",
      "status": "approved",
      "created_at": "2026-04-01 10:00:00",
      "updated_at": "2026-04-01 10:00:00"
    },
    "posts": [],
    "uploads": [],
    "stats": {
      "posts_count": 0,
      "uploads_count": 0
    }
  }
}
```

Possible error cases:

- `422`: invalid user id
- `404`: user not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: query failed

### `GET /api/admin/posts`

Fetches paginated posts across all authors for admin moderation.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Pass filters and pagination values in the query string

Query parameters:

- `status`: optional, default `all`, allowed values: `all`, `draft`, `published`, `archived`
- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `20`, must be between `1` and `100`

Behavior:

- Returns posts across all authors
- Applies an optional `status` filter
- Orders posts by `post_id DESC`
- Adds an `index` field to each returned item based on the current page offset
- Includes the same post fields used by other post listing endpoints
- Returns a standard pagination object in `data.pagination`

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Posts fetched successfully.",
  "data": {
    "items": [
      {
        "post_id": 3,
        "post_title": "Admin review example post",
        "post_slug": "admin-review-example-post",
        "post_content": "Post body here...",
        "post_excerpt": "Post excerpt here...",
        "post_featured_image": "http://localhost:8000/uploads/example-3.png",
        "author_id": 2,
        "post_status": "draft",
        "created_at": "2026-04-01 10:00:00",
        "updated_at": "2026-04-01 10:00:00",
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `422`: invalid `status` filter
- `422`: invalid `page`
- `422`: invalid `limit`
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: query or count failed

### `PATCH /api/admin/posts/status`

Updates the publication status of a post regardless of owner.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `post_id` and `post_status` in the JSON request body

Request body:

```json
{
  "post_id": 3,
  "post_status": "published"
}
```

Validation:

- `post_id`: required, must be a positive integer
- `post_status`: required, must be one of `draft`, `published`, `archived`

Behavior:

- Updates the target post without checking ownership
- Returns a success message even when the payload is valid but no database row changes

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "Post status updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Possible error cases:

- `422`: invalid JSON payload
- `422`: invalid `post_id`
- `422`: invalid `post_status`
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: update failed

### `PATCH /api/admin/posts`

Updates a post as admin, including content, excerpt, featured image, slug-affecting title changes, and status.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `post_id` and any editable fields in the JSON request body

Request body:

```json
{
  "post_id": 3,
  "post_title": "Updated title from admin",
  "post_body": "Updated long post body...",
  "post_excerpt": "Updated excerpt for the post...",
  "featured_image": 9,
  "post_status": "published"
}
```

Behavior:

- `post_id` is required
- all other editable fields are optional
- if `post_title` changes, `post_slug` is regenerated and made unique
- if `post_excerpt` is empty and `post_body` is updated, an excerpt is generated automatically
- `featured_image` may be set to `null` or empty to remove it
- when setting a featured image, the upload must belong to the post author

Validation:

- `post_id`: required, valid integer greater than `0`
- `post_title`: optional, `30` to `200` characters
- `post_body`: optional, `500` to `4999` characters
- `post_excerpt`: optional, `100` to `299` characters when provided as text
- `featured_image`: optional, valid upload id when provided
- `post_status`: optional, one of `draft`, `published`, `archived`

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "Post updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No post changes were made."
}
```

Possible error cases:

- `400`: no fields were provided to update
- `422`: invalid JSON payload
- `422`: invalid `post_id`
- `422`: invalid field values
- `403`: featured image does not belong to the post author
- `404`: post not found
- `404`: featured image upload not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: update failed

### `DELETE /api/admin/posts`

Deletes a post regardless of owner.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `post_id` in the JSON request body

Request body:

```json
{
  "post_id": 3
}
```

Validation:

- `post_id`: required, must be a positive integer

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post deleted successfully."
}
```

Possible error cases:

- `422`: invalid JSON payload
- `422`: invalid `post_id`
- `404`: post not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: delete failed

### `GET /api/admin/uploads`

Fetches paginated uploads across all users for admin moderation.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Pass pagination values in the query string

Query parameters:

- `page`: optional, default `1`, must be greater than `0`
- `limit`: optional, default `20`, must be between `1` and `100`

Behavior:

- Returns uploads across all users
- Orders uploads by `id DESC`
- Adds an `index` field to each returned item based on the current page offset
- Includes `user_id` because this is an admin-wide listing
- Returns a standard pagination object in `data.pagination`

Success response shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Uploads fetched successfully.",
  "data": {
    "items": [
      {
        "id": 5,
        "user_id": 2,
        "uploaded_to": null,
        "file_name": "example.png",
        "base_path": "/uploads/example.png",
        "mime_type": "image/png",
        "file_size": 102400,
        "alt_text": null,
        "captions": null,
        "created_at": "2026-04-01 10:00:00",
        "updated_at": "2026-04-01 10:00:00",
        "index": 1
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

Possible error cases:

- `422`: invalid `page`
- `422`: invalid `limit`
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: query or count failed

### `PATCH /api/admin/uploads`

Updates the `alt_text` and/or `captions` fields of any upload as admin.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` and any fields to update in the JSON request body

Request body:

```json
{
  "id": 5,
  "alt_text": "Updated alt text",
  "captions": "Updated caption"
}
```

Behavior:

- `id` is required and must be an integer
- `alt_text` is optional
- `captions` is optional
- both text fields are sanitized with trimming and tag removal
- if both optional fields are empty or missing, the API returns an error instead of updating
- unlike the author upload edit route, admin does not need to own the upload

Validation:

- `alt_text`: maximum `200` characters
- `captions`: maximum `200` characters

Success response when an update is applied:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload updated successfully."
}
```

Success response when the payload is valid but nothing changes:

```json
{
  "success": true,
  "code": 200,
  "message": "No upload changes were made."
}
```

Possible error cases:

- `400`: no fields were provided to update
- `422`: invalid JSON payload
- `422`: invalid upload id
- `422`: `alt_text` exceeds `200` characters
- `422`: `captions` exceeds `200` characters
- `404`: upload not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: update failed

### `DELETE /api/admin/uploads`

Deletes an upload record and its stored file regardless of owner.

Auth: `auth` + `admin` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user must have `user_role = admin`
- authenticated user must have `status = approved`

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `id` in the JSON request body

Request body:

```json
{
  "id": 5
}
```

Validation:

- `id`: required, must be a positive integer

Behavior:

- Looks up the upload record by id
- Attempts to delete the physical file from `public/uploads` when it exists
- Deletes the upload record from the database after the file step succeeds
- Does not require ownership checks because the endpoint is admin-only

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload deleted successfully."
}
```

Possible error cases:

- `422`: invalid JSON payload
- `422`: invalid upload id
- `404`: upload not found
- `401`: missing or invalid authentication
- `403`: authenticated user is not an approved admin
- `500`: uploaded file could not be deleted from storage
- `500`: delete failed

## Database Expectations

The app now includes a minimal schema bootstrap helper that checks for the required tables during startup and creates them if they do not exist yet. This is not a full migration system, but it gives the project a lightweight ORM-like safety net for local/dev setup.

Schema bootstrap classes:

- `App/Core/Schema/TableDefinition.php`
- `App/Core/Schema/RequiredTables.php`
- `App/Core/Schema/SchemaManager.php`

Bootstrap entrypoint:

- `bootstrap.php`

Environment toggle:

- `AUTO_CREATE_SCHEMA=false`
- set it to `true` only when you want the app to auto-create missing base tables during startup
- keep it `false` during normal runtime to avoid schema lookup overhead on every request

Current managed tables:

- `users`
- `refreshtokens`
- `uploads`
- `posts`

The code implies the existence of at least these tables:

- `users`
- `refreshtokens`
- `uploads`
- `posts`

The exact schema is not included in the repository, but the following fields are referenced:

### `users`

- `id`
- `username`
- `email`
- `password`
- `user_role` (`author`, `admin`)
- `status` (`pending_approval`, `approved`, `blocked`)

### `refreshtokens`

- `refreshtoken`
- `userid`
- `issued_at`
- `expires_at`
- `is_revoked`

### `uploads`

- `id`
- `user_id`
- `uploaded_to`
- `file_name`
- `base_path`
- `mime_type`
- `file_size`
- `alt_text`
- `captions`

### `posts`

- `post_id`
- `post_title`
- `post_slug`
- `post_content`
- `post_excerpt`
- `post_featured_image`
- `author_id`
- `post_status` (`draft`, `published`, `archived`)
- `created_at`
- `updated_at`

## Known Gaps in the Current Codebase

These are useful for anyone integrating against the API:

- Config and secrets are hardcoded instead of using environment variables
- No real migration/versioning system is included yet; the current schema helper only creates missing base tables
- No automated test suite is included

## Testing Guide

Use these examples to quickly validate the API.

### Login

```json
{
  "email": "sameer@example.com",
  "password": "StrongPassword1!"
}
```

### Create post

Minimal valid payload:

```json
{
  "post_title": "How I Structured My First Custom PHP Blog Backend",
  "post_body": "This is a long dummy post body meant for testing the create post endpoint. To make it pass the current validation, the content needs to be at least five hundred characters long. So this sample keeps going with realistic filler text about building routes, controllers, models, middleware, upload systems, and authentication flows in a custom PHP project. The purpose here is not meaning, but length and structure. Keep adding enough text so the validator accepts it without errors while still looking like something close to a real article body for practical API testing.",
  "post_status": "draft"
}
```

Valid payload with featured image:

```json
{
  "post_title": "Building Uploads and Posts Together in a Simple PHP API",
  "post_body": "This sample post body is written to test the relationship between posts and uploaded media in your backend. The featured image should be sent as an upload id, not a URL, and the excerpt can be omitted because the controller now generates it from the opening part of the content. This text is intentionally long so it satisfies the minimum body validation and also gives the excerpt generator enough words to work with when creating a short summary automatically from the first section of the body content for storage in the database during creation.",
  "featured_image": 1,
  "post_status": "published"
}
```

### Edit post

```json
{
  "post_id": 1,
  "post_title": "Updated Custom PHP Blog Architecture for Better Maintainability",
  "post_body": "This is an updated post body that is intentionally long enough to pass the current validation rules. It should contain at least five hundred characters so that the API accepts it as valid content. The purpose of this payload is to test the patch endpoint and confirm that title, content, excerpt, featured image, and status updates all work correctly under the authenticated author flow. Keep the text long enough so that body length is not the reason for failure. This also helps validate slug regeneration and the optional excerpt handling used in the current controller logic.",
  "post_excerpt": "This is a manually supplied excerpt that should pass the minimum and maximum limits for the update endpoint during testing.",
  "featured_image": 1,
  "post_status": "published"
}
```

### Delete post

```json
{
  "post_id": 1
}
```

### Public list and search

```text
GET /api/posts?page=1&limit=10
GET /api/posts/single?id=1
GET /api/posts/slug?slug=my-first-post
GET /api/posts/search?query=php&page=1&limit=10
```

### Authenticated author-only reads

```text
GET /api/posts/me?page=1&limit=10
GET /api/posts/me/single?id=1
```

## What a Backend README Should Usually Contain

For a backend codebase, a good `README.md` usually includes:

- Project purpose and scope
- Stack and dependencies
- Local setup instructions
- Environment/config variables
- How to run the server
- Database requirements and migrations
- API authentication rules
- Request/response conventions
- Endpoint documentation
- Error handling conventions
- Deployment notes
- Testing instructions
- Known limitations

## How Backend API Documentation Is Shared With Frontend Developers

There are several common ways to share backend API documentation with frontend consumers:

- A repository `README.md` for quick-start and high-level endpoint notes
- A dedicated API reference in Markdown inside the repo
- OpenAPI or Swagger documentation for machine-readable, versioned API contracts
- Postman or Insomnia collections for easy request testing
- Example request/response payloads and auth flow notes for frontend integration

For this project, the README can serve as the current human-readable API contract. If the frontend grows, the next step should be to add an OpenAPI spec or a Postman collection so the contract is easier to keep in sync.
