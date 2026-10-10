# Module Roadmap

| Module | Scope | Status |
|---|---|---|
| 01 | Project foundation, authentication, roles, responsive UI | Complete (2026-10-09) |
| 02 | Staff and cashier management | Complete (2026-10-09) |
| 03 | Business settings | Complete (2026-10-09) |
| 04 | Product categories | Complete (2026-10-09) |
| 05 | Product management | Complete (2026-10-10) |
| 06 | Stock receiving and inventory | Complete (2026-10-10) |
| 07 | Spoiled and damaged stock | Complete (2026-10-10) |
| 08 | POS and sales management | Complete (2026-10-10) |
| 09 | Sales history and receipts | Complete (2026-10-10) |
| 10 | Expense management | Complete (2026-10-10) |
| 11 | Dashboard and reports | Complete (2026-10-10) |
| 12 | Notifications and stock alerts | Complete (2026-10-10) |
| 13 | Testing, security, and deployment | Implementation complete; browser acceptance pending |

Module 01 establishes Laravel 12, MySQL configuration, Livewire 4, session authentication, administrator/cashier access control, password management, dashboard foundations, the shared responsive layout, and the reusable Blade design system. Verification completed with 19 passing feature tests (67 assertions), Laravel Pint, a production Vite build, and Composer/npm security audits.

Module 02 adds administrator-only cashier listing, search, pagination, creation, viewing, editing, activation/deactivation, password resets, username login, policy protection, and session invalidation. The full regression suite passes with 29 tests and 128 assertions.

Module 03 adds a protected single-store profile, validated logo upload/replacement/removal, reusable application and receipt branding, Naira and kilogram formatters, Lagos date/time presentation, receipt preferences, and a default low-stock threshold. The full regression suite passes with 39 tests and 188 assertions.

Module 04 adds administrator-only category creation, editing, searching, pagination, activation/deactivation, editable default seed data, normalized duplicate protection, and an active-only availability contract for future product and POS modules. Category deletion is intentionally unavailable. The full regression suite passes with 47 tests and 237 assertions.

Module 05 adds administrator-only product registration, category relationships and real category product counts, optional image management, exact selling prices per kilogram, low-stock thresholds, searchable category selection, product/category/status filtering, activation/deactivation, and combined product/category sales eligibility. Stock quantities and receipt-level purchase costs remain deferred to Module 06. The full regression suite passes with 57 tests and 322 assertions.

Module 06 adds administrator-only stock receiving, immutable delivery history, independently costed batches, authoritative batch-derived balances, an auditable movement ledger, decimal-safe kilogram and money calculations, FIFO cost allocation, positive and negative corrections, idempotent submissions, database transactions, and row locking. Product pages now show real available and low-stock values. The full regression suite passes with 69 tests and 394 assertions.

Module 07 adds administrator-only spoiled, damaged, freezer-failure, missing, and other stock-loss recording; known-batch or FIFO allocation; exact purchase-cost loss valuation; searchable and filterable history; immutable original records; and one-time, reasoned reversals that restore exact source batches. The full regression suite passes with 79 tests and 468 assertions.

Module 08 adds a mobile-first Livewire cashier POS, sell-by-amount and sell-by-kilogram calculations, documented half-up weight rounding, multi-product and duplicate-product carts, payment capture, server price verification, FIFO purchase-cost allocation, atomic inventory deduction, stock movements, active-account enforcement, and idempotent checkout. The full regression suite passes with 94 tests and 545 assertions.

Module 09 adds role-scoped, searchable, filterable, paginated sales history; immutable sale-time detail views; store-branded browser and thermal receipt printing; administrator-only, reasoned full-sale reversals; separate refund records; exact original-batch stock restoration for verified sellable returns; no-restock treatment for missing or unusable returns; and double-reversal, retry, and rollback protection. The full regression suite passes with 102 tests and 604 assertions.

Module 10 adds editable seeded expense categories, idempotent operating-expense entry, responsive searchable and filterable history, exact totals across filtered pages, administrator-only corrections with changed-value audit snapshots, and reusable date-range, daily, weekly, monthly, and category reporting totals. Expenses remain separate from stock movements and stock-loss valuation. The full regression suite passes with 110 tests and 665 assertions.

Module 11 adds real administrator and cashier dashboards, Chart.js sales analytics, consistent Lagos reporting periods, exact revenue/FIFO COGS/profit/loss calculations, remaining-batch stock valuation, nine responsive detailed reports, sale-time category snapshots, protected CSV exports, and correct treatment of reversals and non-restocked refunds. The full regression suite passes with 119 tests and 738 assertions.

Module 12 adds synchronous database notifications for administrators, persisted product alert states, authoritative batch-derived low/out-of-stock transitions, stock adjustment and loss activity alerts, responsive read/unread management, navigation counters, and live dashboard warnings. Stock-changing services synchronize alerts inside their existing locked transactions, while unchanged states and idempotent retries cannot create duplicates. The full regression suite passes with 126 tests and 769 assertions.

Module 13 adds end-to-end stock reconciliation, MySQL-engine regression coverage, a real two-process oversell race, immutable `DATETIME` compatibility for XAMPP/MariaDB FIFO ordering, query reductions, private non-executable image delivery, combined searchable selectors, improved stock-loss filters, login password visibility, and administrator POS support. Deployment, testing, and review documentation is available in `docs/DEPLOYMENT.md`, `docs/TESTING.md`, and `docs/MODULE_13_REPORT.md`. The required real-browser viewport pass could not be executed because no browser was connected, so this module remains pending final acceptance rather than being marked complete.
