# Complementary (Free) Item — Feature Plan

Goal: let an Admin/Manager mark **one cart line item as free** (price → 0) for a
customer, end-to-end (billing, kitchen, running-order reload) — kept as small
as possible for v1.

**Status: implemented on branch `feature/complementary-item`.** See §9 for
what was actually built and known gaps found during implementation.

## 1. Design decision: reuse the existing discount pipeline

Researched the live discount flow (`frequent_changing/js/pos_script_v7.3.js`,
`application/controllers/Sale.php`) before designing anything new:

- The cart's per-row "Amt or %" box is **display-only** (`disabled`, never
  toggled). The real editable discount lives in the pencil "edit item" modal
  (`#modal_discount`, `main_screen.php:1848`), gated by permission `#pos_4`.
- Line total = `original_price − discount_amount`
  (`set_all_visible_discounted_item_price`, `pos_script_v7.3.js:11435`).
- **Tax is computed off that already-discounted line total**
  (`get_total_vat`, `:11523`), and cart subtotal sums the discounted line
  totals (`get_total_of_all_items_and_modifiers`, `:11469`).
- The server (`Sale::push_online`, `Sale::add_kitchen_sale_by_ajax`) does
  **not** recompute money — it trusts and stores whatever
  `menu_price_with_discount` / `discount_amount` / `discount_type` the client
  sends (`trim_checker()` only trims, doesn't calculate).
- KOT / kitchen panel never render price at all (`print_kot_popup_print`,
  `Kitchen_model.php`) — confirmed no price field anywhere in that path.

**Consequence:** if "Complementary" is implemented as *a forced 100% discount
on that line*, tax and subtotal already zero out correctly for free with zero
new arithmetic, and nothing on the kitchen side needs to change. We don't
need a separate "free price" code path — we need a **toggle that drives the
existing discount fields to 100%, plus a flag to remember it was
Complementary (not a manual discount)** for UI/reporting/audit purposes.

### ⚠️ Naming collision to avoid
`tbl_sales_details.is_free_item` **already exists**, but it means something
else: it's set from the BOGO-style promotion "free item" flag (`is_free` in
JS, `application/controllers/Sale.php` ~2109-2139), and in the kitchen table
it's overloaded to store the *previous item's row id*, not a boolean. **Do
not reuse this column.** Use a new column name: `is_complementary`.

## 2. Scope (v1 / MVP)

In scope:
- Per cart-row toggle button, Admin/Manager only.
- Forces that line's discount to 100% of its price, tags it as Complementary.
- Flows through to billing (`tbl_sales_details`) and kitchen
  (`tbl_kitchen_sales_details`) tables.
- Visual marker in the cart (badge + strikethrough price).
- Survives running-order reload (reopen a held/running order still shows the
  item as Complementary, not just discounted).

Out of scope for v1 (call out, don't build):
- Report changes. `productAnalysisReport()` (net, uses
  `menu_price_with_discount`) will correctly show ₹0 automatically.
  `foodMenuSales()`/`totalFoodSales()` (Top Selling / Food Sales reports) use
  **gross** `menu_price_without_discount` and will keep showing full value —
  acceptable for v1, revisit if the client wants a "comp'd value given away"
  report.
- KOT/kitchen "FREE" badge — cosmetic only, kitchen doesn't show price today
  and doesn't need to. Can add a small `(Comp)` tag later, same pattern as
  the existing `VOID` badge (`pos_script_v7.3.js:1443`), if requested.
- Making individual **modifiers** free — v1 only zeroes the base item price;
  modifiers keep their price unless separately discounted.
- Capturing a "reason" note for the comp (e.g. "manager comp", "wrong dish").
  Easy follow-up if wanted — reuse the item-note field.

## 3. Data model

New additive migration, same style as the existing
`Update/void_kot_delta_migration.sql`:

```sql
ALTER TABLE tbl_sales_details
    ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_free_item;

ALTER TABLE tbl_kitchen_sales_details
    ADD COLUMN is_complementary TINYINT(1) NOT NULL DEFAULT 0 AFTER is_free_item;
```

## 4. Backend changes

- `application/helpers/my_helper.php`: new `canGiveComplementaryItem()`,
  same shape as existing `canVoidOrderedItem()` (Admin role OR designation in
  Admin/Super Admin/Manager) — reuses a pattern the client has already
  accepted twice (void-qty gate, clear-cart-without-password gate).
- `application/views/sale/POS/hidden_input_html.php`: new hidden input
  `#can_give_complementary`, same convention as `#can_void_order_item`.
- `application/controllers/Sale.php`:
  - `push_online()` (~2109-2139): read `$item->is_complementary` from the
    posted item JSON, write to the new column — one line, next to the
    existing `is_free_item` assignment.
  - `add_kitchen_sale_by_ajax()` (~1303-1307): same, one line, kept separate
    from the existing `is_free_item` (previous-id) logic.
- `application/models/Common_model.php`: add `is_complementary` to the
  running-order "modify/reload" select list
  (`getAllItemsFromSalesDetailBySalesIdModify`) so reopening a held/running
  order can restore the badge state client-side.

## 5. Frontend changes (`pos_script_v7.3.js` + `main_screen.php`)

- Cart row template (4 creation sites, same pattern as the discount box:
  `:5572`, `:7448`, `:13236`, `:14942`): add one small toggle
  button/icon per row (e.g. next to the existing pencil edit icon) and one
  hidden data-carrier span `item_is_complementary_table<id>`, mirroring the
  existing `item_discount_table<id>` convention.
- Toggle handler (new):
  - Checks `#can_give_complementary` first; if 0, `toastr.error(...)` and
    stop (same guard shape as the `#pos_4` / void-qty checks).
  - **On**: set that row's `percentage_table_<id>` value to `100%`, set the
    hidden `is_complementary` marker to `1`, disable the discount modal entry
    for that row (Complementary overrides manual discount), add a `.is-comp`
    CSS class (strike price + "FREE" badge), then call the existing
    `do_addition_of_item_and_modifiers_price()` to recompute subtotal/tax —
    no new math functions needed.
  - **Off**: clear discount back to `0`, clear the marker, remove the class,
    recompute.
- Serialization: add `is_complementary` (0/1) to the per-item payload built
  before AJAX, at both existing serialization sites — the `push_online`
  payload (~`:8377-8524`, alongside the already-present `is_free`) and the
  `add_kitchen_sale_by_ajax` payload.
- Running-order reload: when rebuilding cart rows from a modify/reopen
  response, if the row's `is_complementary` came back `1`, apply the same
  `.is-comp` class + hidden marker so the badge survives (mirrors how
  `p_qty_<id>` is restored today for the void-qty feature).

## 6. Language keys (all 4 files, same convention as prior features)

`application/language/{english,french,spanish,arabic}_lang.php`:
- `complementary` — button/badge label ("Complementary" / "FREE")
- `mark_as_complementary` — tooltip/confirm text
- `complementary_only_admin_manager` — permission-denied toastr message

## 7. Decisions (confirmed with client)

1. **Trigger UI**: direct toggle icon on the cart row (next to the existing
   pencil edit icon) — one click, no modal.
2. **Discount conflict**: turning Complementary **on** silently clears any
   existing manual discount on that row and forces it to 100% — no warning.
3. **Confirmation**: Admin/Manager permission gate (`canGiveComplementaryItem`)
   is sufficient — no extra confirm popup, same as the existing `#pos_4`
   discount-modal gate.

## 8. Manual test checklist (post-implementation)

- Non-admin waiter login: toggle hidden/blocked with permission message.
- Admin marks item Complementary → line total, subtotal, tax, grand total
  all drop correctly; KOT still prints qty/name/note normally, no price.
- Place order → reopen the running order → Complementary badge still shown,
  price still 0.
- Un-mark Complementary → original price restored, totals recalculate.
- Complementary item + modifiers → base item free, modifier price unaffected
  (as scoped).
- Check `tbl_sales_details.is_complementary` and
  `tbl_kitchen_sales_details.is_complementary` populate correctly after
  settlement.
- Confirm `tbl_sales_details.is_free_item` (unrelated BOGO promo flag) is
  untouched by this feature.

## 9. What was actually built (implementation notes)

- Migration applied directly to the local `restodb` database (both columns
  confirmed via `DESCRIBE`), plus committed as
  `Update/complementary_item_migration.sql`.
- `canGiveComplementaryItem()` added to `my_helper.php` (mirrors
  `canVoidOrderedItem()` exactly); exposed via `#can_give_complementary`,
  `#complementary_only_admin_manager`, `#mark_as_complementary_lang`,
  `#remove_complementary_lang` hidden inputs.
- `Sale.php` writes `is_complementary` in all three places that already write
  `is_free_item`: `add_kitchen_sale_by_ajax()`, `push_online()`, and the
  order-split path (`add_sale_by_ajax_split`, copies the flag from the
  existing row like it already does for `is_free_item`).
- **No `Common_model`/`Sale_model` query changes were needed** — the
  modify/reload queries (`getAllItemsFromSalesDetailBySalesIdModify` etc.)
  already `select('*')`, so `is_complementary` flows through automatically
  once the column exists.
- JS (`pos_script_v7.3.js`): hidden per-row marker
  `#item_is_complementary_table<id>` + a `.comp_item_toggle` gift icon added
  at all 4 cart-row-template sites. One delegated click handler
  (`.comp_item_toggle`, near the existing `.removeCartItem` handler) does the
  permission check, sets `#percentage_table_<id>` to `100%` (or clears it),
  toggles the marker/badge, and calls the existing
  `do_addition_of_item_and_modifiers_price()` — no new price math. Payload
  serialization for the **Place Order** and **Quick Invoice** flows now sends
  `is_complementary`. CSS badge (`FREE` tag + strikethrough) added to
  `assets/POS/css/custom_pos.css`.
- **Known gap — Hold / Draft round-trip**: the Hold-order flow
  (`#hold_sale`/`#hold_cart_info`) builds its own separate item JSON block
  and was **not** patched to carry `is_complementary`. Practical impact is
  small: the 100% discount itself (the actual free pricing) already survives
  a hold/resume cycle because it rides the pre-existing generic discount
  fields, which Hold does serialize. What's lost on a resumed held order is
  only the **Complementary badge/tag** — the item still shows ₹0, just
  without the green "FREE" marker until re-toggled. Fix later by mirroring
  the same one-line change (read `#item_is_complementary_table<id>`, append
  `"is_complementary"` to the item JSON) in the Hold builder
  (~`pos_script_v7.3.js` `let order_info = "{"` block feeding
  `add_hold_by_ajax`) and in `get_details_of_a_particular_hold`'s restore
  template.
- Reports (§2 "out of scope") confirmed unchanged, as designed:
  `productAnalysisReport()` will show ₹0 net automatically;
  `foodMenuSales()`/`totalFoodSales()` will still show gross value.
