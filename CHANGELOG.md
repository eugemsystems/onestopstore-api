# Changelog — onestopstore-api

All notable changes to this folder are recorded here, newest first.
Update this file in the same commit as every change.

## Tagging convention
- Semantic versioning tags `vMAJOR.MINOR.PATCH` (patch: fixes, minor: features, major: breaking). Versions start at 1.
- After a release-worthy commit: `git tag -a vX.Y.Z -m "summary" && git push origin vX.Y.Z`.
- `v1.0.0` is the first tag: tracking starts here. Earlier history is in `git log`.
- Never remove or rename existing API response fields (mobile app depends on them); only add.

---

## [v1.0.0] — 2026-11-23

### Added
- **Delivery options per product** (ported from raines laravel-api v1.3.1) — one product can be sold at several prices for different delivery times (e.g. local stock delivered fast vs imported stock delivered later). Option names are admin-editable; the first two rows added in admin are pre-named "Fastest Delivery" and "Best Price".
  - New table `product_delivery_options`; new columns `carts.delivery_option_id` and `order_products.delivery_option_id/label/text/price` (frozen snapshot). Migration `2026_11_23_100000_create_product_delivery_options_table`. (`app/Models/ProductDeliveryOption.php`)
  - Admin product edit: new **Delivery Options** tab (add/remove repeater); saving re-derives the product's own price/sale price/quantity/stock status from the cheapest in-stock option so search, filters, sorting, feeds and layby keep working. (`AdminProductController::update/syncDeliveryOptions`, `admin/products/edit.blade.php`)
  - Pricing/stock: `Helpers::getSalePrice` uses the chosen option's (sale) price; option stock is checked and decremented per option; the cart and checkout refuse a product that has options unless a valid in-stock option is chosen; guest-cart merge skips such lines instead of failing. (`Helpers.php`, `UtilityTrait`, `CartRepository`, `CheckoutTrait`)
  - Cart lines keyed by product + variation + delivery option; order lines store the option snapshot. (`Cart`, `OrderProductPivot`, `config/enums.php`)
  - Product API/search: `delivery_options` (and `has_delivery_options` in the search index) returned on lists and details — additive only; reindex with `php artisan es:reindex-products`. (`Product`, `ReindexProducts`)
  - Order emails/admin order page show the chosen option; CRM webhook payload carries `delivery_option_*`; CRM item-status sync/matching accepts `delivery_option_id`. (`orders-table.blade.php`, `admin/orders/show.blade.php`, `Crm/OrderResource`, `SyncOrderItemStatusToCrm`, `OrderController::updateItemStatus`)
- Not included: layby (uses the lowest option price), refund restocking per option, manual admin order creation, invoices/quotations. This repo has no order idempotency signature (`orderItemsSignature`), so that part of the Raines change was not needed.
- Verified locally against the dev database: option pricing, per-option stock decrement, separate cart lines per option, order lines with the option snapshot, product reindex, admin edit page render.

### Rollout
Run the migration, deploy onestopstore-crm migration first, then `php artisan es:reindex-products`, then deploy onestopstore-main.
