# Screenshot Capture Guide

Use authentic application pages only. Do not fabricate screens or include passwords, personal contact details, private customer information, `.env` values, browser extensions, developer tools, or unrelated desktop content.

## Gallery files

| File | Page | Viewport | State to show |
|---|---|---:|---|
| `01-login-desktop.png` | `/login` | 1440 × 900 | Branded login form with empty credentials |
| `02-admin-dashboard-desktop.png` | `/admin/dashboard` | 1440 × 900 | Dashboard cards, real charts, and navigation |
| `03-pos-desktop.png` | `/admin/pos` | 1440 × 900 | Product list and a sample cart before checkout |
| `04-inventory-desktop.png` | `/admin/inventory` | 1440 × 900 | Available stock, prices, and low-stock states |
| `05-reports-desktop.png` | `/admin/reports/profit` | 1440 × 900 | Date filters and calculated report totals |
| `06-receipt-print-preview.png` | Sale receipt print preview | 900 × 900 | Store branding and compact receipt layout |
| `07-pos-mobile.png` | `/admin/pos` | 390 × 844 | Touch-friendly product and cart layout |
| `08-notifications-mobile.png` | `/admin/notifications` | 390 × 844 | Read and unread inventory alerts |

## Capture checklist

1. Use a disposable/demo dataset with realistic product names and amounts.
2. Remove or replace personal business email, telephone, and address values.
3. Keep the browser zoom at 100% and hide bookmarks, extensions, and developer tools.
4. Capture PNG images at the exact viewport dimensions above.
5. Confirm that no loading overlay, validation error, raw JSON, or debugging output is visible.
6. Review each image at full size before adding it to Git.
7. Uncomment the screenshot gallery block in the root `README.md` only after all referenced files exist.

The mobile screenshots also form part of the responsive acceptance pass documented in [`docs/TESTING.md`](../TESTING.md).
