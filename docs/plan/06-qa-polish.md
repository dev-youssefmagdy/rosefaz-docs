# Phase 06 — QA & polish (read 00-overview.md first; phases 01–05 done)
Do NOT add features. Audit and fix.
1. `php artisan migrate:fresh --seed`, `php artisan route:list`, `php artisan test --compact` (all green), `vendor/bin/pint`, `npm run build`.
2. Smoke test with the real app: start `php artisan serve --port=8765` in background, use `curl` with cookie jar to log in as `gm@rosefaz.test` and `branch1@rosefaz.test`, hit every page + JSON endpoint, create a request, view it, add a note; confirm status codes and that branch2 manager gets 403 on branch1's request. Stop the server afterwards.
3. Security review of diff: mass assignment, authorization on every route, CSRF on all AJAX, no N+1 (eager load), XSS (all client-side rendering escapes text — use textContent, never innerHTML with user data), Arabic validation messages present.
4. RTL/print review: no physical `left/right` utilities in views, print CSS present, `_document` identical in create/show.
5. Write `README.md` section (≤30 lines): setup, seeded accounts, modules, roles.
6. Reply with a ≤15 line final report.
7. Known issue: branches/employees pages run inline scripts before the Vite module loads (`window.App` may be undefined). Verify and fix (e.g. wait for DOMContentLoaded or move to modules). Also list any stray dev-DB rows (branch id 4, user id 5, employee id 8 from a tinker check) in the final report; do NOT delete them.
