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
- Allowed methods currently configured at bootstrap level: `POST`, `GET`, `OPTIONS`.
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

## Request and Response Format

### Standard response shape

Most endpoints return JSON in this format:

```json
{
  "status": "success",
  "code": 200,
  "message": "Message text",
  "data": {}
}
```

`data` is included only when the handler sends additional payload.

### Content types used by this API

- `application/json` for auth, post, refresh-token, and upload metadata requests
- `multipart/form-data` for file uploads

## API Endpoints

The following endpoints are currently registered in `routes.php`.

### `GET /`

Simple health/welcome endpoint.

Auth: none

Expected input: none

Example response:

```json
{
  "status": "succeess",
  "code": 200,
  "message": "Welcome to Sameer's Code Lab"
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
  "status": "success",
  "code": 200,
  "message": "Registration Successfull, Please Login with your provided credentials"
}
```

Possible error cases:

- `400`: validation failed
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
  "status": "success",
  "code": 200,
  "message": "Login successful",
  "data": {
    "jwt": "<token>"
  }
}
```

Possible error cases:

- `400`: missing required fields
- `401`: invalid email or password
- `500`: refresh token save or DB error

### `POST /api/refresh-token`

Uses the `refreshToken` cookie to mint a fresh JWT.

Auth: none at route level, but requires cookie-based refresh token flow

Expected input:

- No JSON body required by the controller
- Requires `refreshToken` cookie

Success response:

```json
{
  "status": "success",
  "code": 200,
  "message": "User logged in successfully",
  "data": {
    "token": "<new-jwt>"
  }
}
```

Possible error cases:

- `403`: missing refresh token cookie
- `403`: expired or revoked refresh token
- `403`: user not found for token
- `500`: DB error

Implementation note:

- The current refresh-token validation logic exists, but this flow should be treated as under refinement because the expiration condition in code needs cleanup.

### `GET /api/posts`

Placeholder endpoint for listing posts.

Auth: none

Expected input: none

Current response:

```json
{
  "status": "success",
  "code": 200,
  "message": "These are all the posts"
}
```

Implementation note:

- This route currently does not fetch posts from the database.

### `POST /api/post/create`

Validates a post creation payload.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

Request body expected by validation helper:

```json
{
  "postTitle": "A sufficiently long blog title...",
  "postContent": "Main post content...",
  "postExcerpt": "Short summary...",
  "postFeaturedImage": "/uploads/example.webp",
  "postStatus": "draft"
}
```

Validation currently present in the code:

- `postTitle`: required, length 30 to 200
- `postExcerpt`: required, length 100 to 499
- `postFeaturedImage`: required
- `postStatus`: required
- The helper also checks `postBody`, not `postContent`

Implementation note:

- This route is not complete. The validation helper and controller do not currently line up correctly, and the model method is still empty.

### `POST /api/uploads/add`

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
  "status": "success",
  "code": 200,
  "message": "files Uploaded Successfully",
  "data": [
    {
      "filename": "example.png",
      "success": true,
      "response": "localhost/uploads/example-23-03-2026-10-10-10-123.png"
    }
  ]
}
```

### `POST /api/uploads/test`

Fetches an upload record by ID.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

Request body:

```json
{
  "id": 1
}
```

Validation:

- `id` is required
- `id` must be an integer

Success response when found:

```json
{
  "status": "success",
  "code": 200,
  "message": "Upload Found Successfully",
  "data": {
    "...": "upload row"
  }
}
```

Success response when not found:

```json
{
  "status": "success",
  "code": 200,
  "message": "No Upload found for  the following ID"
}
```

### `POST /api/uploads/delete`

Deletes an upload record owned by the authenticated user.

Auth: `auth` middleware

Required auth:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie

Request body:

```json
{
  "id": 1
}
```

Validation:

- `id` is required
- `id` must be an integer

Implementation note:

- This endpoint is currently incomplete for production use because the controller still calls `dd($this->image)` before deletion logic completes.

## Database Expectations

The code implies the existence of at least these tables:

- `users`
- `refreshtokens`
- `uploads`

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

## Known Gaps in the Current Codebase

These are useful for anyone integrating against the API:

- `GET /api/posts` is a placeholder
- `POST /api/post/create` is incomplete
- `POST /api/uploads/delete` is interrupted by a debug dump
- Refresh token validation logic needs cleanup
- Config and secrets are hardcoded instead of using environment variables
- No schema or migration files are included
- No automated test suite is included

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
