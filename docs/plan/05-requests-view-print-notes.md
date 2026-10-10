# Phase 05 — View, print, notes history (read 00-overview.md first; phases 01–04 done)
1. `GET /requests/{contractRequest}` show page (policy `view`): the **same** `_document.blade.php` in `view` mode (identical design) inside a page toolbar: back, "طباعة" button (`window.print()`). GM opening sets `viewed_at` if null (only once, never for BM).
2. Print: `@media print` in `app.css` — A4 portrait, hide nav/toolbar/notes panel/toast, remove shadows & page margins, `print-color-adjust: exact` so yellow cells and borders print, avoid page breaks inside the document. Verify via markup/CSS review (cannot open a browser).
3. Notes (history): panel below the document (screen only, `print:hidden`):
   - `GET /requests/{r}/notes` → list newest first (author name, role label, branch?, formatted datetime, body).
   - `POST /requests/{r}/notes` (GM only, `body` required max 2000) → returns the created note; client prepends it without reload (AJAX).
   - BM sees the history read-only (can view notes on own branch's requests) — no form. No PUT/PATCH/DELETE routes; `RequestNotePolicy` denies update/delete; model events already throw.
   - Timeline UI: vertical list, each item immutable (no edit/delete buttons).
4. GM list (phase 04) badge "جديد" disappears after viewing; add notes count column.
5. Tests: show access matrix (GM any, BM own branch 200 / other branch 403), viewed_at set once & only by GM, notes add (GM) / forbidden (BM), notes cannot be updated/deleted (model throws, routes 404/405), notes list order.
