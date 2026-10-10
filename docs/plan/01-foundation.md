# Phase 01 — Foundation (read 00-overview.md first)
Goal: schema for users/branches/employees, auth, layout, shared JS. No feature screens yet.
1. Enums `Role`, `RentalType` (with Arabic `label()`).
2. Migrations: users add role/branch_id; branches; employees. Models `Branch` (hasMany users, employees), `Employee`; `User` gets role cast, `branch()`, `isGeneralManager()`, `isBranchManager()`. Factories for all.
   (The contract_requests/request_notes migrations belong to phase 04.)
3. Seeder as in overview. Run `php artisan migrate:fresh --seed`.
4. Auth: `AuthController` login/logout (email+password, Arabic validation messages `lang/ar` or inline), middleware alias `role` (register in `bootstrap/app.php`), redirect home `/` → `/requests` (route may 404 until phase 04; use a placeholder dashboard route name `requests.index` registered later — for now home redirects to a simple `/home` placeholder view showing user name & role).
5. Layout `resources/views/layouts/app.blade.php`: RTL, Tajawal font, top nav (logo, links by role: GM → الطلبات / الفروع / الموظفون; BM → طلباتي / طلب جديد), user name + logout. `@stack('scripts')`, toast container, `@vite`. Guest layout for login (centered card with logo).
6. `resources/js/http.js` (api helper per overview), `resources/js/ui.js` (toast, `showErrors(form, errors)`, `clearErrors`, debounce, `renderPagination`), imported by `app.js`. `app.css` print block + RTL base.
7. Update `vite.config.js` font to Tajawal. `npm run build` must pass.
8. Tests: login ok/fail, role middleware blocks BM from GM routes (add a temporary test route if needed), seeder creates expected users.
