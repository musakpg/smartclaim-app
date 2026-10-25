# Database Migration & Schema Modification Guide

## Database Engine Context
- **Target Database**: TiDB Cloud (Distributed SQL, MySQL 8.0-compatible).
- **Driver**: `mysql`.

## Strict Migration Guardrails
1. **Never Drop Production Columns In-Flight**:
   - Always prefer additive migrations. If renaming or dropping columns, write phased migrations to prevent breaking active read queries.
2. **Indexing & Constraints**:
   - Foreign key constraints must explicitly define `onDelete('cascade')` or `onDelete('restrict')` depending on domain logic.
   - Any column frequently queried in queues or filters (`status`, `user_id`, `created_at`) MUST have an explicit index defined.
   - The `receipt_hash` column on claims must have a unique or compound index for fast duplicate lookup ($O(1)$ search time).
3. **Data Types**:
   - Currency and financial amounts must ALWAYS use `DECIMAL(10, 2)`. NEVER use `FLOAT` or `DOUBLE` due to floating-point rounding errors.
   - Timestamps must use `TIMESTAMP` or `DATETIME` with UTC storage.
4. **Testing Migrations**:
   - Verify all migrations can be rolled back safely using `php artisan migrate:rollback --step=1` before deploying.
