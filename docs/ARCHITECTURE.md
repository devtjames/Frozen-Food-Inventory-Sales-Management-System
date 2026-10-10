# Application Architecture

## Runtime structure

The application is a conventional Laravel monolith. Browser requests enter through `public/index.php`, route through `routes/web.php`, then pass authentication and role middleware before reaching controllers or view routes. Blade components provide the shared design system; Livewire 4 and its bundled Alpine.js provide the POS and searchable-combobox interactions without a separate frontend application.

Configuration is environment-based. The default database is MySQL, session state is database-backed, application timestamps are UTC, and business-facing dates use the configured Lagos timezone.

## Module boundaries

- `app/Http/Controllers` coordinates requests and responses.
- `app/Http/Requests` owns input validation and login throttling behavior.
- `app/Http/Middleware` enforces cross-cutting role access.
- `app/Models` contains Eloquent entities and relationships.
- `app/Policies` enforces record-specific abilities for protected business records.
- `app/Services` contains transactional domain rules, including inventory and FIFO allocation, and will host later sales and reporting rules.
- `resources/views/components` contains reusable layout and interface primitives.
- `tests/Feature` verifies complete HTTP, authorization, command, and business workflows.

## Relationship map

Implemented relationships:

- Category has many products.
- Product belongs to a category.
- Product has many stock receipts, batches, movements, and adjustments.
- A stock receipt belongs to a product and recording user and owns one delivery batch and movement.
- A stock adjustment belongs to a product and recording user and owns its movement, optional positive batch, and negative FIFO allocations.
- A stock batch belongs to its product, recorder, and either a receipt or positive adjustment; it has many allocations.
- Stock movements and allocations use polymorphic source references so later sales, losses, and reversals can reuse the ledger.
- A stock loss belongs to its product, recording administrator, optional explicitly selected batch, movement, FIFO allocations, and optional one-time reversal.
- A stock-loss reversal belongs to its original loss and recording administrator and owns a restoring movement.
- A sale belongs to its cashier and has many immutable sale items plus one payment.
- A sale item belongs to its sale and product and owns FIFO stock allocations and one stock movement.
- A sale item also preserves the category ID and category name captured at checkout for stable historical category analytics.
- A payment belongs to its sale and recording cashier.
- A sale has at most one sale reversal and one refund; each reversal belongs to its responsible administrator.
- A sale reversal has item-level audit rows linked to the original sale items. Restored items own positive stock movements; non-restocked items create no inventory mutation.
- A refund belongs to its original sale, reversal, and recording administrator while the original payment remains unchanged.
- An expense belongs to an editable expense category and recording administrator and has many immutable correction revisions.
- An expense revision belongs to its expense and responsible administrator and stores the changed before/after values.

Users now own their sales, receipts, adjustments, losses, reversals, refunds, expenses, and audit records through explicit recorder foreign keys. Business settings form one protected single-store profile.

## Authentication flow

When the database has no administrator, `/login` redirects to the one-time `/setup` owner form. `InitialAdministratorService` rechecks the administrator invariant inside a transaction, assigns the administrator role and active status on the server, hashes the password, and closes both browser and console initialization once any administrator exists. The owner is authenticated with a regenerated session immediately after setup. A hosted installation must complete this bootstrap step on a private connection before public exposure.

After initialization, administrators and cashiers share the same `/login` form. Login validation normalises email or username access through Laravel's session guard, checks active state, limits repeated attempts by identifier/IP, regenerates the session ID, and redirects by role. Protected routes first require authentication and then explicit `role` middleware. Logout invalidates the session and rotates the CSRF token. Password changes require the current password and confirmation.

The `php artisan app:create-administrator` command remains a non-browser fallback for the same initial setup; its password is entered through hidden interactive prompts and is never a command option.

## Staff management flow

Administrator-only staff routes are protected by authentication, active-account, and administrator-role middleware. `UserPolicy` adds record-level protection and limits the module to cashier targets. Dedicated Form Requests normalize usernames and emails, enforce uniqueness, and reject unauthorized requests before controller actions run.

`CashierAccountService` explicitly assigns the cashier role and owns account updates, status transitions, password resets, and session invalidation. Deactivation and password resets run in database transactions with a locked user row, rotate the remember token, and remove stored sessions. There is intentionally no staff deletion route.

The staff index uses server-side search and pagination. Desktop renders a table while mobile renders touch-friendly cards from the same paginator.

## Business settings flow

`business_settings` holds one protected store profile at the fixed application profile ID. `BusinessSettingsService` is the sole shared retrieval and update boundary for dashboards, reports, and receipts. It exposes non-sensitive profile and receipt payloads so future modules do not duplicate store details.

Administrators update the profile through a policy-backed Form Request. Logos are stored on the configurable private upload disk under generated names and streamed through the media controller. A replacement is uploaded before the database transaction, the profile is then updated under a row lock, and only afterward is the obsolete file removed. A failed database write cleans up the newly uploaded file.

`BusinessFormatter` formats decimal strings without using floating-point arithmetic. It provides consistent Naira, kilogram, business-date, and transaction-timestamp presentation. Stored timestamps remain UTC and date/time output is converted to `Africa/Lagos` at the display boundary.

The shared application logo consumes the store profile, while receipt header/footer Blade components and `receiptProfile()` are ready for the later sales and receipt modules.

## Product category flow

`categories` stores editable product groups with display names, optional descriptions, active status, and a unique normalized name. Normalization collapses repeated whitespace and lowercases a separate internal value, providing case-insensitive duplicate protection consistently in MySQL and SQLite while preserving the administrator's display capitalization.

Administrator routes are protected by authentication, active-account, role middleware, `CategoryPolicy`, and category Form Requests. `CategoryService` owns transactional create, update, and row-locked status changes. There is no category deletion route, so category identities remain available for future products and historical records.

The default `CategorySeeder` uses `firstOrCreate` to install Fish, Chicken, Turkey, Seafood, and Other Frozen Foods without overwriting existing records. The records have no special lock and remain editable through the normal administrator workflow.

`Category::active()` and `isAvailableForNewSales()` define the availability contract: a product is not offered in a new POS transaction unless both the product and its category are active. Deactivation preserves the category and its product relationships. `Category::products()` lets category listings display real relationship counts.

## Product management flow

`products` belongs to `categories` through a restricted foreign key. Product deletion is not exposed, and a category cannot be deleted while products reference it. Category listings now use the real `products()` relationship count.

Product names retain administrator-entered capitalization while a normalized name provides database-enforced, case-insensitive uniqueness within each category. The same product name may be used in different categories. `ProductPolicy`, administrator middleware, and dedicated Form Requests protect every read and write route.

Selling prices use `DECIMAL(12,2)` and are passed through the string-based `BusinessFormatter`; low-stock thresholds use `DECIMAL(12,3)` kilograms. The products table has no stock balance or purchase-cost field. Inventory is derived from stock batches rather than edited on product records.

`ProductService` owns transactional product changes, row-locked status updates, and safe image replacement/removal. Images use Laravel's configurable public filesystem disk. Failed database writes remove newly uploaded files, while obsolete images are deleted only after a successful commit.

`Product::availableForSale()` and `isAvailableForNewSales()` require both product and category to be active. POS queries additionally require positive available inventory. Inactive records and their historical relationships remain preserved.

The product listing supports server-side name/description search, category and status filters, pagination, real relationship data, responsive cards/tables, exact price display, and inventory-derived available and low-stock values.

## Inventory flow

`stock_receipts` is immutable delivery history. Each receipt creates one `stock_batches` row with its original quantity, independently tracked remaining quantity, purchase cost per kilogram, exact total cost, business date, reference, and recorder. Later deliveries always create new receipts and batches; earlier records are never rewritten.

The sum of positive `stock_batches.remaining_quantity` values is the only available-stock authority. Product records do not cache a competing total. `InventoryService` exposes this balance to inventory and product screens and applies each product's threshold for low-stock presentation. Collection screens retrieve balances with one grouped aggregate instead of one query per product.

`stock_movements` records signed quantity changes and a polymorphic source. The movement enum reserves receipt, sale, spoiled, damaged, positive adjustment, negative adjustment, and sale-reversal types so later modules can extend the same ledger. `stock_allocations` preserves the exact batch quantities, unit costs, and total costs consumed by a source record.

`InventoryService` performs receipts and corrections in database transactions, locks the product and affected batch rows, validates all arithmetic again in the domain layer, and relies on unique UUID idempotency keys to make repeated form submissions safe. Positive corrections create a separately costed batch. Negative corrections use `FifoStockAllocator`, which locks eligible batches in receiving-date and ID order, verifies the full quantity before mutation, and rolls back completely on insufficient stock or any subsequent failure. The allocator also accepts an explicitly selected valid batch for later workflows that need one.

Weights are persisted as `DECIMAL(12,3)` and costs as `DECIMAL(12,2)` / `DECIMAL(24,2)`. `DecimalMath` calculates with scaled decimal strings instead of binary floating point. User-entered business dates are interpreted in `Africa/Lagos` and stored as UTC timestamps.

## Stock loss flow

`stock_losses` records the product, loss category, quantity, reason, optional description, loss timestamp, recorder, calculated financial value, and optional administrator-selected batch. It contains no editable stock total. The financial value is the sum of immutable `stock_allocations.total_cost` values based on batch purchase costs, so stock loss never uses selling price or contributes to sales revenue.

`StockLossService` creates the loss inside a transaction, locks the product and eligible batches, and delegates deduction to the existing `FifoStockAllocator`. A known affected batch restricts allocation to that valid batch; otherwise the standard oldest-first ordering applies. The loss then creates a signed movement using spoiled, damaged, or general stock-loss movement types. UUID idempotency keys prevent repeat submissions from deducting inventory twice.

Corrections use `stock_loss_reversals`, not edits or deletion. The original loss row, allocations, cost, and movement remain unchanged. A reversal locks the original loss, product, and every allocated batch, restores each exact allocation quantity, and creates a positive stock-loss-reversal movement. A unique `stock_loss_id` constraint plus policy and service checks prevents double restoration.

Loss history supports product/reason search and product, loss-category, status, and Lagos business-date filters. Indexed product, category, timestamp, quantity, and financial-value fields are ready for later daily, monthly, product, category, reason, and total-loss reporting without mixing losses with revenue.

## POS and sales flow

`PosTerminal` is a Livewire 4 sales component available through separate administrator and cashier routes. Product search, category filtering, selection, exact server-side previews, cart additions, updates, removals, payment selection, and checkout occur without full page reloads. Every Livewire action rechecks that the authenticated user is an active cashier or administrator because component updates use Livewire's update endpoint rather than the original page middleware. Checkout redirects to the role-appropriate sale history.

`SaleCalculator` uses `DecimalMath` scaled-string operations. For amount sales it divides requested money by the selling price and rounds kilograms half-up to three decimal places. The requested amount remains payable; `calculated_value` stores rounded weight multiplied by price and `rounding_adjustment` stores their signed difference. Kilogram sales multiply the three-decimal quantity by the price and round money half-up to two decimals. Binary floating point is never authoritative.

`SalesService` is the checkout boundary. It validates the cashier, cart size, selling methods, product/category status, price snapshots, payment method, optional cash tender, and combined quantities for repeated products. It locks all products in ID order and available batches before mutation, creates the sale and item snapshots, delegates every deduction to `FifoStockAllocator`, records each item's purchase cost and sale movement, creates one paid payment, and commits everything in a single transaction. Any failure rolls back sale, payment, movement, allocations, and batch changes.

Sales use unique UUID checkout keys for retry safety and unique human-readable transaction numbers. `sales` stores the total payable and total rounding reconciliation; `sale_items` preserves names, prices, methods, requested values, displayed/deducted quantities, payable amounts, calculated values, rounding adjustments, and FIFO purchase-cost totals. Cashiers never see purchase costs.

## Sales history, receipt, and reversal flow

`SaleHistoryService` builds the server-side sales query. Administrators may filter every store transaction; cashiers are always constrained to their own `cashier_id`, regardless of submitted parameters. Transaction number, Lagos date boundaries, cashier, payment method, effective payment status, and effective transaction status filters are supported with pagination.

Sale detail and receipt pages read immutable `sale_items` snapshots rather than current product names or prices. The standalone receipt uses `BusinessSettingsService::receiptProfile()`, browser printing, and an `80mm` print stylesheet that removes controls and unrelated application navigation. The same route supports first printing and reprinting on desktop or mobile without a printer subscription.

`SaleReversalService` is the only reversal write boundary. It requires an active administrator, locks the original sale, products, and applicable batches, then creates a separate reversal, item audit rows, and completed refund while leaving the original completed sale and paid payment untouched. For a mistaken sale or verified sellable return, every original allocation is restored to its exact batch and each reversal item creates a positive ledger movement. For missing, unreturned, or unsuitable products, the refund is recorded but no stock is restored or deducted again. Unique sale and UUID constraints prevent double reversal and repeated submissions; the complete operation rolls back on any failure.

## Expense management flow

`expense_categories` provides editable operating-cost classifications. `ExpenseCategorySeeder` installs the standard electricity, fuel, transport, salary, rent, freezer, equipment, and miscellaneous groups without making them permanent restrictions. Categories referenced by expenses cannot be deleted, and no deletion route is exposed.

`ExpenseService` is the financial write boundary. Creation validates an active administrator, locks the selected category, normalizes money through `DecimalMath`, and uses a unique UUID to make repeated submissions safe. Corrections lock the expense and selected category, require a reason, update the current record, and write an `expense_revisions` row containing only the changed previous and updated values. Revision UUIDs prevent a retried correction from producing duplicate audit entries.

`ExpenseHistoryService` applies description/note search, category, and date filters and calculates an exact total before pagination so every matching page is represented. `ExpenseReportingService` exposes arbitrary date-range totals and decimal-safe daily, ISO-week, monthly, and category groupings for Module 11. Expenses are ordinary operating costs only: neither service writes inventory batches, allocations, movements, or stock-loss records.

## Dashboard and reporting flow

`ReportingPeriodFactory` is the shared boundary for today, yesterday, current-week, current-month, and custom periods. It interprets dates in `Africa/Lagos`, creates inclusive local boundaries, and converts timestamp boundaries to UTC before querying sales, receipts, losses, and reversals. Date-only expenses use the corresponding local date strings.

`FinancialReportService` excludes reversed sales from revenue, transaction counts, and FIFO COGS. It subtracts sale-item allocation costs from revenue for gross profit, then subtracts operating expenses and recognized inventory losses for estimated net profit. Active stock-loss values and non-restocked reversed-item purchase costs are recognized separately; restored reversals are excluded. This preserves the original audit history without double counting COGS or losses.

`InventoryReportService` treats the sum and purchase-cost value of remaining stock batches as authoritative. It derives received, valid sold, and damaged/spoiled totals with constrained database aggregates while loading only remaining batches required for valuation, avoiding full historical relationship hydration. `SalesAnalyticsService` groups valid sales into daily, ISO-week, monthly, product, sale-time category, and seller datasets rendered by responsive Chart.js components.

`ReportDataService` supplies the sales, product, category, cashier, inventory, receiving, stock-loss, expense, and profit reports from the same calculation rules. `ReportController` applies administrator authorization, collection pagination, exact display formatting, and UTF-8 CSV streaming with spreadsheet-formula protection. The cashier dashboard requests only personal valid revenue/count data and recent personal transactions; administrator report routes remain inaccessible to cashiers.

## Notifications and stock-alert flow

Laravel database notifications provide the internal alert channel synchronously, so core behavior requires neither Redis nor a queue worker. `AdminNotificationService` addresses only active administrators and stores a typed title, description, optional product identity, and internal related-record URL. Cashiers receive no administrator inventory or financial activity data.

`products.stock_alert_state` persists only the last notified state (`normal`, `low_stock`, or `out_of_stock`); it is not a stock balance. `InventoryAlertService` always calculates current stock from the authoritative sum of remaining batch quantities and compares that decimal value with the product threshold. A locked transition to low or zero creates one notification, an unchanged state creates none, and replenishment returns the state to normal so a later depletion can notify again.

Stock receipt, sale, adjustment, stock-loss, loss-reversal, sale-reversal, product creation, and threshold-update services synchronize alert state inside their existing database transactions while holding the product lock. Notification rows therefore commit or roll back with the stock operation. Existing UUID idempotency boundaries return before notification generation on repeated submissions.

The administrator dashboard derives warning cards from live batch balances rather than notification history. The responsive notifications page scopes every query and read action to the signed-in administrator, provides per-item and mark-all read controls, and links directly to the affected product or inventory record.

## Responsive interface

The application shell has a compact mobile header and off-canvas navigation below the `lg` breakpoint. Desktop uses a persistent collapsible sidebar, top navigation, and profile menu. Content uses a 320px minimum viewport baseline, fluid spacing, accessible controls of at least 44px, responsive cards, and constrained overflow.

Searchable selections use a single combobox-style field: typing filters the embedded option panel and choosing an option updates a hidden server-submitted identifier. The stock-loss filter area uses explicit desktop grid spans and collapses to one/two-column mobile layouts.

## Upload and media boundary

Logo and product uploads are validated by Laravel's image rule plus explicit JPG/JPEG, PNG, and WebP MIME/extension limits, dimensions, and maximum size. Generated filenames are stored on the private `uploads` disk under `storage/app/uploads`, outside `public`. `MediaController` resolves only the database-linked image, verifies that it still exists and has an allowed detected MIME type, and streams it inline with `X-Content-Type-Options: nosniff` and a restrictive content security policy. Product media requires an active authenticated session; the single configured business logo is public so it can appear on login.

## MySQL and XAMPP compatibility

Business event timestamps are UTC `DATETIME` fields. Migration 21 converts earlier required `TIMESTAMP` definitions because legacy MariaDB can implicitly add `ON UPDATE CURRENT_TIMESTAMP` to the first required timestamp in a table. Without the conversion, changing a batch balance could also change its receiving date and corrupt FIFO ordering. Row locks, idempotency constraints, and InnoDB transactions remain the concurrency boundary.
