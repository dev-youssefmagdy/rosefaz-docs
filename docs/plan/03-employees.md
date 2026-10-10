# Phase 03 — Employees module (read 00-overview.md first; phases 01–02 done)
GM only. Same UX pattern as the branches page (copy its structure/JS style for consistency; reuse helpers, don't duplicate).
- Routes: `GET /employees` (page), `GET /employees/list?q=&branch_id=&page=`, `POST /employees`, `PUT /employees/{employee}`, `PATCH /employees/{employee}/toggle`. No hard delete.
- Fields: name (required), branch_id (nullable select; empty option = "كل الفروع"), is_active.
- Table: name, branch (or "كل الفروع"), status, actions; filter by branch + search; modal create/edit via AJAX.
- Lookup endpoint used later by the request form: `GET /lookups/employees?branch_id=` (auth, any role) → `[{id,name}]` active employees of that branch + global ones. Branch manager may only query his own branch (force server-side).
- Tests: CRUD, filter, lookup scoping for BM (ignores other branch_id), BM 403 on module pages.
