# AGENTS.md - SmartClaim System Instructions & Guardrails

## 1. Project Context & High-Level Architecture
- **System**: SmartClaim (An Automated Expense Claim Management System).
- **Core Stack**: Laravel 10, PHP 8.1, Blade, Tailwind CSS, Alpine.js.
- **Database**: TiDB Cloud (Distributed MySQL 8.0-compatible). Strict ACID compliance required.
- **External Services**: 
  - Google Cloud Vision API (OCR for receipts text and total extraction).
  - Google Maps Platform:
    - Places API (Place Autocomplete & Details for location search).
    - Distance Matrix API (Point-to-point driving distance calculation for mileage allowance).
    - Maps JavaScript API (Interactive map rendering & route visualization on Blade views).
    - Geocoding API (Address to coordinate conversion & validation, if applicable).
- **Hosting Environment**: Render (Linux/Nginx, stateless container).

---

## 2. Hard Boundaries & Non-Negotiable Rules (Specific)
When generating, modifying, or refactoring code for this project, you MUST strictly adhere to the following:

1. **Authentication & Session Lifecycle**:
   - Never bypass Laravel's built-in session regeneration (`request()->session()->regenerate()`) upon login or elevation.
   - On logout, sessions must be invalidated (`$request->session()->invalidate()`) and tokens regenerated (`$request->session()->regenerateToken()`).
   - All session mismatch errors (HTTP 419) must be intercepted by `app/Exceptions/Handler.php` and cleanly redirected with flash feedback. Do NOT create custom standalone session tables.

2. **Database Transactions & Concurrency Safety**:
   - Financial disbursements and batch operations MUST be wrapped in explicit atomic transactions (`DB::transaction(function () { ... })`).
   - Bulk processing of claims (e.g., in the Finance portal) MUST use pessimistic row-level locking (`->lockForUpdate()`) to eliminate double-disbursement race conditions.
   - Never write raw unparameterized SQL queries. Always use Eloquent ORM or parameterized Query Builder to prevent SQL injection.

3. **Fraud Prevention & File Handling**:
   - Every uploaded receipt file must have its SHA-256 binary hash calculated (`hash_file('sha256', ...)`) and compared against historical database entries before persisting to storage.
   - Validate file MIME types and size boundaries explicitly via Form Requests before processing with Google Cloud Vision OCR.
   - Do not store binary blobs in the relational database. Save receipts to secure disk storage and persist the relative URI and cryptographic hash in the database.

4. **Front-End Architecture**:
   - Stick to Tailwind CSS and Alpine.js. 
   - Do NOT introduce heavy single-page application (SPA) frameworks like React, Vue, or Angular into standard Blade views unless explicitly instructed.
   - Keep JavaScript vanilla or embedded via Alpine directives (`x-data`, `x-init`, `x-on`).

5. **Code Comments & Documentation**:
   - All generated code comments, PHPDocs, and commit summaries MUST be written in English only.

---

## 3. Minimal Blast Radius Rule (Minimal)
- Only edit the files explicitly requested or strictly required to solve the target issue.
- Do NOT refactor surrounding functions, change naming conventions of existing tables/columns, or reformat entire files that you are not directly asked to fix.
- Preserve existing route configurations in `routes/web.php` and `routes/api.php`. Never overwrite entire route files.

---

## 4. Verification & Evidence Requirements (Observable)
Passing tests are considered evidence, not an absolute guarantee. Whenever you complete a feature, bug fix, or refactor:

1. **Static Analysis & Syntax**:
   - Verify that your code compiles cleanly against PHP 8.1 syntax.
   - Ensure imports (`use` statements) are properly declared and resolved.

2. **Automated Unit & Feature Tests**:
   - You MUST run and verify the test suite using:
     ```bash
     php artisan test
     ```
   - All existing 55 tests must remain passing (100% pass rate). Zero regressions are tolerated.
   - If a new feature or endpoint is added, generate a corresponding Feature Test in `tests/Feature/` asserting both positive authorization (`200 OK`) and unauthorized boundary handling (`403 Forbidden` / `401 Unauthorized`).

3. **Output Evidence**:
   - Present a concise diff or summary of modified lines along with the terminal execution output of `php artisan test` demonstrating passing assertions.

---

## 5. Maintenance & Version Control (Maintained)
- This configuration file reflects the system state as of October 2026.
- Update this file whenever new external APIs (e.g., payment gateways) are integrated or architectural boundaries change.
