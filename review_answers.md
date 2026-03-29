# Review Answers

This file answers the questions listed in `review.md`.

## 1. Main Development Strategies Used in This Codebase

### Q1. Why would someone build a lightweight custom backend instead of using Laravel?
To learn the fundamentals directly, keep the stack small, and understand routing, middleware, auth, validation, and SQL without framework abstraction. The tradeoff is that you must design and secure more things yourself.

### Q2. What responsibilities belong in a controller vs a model in this codebase?
Controllers handle request input, validation, authorization checks, and responses. Models handle SQL queries and data persistence. Controllers decide what should happen; models perform the DB work.

### Q3. Why is a shared response helper useful in an API?
It keeps all responses structurally consistent, reduces duplication, and makes frontend handling easier because every endpoint returns the same top-level keys.

## 2. Authentication and Authorization Flow

### Q4. Why use both JWT and refresh token instead of only one token?
A short-lived JWT reduces exposure if the token leaks. A refresh token allows session continuity without forcing login on every access-token expiry.

### Q5. Why store refresh tokens in the database?
So they can be revoked, checked for expiry, tied to a user, and invalidated when account state changes.

### Q6. Why is the refresh token in a cookie while the access token is returned in the response body?
The cookie supports browser session behavior and can be sent automatically. The access token is typically held by the client app and sent in the `Authorization` header for API calls.

### Q7. Why reload the user from the DB on protected requests?
To enforce the current account state, not stale token claims. If the user is deleted, disabled, or role-changed, the next request should reflect that.

### Q8. What security problem does this solve?
It prevents a previously approved or privileged user from continuing to access protected routes until token expiry after their DB state has changed.

### Q9. What are the performance tradeoffs of doing that?
It adds one DB read per authenticated request. That is acceptable for an MVP, but at larger scale you would consider caching or token-version strategies.

### Q10. What is the difference between authentication and authorization?
Authentication answers “who is this user?” Authorization answers “what is this user allowed to do?”

### Q11. Why should role checks happen separately from login?
Because login only proves identity. Permissions depend on the route being accessed and should be enforced where access decisions are made.

### Q12. Why is it useful to split `auth` and `author` middleware?
It keeps authentication reusable and role checks modular. Some routes need any authenticated user; others need only authors or admins.

### Q13. Why is checking only cookie presence not enough for guest routes?
Because a stale, revoked, or invalid token cookie does not mean the user is actually logged in.

### Q14. What problem happens if guest middleware trusts stale cookies blindly?
Users can get blocked from login/register even though their session is no longer valid.

### Q15. Why did logout need a dedicated middleware instead of reusing `auth` directly?
Because `auth` required a non-expired JWT, which prevented server-side logout after access-token expiry. Logout only needs a validly signed bearer token plus the refresh cookie.

### Q16. What bug happens if logout depends on a non-expired access token?
Users cannot revoke their refresh token through the server once the access token expires, even though they still have a valid refresh cookie.

### Q17. How does this design reduce CSRF-style logout compared to cookie-only logout?
It requires both the refresh-token cookie and an `Authorization` bearer token, so a cross-site request without the bearer token cannot trigger logout.

## 3. Posts Feature Review

### Q18. Why should public endpoints only return `published` posts?
Because drafts and archived posts are not public content. Public routes should expose only content intended for readers.

### Q19. Why not let authenticated users silently see drafts through the same public endpoints?
Because it makes endpoint behavior inconsistent and harder to reason about, cache, and document. Separate public and author endpoints are clearer.

### Q20. How is ownership enforced in edit/delete?
The controller loads the post using both `post_id` and the authenticated `author_id`, or checks the loaded record against the current user before update/delete.

### Q21. Why is ownership checked in the controller before update/delete?
So the API can return a clear authorization error and avoid updating or deleting records the user does not own.

### Q22. Why is slug uniqueness important?
Because public slug-based routes rely on one stable record per slug. Duplicate slugs create ambiguous routing.

### Q23. Why should the current post be ignored when checking slug collisions on edit?
Because a post should not conflict with its own existing slug when the title stays the same or resolves to the same slug.

### Q24. Why is DB uniqueness alone not enough for a clean UX?
Because DB uniqueness alone would just fail the insert/update. Application-level resolution gives predictable slugs like `-2`, `-3` instead of an error.

### Q25. Why return the image URL in responses instead of only the upload id?
Because the frontend needs a directly usable asset URL to render images.

### Q26. Why still store the upload reference in the DB instead of the full URL?
Because the upload record is the real resource. Storing the reference is normalized, flexible, and avoids duplicating a derived URL.

### Q27. What happens if the upload record is deleted later?
The post’s featured image reference becomes unresolved. The API should then return `null` or no resolved image URL for that post.

### Q28. Why is `post_excerpt` optional while `post_body` is required?
The body is the real content. The excerpt is metadata and can be derived automatically.

### Q29. Why generate an excerpt automatically?
To reduce required input and still provide preview text for listings or cards.

### Q30. Why append `...` when the generated excerpt is trimmed?
To signal clearly that the excerpt is a shortened preview rather than the complete original text.

## 4. Uploads Feature Review

### Q31. Why is `alt_text` useful?
It improves accessibility, especially for screen readers, and provides semantic description of the image.

### Q32. Why are captions and alt text treated as separate fields?
Because they serve different purposes: alt text describes the image for accessibility; captions are visible content for users.

### Q33. Why should uploads be owned per user?
To prevent one user from editing, deleting, or attaching another user’s media without authorization.

### Q34. Why is checking file extension alone not enough?
Because extensions can be renamed easily and do not prove the file is actually an image.

### Q35. Why use both MIME/image validation and extension validation?
Extension validation filters allowed file types; image/MIME checks validate the actual file content more reliably.

### Q36. Why should upload logic handle PHP upload errors explicitly?
Because malformed or partial uploads can otherwise cause warnings or inconsistent behavior before validation runs.

### Q37. Why delete the file from storage before deleting the DB row?
Because if storage deletion fails, you still retain the DB metadata and can report a clear failure instead of leaving a broken DB state pointing to a missing file.

### Q38. What are the risks if one delete succeeds and the other fails?
You can end up with an orphan file on disk or an orphan DB row. That creates inconsistency and cleanup work.

## 5. Database Layer Review

### Q39. Why use prepared statements?
They prevent SQL injection and separate SQL structure from user-supplied values.

### Q40. Why bind integers differently from strings?
Because some SQL operations, especially `LIMIT` and `OFFSET`, need integer binding. It also preserves correct data typing.

### Q41. Why is this safer than string-concatenated SQL?
Because string concatenation lets untrusted input change the SQL statement. Prepared statements treat values as data, not SQL code.

### Q42. Why separate SQL by model domain?
It keeps data access organized by feature area and makes the code easier to maintain.

### Q43. What are the tradeoffs of using raw SQL instead of an ORM?
Raw SQL gives full control and transparency, but it requires more manual work for validation, relationships, and consistency.

## 6. Router and Middleware Review

### Q44. How does the router distinguish `404` from `405`?
If no path matches, it returns `404`. If a path matches but the HTTP method does not, it returns `405`.

### Q45. Why is shared-path multi-method routing important?
Because REST-style APIs often use the same path with different methods, like `GET /api/posts` and `PATCH /api/posts`.

### Q46. What are the limitations of this router compared to framework routers?
It does not support route parameters, route groups, middleware parameters, advanced pattern matching, or built-in URL generation.

### Q47. Why use named middleware aliases instead of hardcoding class names in every route?
It keeps routes cleaner and centralizes middleware registration.

### Q48. What would you change if middleware needed parameters?
I would extend the router or middleware kernel to support middleware definitions with arguments, such as role names or permission sets.

## 7. Naming, Conventions, and API Contract

### Q49. Why is consistency in request naming important for frontend integration?
Because the frontend should not need special-case mapping per endpoint. Consistency reduces bugs and confusion.

### Q50. Why is it risky when read payloads and write payloads use different conventions?
Because frontend code may accidentally send read-shape data back into write endpoints, causing ignored fields or validation failures.

### Q51. Why is API documentation as important as the controller code itself?
Because the frontend integrates against the documented contract, not by reading controller source. Poor docs create integration bugs.

## 8. Security Questions Relevant to This Codebase

### Q52. Why are refresh tokens revoked in the database instead of only being forgotten client-side?
Because server-side revocation lets the backend reject stolen or outdated refresh tokens even if a client still has them.

### Q53. Why should protected routes re-check user status on every request?
To ensure account changes such as approval removal or deletion take effect immediately.

### Q54. Why should guest routes check current user state instead of only the cookie?
Because the cookie alone does not prove the session is still valid or that the user is still approved.

### Q55. Why is cookie-only logout more vulnerable to CSRF-style triggering?
Because the browser may send cookies automatically on cross-site requests. Requiring an auth header adds an extra signal attackers usually cannot provide.

### Q56. Why should access control be enforced on both posts and uploads?
Because both are owned resources. Without ownership checks, users could manipulate each other’s content and media.

### Q57. Why should featured images be ownership-checked before attaching them to posts?
To prevent one user from attaching another user’s upload to their post without permission.

### Q58. What is the cost of reloading the user from the DB on every protected request?
Extra DB reads and some added latency.

### Q59. How would you optimize that later without losing security?
Use caching, token-version checks, short-lived access tokens, or a centralized auth/session store like Redis.

### Q60. What would change if you moved refresh-token state into Redis?
Revocation and lookup could become faster and more centralized, but you would add infrastructure complexity and operational dependence on Redis.

### Q61. What additional production controls would you add for cookies, CORS, and rate limiting?
Set secure cookies over HTTPS, configure strict allowed origins, and rate-limit login, refresh, and upload endpoints.

## 9. Code Quality and Improvement Questions

### Q62. What parts of this backend show intermediate-level backend skill?
The custom router, middleware separation, DB-backed refresh-token auth, ownership checks, slug resolution, and upload handling all show solid intermediate-level thinking.

### Q63. Which parts show good system thinking rather than only coding?
The separation of public and author-only routes, the re-check of DB user state on protected requests, and the normalized response contract.

### Q64. If you had one more week, what would you improve first?
I would add automated tests, move config to environment variables, and improve deployment/security settings.

### Q65. What would you change before production deployment?
Use secure cookies, environment-based config, better CORS policy, rate limiting, logs/monitoring, migrations, and tests.

### Q66. What parts are MVP-quality but not production-grade yet?
Config handling, lack of migrations, lack of automated tests, and limited router features.

## 10. Direct Knowledge-Test Questions

### Architecture
### Q67. Explain the full request lifecycle from `public/index.php` to a JSON response.
The request enters through `public/index.php`, loads bootstrap, bootstrap loads config/container/routes, the router matches the path and method, runs middleware, constructs the controller with dependencies, controller calls model/helpers, and `sendResponse()` returns JSON and exits.

### Q68. What role does `Container.php` play in this codebase?
It registers shared services like the database, router, and middleware container so they can be reused instead of recreated everywhere manually.

### Q69. Why is dependency injection useful here?
It makes controllers and models easier to test, reuse, and reason about because dependencies are explicit.

### Q70. What is the purpose of `App.php`?
It registers the middleware aliases used by the route table.

### Authentication and authorization
### Q71. Explain the login flow end-to-end.
The login controller validates payload, fetches the user, verifies password, ensures status is approved, creates JWT, creates refresh token, stores the refresh token in DB, sets the refresh cookie, and returns the JWT.

### Q72. Explain the refresh-token flow end-to-end.
The refresh endpoint reads the refresh cookie, loads the token from DB, checks expiry/revocation, loads the user, checks user status, then returns a new access token.

### Q73. Explain the logout flow and why it uses separate middleware.
Logout checks for a signed bearer token plus refresh cookie, clears the cookie, revokes the refresh token in DB, and returns success. Separate middleware is used so logout still works when the access token is expired.

### Q74. Explain how `auth`, `author`, and `guest` middleware differ.
`auth` authenticates the user and loads them from DB. `author` checks role permissions after authentication. `guest` blocks login/register when the user is still effectively logged in.

### Q75. Why is status checked in protected routes instead of only on login?
Because account status can change after login, and protected routes should enforce the current DB state.

### Q76. Why are role checks not mixed into `LoginController`?
Because roles matter per route, not only at login time.

### Posts
### Q77. Explain the difference between public post endpoints and author-only post endpoints.
Public endpoints return only published posts to any reader. Author-only endpoints return the authenticated author’s own posts across all statuses.

### Q78. How does slug generation work?
The title is lowercased, sanitized, and non-alphanumeric groups are converted to hyphens.

### Q79. How is slug uniqueness handled on create vs edit?
On create it checks existing slugs and appends `-2`, `-3`, etc. On edit it does the same but ignores the current post id.

### Q80. Why does the API return `post_featured_image` as a URL in responses?
Because the frontend needs a directly renderable image source.

### Q81. How is ownership enforced when attaching a featured image?
The controller fetches the upload by id and verifies that `user_id` matches the authenticated user.

### Uploads
### Q82. Explain the upload lifecycle from request to saved file and DB row.
The controller validates the uploaded files, normalizes file names, checks extension and image validity, moves the file into storage, stores metadata in the uploads table, and returns the result array.

### Q83. Why are uploads validated using more than one signal?
Because no single signal is enough: extension, MIME/image checks, upload status, and size checks each catch different failure or abuse cases.

### Q84. How does upload deletion work safely?
It validates input, verifies ownership, deletes the physical file, then deletes the DB row and returns a clear response.

### Q85. Why are upload edit fields limited to metadata only?
Because metadata edits are simpler, safer, and do not require replacing or re-processing the actual file content.

### Data and validation
### Q86. Why is `post_excerpt` optional?
Because it can be generated from the post body.

### Q87. Why is `post_body` length-constrained?
To enforce meaningful content length and avoid extremely short or excessively large payloads.

### Q88. Why does the project use `snake_case` for post write payloads now?
To align the API contract more closely with response fields and database-style naming, reducing frontend mapping confusion.

### Q89. Why should request validation happen before model calls?
To stop bad input early, prevent unnecessary DB work, and keep error handling clearer.

### Q90. Why are query parameters used for GET endpoints in this router design?
Because GET requests in this API are designed to read filters and ids from the query string, and the router does not support path parameters.

### Tradeoffs
### Q91. What are the benefits and drawbacks of building this without Laravel?
Benefits: learning, control, and simplicity. Drawbacks: more manual work, fewer built-in safety features, and more maintenance responsibility.

### Q92. What are the benefits and drawbacks of raw PDO + SQL vs an ORM?
Benefits: control, transparency, and predictable queries. Drawbacks: more manual boilerplate and no higher-level abstraction for relationships or schema handling.

### Q93. Where is the code intentionally simple because it is an MVP?
The custom router, simple middleware model, basic docs, and lack of advanced infra concerns reflect MVP simplicity.

### Q94. What would break first under production traffic?
Likely auth-heavy endpoints, uploads, and DB-bound protected-route checks due to lack of caching, rate limiting, and operational hardening.

## 11. Function-Level Review Questions

### Helper functions
### Q95. What does `sendResponse()` do and why is `exit` called inside it?
It sets the HTTP code, builds a JSON response, outputs it, and exits so no extra code runs afterward.

```php
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
```

Use this snippet when explaining centralized API responses and why controller execution should stop immediately after sending the response.

### Q96. Why does `sendResponse()` derive `success` from the HTTP status code instead of receiving it as an argument?
Because success should be a direct reflection of response status. That prevents mismatches like `success: true` with a `500` code.

### Q97. What is the purpose of `absoluteUrl()` and why is it useful for frontend integration?
It converts stored relative paths into full URLs that the frontend can render directly.

### Q98. Why does `correctPath()` normalize slashes before building a full path?
Because file paths can contain mixed separators, especially on Windows. Normalizing them prevents broken paths.

### Q99. What does `validatePost()` validate and why is it centralized?
It validates required post fields, lengths, featured-image id shape, and allowed status values. Centralizing it keeps create validation consistent.

### Q100. Why does `validImage()` check both image metadata and extension?
Because an allowed extension alone is not enough to trust the file as an image.

### Auth-related methods
### Q101. What is the difference between `generate_jwt()` and `decode_jwt()`?
`generate_jwt()` creates a signed token. `decode_jwt()` verifies and decodes a token.

### Q102. Why is `generate_refresh_token()` implemented separately from JWT generation?
Because refresh tokens serve a different purpose and are random opaque values, not signed claim containers.

### Q103. What does `Auth::setUser()` store and why is that useful for downstream middleware/controllers?
It stores the authenticated user state for the current request so later code can reuse it without repeating auth work.

### Q104. Why does `Auth::id()` exist if `Auth::user()` already exists?
Because many checks only need the id. `Auth::id()` is a small convenience method.

### Model methods
### Q105. What does `PostModel::slugExists()` solve?
It checks slug collisions so the app can generate a unique slug before insert/update.

### Q106. Why does `PostModel::updatePost()` build the SQL field list dynamically?
So it updates only the fields that were actually provided.

```php
$fields = [];
$params = ['post_id' => $postId, 'author_id' => $authorId];

if (array_key_exists('post_title', $data)) {
    $fields[] = 'post_title = :post_title';
    $params['post_title'] = $data['post_title'];
}

$sql = 'UPDATE posts SET ' . implode(', ', $fields) . ' WHERE post_id = :post_id AND author_id = :author_id';
```

This snippet is best for explaining `PATCH` semantics and partial updates. It performs better than overwriting every column because it only updates what was actually sent.

### Q107. Why does `UploadsModel::updateUpload()` return `0` when there are no fields to update?
Because there is nothing to send to the DB, and the caller can return a clear “no changes” response.

### Q108. What does `RefreshTokenModel::revokeRefreshTokensByUser()` protect against?
It invalidates all refresh tokens for a user when account state changes or broader session invalidation is needed.

### Q109. Why does `Database::Query()` bind parameter types explicitly?
To preserve correct SQL typing and avoid issues like quoted integer-only clauses.

### Controller methods
### Q110. Why does `CreatePostController` generate excerpts inside the controller instead of the model?
Because excerpt generation is request/business logic, not raw persistence logic.

### Q111. Why does `EditPostController` fetch the post before running the update query?
To confirm the resource exists and belongs to the current user before attempting the update.

### Q112. Why does `DeleteUploadController` delete the file from disk before deleting the DB row?
To avoid losing the DB reference before confirming that storage cleanup succeeded.

### Q113. Why does `LoginController` check account status after verifying the password?
Because status matters only for a valid account, and password verification should happen before deciding whether that valid account may log in.

### Q114. Why is logout protected by dedicated middleware instead of directly reusing `auth`?
Because logout needs slightly different rules: validly signed bearer token plus refresh cookie, but not necessarily a non-expired access token.

## 12. PHP Syntax and Quiz Questions

### General PHP syntax
### Q115. What is the difference between `==` and `===` in PHP, and where should strict comparison be preferred here?
`==` compares after type coercion. `===` compares both value and type. Strict comparison should be preferred for statuses, roles, and values like `null`, `0`, or booleans.

```php
$status = '0';
var_dump($status == 0);  // true
var_dump($status === 0); // false
```

Use strict comparison for auth statuses, role checks, null checks, and request validation to avoid accidental matches.

### Q116. What is the difference between `isset()` and `array_key_exists()`?
`isset()` returns false if the key is missing or its value is `null`. `array_key_exists()` returns true if the key exists even when the value is `null`.

```php
$data = ['featured_image' => null];

var_dump(isset($data['featured_image'])); // false
var_dump(array_key_exists('featured_image', $data)); // true
```

This matters in update endpoints where sending `null` can mean “clear this field.”

### Q117. Why is `trim((string) $value)` used repeatedly before validation?
It normalizes input to a string and removes surrounding whitespace so emptiness and length checks behave predictably.

### Q118. What does `json_decode(..., true)` do differently from `json_decode(...)` without `true`?
With `true`, JSON objects become associative arrays. Without it, they become PHP objects.

### Q119. What is the difference between `null`, an empty string, and `0` in PHP validation logic?
`null` means no value, `''` means an empty string value, and `0` is a real numeric value. Confusing them can break required-field logic.

### Arrays and control flow
### Q120. Why does the code often check `array_key_exists()` before reading request fields?
Because it distinguishes between a missing field and a present field whose value may be `null` or empty.

### Q121. What is the purpose of `switch` in `validatePost()` and when would `if/else` be better?
`switch` cleanly handles validation by field name. `if/else` is better when conditions are more relational or not based on one selector.

### Q122. Why does the code use `foreach` on request arrays instead of hardcoding every field in one block?
It scales better for repeated validation patterns and makes field-wise processing simpler.

### Q123. What does `continue` do in the upload loop?
It skips the rest of the current file-processing iteration and moves to the next file.

### Q124. Why is `return` used inside middleware handlers after successful checks?
To stop execution of the middleware cleanly once authorization/authentication is complete.

### Functions and methods
### Q125. What is the difference between a global helper function and a class method in this codebase?
A global helper is callable anywhere after inclusion; a class method belongs to a class instance or class context and usually serves a domain-specific responsibility.

### Q126. Why are some methods marked `private` and others `public`?
`public` methods form the callable API of a class. `private` methods are internal implementation details.

### Q127. What is the purpose of constructors in the controllers and models?
They set up dependencies and initial state needed by the object.

### Q128. Why is dependency injection done through constructor arguments instead of creating models inside every controller method?
It keeps dependencies explicit, reduces duplication, and makes the code easier to test and maintain.

### OOP and static usage
### Q129. Why are middleware handlers implemented as static methods?
Because they are used as callable handlers without needing per-request object instances.

### Q130. What is the purpose of `Auth::check()`?
It tells later code whether an authenticated user has already been loaded into the request context.

### Q131. What is the difference between instance properties like `$this->postModel` and static properties like `Auth::$user`?
Instance properties belong to one object instance. Static properties belong to the class itself and are shared at class level.

### PDO and SQL syntax
### Q132. Why is `:post_id` used in SQL instead of concatenating variables directly?
Because named placeholders are used for prepared statements, which is safer and cleaner.

```php
$sql = 'SELECT * FROM posts WHERE post_id = :post_id';
$statement = $pdo->prepare($sql);
$statement->execute(['post_id' => 5]);
```

Compared with string concatenation, this keeps untrusted values out of the SQL structure and is the correct approach for API-backed database queries.

### Q133. What is the difference between `fetch()` and `fetchAll()`?
`fetch()` returns one row. `fetchAll()` returns all matching rows.

### Q134. Why does `rowCount()` work differently for `UPDATE` queries than for `SELECT` queries?
For write queries it reflects affected rows. For `SELECT`, support varies by driver and it is not the normal way to count returned records.

### Q135. Why does the database wrapper check `is_int($value)` before binding?
So it can bind integer values with `PDO::PARAM_INT` instead of treating them as strings.

### File and upload syntax
### Q136. What is `$_FILES` and how is it structured for `files[]` uploads?
`$_FILES` is the PHP superglobal for uploaded files. For `files[]`, it contains nested arrays like `name`, `tmp_name`, `size`, `type`, and `error` indexed per file.

### Q137. Why does the upload code call `is_uploaded_file()` before moving the file?
To confirm the temp file was created by PHP’s HTTP upload mechanism and is not an arbitrary server path.

### Q138. What is the purpose of `move_uploaded_file()`?
It safely moves the uploaded temp file into the destination storage path.

### Q139. Why does `pathinfo()` need guarding when the uploaded filename has no extension?
Because `pathinfo()` may not return an `extension` key, and blindly reading it can cause warnings.

### Security syntax questions
### Q140. Why is `password_hash()` used during registration and `password_verify()` during login?
Passwords should never be stored in plain text. `password_hash()` stores a secure hash, and `password_verify()` checks the plain input against that hash.

```php
$hash = password_hash($password, PASSWORD_ARGON2ID);

if (password_verify($inputPassword, $hash)) {
    // authenticated
}
```

This is the correct modern password flow. Do not compare raw passwords and do not invent custom hashing for this use case.

### Q141. What is the purpose of `strip_tags()` in request sanitization?
It removes HTML tags from text input to reduce unsafe or unwanted markup in stored values.

### Q142. Why does the middleware use `preg_match('/Bearer\\s+(.+)/i', ...)` to parse the auth header?
To extract the token from the standard `Authorization: Bearer <token>` format.

### Q143. Why should `in_array(..., true)` use strict mode here?
Because strict mode avoids accidental matches caused by PHP type coercion.

### Q144. What is the difference between checking JWT signature validity and checking custom expiry logic in this project?
Signature validity confirms the token was issued with the right key and was not tampered with. Custom expiry logic checks whether the token is still accepted by the application based on its `expiresAt` claim.

## 13. Builder Reflection Questions

### Q145. Why did you choose to build a custom backend instead of a framework?
A good answer is: to understand the core backend concepts deeply and keep the system small enough to reason about end-to-end.

### Q146. Which bug in this codebase taught you the most?
A good answer would identify one real bug, explain why it happened, and explain what architectural lesson came from fixing it.

### Q147. Which security improvement changed the architecture the most?
The DB-backed re-check of user status/role on protected routes is the strongest answer because it changed how authenticated requests are evaluated.

### Q148. Which part of the API contract was hardest to keep consistent?
A strong answer is the mismatch between read and write payload conventions, especially around featured images and post payload naming.

### Q149. What would you refactor first if the codebase doubled in size?
Likely the router/middleware system, validation layer, and documentation strategy.

### Q150. What assumptions does the current MVP make about the frontend?
It assumes the frontend understands the token/cookie model, uses the documented request shapes, and distinguishes public vs author-only routes correctly.

### Q151. If the app needed admin moderation next, where would you extend the current design?
Add admin-only middleware and admin routes for user approval, role changes, content moderation, and possibly upload moderation.

## 14. Notes on Using This Answers File

- Use this file side by side with `D:\Blog\review.md`.
- Read a question from `review.md`, answer it yourself first, then verify against this file.
- For the PHP syntax section, focus on understanding why each construct is used in this codebase, not just memorizing the wording.
- For builder-reflection questions, use these answers as direction, then replace them with your own experience and reasoning.



