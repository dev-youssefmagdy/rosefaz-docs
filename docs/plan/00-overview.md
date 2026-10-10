# Rose Fan Contract Authentication Requests — Shared Context (READ FIRST, every phase)

Laravel 13 / PHP 8.3 / SQLite / Tailwind 4 (vite) / Blade / vanilla JS `fetch` (no jQuery, no Livewire, no SPA).
UI is **Arabic, RTL** (`<html lang="ar" dir="rtl">`). Logo: `public/logo.png` (use `asset('logo.png')`).
Follow existing code style; run `vendor/bin/pint --dirty` after PHP edits. Do NOT touch files outside your phase scope.

## Business
Form "طلب توثيق عقد الكتروني" (electronic contract authentication request), company "مجموعة روز فان الفندقية – الادارة العامة".
Two roles:
- **branch_manager** – belongs to exactly one branch (a branch has many managers). Creates a request with all data and submits it. After creation: **read-only forever** (no edit, no delete). Sees only requests of his own branch.
- **general_manager** – receives requests of all branches. Sees list + details, can add **notes**. Manages branches (+ their manager users) and employees.
Notes = history table (append-only): can never be updated/deleted (no routes, policy denies, model throws on update/delete).
No approve/reject workflow (not requested). Only `viewed_at` (set when general manager first opens it) to show "new" badge.

## Data model (exact names)
- `users`: + `role` string (`general_manager`|`branch_manager`), `branch_id` nullable FK. Enum `App\Enums\Role`.
- `branches`: id, `name`, `number` (unique, shown as "رقم الفرع"/"فرع 3"), `is_active` bool default true, timestamps.
- `employees`: id, `branch_id` nullable FK (null = all branches), `name`, `is_active` bool default true, timestamps. Used for "الموظف المختص / Responsible Officer" dropdown (filtered: employees of request branch + branch_id null).
- `contract_requests` (model `ContractRequest`): id, `ref_no` unique (`RF-{Y}-{000001}`, generated server-side in a transaction), `branch_id` FK, `created_by` FK users, `employee_id` FK employees, `request_date` date (server today), `full_name`, `id_number` string(10) digits, `birth_date` date, `mobile` string(10) `^05\d{8}$`, `needs_electricity_bill` bool, `apartment_no` nullable string, `annual_rent` decimal(12,2), `insurance_amount` decimal(12,2), `authentication_amount` decimal(12,2), `rental_type` string enum `App\Enums\RentalType` (`residential` سكني, `commercial` تجاري), `contract_duration_months` unsigned smallint default 12, `rental_date` date, `amount_paid` decimal(12,2), `remaining_amount` decimal(12,2) (**server computes = annual_rent − amount_paid; never trust client**; amount_paid ≤ annual_rent), `start_date` date, `viewed_at` nullable timestamp, timestamps. Hijri dates for `birth_date`/`start_date` are **computed, not stored** (accessor via `IntlDateFormatter('en_US@calendar=islamic-umalqura', pattern 'dd/MM/yyyy')`; JS uses same Intl for live preview).
- `request_notes`: id, `contract_request_id` FK, `user_id` FK, `body` text, `created_at` only (no updated_at).

## Architecture rules
- Routes: `routes/web.php`, auth via session login (`/login`, `/logout`), all else under `auth`. Role middleware alias `role:general_manager`.
- Controllers thin; validation in FormRequest classes; policies in `app/Policies`; JSON resources not required — return arrays.
- **AJAX pattern**: pages render shell via Blade; lists/forms use `fetch` through one helper `resources/js/http.js` (`api(url,{method,body})`: sends `X-CSRF-TOKEN`, `Accept: application/json`, `X-Requested-With`, throws `{status, errors}` on 422). Validation errors displayed under fields (`data-error-for="field"`). Toast helper for success/failure. Lists: JSON endpoint with search + pagination (`?q=&page=`), re-rendered client side.
- Tailwind 4 utility classes only (+ small `@media print` block in `resources/css/app.css`). RTL: use logical utilities (`ms-*`, `me-*`, `ps-*`, `text-start`). Font: Tajawal/Cairo via the bunny font in `vite.config.js` (replace Instrument Sans) with system fallback.
- **Single source of truth for the form look**: Blade partial `resources/views/requests/_document.blade.php` renders the form (header with logo, title bar, bordered grid rows, bilingual labels, boxed digit cells for ID/mobile/ref, yellow input cells exactly like the original paper form). It takes `$request` (nullable) and `$mode` = `edit`|`view`. In `edit` mode cells contain inputs; in `view` mode the same markup shows values. Same partial is used for create page, show page and print. Print: `window.print()` button, `@media print` hides nav/buttons/notes, A4 portrait, `print-color-adjust: exact`.
- Seeds (`DatabaseSeeder`): general manager `gm@rosefaz.test`, 3 branches (numbers 1–3) each with a manager `branch{n}@rosefaz.test`, ~4 employees (some per-branch, one global). Password for all: `password`.
- Tests: PHPUnit feature tests, `RefreshDatabase`. Each phase adds tests for its scope and runs `php artisan test --compact` (only its own tests while iterating).
- Every phase ends by: running pint, running its tests, and replying with **≤10 lines**: files created, how verified, open issues. No long explanations.

## Phases (run in order, each as a fresh agent)
01 foundation · 02 branches · 03 employees · 04 requests-create · 05 requests-view-print-notes · 06 qa-polish
