# Admin API Frontend Guide

This guide is for the frontend/admin dashboard consuming the current admin API in this PHP backend.

## Auth Requirements

All admin routes require:

- `Authorization: Bearer <jwt>`
- `refreshToken` cookie
- authenticated user with `user_role = admin`
- authenticated user with `status = approved`

Important note:

- The backend requires both the bearer token and the `refreshToken` cookie for protected routes.
- If the frontend loses the cookie or uses a JWT from a different session, admin requests will fail with `401`.

## Response Format

All responses use the shared JSON envelope:

```json
{
  "success": true,
  "code": 200,
  "message": "Message text",
  "data": {}
}
```

Notes:

- `data` is present when the route returns payload data.
- Validation and handled failures still return the same envelope shape.

## Admin Users

### `GET /api/admin/users`

Purpose:

- Fetch paginated users for the admin dashboard.

Query params:

- `status`: optional, default `all`
- allowed values: `all`, `pending_approval`, `approved`, `blocked`
- `page`: optional, default `1`
- `limit`: optional, default `20`, max `100`

Example:

```text
/api/admin/users?status=all&page=1&limit=20
```

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

Frontend notes:

- Use `data.items` for the table/list.
- Use `data.pagination` for page controls.
- `index` is already computed by the backend for table row numbering.
- The list response does not include passwords.

Common handled errors:

- `401`: missing/invalid auth
- `403`: logged-in user is not an approved admin
- `422`: invalid query params

### `GET /api/admin/users/single`

Purpose:

- Fetch a safe detailed view of one user for the admin dashboard.
- Returns the user record plus the user's posts and uploads.

Query params:

- `id`: required, positive integer

Example:

```text
/api/admin/users/single?id=12
```

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

Frontend notes:

- The password is not exposed in this endpoint.
- Use `data.user` for profile info.
- Use `data.posts` and `data.uploads` for per-user moderation views.
- Use `data.stats` for summary cards in the admin UI.

Common handled errors:

- `404`: user not found
- `422`: invalid `id`

### `PATCH /api/admin/users/status`

Purpose:

- Approve, block, or move a user back to pending approval.

Request body:

```json
{
  "id": 12,
  "status": "approved"
}
```

Validation:

- `id` must be a positive integer
- `status` must be one of:
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

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Frontend notes:

- When a user is moved to anything other than `approved`, the backend revokes that user's refresh tokens.
- Good UI labels:
  - `pending_approval` -> Pending
  - `approved` -> Approved
  - `blocked` -> Blocked

Common handled errors:

- `404`: user not found
- `422`: invalid payload

### `PATCH /api/admin/users/role`

Purpose:

- Change a user's role.

Request body:

```json
{
  "id": 12,
  "user_role": "admin"
}
```

Validation:

- `id` must be a positive integer
- `user_role` must be one of:
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

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Frontend notes:

- This endpoint is separate from user approval/blocking.
- Keep role controls separate from status controls in the UI to avoid accidental privilege changes.

Common handled errors:

- `404`: user not found
- `422`: invalid payload or invalid `user_role`

## Admin Posts

### `GET /api/admin/posts`

Purpose:

- Fetch paginated posts across all authors for moderation.

Query params:

- `status`: optional, default `all`
- allowed values: `all`, `draft`, `published`, `archived`
- `page`: optional, default `1`
- `limit`: optional, default `20`, max `100`

Example:

```text
/api/admin/posts?status=all&page=1&limit=20
```

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

Frontend notes:

- This is an admin-wide listing, not scoped to the current admin.
- `post_featured_image` is already returned as a full absolute URL when available.
- `author_id` is included, but author profile details are not joined in this response.

### `PATCH /api/admin/posts/status`

Purpose:

- Change the moderation/publication status of any post.

Request body:

```json
{
  "post_id": 3,
  "post_status": "published"
}
```

Validation:

- `post_id` must be a positive integer
- `post_status` must be one of:
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

```json
{
  "success": true,
  "code": 200,
  "message": "No changes were made."
}
```

Frontend notes:

- This route does not check post ownership because it is admin-only.
- Useful UI actions:
  - publish post
  - move back to draft
  - archive post

### `PATCH /api/admin/posts`

Purpose:

- Edit a post as admin with the same general capabilities the author has from the frontend.

Request body:

```json
{
  "post_id": 3,
  "post_title": "Updated title from admin",
  "post_body": "Long updated body...",
  "post_excerpt": "Updated excerpt...",
  "featured_image": 9,
  "post_status": "published"
}
```

Fields:

- `post_id`: required
- all other fields are optional

Validation:

- `post_id` must be a positive integer
- `post_title`: `30` to `200` characters when provided
- `post_body`: `500` to `4999` characters when provided
- `post_excerpt`: `100` to `299` characters when provided as text
- `featured_image`: valid positive upload id when provided
- `post_status`: one of `draft`, `published`, `archived`

Behavior:

- If `post_title` changes, the backend regenerates a unique slug.
- If `post_excerpt` is sent as an empty string and `post_body` is updated, the backend auto-generates an excerpt.
- If `featured_image` is `null` or an empty string, the featured image is removed.
- If `featured_image` is set, that upload must belong to the post author, not just any user.

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Post updated successfully."
}
```

```json
{
  "success": true,
  "code": 200,
  "message": "No post changes were made."
}
```

Common handled errors:

- `400`: no fields were provided to update
- `403`: selected featured image does not belong to the post author
- `404`: post not found
- `404`: featured image upload not found
- `422`: invalid payload or field values

### `DELETE /api/admin/posts`

Purpose:

- Permanently delete any post.

Request body:

```json
{
  "post_id": 3
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

Common handled errors:

- `404`: post not found
- `422`: invalid `post_id`

Frontend notes:

- Treat this as a destructive action and show a confirmation modal before calling it.

## Admin Uploads

### `GET /api/admin/uploads`

Purpose:

- Fetch paginated uploads across all users.

Query params:

- `page`: optional, default `1`
- `limit`: optional, default `20`, max `100`

Example:

```text
/api/admin/uploads?page=1&limit=20
```

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

Frontend notes:

- `base_path` is stored as a relative uploads path in this admin response.
- To preview the image in the frontend, prefix it with your backend origin if needed.
- `user_id` is included because this is an admin-wide listing.

### `PATCH /api/admin/uploads`

Purpose:

- Edit upload metadata as admin.

Request body:

```json
{
  "id": 5,
  "alt_text": "Updated alt text",
  "captions": "Updated caption"
}
```

Validation:

- `id` must be a positive integer
- `alt_text`: max `200` characters
- `captions`: max `200` characters

Behavior:

- `id` is required
- `alt_text` is optional
- `captions` is optional
- both values are trimmed and sanitized
- if no updatable field is provided, the backend returns an error

Success responses:

```json
{
  "success": true,
  "code": 200,
  "message": "Upload updated successfully."
}
```

```json
{
  "success": true,
  "code": 200,
  "message": "No upload changes were made."
}
```

Common handled errors:

- `400`: no fields were provided to update
- `404`: upload not found
- `422`: invalid upload id or field lengths

### `DELETE /api/admin/uploads`

Purpose:

- Delete an upload record and the physical file from storage.

Request body:

```json
{
  "id": 5
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

Common handled errors:

- `404`: upload not found
- `422`: invalid upload id
- `500`: storage delete failed

Frontend notes:

- This is a destructive action.
- The backend first checks the upload record, then removes the physical file, then deletes the DB row.
- If file deletion fails, the API returns `500` and the record is not treated as fully deleted.

## Recommended Frontend Flow

Suggested admin dashboard flow:

1. Log in as admin through the existing auth flow.
2. Keep requests credentialed so the `refreshToken` cookie is sent.
3. Use:
   - `GET /api/admin/users` for user moderation
   - `GET /api/admin/users/single` for user detail drill-down
   - `GET /api/admin/posts` for post moderation
   - `GET /api/admin/uploads` for media moderation
4. Use:
   - `PATCH /api/admin/users/status` for approval/blocking
   - `PATCH /api/admin/users/role` for role management
   - `PATCH /api/admin/posts` for content editing
   - `PATCH /api/admin/posts/status` for moderation status changes
   - `PATCH /api/admin/uploads` for media metadata editing
5. Use optimistic UI carefully only for simple status/role changes.
5. For delete actions, prefer refetching the relevant list after success.

## Quick Status Mapping

Recommended UI badge labels:

- User statuses:
  - `pending_approval` -> Pending Approval
  - `approved` -> Approved
  - `blocked` -> Blocked
- Post statuses:
  - `draft` -> Draft
  - `published` -> Published
  - `archived` -> Archived
