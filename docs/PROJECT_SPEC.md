# Project Specification

## Purpose

The Frozen Food Inventory & Sales Management System is a single-store Laravel application for frozen-food stock control, point-of-sale transactions, losses, expenses, staff access, and financial reporting. It serves administrators and cashiers through a mobile-first web interface.

The complete source specification is `Frozen Food Inventory and Sales Management System.md`. This document records the non-negotiable implementation rules used across modules.

## Technology baseline

- PHP 8.2, Laravel 12, MySQL, Eloquent, migrations, and PHPUnit
- Blade, Tailwind CSS, Livewire 4, Livewire-bundled Alpine.js, and Vite
- Laravel session authentication, Form Requests, middleware/policies, service classes, transactions, and logging
- XAMPP-compatible local operation and conventional shared-hosting deployment

## Access rules

- Administrator: management access, including staff, catalog, stock, losses, expenses, reporting, and settings as those modules are delivered.
- Cashier: POS, own sales history, and own password only as those modules are delivered.
- An active administrator may also process sales through the administrator POS route when covering the counter. This does not grant access to cashier-only routes or weaken role separation.
- Public registration is disabled. The first administrator is created interactively from the CLI.
- Every sensitive route requires server-side authentication and role authorization.

## Inventory and financial rules

- All user-facing quantities are kilograms. Persistent inventory precision is `DECIMAL(12,3)`.
- Available stock equals received stock minus sold stock minus losses plus approved positive adjustments minus approved negative adjustments.
- Each delivery remains a distinct batch. FIFO consumes the oldest eligible stock first unless an explicitly known loss batch is selected.
- Stock-changing workflows use a database transaction and row locks. Partial sales or deductions must roll back completely.
- Money and weight calculations use decimal-safe values, consistent rounding, and backend verification.
- Completed sale items preserve price and cost snapshots. History is reversed or adjusted through new auditable records, not silently edited.
- Revenue, cost of goods sold, gross profit, operating expenses, and separately recognised losses remain distinct to prevent double counting.

## Time and locale

- Database and application timestamps use UTC.
- `BUSINESS_TIMEZONE=Africa/Lagos` controls business-facing dates and future report boundaries.
- Currency is Nigerian Naira and the default locale is English.

## Implemented data model through Module 13 review

A user has a unique email, optional unique username, optional phone number, hashed password, `administrator` or `cashier` role, active state, and Laravel timestamps. The application also contains the single-store business profile, categories, products, immutable stock receipts, independently costed batches, stock adjustments, stock movements, cost allocations, stock losses, stock-loss reversals, completed sales, sale items, payments, sale reversals, reversal items, refunds, expense categories, operating expenses, and expense correction revisions.

Administrators can create, view, search, paginate, edit, activate, deactivate, and reset passwords for cashier accounts. The staff module never accepts a role assignment from form data, never permanently deletes staff, and cannot target administrator accounts. Deactivation and administrator password resets invalidate the cashier's stored sessions and remember token.

Products contain catalog and selling-price data but no editable stock total or permanent purchase cost. Remaining batch quantities are the inventory authority. Receipt and adjustment submissions use unique idempotency keys, and FIFO deductions persist cost snapshots in allocation records.

Stock losses use the same batch balances and FIFO allocation records as other deductions. A known affected batch may be selected. Losses preserve purchase-cost financial values and never affect sales revenue. Reversals are separate one-time records that restore the exact original allocations without deleting or rewriting the loss.

Completed sales preserve cashier, transaction number, timestamps, product names, selling prices, selling methods, requested amount or quantity, exact displayed/deducted weight, payable item amount, weight-rounding reconciliation, FIFO purchase cost, payment method, and paid/completed statuses. Checkout is atomic and idempotent. Cashiers cannot change completed records or see purchase-cost data.

Sales history is scoped by policy: administrators see every store sale and cashiers only their own. Receipt rendering always uses saved sale values and the shared business profile. A reversal never overwrites or deletes the original sale or payment. It creates one reasoned reversal, item-level audit snapshots, and one refund. Verified sellable returns restore the exact original FIFO batch allocations and create positive sale-reversal movements; products not returned or unsuitable for resale do not change inventory again. Unique constraints and idempotency keys prevent duplicate refunds and stock restoration.

Operating expenses belong to editable categories and the administrator who recorded them. Amounts use `DECIMAL(24,2)`, creation is protected by UUID idempotency, and correction revisions preserve the reason, responsible administrator, timestamp, and only the values changed before and after the edit. Expense records never create stock batches, allocations, or movements. Reporting services expose daily, weekly, monthly, category, and arbitrary date-range totals independently from purchase cost and stock-loss valuation.

Dashboard and reporting calculations share Lagos-aware period boundaries. Valid revenue and FIFO COGS exclude every reversed sale. Gross profit is valid revenue minus valid sale-item purchase-cost allocations. Estimated net profit subtracts operating expenses and separately recognized inventory losses. Active stock losses contribute their allocated purchase cost; a refunded sale that was not returned to sellable inventory contributes its original allocated purchase cost as a separate inventory loss. A restored reversal contributes neither COGS nor loss, preventing double counting.

Inventory valuation is the decimal-safe sum of each remaining batch quantity multiplied by that batch's historical purchase cost. Sale items preserve category ID and category name snapshots in addition to product and price snapshots, allowing category analytics to remain stable after later catalog edits. Administrator report exports preserve raw two-decimal money and three-decimal kilogram values. Cashier dashboards and routes never expose purchase cost, profit, expense, stock-loss value, or store-wide sales.

Uploaded logos and product images accept only validated JPG, PNG, or WebP content within configured size and dimension limits. Randomly named originals are stored outside the web root and returned through controlled image-only responses with MIME allow-listing and `nosniff`. Script-like uploads are rejected and legacy public upload paths deny executable extensions.

Immutable business event times use UTC `DATETIME` columns. This avoids legacy MariaDB's implicit `ON UPDATE CURRENT_TIMESTAMP` behavior, which could otherwise change a batch's receiving date when its remaining quantity changes and invalidate FIFO ordering.
