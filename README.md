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
- CORS currently allows `http://localhost:5173`.
- The custom router supports `GET`, `POST`, `PUT`, `PATCH`, and `DELETE`.
- The currently registered routes use `GET`, `POST`, `PATCH`, and `DELETE`.
- The API sets `Content-Type: application/json` globally.
- The login flow sets a `refreshToken` cookie with `HttpOnly`.

## Configuration

Configuration is currently hardcoded in `config.php`:

- `HOST`
- `DB_NAME`
- `USER_NAME`
- `DB_PASSWORD`
- `JWT_KEY`

Current defaults in the codebase:

```php
const HOST = 'localhost';
const DB_NAME = 'blog';
const USER_NAME = 'root';
const DB_PASSWORD = '';
const JWT_KEY = 'testSecretKey';
```

For real deployment, these values should be moved to environment variables.

## Local Setup

1. Install PHP and MySQL.
2. Create a MySQL database named `blog`.
3. Update `config.php` if your local DB credentials differ.
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

## Architecture Notes

The backend is structured around a few custom primitives:

- A service container that lazily creates shared instances
- A router that maps routes to controller class/method pairs
- Route-level dependency injection
- Named middleware (`auth`, `guest`)
- Controller and model separation

## Authentication Model

Authentication is implemented with two pieces:

1. A JWT sent by the backend in the login response body
2. A `refreshToken` cookie stored by the browser

### Protected routes

Routes behind `auth` middleware require:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie present

### Guest routes

Routes behind `guest` middleware are intended for unauthenticated users.

If an already-authenticated user hits a guest-only route, the API returns:

- `409`: `You are already logged in.`

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
- `409`: user is already logged in
- `500`: refresh token save or DB error

### `POST /api/auth/logout`

Logs out the current browser session by revoking the stored refresh token and clearing the `refreshToken` cookie.

Auth: none at route level

How parameters must be passed:

- No JSON body is required
- Requires the `refreshToken` cookie

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
- `404`: user not found for token
- `500`: DB error

Implementation note:

- The current refresh-token validation logic exists, but this flow should be treated as under refinement because the expiration condition in code needs cleanup.

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
        "post_featured_image": "12",
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
    "post_featured_image": "12",
    "author_id": 1,
    "post_status": "draft",
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
    "post_featured_image": "12",
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
        "post_featured_image": "1",
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

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass the post fields in the JSON request body

Request body:

```json
{
  "postTitle": "A sufficiently long blog title...",
  "postBody": "Main post content...",
  "postExcerpt": "Optional summary...",
  "featuredImage": 12,
  "postStatus": "draft"
}
```

Required fields:

- `postTitle`
- `postBody`
- `postStatus`

Optional fields:

- `postExcerpt`
- `featuredImage`

Validation:

- `postTitle`: required, `30` to `200` characters
- `postBody`: required, `500` to `4999` characters
- `postExcerpt`: optional, `100` to `299` characters when provided
- `featuredImage`: optional, must be a valid positive upload id when provided
- `postStatus`: required, must be one of `draft`, `published`, `archived`

Behavior:

- Generates `post_slug` from `postTitle`
- Uses the authenticated user as `author_id`
- If `postExcerpt` is not provided, it is generated from the first part of `postBody`
- Auto-generated excerpts that are trimmed end with `...`
- If `featuredImage` is provided, the upload must exist and belong to the authenticated user
- Stores post data in the `posts` table using the real table columns

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
    "post_featured_image": "12",
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

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `postId` and any fields to change in the JSON request body

Request body:

```json
{
  "postId": 1,
  "postTitle": "Updated Custom PHP Blog Architecture for Better Maintainability",
  "postBody": "Updated post body content...",
  "postExcerpt": "Updated summary...",
  "featuredImage": 1,
  "postStatus": "published"
}
```

Behavior:

- `postId` is required
- Only the authenticated author can update the post
- All editable fields are optional except `postId`
- If `postTitle` changes, `post_slug` is regenerated
- If `postExcerpt` is empty and `postBody` is updated, an excerpt is generated automatically
- `featuredImage` may be set to `null` or empty to remove it

Validation:

- `postId`: required, valid integer greater than `0`
- `postTitle`: optional, `30` to `200` characters
- `postBody`: optional, `500` to `4999` characters
- `postExcerpt`: optional, `100` to `299` characters when provided as text
- `featuredImage`: optional, valid upload id when provided
- `postStatus`: optional, one of `draft`, `published`, `archived`

Possible error cases:

- `400`: missing `postId`
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

How parameters must be passed:

- Send `Content-Type: application/json`
- Pass `postId` in the JSON request body

Request body:

```json
{
  "postId": 1
}
```

Possible error cases:

- `400`: missing `postId`
- `422`: invalid `postId`
- `403`: post does not belong to the authenticated user
- `404`: post not found
- `500`: delete failed

### `POST /api/uploads`

Uploads one or more image files and stores metadata in the `uploads` table.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

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
      "response": "localhost/uploads/example-23-03-2026-10-10-10-123.png"
    }
  ]
}
```

### `GET /api/uploads`

Fetches paginated uploads for the authenticated user.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

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
        "base_path": "/uploads/example.png",
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

## Database Expectations

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
- `userRole`

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
- `post_status`
- `created_at`
- `updated_at`

## Known Gaps in the Current Codebase

These are useful for anyone integrating against the API:

- Refresh token validation logic needs cleanup
- Config and secrets are hardcoded instead of using environment variables
- No schema or migration files are included
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
  "postTitle": "How I Structured My First Custom PHP Blog Backend",
  "postBody": "This is a long dummy post body meant for testing the create post endpoint. To make it pass the current validation, the content needs to be at least five hundred characters long. So this sample keeps going with realistic filler text about building routes, controllers, models, middleware, upload systems, and authentication flows in a custom PHP project. The purpose here is not meaning, but length and structure. Keep adding enough text so the validator accepts it without errors while still looking like something close to a real article body for practical API testing.",
  "postStatus": "draft"
}
```

Valid payload with featured image:

```json
{
  "postTitle": "Building Uploads and Posts Together in a Simple PHP API",
  "postBody": "This sample post body is written to test the relationship between posts and uploaded media in your backend. The featured image should be sent as an upload id, not a URL, and the excerpt can be omitted because the controller now generates it from the opening part of the content. This text is intentionally long so it satisfies the minimum body validation and also gives the excerpt generator enough words to work with when creating a short summary automatically from the first section of the body content for storage in the database during creation.",
  "featuredImage": 1,
  "postStatus": "published"
}
```

### Edit post

```json
{
  "postId": 1,
  "postTitle": "Updated Custom PHP Blog Architecture for Better Maintainability",
  "postBody": "This is an updated post body that is intentionally long enough to pass the current validation rules. It should contain at least five hundred characters so that the API accepts it as valid content. The purpose of this payload is to test the patch endpoint and confirm that title, content, excerpt, featured image, and status updates all work correctly under the authenticated author flow. Keep the text long enough so that body length is not the reason for failure. This also helps validate slug regeneration and the optional excerpt handling used in the current controller logic.",
  "postExcerpt": "This is a manually supplied excerpt that should pass the minimum and maximum limits for the update endpoint during testing.",
  "featuredImage": 1,
  "postStatus": "published"
}
```

### Delete post

```json
{
  "postId": 1
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
