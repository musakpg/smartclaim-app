# Antigravity IDE Context Rules - SmartClaim

## 1. Development Principles
- **Framework Conventions**: Follow standard Laravel 10 conventions strictly.
- **Language Policy**: Code comments, PHPDoc blocks, commit messages, and documentation must be in English only.
- **PHP Version**: Target PHP 8.1 features (typed properties, match expressions, constructor promotion).

## 2. Architectural Boundaries
- **Thin Controllers, Dedicated Services**: 
  - Controllers must only coordinate HTTP requests, call Service classes or Form Requests, and return views/JSON responses.
  - Offload heavy tasks (OCR payload parsing, Distance Matrix API calculations, SHA-256 deduplication) to `app/Services/`.
- **Validation**: 
  - Never write inline `$request->validate()` inside controller actions. Always create and use dedicated Form Request classes under `app/Http/Requests/`.
- **UI Stack**:
  - Exclusively use Tailwind CSS and Alpine.js inside Blade templates.
  - Do NOT generate inline CSS styles or introduce React/Vue dependencies.
- **Secrets & Credentials**:
  - Never hardcode API keys, database credentials, or secret paths in source code. Reference them strictly via `config('services.xxx')` reading from `.env`.

## 3. Scope Restraint
- Only touch files directly related to the user prompt. 
- Do not refactor untouched legacy code or alter untouched route definitions.
