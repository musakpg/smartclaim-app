# SMARTCLAIM (AERO ART SDN BHD) - SYSTEM ARCHITECTURE & ROADMAP

## Core Tech Stack & Conventions

- Framework: Laravel 10 + PHP 8.1
- Database: MySQL
- Styling & Interactivity: Tailwind CSS, Alpine.js (Accordion navigation & smooth UI transitions)
- Libraries: barryvdh/laravel-dompdf, Google Cloud Vision AI (OCR), WebPush v9
- Key Rules:
    - User model primary key is `user_id` (NOT `id`).
    - Code comments must strictly be in English.

## Current System State (COMPLETED)

1. Web Push Notification: VAPID keys, sw.js service worker, in-app notification dispatch.
2. Anti-Fraud & Forensic Desk: SHA-256 duplicate receipt hashing, image tamper check.
3. Dual-Resource Forensic Modal: Side-by-side merchant receipt and bank payment slip verification.
4. Manager Navigation Layout: Reorganized sidebar with smooth CSS Grid accordion animation.
5. PDF Voucher Generator: DomPDF installed and voucher template prepared.

## Immediate Roadmap & Upcoming To-Dos

1. Audit Trail Logging System:
    - Create `audit_logs` table (user_id, claim_id, action, old_values, new_values, ip_address, user_agent, created_at).
    - Hook automated event listeners to single/batch disbursements, approvals, and amount edits.
    - Build Audit Trail UI timeline modal for Managers and External Auditors.
2. Comprehensive Financial Reporting Engine:
    - Export claims to Excel/CSV with LHDN tax categories, SST breakdowns, and voucher reference numbers.
3. Mobile PWA Integration:
    - Setup `manifest.json`, high-resolution icons (192x192, 512x512), and standalone display mode.
4. In-App Quick Camera OCR:
    - Native WebRTC camera capture modal with receipt bounding box and auto-crop before sending to Vision OCR.
