# Staff Meal — Feature Notes

Goal: let an Admin/Manager settle a **running order** as a *Staff Meal* — the
percentage the client configures in Settings comes off the bill, the sale is
tagged as a staff meal, and no customer invoice is printed.

**Status: implemented.** Migration `Update/staff_meal_migration.sql` is a hard
prerequisite — the feature is invisible (button hidden) until the columns exist
and a percentage is set.

## 1. Design decision: ride the existing finalize-discount pipeline

The payment/finalize modal already supports an ad-hoc discount at settle time:
`#sub_total_discount_finalize` (flat amount) →
`set_finalize_discount()` recomputes `#finalize_total_payable`/due →
`close_order()` / `update_order_status_to_invoiced()` carry it to the server →
`Sale::push_online()` / `Sale::update_order_status_ajax()` subtract it from
`total_payable` and add it to `sub_total_discount_amount` /
`total_discount_amount` (`application/controllers/Sale.php:3105-3115`).

So Staff Meal = **pre-fill that existing discount field with X% of the order
total, plus a flag that says why**. Zero new price math, zero report math, tax
and subtotal untouched (the discount lands on total payable exactly like a
manually keyed finalize discount).

## 2. Scope

In scope:
- Global percentage setting (per company) in Settings.
- `Staff Meal` button in the POS left panel, above `Modify Order`, acting on the
  **selected running order**.
- Confirmation popup showing total → discount → payable, then the normal
  payment modal (user picks the payment method and submits).
- No invoice print for staff meal settlements.
- `Staff Meal` tag in the admin Sale list.

Out of scope (deliberate):
- Which employee ate — no staff/employee picker. Add later against
  `tbl_users`/`tbl_employees` if the client asks.
- Split bill + staff meal (staff meal always takes the single-pay path).
- Separate report. Staff meal money shows up inside the normal discount totals
  of existing reports; `tbl_sales.is_staff_meal` is there to build a dedicated
  report later.
- Cart-level staff meal before Place Order (decided: running order only).

## 3. Data model — `Update/staff_meal_migration.sql`

```sql
ALTER TABLE tbl_companies
    ADD COLUMN staff_meal_percentage DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER service_amount;

ALTER TABLE tbl_sales
    ADD COLUMN is_staff_meal TINYINT(1) NOT NULL DEFAULT 0 AFTER order_status;
```

`Sale_model::make_query()` selects `tbl_sales.*`, so the new column reaches the
sale list with no query change.

## 4. Backend

- `application/helpers/my_helper.php`
  - `canGiveStaffMeal()` — Admin role OR designation Admin/Super Admin/Manager
    (same shape as `canVoidOrderedItem()` / `canGiveComplementaryItem()`).
  - `getStaffMealPercentage()` — reads `tbl_companies.staff_meal_percentage`
    via `getCompanyInfo()`, clamped 0–100.
- `application/controllers/Setting.php::index()` — saves
  `staff_meal_percentage` (clamped 0–100) next to `service_amount`.
- `application/views/authentication/setting.php` — percentage input with
  tooltip, next to Delivery Amount.
- `application/controllers/Sale.php`
  - `push_online()` — writes `is_staff_meal` from the posted order object
    (settle path used when the bill is created at payment time).
  - `update_order_status_ajax()` — writes `is_staff_meal` from POST (settle
    path used when the bill row already exists).
  - Both re-check `canGiveStaffMeal()` server side, so the flag can't be forged
    from a non-manager session.
  - `getAjaxData()` — green `Staff Meal` badge appended to the Sale No column.

## 5. Frontend

- `hidden_input_html.php` — `#can_give_staff_meal`, `#staff_meal_percentage_value`,
  the three lang strings, plus two runtime carriers: `#is_staff_meal_sale`
  (1 only while a staff meal settlement is in flight) and
  `#staff_meal_discount_hidden` (amount computed in the confirm popup).
- `main_screen.php` — `#staff_meal_order` button (rendered only when the
  percentage > 0) and `#staff_meal_confirm_modal`.
- `pos_script_v7.3.js`
  - `#staff_meal_order` click: requires a selected running order, checks the
    permission and the configured percentage, reads the order from IndexedDB,
    fills and opens the confirm modal.
  - `#submit_staff_meal`: closes the popup, opens the normal payment modal the
    same way the Invoice button does (Dine In additionally clicks *Single Pay*),
    then `apply_staff_meal_on_payment_modal()` polls until the modal is up and
    sets the discount + `#is_staff_meal_sale`.
  - `print_invoice_and_close()`: reads and clears the flag up front, passes it
    to `close_order()` / `update_order_status_to_invoiced()`, and **skips
    `print_invoice()`** when it is a staff meal.
  - The flag is cleared whenever a normal invoice modal opens
    (`#create_invoice_and_close`, `.invoice_btn_class`) and in
    `reset_finalize_modal()`, so it can never leak into the next sale.

## 6. Language keys (all 4 files)

`staff_meal`, `staff_meal_percentage`, `staff_meal_percentage_tooltip`,
`staff_meal_discount`, `payable_after_discount`, `staff_meal_confirm_msg`,
`staff_meal_applied`, `staff_meal_only_admin_manager`,
`staff_meal_not_configured`.

## 7. Manual test checklist

- Apply the migration, set e.g. `30` in Settings → Staff Meal Discount (%).
- Admin/Manager: POS shows the Staff Meal button above Modify Order.
- Waiter/cashier login: button visible but clicking shows the permission error
  (the money gate is also enforced server side).
- Percentage `0`: button not rendered at all.
- Take Away / Delivery running order → Staff Meal → popup shows total, 30%,
  payable → Submit → payment modal opens with the reduced payable, discount
  pre-filled → choose method → Submit → order leaves the running list, **no
  invoice print**.
- Dine In running order → same, via the automatic Single Pay step.
- Sale list: the settled bill carries the green `Staff Meal` tag; total payable
  and discount columns show the reduced/increased amounts.
- Do a normal (non staff meal) invoice right after → no tag, invoice prints as
  usual (flag must not leak).
- Cancel the confirm popup → nothing changes, order still running.
