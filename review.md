# Codebase Review and Knowledge Review

## 1. What This Codebase Is

This repository is a custom PHP blog backend API.

It currently supports:
- user registration and login
- JWT + refresh-token based authentication
- approval-based account activation
- author-only post creation, editing, and deletion
- public reading of published posts
- author-only media upload management

This is not a full CMS or a Medium-like platform yet. It is a custom MVP backend for a blog application with author accounts and public readers.

## 2. Core Architecture

### Main structural parts
- `public/index.php`
  - entry point for HTTP requests
- `bootstrap.php`
  - sets headers, loads config, loads the app, then dispatches the router
- `Container.php`
  - registers shared services such as the database, router, and middleware container
- `App.php`
  - registers named middleware aliases
- `routes.php`
  - defines the route table and injects controller dependencies
- `App/Core`
  - contains router, DB wrapper, auth state, helpers, and middleware infrastructure
- `App/Controllers`
  - request handlers grouped by feature
- `App/Models`
  - DB access layer

### Architectural style used
This project uses a lightweight custom MVC-like structure:
- router -> controller -> model
- middleware for auth/guest/role checks
- helper functions for shared concerns like responses and JWT helpers
- dependency injection through a small custom container

## 3. PHP Topics Used in This Codebase

The codebase uses these PHP topics directly:
- variables, arrays, loops, and conditionals
- functions and helper functions
- object-oriented programming
- classes, constructors, properties, and methods
- namespaces and `use` imports
- static methods and static state
- visibility modifiers: `public`, `private`, `protected`
- associative arrays and array access
- `isset()` and `array_key_exists()`
- string handling with `trim()`, `strip_tags()`, `preg_match()`, `preg_replace()`
- JSON handling with `json_decode()` and `json_encode()`
- HTTP request/response handling through `$_SERVER`, `$_GET`, `$_COOKIE`, `$_FILES`
- file uploads with `move_uploaded_file()` and `is_uploaded_file()`
- path handling with `pathinfo()` and custom path normalization
- exception handling with `try/catch`
- PDO database access
- prepared statements and parameter binding
- password hashing with `password_hash()` and `password_verify()`
- JWT usage through external library calls
- cookies with `setcookie()`
- basic date/time usage with `DateTime`
- MIME/file inspection with `finfo` and `getimagesize()`
- middleware and dependency injection patterns built in plain PHP

## 4. Main Development Strategies Used in This Codebase

### 1. Custom lightweight framework approach
Instead of Laravel or Symfony, the project uses custom primitives:
- custom router
- custom DI container
- custom middleware kernel
- custom auth state handling

Possible question:
- Why would someone build a lightweight custom backend instead of using Laravel?

Suggested answer direction:
- for learning core backend concepts
- for smaller control surface
- for understanding routing, middleware, auth, and DB access from first principles
- tradeoff: more responsibility for security, conventions, and maintainability

### 2. Thin controllers with model delegation
Controllers handle:
- input reading
- validation
- authorization checks
- response formatting

Models handle:
- SQL queries
- DB reads/writes

Possible question:
- What responsibilities belong in a controller vs a model in this codebase?

### 3. Shared response contract
Most endpoints return a consistent response shape through `sendResponse()`:
- `success`
- `code`
- `message`
- optional `data`

Possible question:
- Why is a shared response helper useful in an API?

## 5. Authentication and Authorization Flow

### Authentication model
The API uses two pieces for authenticated sessions:
- JWT access token in the response body after login
- `refreshToken` cookie stored by the browser

### Login flow
1. User sends email and password.
2. Backend validates credentials.
3. Backend checks that the user status is `approved`.
4. Backend returns a JWT.
5. Backend stores a refresh token in the DB.
6. Backend sets a `refreshToken` cookie.

Possible questions:
- Why use both JWT and refresh token instead of only one token?
- Why store refresh tokens in the database?
- Why is the refresh token in a cookie while the access token is returned in the response body?

### Protected route flow
For routes behind `auth` middleware:
1. Require bearer token.
2. Require refresh-token cookie.
3. Validate refresh token from the DB.
4. Decode JWT.
5. Ensure the JWT user matches the refresh-token user.
6. Reload the current user from the DB.
7. Ensure the user still exists and has `status = approved`.
8. Store the authenticated user in `Auth`.

Possible questions:
- Why reload the user from the DB on protected requests?
- What security problem does this solve?
- What are the performance tradeoffs of doing that?

### Authorization model
After authentication, `author` middleware enforces role access:
- `user_role` must be `author` or `admin`

This is used for author-only routes like:
- post create/edit/delete
- upload create/list/edit/delete

Possible questions:
- What is the difference between authentication and authorization?
- Why should role checks happen separately from login?
- Why is it useful to split `auth` and `author` middleware?

### Guest middleware
Guest middleware blocks login/register when the user is still effectively logged in.
It checks:
- refresh token exists
- refresh token is valid and not revoked
- current user still exists
- current user is still approved

Possible questions:
- Why is checking only cookie presence not enough for guest routes?
- What problem happens if guest middleware trusts stale cookies blindly?

### Logout flow
Logout now uses a dedicated `logout` middleware.
It requires:
- bearer token to be present and validly signed
- refresh-token cookie to be present

It does not require the JWT to still be unexpired.
That allows server-side logout even when the access token expired.

Possible questions:
- Why did logout need a dedicated middleware instead of reusing `auth` directly?
- What bug happens if logout depends on a non-expired access token?
- How does this design reduce CSRF-style logout compared to cookie-only logout?

## 6. Posts Feature Review

### Public post behavior
Public users can:
- list published posts
- fetch a single published post by id
- fetch a single published post by slug
- search published posts

Possible questions:
- Why should public endpoints only return `published` posts?
- Why not let authenticated users silently see drafts through the same public endpoints?

### Author post behavior
Authenticated authors can:
- fetch their own posts across all statuses
- create posts
- edit their own posts
- delete their own posts

Possible questions:
- How is ownership enforced in edit/delete?
- Why is ownership checked in the controller before update/delete?

### Slug strategy
The code generates `post_slug` from `post_title`.
If the slug already exists, it appends a numeric suffix like:
- `my-post`
- `my-post-2`
- `my-post-3`

Possible questions:
- Why is slug uniqueness important?
- Why should the current post be ignored when checking slug collisions on edit?
- Why is DB uniqueness alone not enough for a clean UX?

### Featured image handling
Posts store `post_featured_image` as the upload reference in the DB.
Read endpoints resolve that into an absolute URL for frontend use.
Create responses also return the image as an absolute URL.

Possible questions:
- Why return the image URL in responses instead of only the upload id?
- Why still store the upload reference in the DB instead of the full URL?
- What happens if the upload record is deleted later?

### Post validation strategy
Create/update validate fields like:
- `post_title`
- `post_body`
- `post_excerpt`
- `featured_image`
- `post_status`

Possible questions:
- Why is `post_excerpt` optional while `post_body` is required?
- Why generate an excerpt automatically?
- Why append `...` when the generated excerpt is trimmed?

## 7. Uploads Feature Review

### Upload behavior
Authors can:
- upload media
- list their uploads
- edit `alt_text` and `captions`
- delete uploads

Upload responses now return absolute URLs via `base_path`.

Possible questions:
- Why is `alt_text` useful?
- Why are captions and alt text treated as separate fields?
- Why should uploads be owned per user?

### Upload validation strategy
The upload flow now checks:
- file presence
- PHP upload error code
- temporary uploaded file existence
- valid extension
- valid image content
- max file size

Possible questions:
- Why is checking file extension alone not enough?
- Why use both MIME/image validation and extension validation?
- Why should upload logic handle PHP upload errors explicitly?

### Delete upload behavior
Deleting an upload:
1. validates request body
2. confirms upload exists
3. confirms the current user owns it
4. deletes the physical file
5. deletes the DB row

Possible questions:
- Why delete the file from storage before deleting the DB row?
- What are the risks if one delete succeeds and the other fails?

## 8. Database Layer Review

### Database wrapper
`App/Core/Database.php` wraps PDO and handles:
- connection creation
- prepared statement execution
- basic type binding

Possible questions:
- Why use prepared statements?
- Why bind integers differently from strings?
- Why is this safer than string-concatenated SQL?

### Model design
Each model focuses on one feature area:
- `UserModel`
- `RefreshTokenModel`
- `PostModel`
- `UploadsModel`

Possible questions:
- Why separate SQL by model domain?
- What are the tradeoffs of using raw SQL instead of an ORM?

## 9. Router and Middleware Review

### Router behavior
The router:
- registers routes by method and path
- supports `GET`, `POST`, `PUT`, `PATCH`, `DELETE`
- supports middleware per route
- supports constructor dependency injection

Possible questions:
- How does the router distinguish `404` from `405`?
- Why is shared-path multi-method routing important?
- What are the limitations of this router compared to framework routers?

### Middleware kernel behavior
The middleware kernel resolves a middleware name and runs its handler.

Possible questions:
- Why use named middleware aliases instead of hardcoding class names in every route?
- What would you change if middleware needed parameters?

## 10. Naming, Conventions, and API Contract Questions

The current API now prefers:
- snake_case for post write payloads
- snake_case for response field names in resource data
- explicit JSON request bodies for write routes
- query string for GET filters and ids

Possible questions:
- Why is consistency in request naming important for frontend integration?
- Why is it risky when read payloads and write payloads use different conventions?
- Why is API documentation as important as the controller code itself?

## 11. Security Questions Relevant to This Codebase

### Strong questions you could be asked
- Why are refresh tokens revoked in the database instead of only being forgotten client-side?
- Why should protected routes re-check user status on every request?
- Why should guest routes check current user state instead of only the cookie?
- Why is cookie-only logout more vulnerable to CSRF-style triggering?
- Why should access control be enforced on both posts and uploads?
- Why should featured images be ownership-checked before attaching them to posts?

### Follow-up security tradeoff questions
- What is the cost of reloading the user from the DB on every protected request?
- How would you optimize that later without losing security?
- What would change if you moved refresh-token state into Redis?
- What additional production controls would you add for cookies, CORS, and rate limiting?

## 12. Code Quality and Improvement Questions

### What is already done well
- custom router and middleware are actually working coherently
- response shape is consistent
- auth is stronger than a simplistic JWT-only setup
- role and status checks are separated cleanly
- public vs author-only post visibility is clearly separated
- upload URLs are normalized for frontend usage
- slug uniqueness is handled pragmatically

Possible questions:
- What parts of this backend show intermediate-level backend skill?
- Which parts show good system thinking rather than only coding?

### What still could be improved
- move config and secrets to environment variables
- add migrations/schema files
- add automated tests
- add rate limiting on auth and upload endpoints
- support path parameters in the router
- add a dedicated single-upload GET endpoint if needed
- improve production cookie settings (`secure`, domain strategy, etc.)
- add OpenAPI/Swagger documentation later

Possible questions:
- If you had one more week, what would you improve first?
- What would you change before production deployment?
- What parts are MVP-quality but not production-grade yet?

## 13. Direct Knowledge-Test Questions

### Architecture
1. Explain the full request lifecycle from `public/index.php` to a JSON response.
2. What role does `Container.php` play in this codebase?
3. Why is dependency injection useful here?
4. What is the purpose of `App.php`?

### Authentication and authorization
5. Explain the login flow end-to-end.
6. Explain the refresh-token flow end-to-end.
7. Explain the logout flow and why it uses separate middleware.
8. Explain how `auth`, `author`, and `guest` middleware differ.
9. Why is status checked in protected routes instead of only on login?
10. Why are role checks not mixed into `LoginController`?

### Posts
11. Explain the difference between public post endpoints and author-only post endpoints.
12. How does slug generation work?
13. How is slug uniqueness handled on create vs edit?
14. Why does the API return `post_featured_image` as a URL in responses?
15. How is ownership enforced when attaching a featured image?

### Uploads
16. Explain the upload lifecycle from request to saved file and DB row.
17. Why are uploads validated using more than one signal?
18. How does upload deletion work safely?
19. Why are upload edit fields limited to metadata only?

### Data and validation
20. Why is `post_excerpt` optional?
21. Why is `post_body` length-constrained?
22. Why does the project use `snake_case` for post write payloads now?
23. Why should request validation happen before model calls?
24. Why are query parameters used for GET endpoints in this router design?

### Tradeoffs
25. What are the benefits and drawbacks of building this without Laravel?
26. What are the benefits and drawbacks of raw PDO + SQL vs an ORM?
27. Where is the code intentionally simple because it is an MVP?
28. What would break first under production traffic?

## 14. Function-Level Review Questions

These questions focus on specific functions and methods used in the codebase.

### Helper functions
1. What does `sendResponse()` do and why is `exit` called inside it?
2. Why does `sendResponse()` derive `success` from the HTTP status code instead of receiving it as an argument?
3. What is the purpose of `absoluteUrl()` and why is it useful for frontend integration?
4. Why does `correctPath()` normalize slashes before building a full path?
5. What does `validatePost()` validate and why is it centralized?
6. Why does `validImage()` check both image metadata and extension?

### Auth-related methods
7. What is the difference between `generate_jwt()` and `decode_jwt()`?
8. Why is `generate_refresh_token()` implemented separately from JWT generation?
9. What does `Auth::setUser()` store and why is that useful for downstream middleware/controllers?
10. Why does `Auth::id()` exist if `Auth::user()` already exists?

### Model methods
11. What does `PostModel::slugExists()` solve?
12. Why does `PostModel::updatePost()` build the SQL field list dynamically?
13. Why does `UploadsModel::updateUpload()` return `0` when there are no fields to update?
14. What does `RefreshTokenModel::revokeRefreshTokensByUser()` protect against?
15. Why does `Database::Query()` bind parameter types explicitly?

### Controller methods
16. Why does `CreatePostController` generate excerpts inside the controller instead of the model?
17. Why does `EditPostController` fetch the post before running the update query?
18. Why does `DeleteUploadController` delete the file from disk before deleting the DB row?
19. Why does `LoginController` check account status after verifying the password?
20. Why is logout protected by dedicated middleware instead of directly reusing `auth`?

## 15. PHP Syntax and Quiz Questions

These are short exam-style or quiz-style questions that can be asked from this codebase.

### General PHP syntax
1. What is the difference between `==` and `===` in PHP, and where should strict comparison be preferred here?
2. What is the difference between `isset()` and `array_key_exists()`?
3. Why is `trim((string) $value)` used repeatedly before validation?
4. What does `json_decode(..., true)` do differently from `json_decode(...)` without `true`?
5. What is the difference between `null`, an empty string, and `0` in PHP validation logic?

### Arrays and control flow
6. Why does the code often check `array_key_exists()` before reading request fields?
7. What is the purpose of `switch` in `validatePost()` and when would `if/else` be better?
8. Why does the code use `foreach` on request arrays instead of hardcoding every field in one block?
9. What does `continue` do in the upload loop?
10. Why is `return` used inside middleware handlers after successful checks?

### Functions and methods
11. What is the difference between a global helper function and a class method in this codebase?
12. Why are some methods marked `private` and others `public`?
13. What is the purpose of constructors in the controllers and models?
14. Why is dependency injection done through constructor arguments instead of creating models inside every controller method?

### OOP and static usage
15. Why are middleware handlers implemented as static methods?
16. What is the purpose of `Auth::check()`?
17. What is the difference between instance properties like `$this->postModel` and static properties like `Auth::$user`?

### PDO and SQL syntax
18. Why is `:post_id` used in SQL instead of concatenating variables directly?
19. What is the difference between `fetch()` and `fetchAll()`?
20. Why does `rowCount()` work differently for `UPDATE` queries than for `SELECT` queries?
21. Why does the database wrapper check `is_int($value)` before binding?

### File and upload syntax
22. What is `$_FILES` and how is it structured for `files[]` uploads?
23. Why does the upload code call `is_uploaded_file()` before moving the file?
24. What is the purpose of `move_uploaded_file()`?
25. Why does `pathinfo()` need guarding when the uploaded filename has no extension?

### Security syntax questions
26. Why is `password_hash()` used during registration and `password_verify()` during login?
27. What is the purpose of `strip_tags()` in request sanitization?
28. Why does the middleware use `preg_match('/Bearer\\s+(.+)/i', ...)` to parse the auth header?
29. Why should `in_array(..., true)` use strict mode here?
30. What is the difference between checking JWT signature validity and checking custom expiry logic in this project?

## 16. Builder Reflection Questions

These are the kinds of questions someone may ask the builder directly.

- Why did you choose to build a custom backend instead of a framework?
- Which bug in this codebase taught you the most?
- Which security improvement changed the architecture the most?
- Which part of the API contract was hardest to keep consistent?
- What would you refactor first if the codebase doubled in size?
- What assumptions does the current MVP make about the frontend?
- If the app needed admin moderation next, where would you extend the current design?

## 17. Short Overall Review

This codebase shows a strong learning-oriented custom backend with working routing, middleware, authentication, role checks, ownership checks, post publishing rules, and upload management.

It demonstrates practical understanding of:
- API routing
- request validation
- JWT and refresh-token auth
- role-based authorization
- resource ownership checks
- file upload handling
- SQL-based persistence
- API response contract design

The main gaps are not conceptual weaknesses in the MVP itself. They are the normal next steps toward production quality:
- better deployment configuration
- automated tests
- schema management
- stronger operational protections
- more formal API documentation

## 18. How To Use This Review File

Use this file in three ways:
- as an interview-prep sheet
- as a self-review checklist for backend knowledge
- as a discussion guide for explaining the design choices in this project
