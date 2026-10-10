# Development Instructions

## Scope and source of truth

- Read `Frozen Food Inventory and Sales Management System.md` before starting a module.
- Build only the requested module and its direct dependencies. Never start the next module automatically.
- Keep `docs/MODULE_ROADMAP.md` current, but mark a module complete only after its tests pass.
- Preserve historical transaction data; corrections in later modules must be auditable rather than destructive.

## Required stack

- PHP 8.2 and Laravel 12 only.
- MySQL in local and production environments; SQLite in-memory is allowed for automated tests.
- Blade, Tailwind CSS, Livewire 4, Alpine.js bundled with Livewire, and Vite.
- Do not introduce a JavaScript SPA framework, separate backend, Redis requirement, or unnecessary package.

## Architecture and data rules

- Follow Laravel conventions for controllers, Form Requests, middleware, policies, models, migrations, services, and tests.
- Put inventory and financial operations in focused service classes and execute multi-record changes in database transactions.
- Use row locks when deducting stock and treat the stock ledger and batch balances as authoritative.
- Store weights as `DECIMAL(12,3)` kilograms and money in decimal-safe columns/calculations. Never use unchecked floats.
- Use FIFO batch allocation for cost of goods sold and unassigned stock loss.
- Store database timestamps in UTC and convert business-facing dates to `Africa/Lagos`.
- Keep role checks on the backend. Hiding a link is not authorization.
- Do not seed or commit real credentials. When no administrator exists, the owner creates the first administrator through the one-time `/setup` screen; `php artisan app:create-administrator` remains a console fallback and closes after initial setup.
- Keep user-uploaded originals outside the web root. Permit only validated JPG, PNG, and WebP images, serve them through controlled routes with `nosniff`, and never add executable upload types.
- Active administrators and cashiers may process POS sales, but their dashboards, management routes, reports, and cashier-only history routes remain role-separated.

## Interface rules

- Design mobile-first for 320px and wider, then verify 360, 375, 390, 430, 768, 1024, and 1280px layouts.
- Reuse the Blade components in `resources/views/components` and maintain the deep-green, white-card design system.
- Use accessible labels, keyboard focus states, useful validation feedback, loading states, empty states, and confirmations.
- Use responsive cards on small screens when wide tables would harm usability.

## Quality gates

- Add feature tests for authentication, authorization, and every critical business workflow.
- Run `php artisan test`, `vendor/bin/pint --test`, and `npm run build` before completing a module.
- Run migrations against the intended environment and report precisely what was or was not executed.
- Run the suite on SQLite and a disposable MySQL schema before release; critical stock concurrency must be tested with genuinely competing processes.
- Do not claim responsive acceptance without a real browser pass at every width listed above.
- Keep `.env.example`, architecture documentation, installation instructions, and the module roadmap updated.
