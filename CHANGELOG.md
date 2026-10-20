# Changelog

## [Unreleased] — 2026-10-15

### Security
- **File upload:** uploads are now restricted to jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv and zip. Each file can be up to 20 MB, with up to 10 files per request. The type is checked by content sniffing and files are stored under random names.
- **nginx:** only `index.php` is executed now, and every other `.php` returns 404. Dotfiles are denied. Responses now include the `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` headers.
- **XSS:** fixed about 60 stored-XSS sinks in the Blade/JS views, using the global helpers `escapeHtml`, `escAttr`, `safeUrl` and `sanitizeHtml` (DOMPurify).
  - Rich-text descriptions are also sanitized on the server by `App\Support\HtmlSanitizer`.
  - Email HTML is now escaped in `AlertService`.
- **Authorization:**
  - Comment threads are restricted to plan participants (fixes an IDOR).
  - All authorization rules now live in a single `TestplanPolicy`.
  - Category-tester lookup is limited to users who can create plans.
- **Login:** limited to 5 attempts per minute per email+IP.
- **Information leaks:** errors are now logged instead of returning `$e->getMessage()` to clients.
- **List endpoints:** `limit` is clamped to 1..100 on test-plan and notification lists.
- **User search:** escapes LIKE wildcards and requires at least 2 characters.
- **Docker:**
  - MySQL, Redis and phpMyAdmin are bound to `127.0.0.1`.
  - `composer:2` is pinned.
  - `chmod 777` is replaced by `chown www-data` plus `ug+rwX`.
- **Documentation:** the README no longer documents default admin credentials, and `ADMIN_PASSWORD` is empty in `.env.example`.

### Fixed
- **Plan creation:** the form now posts the category id instead of the category name (creation was failing).
- **Quill:** now loaded from jsDelivr, since `cdn.quilljs.com` redirects without CORS headers and broke the SRI check.
- **`goLive`:** now adds the creator to `live_tester_ids`, so the plan stays visible in their list.
- **Assignment validation:** `level` must be between 0 and 100 and is never saved as negative. `whatStatus`, `goLive` and `mainstatus` are now validated against fixed values.
- **Alerts and emails:** now sent after the transaction commits. An alert failure no longer turns a saved assignment into a 500.
- **`public/storage`:** the broken symlink is recreated.
- **Search box:** typing no longer resets the active filter.
- **Add Request:** the submit button no longer stays disabled after an error.
- **Notifications:** "mark read" now waits for the request to finish before navigating.
- **`UserFactory`:** no longer references the missing `email_verified_at` column. The stock tests were replaced.

### Performance
- **Eager loading:** the test-plan list, category-tester index and comment list are now eager-loaded, and categories use `withCount`. This removes their N+1 queries.
- **Assignee lookup:** done in one batched query when a plan is created.
- **Search:** debounce reduced from 2000 ms to 500 ms. Notification polling stops when the session expires.

### UI / UX
- **Brand colour:** a single `brand` Tailwind palette (`#009FE3`) replaces the four inconsistent blues.
- **Pages:** each page has its own title. The navbar is responsive, the login page is redesigned, and button and input styles are consistent.
- **Accessibility:**
  - Added a skip link and visible focus rings.
  - Labels now point to their inputs.
  - Icon-only controls and dropdowns are real buttons with ARIA labels, and Escape closes dropdowns.
- **Tables:** scroll horizontally on small screens, and the test-plan table uses a sticky header at 70vh. Filter rows wrap. The notification dropdown fits the viewport.
- **Pagination:** working Prev, numbered and Next buttons, with `aria-current`.
- **Loading states:** loading, empty and error-with-Retry states on the test-plan list, notifications and categories.
- **Error handling:** a global AJAX error handler covers an expired session (redirects to login), 403, 422, network errors and 5xx.
- **Safeguards:** guards against duplicate submits on every mutating action, and a confirmation dialog before removing a tester.
- **Uploads:** the client-side limits and `accept` attributes now match the server's upload rules.
- **CDN assets:** pinned to exact versions, with SRI on jQuery 3.7.1, SweetAlert2 11.14.5, DOMPurify 3.2.4 and Quill 1.2.2. Tailwind Play is pinned to 3.4.16.
- **Cleanup:** removed the debug `console.log` calls, the dead `searchPeople()` function and the unreachable category modal (about 600 lines).

### Database
- **New migration** `2026_10_15_000100_add_indexes_to_assignments_and_testplans`, which adds:
  - a unique index on `assignments(testplan_id, user_id, role_id)`;
  - an index on `testplans(main_status)`.
- It is additive and reversible, and has already been applied to the dev database. There were no existing duplicates.

### Tests
- **133 PHPUnit tests (598 assertions)** covering:
  - auth;
  - admin access;
  - test-plan CRUD and scoping;
  - the assignment workflow;
  - comments;
  - uploads;
  - categories;
  - notifications;
  - user search;
  - email escaping and dedup;
  - the HTML sanitizer.
- **New factories:** Testplan, Assignment, Category, Comment and Notification. `UserFactory` gained `admin()` and `creator()` states.

### Configuration / setup required
- **Test database:** tests use the MySQL database `taskassign_test`. On a new environment, create it once:
  `docker compose exec db mysql -uroot -p -e "CREATE DATABASE taskassign_test; GRANT ALL ON taskassign_test.* TO 'taskassign'@'%';"`
- **Compose changes:** after pulling them, run `docker compose up -d --build`. This is non-destructive and keeps volumes.
- **Optional `DB_ROOT_PASSWORD`:** separates the MySQL root password from the app password. It only applies to a fresh volume.
- **`.env.example`:** `APP_URL` is now `http://localhost:8087`. For production, set `APP_DEBUG=false` and a strong `ADMIN_PASSWORD`.
- **Ports:** phpMyAdmin, MySQL and Redis are now reachable only from localhost.
