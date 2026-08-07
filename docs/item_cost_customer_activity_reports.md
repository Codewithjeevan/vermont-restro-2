# Two new reports: Item Wise Cost & Customer Activity

Both live under **Report** in the sidebar and follow the same shape as every other
report in the app: a filter row that POSTs back to itself, one `#datatable` with the
DataTables export/print buttons, and a totals row in `<tfoot>`.

**Deployment step (required):** run `Update/item_cost_customer_activity_migration.sql`
once per database, then **log out and back in**. The migration only registers the two
menus in `tbl_access`; the session's `function_access` list is built at login, so an
already-open session will not show the new menu items (the direct URLs still work for
Admin, because `checkAccess()` short-circuits on the Admin role).

| file | what changed |
| --- | --- |
| `Update/item_cost_customer_activity_migration.sql` | registers access ids 358/359 and 360/361 under main module 8 (report) |
| `Report::__construct()` | maps `itemWiseCostReport` → 358 and `customerActivityReport` → 360 for `checkAccess()` |
| `Report::itemWiseCostReport()` / `Report::customerActivityReport()` | the two controller actions |
| `Report_model::itemWiseCostReport()` | one row per sold food menu |
| `Report_model::customerActivityReport()` | one row per customer (bill counts / money / first+last visit) |
| `Report_model::customerActivityItems()` | one row per customer + food menu, qty desc |
| `application/views/report/itemWiseCostReport.php` | view |
| `application/views/report/customerActivityReport.php` | view |
| `application/views/userHome.php` | the two sidebar entries |
| `application/language/*/`\*`_lang.php` | 15 new keys, all four languages |

## 1. Item Wise Cost Report — `Report/itemWiseCostReport`

Filters: start date, end date, category, product type (Regular / Combo / Product),
plus the outlet selector on multi-outlet installs.

| column | source |
| --- | --- |
| Qty of Sale | `sum(tbl_sales_details.qty)` |
| Cost | `tbl_food_menus.total_cost` |
| Cost Total Amount | Cost × Qty of Sale |
| Sale Price | `tbl_food_menus.sale_price` (the Dine In price on the menu) |
| Total Sale Price | Sale Price × Qty of Sale |

**Where "Cost" comes from — and why it can read 0.** `tbl_food_menus.total_cost` is the
cost kept on the menu itself: for a normal menu it is the grand total of the recipe
ingredients, for a direct product (`product_type = 3`) it is the purchase price. Both are
written by the Add/Edit Food Menu screen. If a restaurant never entered ingredients or a
purchase price the column stays `0`, and this report will show `0.00` cost for that item —
that is missing master data, not a report bug. `productAnalysisReport` reads the same
column and behaves the same way.

Because the cost lives on the menu and not on the sale line, the figure is the cost **as of
today** applied to historical quantities. Re-costing a recipe changes what past periods
report. There is no per-sale cost snapshot anywhere in the schema to use instead.

**Sale Price is the menu's Dine In price, not what was billed.** It mirrors the Cost column:
both read the current master value off `tbl_food_menus`, so Total Sale Price is a list-price
valuation of the quantity sold, not revenue. It ignores take-away / delivery price tiers,
per-line discounts, and any price change since the sale. Use the sale reports for actual
billed amounts.

## 2. Customer Activity Report — `Report/customerActivityReport`

Filters: start date, end date, customer (blank = all), plus the outlet selector.

One row per customer: total visits (settled bills), total qty purchased, unique items,
**most purchased item** with its qty, total purchase amount, average order value, due,
first visit and last visit.

Pick a single customer and a second table appears underneath — everything that customer
bought in the period, qty desc: item, category, times ordered, qty, amount, last ordered.
That table is deliberately not rendered for "all customers"; it would be one block per
customer with no way to tell them apart.

The favourite item is not a second query. `customerActivityItems()` already returns the
customer + food menu rows sorted `total_qty DESC`, so the controller buckets them by
customer and takes the first row of each bucket. The same buckets give the qty and
unique-item counts, and the bucket for the selected customer is the detail table.

## Scoping rules both reports share

Money and quantities come from the **billing** side (`tbl_sales` / `tbl_sales_details`),
never the kitchen copy, and are limited to settled bills:

* `tbl_sales.order_status = '3'` — a running or due order is not revenue yet
* `del_status = 'Live'` on both the sale and the sale line — this is what drops voided lines
* `outlet_id` on the same table the row comes from

Dates filter on `tbl_sales.sale_date` (a `varchar` day stamp, `Y-m-d`), matching every
other report. Deliberately **not** `date_time`: the app timezone, MySQL and the OS clock
disagree on this deployment, and a `date_time` comparison drops sales made "later today".

Staff meals and complementary items are included. They are real quantities that consumed
real cost, and the money side already carries their discount.
