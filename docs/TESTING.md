# Testing and Acceptance Guide

## Automated gates

Run from the project root:

```powershell
php artisan test
vendor\bin\pint --test
php artisan view:cache
npm run build
composer validate --strict
composer audit
npm audit --audit-level=moderate
```

The normal PHPUnit configuration uses an in-memory SQLite database and cannot modify local MySQL data. Before release, also run the suite against a separate disposable MySQL schema. Never point a destructive test command at the live application database.

The application-readiness test reconciles this exact flow: receive 100 kg, receive 50 kg, sell 2.5 kg, sell 5 kg, record 3 kg spoiled, receive another 30 kg, and sell 1.5 kg. Expected stock is 168.000 kg; all three receipts remain independent, and batch balances, movement deltas, loss cost, and inventory valuation must agree.

`tests/Support/concurrent_sale_worker.php` supports a real two-process MySQL race. The Module 13 review ran competing 7 kg and 4 kg sales against 10 kg: one committed, one received `InsufficientStockException`, the remaining balance was 6.000 kg, and no batch became negative.

## Security acceptance

- Verify `/setup` is available only while no administrator exists, registration is absent, and login throttling works.
- Verify guests redirect to login and cashier/admin cross-role URLs return 403.
- Deactivate a logged-in cashier and confirm their next authenticated request is denied.
- Upload a valid JPG, PNG, and WebP within configured limits; verify the file is stored under `storage/app/uploads` and served with `nosniff`.
- Attempt PDF, PHP/script content, oversized, over-dimensioned, and misleading-extension uploads; each must be rejected and no file should be stored.
- Verify `.env`, `storage/app/uploads`, logs, and backups cannot be downloaded through the web server.
- Confirm `APP_DEBUG=false`, HTTPS, secure session cookies, protected report exports, CSRF tokens, and non-default production credentials.

## Browser and responsive acceptance

Use a real browser, not only HTML assertions. Test administrator and cashier sessions at 320, 360, 375, 390, 430, 768, 1024, and at least 1280 pixels wide.

At every width confirm:

- no horizontal page overflow;
- navigation opens, closes, and remains touch-friendly;
- profile, notification, confirmation, and searchable-combobox overlays remain within the viewport;
- stock-loss filters align cleanly on desktop and stack on mobile;
- searchable selectors accept typing directly in the visible field and submit the chosen ID;
- login password Show/Hide works without changing the entered value;
- administrator and cashier POS carts add/update/remove items without an exception overlay;
- payment selection, cash tendered/change, checkout confirmation, and role-appropriate redirect work;
- logo and product images load from the same host and receipts print cleanly at standard and 80 mm sizes.

## Manual business reconciliation

Create a disposable product with known costs and reproduce the readiness flow. Compare:

- sum of positive remaining batches = product available stock;
- sum of signed movement quantities = available stock from an empty opening balance;
- FIFO allocations use oldest immutable receiving timestamps;
- inventory value = each remaining batch quantity multiplied by its own purchase cost;
- reversed sales are absent from revenue/COGS; non-restocked refunds become inventory loss once; restored returns do not;
- operating expenses and stock losses are subtracted once in estimated net profit.

Keep screenshots or an acceptance checklist for the final browser/device pass.

