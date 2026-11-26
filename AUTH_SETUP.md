# AOVU Shop – Authentication / Roles setup

The storefront UI is retained. Admin UI is not redesigned; admin routes are protected.

## Roles
- `Admin`: can access `/admin/*` routes.
- `Người dùng`: standard authenticated account role.
- `Khách hàng`: default role for self-registration; may continue shopping/checkout as guest too.

## One-time setup after pulling these changes
1. Back up the database first.
2. Run `php artisan migrate` (the added migrations restore the missing reviews table/columns if needed and create role records without dropping data).
3. Put administrator bootstrap credentials in `.env`:
   - `ADMIN_NAME="Quản trị viên AOVU"`
   - `ADMIN_EMAIL=your-admin-email@example.com`
   - `ADMIN_PASSWORD=choose-a-long-unique-password`
4. Run `php artisan db:seed --class=AdminAccountSeeder`.
5. Visit `/login` to log in. Admin accounts go to `/admin`; customer accounts go to `/shop`.
6. Remove or blank `ADMIN_PASSWORD` from `.env` after creating the initial administrator.

Do not use `php artisan migrate:fresh` on a database containing store data.
