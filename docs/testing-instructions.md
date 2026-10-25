# Test Automation & Verification Guidelines

## Purpose
Passing tests are considered evidence, not an absolute guarantee. Every pull request or generated code change must maintain the 100% test pass rate across the test suite.

## Testing Stack
- PHPUnit / Pest with Laravel test helpers.
- In-memory SQLite or testing database schema.

## Mandatory Test Coverage Rules
1. **RBAC & Authorization**:
   - Every protected route MUST have test cases verifying access for:
     - Authorized user (HTTP 200/302).
     - Unauthorized role (HTTP 403 Forbidden).
     - Unauthenticated guest (Redirect to login, HTTP 302/401).
2. **Financial Concurrency & Locking**:
   - Test cases involving disbursement status updates must assert that atomic transactions and locking mechanisms (`lockForUpdate()`) are executed without throwing unhandled deadlocks.
3. **Fraud Detection Checks**:
   - Ensure unit/feature tests verify SHA-256 duplicate receipt blocking.
4. **Execution Command**:
   - Validate all tests by running:
     ```bash
     php artisan test
     ```
   - No feature is considered complete until all 55+ test cases return green (Pass).
