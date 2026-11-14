# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AovuShop is a Vietnamese clothing shop (Laravel 13, PHP 8.5, served locally via Laragon) with two halves: an admin panel under `/admin/*` and a public storefront (`/shop`, `/cart`, `/checkout`, `/tin-tuc`, `/support-chat`). All routes live in `routes/web.php`; there is no authentication or admin middleware yet. UI text, validation messages and DB column names are in Vietnamese (`APP_LOCALE=vi`). Keep new user-facing copy in Vietnamese.

## Commands

- Run all tests: `composer test` (or `php artisan test --compact`)
- Single file / single test: `php artisan test --compact tests/Feature/OrderManagementTest.php` or `php artisan test --compact --filter=testName`
- Format PHP: `vendor/bin/pint --dirty --format agent`
- Dev server + Vite: `composer run dev`; production assets: `npm run build`
- Seed demo data: `php artisan db:seed` (runs `MasterTestDataSeeder`)

## Database: legacy schema + migrations

This is the most important thing to understand before touching data code.

- The local DB is MySQL `aovu_shop`, originally built from the dump `aovu_shop.sql`. The core commerce tables (`don_hangs`, `chi_tiet_don_hangs`, `tai_khoans`, `chuc_vus`, `vouchers`, `bien_thes`, `gio_hangs`, `danh_gias`, `phuong_thuc_thanh_toans`, `dia_chis`, …) exist **only in that dump**. They have no migrations.
- Migrations cover a subset. Legacy-table migrations (`danh_mucs`, `san_phams`, `yeu_cau_doi_tras`, …) start with `if (Schema::hasTable(...)) return;` so they don't break the existing DB. Newer features (promotions, news, support chat, return workflows, banners) use normal English-named tables.
- Legacy models map to Vietnamese tables with `protected $table = '...'` and usually `public $timestamps = false`. Columns are Vietnamese (`trang_thai`, `ten_san_pham`, `gia_san_pham`, `danh_muc_id`, …). Customers and staff are `App\Models\TaiKhoan` (`tai_khoans`) with role `ChucVu`, **not** `App\Models\User`.
- Status values are integer constants on the models (`Order::STATUS_*`, `Order::PAYMENT_*`, `ReturnRequest::STATUS_*` / `TYPE_*`). Use those, not magic numbers.
- Much of the code queries legacy tables with `DB::table('don_hangs')` and guards with `Schema::hasTable` / `Schema::hasColumn` (see `AppServiceProvider`, `CheckoutService`). Keep those guards when you touch such code.

## Testing

- Tests run on in-memory SQLite (`phpunit.xml`) with `RefreshDatabase`. Because legacy tables have no migrations, feature tests that need them create the tables themselves in `setUp()` with `Schema::create(...)` (see `StorefrontCheckoutTest`, `OrderManagementTest`). Follow that pattern rather than adding migrations for dump-only tables.
- Factories exist only for `User`, `SupportConversation` and `SupportMessage`. For legacy tables, tests insert rows directly with `DB::table(...)->insert(...)`.
- One feature test file per admin module (`*ManagementTest.php`).

## Architecture

- **Controllers hold most of the logic.** Validation is inline (`$request->validate`) and queries are written in controllers. The only services are in `app/Services`:
  - `CheckoutService` builds a quote from the session cart and places orders in a `DB::transaction` with `lockForUpdate` on variants and vouchers.
  - `PromotionPricingService` applies active `promotions` to product prices. The storefront and checkout both use it.
- **Storefront cart** is session-based (`Session` key `storefront_cart`). It is not stored in the legacy `gio_hangs` table.
- **Returns / exchanges**: `ReturnRequest` (`yeu_cau_doi_tras`) has a multi-step status flow. `ReturnRequestWorkflow` and `ReturnRequestHistory` track it. Return requests can be created from the admin returns screen or from a support-chat conversation. Refunds are confirmed manually in `Admin\RefundWalletController` (`/admin/finance/refunds`).
- **Support chat**: the customer side is `SupportChatController` and the admin side is `Admin\SupportChatController`. They use `support_conversations` and `support_messages` and are polled over HTTP (throttled routes, no websockets).
- **Views**: Blade with the AdminLTE 3 (Bootstrap 4 + jQuery) assets served from `public/adminlte`; the `AdminLTE-3.2.0/` folder is the vendor source copy. `layouts/admin.blade.php` and `layouts/storefront.blade.php` are the two layouts. The admin sidebar's counters (pending orders and returns) come from a view composer in `AppServiceProvider`. Admin views follow `resources/views/admin/<module>/{index,create,edit,show}.blade.php`. Tailwind/Vite is installed but the existing pages use AdminLTE/Bootstrap classes.
- Admin routes are named `admin.<module>.<action>`; storefront routes `shop.*`.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
