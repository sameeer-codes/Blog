# Frontend API Mapping

This document summarizes the current PHP blog backend endpoints, the payloads they expect, and the response shapes they return so the frontend can map screens and actions cleanly.

## Base Response Shape

All API responses use this JSON envelope:

```json
{
  "success": true,
  "code": 200,
  "message": "Human readable message",
  "data": {}
}
```

Notes:
- `success` is `false` for error responses.
- `data` may be omitted for some responses.
- Validation and authorization failures still follow the same envelope.

## Auth Requirements

- Public endpoints do not require authentication.
- Protected endpoints require:
  - `Authorization: Bearer <jwt>`
  - `refreshToken` cookie
- Admin endpoints additionally require:
  - authenticated user status = `approved`
  - authenticated user role = `admin`

## Auth Endpoints

### `POST /api/auth/register`

Request body:

```json
{
  "username": "string",
  "email": "string",
  "password": "string"
}
```

Success response:

```json
{
  "success": true,
  "code": 201,
  "message": "Registration successful. You can now log in with your credentials."
}
```

### `POST /api/auth/login`

Request body:

```json
{
  "email": "string",
  "password": "string"
}
```

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Login successful.",
  "data": {
    "jwt": "access-token"
  }
}
```

Notes:
- Also sets the `refreshToken` cookie on success.

### `POST /api/refresh-token`

Requires:
- `refreshToken` cookie

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Access token refreshed successfully.",
  "data": {
    "token": "new-access-token"
  }
}
```

### `POST /api/auth/logout`

Requires:
- bearer token
- `refreshToken` cookie

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Logout successful."
}
```

## Public Post Endpoints

### `GET /api/posts?page=1&limit=10`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Posts fetched successfully.",
  "data": {
    "items": [
      {
        "post_id": 1,
        "post_title": "string",
        "post_slug": "string",
        "post_content": "string",
        "post_excerpt": "string",
        "post_featured_image": "1",
        "featured_image_path": "/uploads/example.jpg",
        "author_id": 1,
        "post_status": "published",
        "created_at": "datetime",
        "updated_at": "datetime",
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

### `GET /api/posts/single?id=1`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post found.",
  "data": {
    "post_id": 1,
    "post_title": "string",
    "post_slug": "string",
    "post_content": "string",
    "post_excerpt": "string",
    "post_featured_image": "1",
    "featured_image_path": "/uploads/example.jpg",
    "author_id": 1,
    "post_status": "published",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

### `GET /api/posts/slug?slug=my-post`

Success response is the same shape as `GET /api/posts/single`.

### `GET /api/posts/search?query=term&page=1&limit=10`

Success response is the same list + pagination shape as `GET /api/posts`.

## Author Post Endpoints

### `GET /api/posts/me?page=1&limit=10`

Requires:
- authenticated author or admin

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Your posts fetched successfully.",
  "data": {
    "items": [],
    "pagination": {
      "page": 1,
      "limit": 10,
      "total": 0,
      "total_pages": 0,
      "has_next_page": false,
      "has_previous_page": false
    }
  }
}
```

### `GET /api/posts/me/single?id=1`

Requires:
- authenticated author or admin

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Your post was found.",
  "data": {
    "post_id": 1,
    "post_title": "string",
    "post_slug": "string",
    "post_content": "string",
    "post_excerpt": "string",
    "post_featured_image": "1",
    "featured_image_path": "/uploads/example.jpg",
    "author_id": 1,
    "post_status": "draft",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

### `POST /api/posts`

Requires:
- authenticated author or admin

Request body:

```json
{
  "post_title": "string",
  "post_body": "string",
  "post_status": "draft",
  "post_excerpt": "string",
  "featured_image": 1
}
```

Notes:
- `post_excerpt` is optional.
- `featured_image` is optional.
- If `post_excerpt` is omitted or empty, the backend generates one automatically.

Success response:

```json
{
  "success": true,
  "code": 201,
  "message": "Post created successfully.",
  "data": {
    "post_title": "string",
    "post_slug": "string",
    "post_content": "string",
    "post_excerpt": "string",
    "post_featured_image": "https://example.com/uploads/example.jpg",
    "author_id": 1,
    "post_status": "draft"
  }
}
```

### `PATCH /api/posts`

Requires:
- authenticated author or admin

Request body:

```json
{
  "post_id": 1,
  "post_title": "string",
  "post_body": "string",
  "post_excerpt": "string",
  "featured_image": 1,
  "post_status": "draft"
}
```

All fields except `post_id` are optional.

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Post updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No post changes were made."
}
```

### `DELETE /api/posts`

Requires:
- authenticated author or admin

Request body:

```json
{
  "post_id": 1
}
```

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post deleted successfully."
}
```

## Author Upload Endpoints

### `POST /api/uploads`

Requires:
- authenticated author or admin

Content type:
- `multipart/form-data`

Form field:
- `files[]`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "File upload completed.",
  "data": [
    {
      "filename": "image.jpg",
      "success": true,
      "base_path": "https://example.com/uploads/example.jpg",
      "message": "File uploaded successfully."
    }
  ]
}
```

### `GET /api/uploads?page=1&limit=10`

Requires:
- authenticated author or admin

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Uploads fetched successfully.",
  "data": {
    "items": [
      {
        "id": 1,
        "uploaded_to": null,
        "file_name": "image.jpg",
        "base_path": "https://example.com/uploads/example.jpg",
        "mime_type": "image/jpeg",
        "file_size": 12345,
        "alt_text": null,
        "captions": null,
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

### `PATCH /api/uploads`

Requires:
- authenticated author or admin

Request body:

```json
{
  "id": 1,
  "alt_text": "string",
  "captions": "string"
}
```

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No upload changes were made."
}
```

### `DELETE /api/uploads`

Requires:
- authenticated author or admin

Request body:

```json
{
  "id": 1
}
```

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload deleted successfully."
}
```

## Admin User Endpoints

### `GET /api/admin/users?status=all&page=1&limit=20`

Requires:
- authenticated admin

Supported `status` values:
- `all`
- `pending_approval`
- `approved`
- `blocked`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Users fetched successfully.",
  "data": {
    "items": [
      {
        "id": 1,
        "username": "string",
        "email": "string",
        "user_role": "author",
        "status": "approved",
        "created_at": "datetime",
        "updated_at": "datetime",
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

### `PATCH /api/admin/users/status`

Requires:
- authenticated admin

Request body:

```json
{
  "id": 1,
  "status": "approved"
}
```

Supported `status` values:
- `pending_approval`
- `approved`
- `blocked`

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "User status updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

### `PATCH /api/admin/users/role`

Requires:
- authenticated admin

Request body:

```json
{
  "id": 1,
  "user_role": "admin"
}
```

Supported `user_role` values:
- `author`
- `admin`

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "User role updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

### `GET /api/admin/users/single?id=1`

Requires:
- authenticated admin

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "User details fetched successfully.",
  "data": {
    "user": {
      "id": 1,
      "username": "string",
      "email": "string",
      "user_role": "author",
      "status": "approved",
      "created_at": "datetime",
      "updated_at": "datetime"
    },
    "posts": [
      {
        "post_id": 1,
        "post_title": "string",
        "post_slug": "string",
        "post_content": "string",
        "post_excerpt": "string",
        "post_featured_image": "1",
        "featured_image_path": "/uploads/example.jpg",
        "author_id": 1,
        "post_status": "draft",
        "created_at": "datetime",
        "updated_at": "datetime"
      }
    ],
    "uploads": [
      {
        "id": 1,
        "user_id": 1,
        "uploaded_to": null,
        "file_name": "image.jpg",
        "base_path": "/uploads/example.jpg",
        "mime_type": "image/jpeg",
        "file_size": 12345,
        "alt_text": null,
        "captions": null,
        "created_at": "datetime",
        "updated_at": "datetime"
      }
    ],
    "stats": {
      "posts_count": 1,
      "uploads_count": 1
    }
  }
}
```

Notes:
- Password is not exposed in this response.

## Admin Post Endpoints

### `GET /api/admin/posts?status=all&page=1&limit=20`

Requires:
- authenticated admin

Supported `status` values:
- `all`
- `draft`
- `published`
- `archived`

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Posts fetched successfully.",
  "data": {
    "items": [
      {
        "post_id": 1,
        "post_title": "string",
        "post_slug": "string",
        "post_content": "string",
        "post_excerpt": "string",
        "post_featured_image": "1",
        "featured_image_path": "/uploads/example.jpg",
        "author_id": 1,
        "post_status": "draft",
        "created_at": "datetime",
        "updated_at": "datetime",
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

### `PATCH /api/admin/posts/status`

Requires:
- authenticated admin

Request body:

```json
{
  "post_id": 1,
  "post_status": "published"
}
```

Supported `post_status` values:
- `draft`
- `published`
- `archived`

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Post status updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

### `PATCH /api/admin/posts`

Requires:
- authenticated admin

Request body:

```json
{
  "post_id": 1,
  "post_title": "string",
  "post_body": "string",
  "post_excerpt": "string",
  "featured_image": 1,
  "post_status": "draft"
}
```

All fields except `post_id` are optional.

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Post updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No post changes were made."
}
```

### `DELETE /api/admin/posts`

Requires:
- authenticated admin

Request body:

```json
{
  "post_id": 1
}
```

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Post deleted successfully."
}
```

## Admin Upload Endpoints

### `GET /api/admin/uploads?page=1&limit=20`

Requires:
- authenticated admin

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Uploads fetched successfully.",
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": 1,
        "uploaded_to": null,
        "file_name": "image.jpg",
        "base_path": "/uploads/example.jpg",
        "mime_type": "image/jpeg",
        "file_size": 12345,
        "alt_text": null,
        "captions": null,
        "created_at": "datetime",
        "updated_at": "datetime",
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

### `PATCH /api/admin/uploads`

Requires:
- authenticated admin

Request body:

```json
{
  "id": 1,
  "alt_text": "string",
  "captions": "string"
}
```

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload updated successfully."
}
```

or

```json
{
  "success": true,
  "code": 200,
  "message": "No upload changes were made."
}
```

### `DELETE /api/admin/uploads`

Requires:
- authenticated admin

Request body:

```json
{
  "id": 1
}
```

Success response:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload deleted successfully."
}
```

## Common Error Responses

### `400 Bad Request`

Used for:
- missing required query params
- missing required body fields
- update request with no editable fields

Example:

```json
{
  "success": false,
  "code": 400,
  "message": "The post id is required."
}
```

### `401 Unauthorized`

Used for:
- missing auth
- invalid bearer token
- missing refresh token cookie
- expired access token

### `403 Forbidden`

Used for:
- blocked or non-approved account
- non-admin access to admin routes
- non-owner access to protected content
- invalid upload ownership for featured image usage

### `404 Not Found`

Used for:
- user not found
- post not found
- upload not found
- refresh token not found

### `422 Unprocessable Entity`

Used for:
- invalid JSON
- invalid ids
- invalid page or limit
- invalid status or role values
- invalid field lengths

Validation errors may also return field-level details inside `data`.

### `500 Internal Server Error`

Used for:
- file system deletion/storage failures
- database failures
- unexpected backend failures

## Frontend Notes

- Use the response `message` directly for toasts/snackbars where useful.
- Treat list endpoints as paginated by default.
- Author and admin update endpoints often return success without returning the updated record; frontend should refetch if it needs the fresh object.
- `GET /api/admin/users/single` is the current detailed admin user screen endpoint because it bundles the safe user profile, that user's posts, and that user's uploads.
