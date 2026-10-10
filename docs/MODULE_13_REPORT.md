# Module 13 Review Report

Review date: 2026-10-10

Status: implementation and automated verification complete; final browser viewport acceptance pending because no controllable browser was connected to this workspace.

## Findings fixed

- Corrected an XAMPP/MariaDB portability defect where required `TIMESTAMP` columns silently gained `ON UPDATE CURRENT_TIMESTAMP`. Updating remaining stock changed batch receiving dates and could break FIFO order. Migration `2026_10_10_000021_convert_business_event_timestamps_to_datetime.php` converts immutable business event times to UTC `DATETIME` columns.
- Corrected single-store profile creation so the protected profile ID remains `1` on MySQL even after rolled-back auto-increment activity.
- Replaced per-product balance queries with one grouped stock-balance query and replaced report-wide loading of full receipt/sale/loss histories with database aggregates.
- Replaced dashboard warning batch hydration with a database stock sum.
- Corrected the POS Livewire failure caused by calling Eloquent `modelKeys()` on a base collection. This was capable of returning an HTML exception response to the Livewire overlay after adding an item.
- Moved new logo/product uploads to a private, non-web-root disk and added controlled image responses, strict MIME allow-listing, `nosniff`, random storage names, and legacy Apache script denial. Corrected local `APP_URL` for `127.0.0.1:8000`.
- Replaced the two-control searchable selector with an in-field searchable combobox, improved desktop stock-loss filters, and added login password visibility control.
- Added an administrator POS route while retaining cashier/admin route separation and role-appropriate sale-history redirects.

## Data and financial verification

The exact workflowâ€”receive 100 kg, receive another 50 kg, sell 2.5 kg, sell 5 kg, record 3 kg spoiled, receive another 30 kg, then sell 1.5 kgâ€”reconciles to 168.000 kg. The ledger contains the same signed deltas; all three receipts remain independent; FIFO batch quantities and remaining-stock purchase-cost valuation agree.

POS coverage includes ₦5,000 at ₦4,000/kg = 1.250 kg, 2.500 kg at ₦4,500/kg = ₦11,250.00, and a repeating-decimal amount case with an explicit rounding adjustment. Existing financial tests cover FIFO COGS, gross profit, expenses, inventory losses, restored/non-restored reversals, and no double counting.

A true two-process MySQL race submitted 7 kg and 4 kg sales against 10 kg simultaneously. One sale committed, the competing sale was rejected for insufficient stock, stock remained 6.000 kg, and zero batches were negative.

## Migration executed

`2026_10_10_000021_convert_business_event_timestamps_to_datetime.php` ran successfully against the configured local MySQL database in batch 13. A clean disposable MySQL schema also migrated from zero and passed the full suite before cleanup.

## Verification performed

- SQLite regression suite: 133 tests, 840 assertions, all passing.
- Disposable MySQL regression suite: 133 tests, 837 assertions, all passing; the schema was removed afterward.
- The attachment's exact seven-step stock scenario was rerun separately on a disposable MySQL schema: 1 test, 21 assertions, all passing; the schema was removed afterward.
- Real two-process MySQL stock race: one completion, one insufficient-stock rejection, consistent 6.000 kg balance, zero negative batches.
- Laravel Pint: passing.
- PHP syntax validation: 224 files passing.
- Blade compilation, configuration cache, route cache, event cache, and application optimization: passing; development caches were cleared afterward.
- Vite production build: passing with local bundled CSS, Livewire/Alpine, and Chart.js assets.
- Composer manifest validation and Composer/npm advisory audits: passing with zero reported dependency vulnerabilities.
- Route/middleware inspection and configured local MySQL migration status: passing; all 27 migrations are applied.

## Remaining acceptance items

- No connected in-app browser was available. The required interactive checks at 320, 360, 375, 390, 430, 768, 1024, and desktop widths have not been claimed as executed. Follow `docs/TESTING.md` before marking Module 13 complete.
- Composer on this machine reports TLS verification disabled. Enable CA verification before any production dependency installation.
- The local PHP CLI lacks `intl`; application tests pass, but Laravel's `db:table` diagnostic requires it. Enable the XAMPP PHP 8.2 extension before handover.
- Collection-built detailed reports are appropriate for the initial single-store deployment but can consume memory for very large unrestricted date ranges. Use bounded reporting periods and revisit database-native pagination/streaming as data volume grows.

Because the mandatory browser/device pass remains unavailable, the roadmap intentionally does not mark Module 13 fully complete.
