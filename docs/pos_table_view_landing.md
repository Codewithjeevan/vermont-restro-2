# POS Table View landing + "More menus" toggle — Feature Notes

Goal (client request, 2026-09-24): when a user opens the POS the **table
selection shows first**; tapping a table goes to the item / cart screen with
that table set. Take Away / Delivery are still one tap away. Second part: the
POS had both a **Dine In** and a **Table** button doing the same thing, and
the top icon bar (register, reports, hold, recent sales, ...) ate space, so
the Table button is now a **More menus** toggle that expands / collapses that
bar and gives the POS the room.

**Status: implemented** on `main` (no migration, no new tables).

## 0. Update 2026-09-28: flat grid look (SambaPOS style)

The client did not like the floor-plan look (table pictures on a tiled floor)
and sent a SambaPOS screenshot: one flat square tile per table, running
tables orange with the total and the minutes since the order. UI only, the
flows below are unchanged.

- **Tiles come from `tbl_tables` live**, no longer from the floor designer's
  saved HTML (`tbl_areas.table_design_content`). `Sale_model::getPosTablesByArea()`
  (Live tables of the company, grouped by `area`, natural name order so `2`
  comes before `10`) → `$area_tables` from `Sale::POS()` → `main_screen.php`
  builds each area's `.set_design` with the **same tile depth** as the
  designer (`#canvas > .table_box > .div_rectangular > .get_table_details`,
  the legacy handlers walk `.parent()` chains). A table added, renamed or
  deleted shows at once; the designer's positions are ignored by the POS
  (the designer page still works, it just has no effect on the POS).
  The waiter app also loads this view but passes no `$area_tables` → guarded
  with `isset`.
- Tile font size by name length: `pos_tile_xl` (≤3 chars), `pos_tile_l` (≤7),
  `pos_tile_m` (≤10), `pos_tile_s`.
- **Running tile** (`posTableView.renderRunningTile`, called from
  `setOrderTabless`): table name, total (number only, the tooltip has the
  currency / waiter / order no.), age of the oldest order ("2 min.",
  "1 h 35 min."). Several running orders on one table → the tile shows their
  sum and a count badge. Area "ordered" colours still win when configured
  (inline style), otherwise orange `#f0913a`.
- Age: `displayOrderList()` rows carry `data-date_time`;
  `posTableView.parseOrderTime()` reads both "2026-09-11 9:04:43 PM" and
  "2026-09-11 21:04:43" as a wall clock and compares with `serverNow()` (same
  frame, no clock-skew). The labels refresh on the POS 7s tick
  (`all_time_interval_operation` → `posTableView.tickTimes()`); a separate
  `setInterval` would be killed by `reset_time_interval()`.
- `signature()` now includes the order total, so items added to a running
  order (here or on another terminal) redraw its tile.
- Layout: grid on top, a **bottom bar** with the areas (left) and the quick
  actions (right). The legend / hint row and the "Area/Floor" heading are
  gone. `fitCanvas()` became `refreshScroll()` (only perfect-scrollbar
  `update()`, the grid flows). The company floor texture (`table_bg_1/2`)
  is hidden in the view.
- New lang keys (4 languages): `min_short`, `hour_short`.
- Tested headless at 1366/1024/800: real order INV-1-327 on G15 → tile
  `G15 / 27.00 / 0 min.`, minute label advanced on the tick, armed Transfer,
  plain tap opens it as Update Order, cancelled afterwards; no exceptions.
- Test gotcha: the page has **two jQuery instances**; `window.$(...).trigger()`
  from a CDP expression does not reach the POS script's delegated handlers.
  Use native `element.click()`.

## 0b. Update 2026-09-28: back to the tables after every action

Client ask: after an order on a table is placed / updated / paid / cancelled
(any cart button), the POS goes back to the Table View by itself. **Take
Away stays on the cart** (counter sales one after another); the cashier
goes back with Dine In. Delivery is treated like Dine In (goes back).

One entry point, `posTableView.returnAfterAction(order_type)`: does nothing
unless `order_type` is 1 (dine in) or 3 (delivery) and `shouldLandOnTables()`;
clears the picked table, opens the view, forces a redraw on the next
running-orders render and calls `displayOrderList()` after 700 ms (the
action's IndexedDB write / delete can land after the first render, a
paid table would stay orange until the next sync). The order type is always
read **before** the action resets the cart to the session's default type:

| action | hook | order type from |
| --- | --- | --- |
| Place / Update Order, Quick Invoice | end of `add_sale_by_ajax()`, only when `action_type` is 1/2 (the other callers pull waiter / online orders in the background) and no payment modal follows (new order + Quick Invoice or pre-payment → after the payment instead) | `sale.order_type` |
| payment (Submit in `#order_payment_modal`, incl. Staff Meal) | `#finalize_order_button`: after `print_invoice_and_close()`; split bill only after the last split | `#order_<id>` row `order_type`, read at the top |
| payment modal Cancel | `#order_payment_modal .cancel`, only with an empty cart (Quick Invoice placed the order already) | row of `#last_future_sale_id` |
| cart Cancel | `clearPosCartConfirmed()`; with an empty cart (table picked, nothing added) the `#cancel_button` else branch | `cartOrderType()` |
| Draft | `add_hold_by_ajax()` success; the type is read in `#hold_cart_info` **before** it unselects the buttons and passed as a 3rd param | `cartOrderType()` |
| Cancel Order / Close Order / Order Details cancel | end of `cancel_order_by_click()`, `close_order_by_click()`, the `#order_details_close_order_button` swal | row `order_type` |

Legacy bugs fixed on the way (all hit the new flow):
- **Cart Cancel / Cancel Order / Close Order did not reset `#update_sale_id`**:
  the next Place Order (another table, a take away) *overwrote the running
  order that had been open in the cart*. Reset wherever the cart is emptied.
- **Paying an order that is open in the cart left it in the cart** (items +
  `#update_sale_id`): `posTableView.dropFromCart(sale_id)` after the payment.
- **`createAnimation()` toggled `.main_left` / `.overlayForCalculator`** 5-6 s
  after every placed order: on the desktop POS that opened a transparent
  full-screen overlay (z 10) that ate the next click, e.g. the first item
  tapped after reopening a table. It now only closes the panel when it is
  open (the waiter app opens it on purpose).
- `#finalize_order_cancel_button` belongs to the old, unused
  `#finalize_order_modal`; the live payment modal is `#order_payment_modal`
  whose Cancel is the generic `.cancel` (`#cancel_discount_modal`, an id
  reused by several modals).

Tested headless (G11-G14, the client's own INV-1-328 on G01 untouched): place,
update with a new item (tile total 27 → 39), empty-cart Cancel, Take Away
place + Cancel Order (stays on cart), Draft, Quick Invoice → payment Cancel,
armed quick-action Cancel, Cancel Order with the order open in the cart,
payment from the cart (cart emptied, next table starts clean). No exceptions.
Two real 3.00 test sales were paid: **INV-1-339, INV-1-340**. Test orders were
cancelled, test drafts deleted.

## 1. What changed for the cashier

| before | after |
| --- | --- |
| POS opens on the cart / items | POS opens on the **Table View** (areas on the left, floor plan on the right) |
| `Dine In` and `Table` both opened the centred "Tables" popup | `Dine In` opens the Table View; the `Table` button is gone |
| top icon bar always visible | bar collapsed by default; **More menus** (4th button of the order-type row, and in the Table View header) toggles it. Remembered per browser |
| picking a table gave no visible feedback | the `Dine In` button shows the cart's table as a badge (`Dine In  G05`): the picked blank table, or the running order's table (`#update_table_text`) while an order is open for modification (tapped running table, Modify Order, transfer). Hidden when Dine In is not the selected type. One hoisted `updateDineInBadge()` (not in `posTableView`: `arrange_info_on_the_cart_to_modify()` also runs at start-up for `#edit_sale_id`, before that const exists) |
| running tables looked like blank ones unless the area had "ordered" colours configured (never copied to the JS, and NULL on every area here) | running tables are orange (`.pos_table_running`) with total + age, see §0 (first version: red ring + waiter / order no.); area colours are now copied to the hidden inputs too |
| tapping a running table only selected it; the cashier then had to pick a quick action | tapping a running table **opens the order** (Running Orders row selected + Modify Order) |
| quick actions: select table, then click the action | click the action first (it is "armed", blue toast), then tap the running table |

Header of the Table View: `Back` (closes the view, cart keeps its current
order type), `More menus`, refresh icon (same as the Running Orders refresh),
`Delivery`, `Take Away` (switch the order type and close the view). Counts
"N running · M free" come from the floor plans + the Running Orders sidebar.

The landing is skipped for self-order / online-order sessions, when the POS
is opened to edit one sale (`#edit_sale_id`), and when no area is configured.

## 2. How it is built (no rewrite of the table popup)

The floor plan, the area tabs, the booked-table rendering (`setOrderTabless`),
transfer table and the quick actions all live in the old popup
`#show_tables_modal2`, and several of its handlers close it by walking
`.parent()` chains (7 parents from a table tile, 8 from a quick action) up to
the modal. So the popup's **inner markup is untouched**; only its position
changed:

- `application/views/sale/POS/main_screen.php`
  - the modal block moved from the end of the page into a new
    `<div id="pos_stage">` that wraps `#main_part`;
  - its `<h1>` became the Table View header (Back / More menus / refresh /
    Delivery / Take Away, counts; the legend + hint row was dropped in §0); the bottom Cancel bar and
    the X went away (Back replaces them);
  - `#table_button` in `.button_holder` replaced by `.pos_more_menus_toggle`
    (the waiter-app `#table_button`s in `.waiter_customer` / waiter modal
    are untouched);
  - `<span id="pos_dine_in_table">` badge inside the Dine In button;
  - a 3-line `<script>` in `<head>` puts `pos_header_open` /
    `pos_header_collapsed` on `<html>` from `localStorage.pos_more_menus_open`
    before paint (no flash of the bar).
- `assets/POS/css/custom_pos.css` (cache-busted by `?v=filemtime`)
  - `#show_tables_modal2` is `position:absolute; inset:0` inside `#pos_stage`
    (edge to edge, no radius / shadow / inner padding),
    `display:none !important` unless `.active`, no transform / animation,
    `z-index:50` so `.pos__modal__overlay` (99) still dims it when a quick
    action opens another modal;
  - `html.pos_header_collapsed .top_header_part` hidden,
    `html.pos_header_open .top_header_part` forced visible (also below 1300px
    where the stock CSS hides it for the mobile toolbar; self-order mode is
    excluded).
- `frequent_changing/js/pos_script_v7.3.js` — block
  "POS table view (landing screen)" (`const posTableView`, just before
  `reset_table_modal()`), plus small hooks in existing code:
  - `displayOrderList()` cursor-end → `posTableView.onRunningOrdersRendered()`:
    re-draws the active area only when the running-order signature
    (sale_id:table_id:sale_no list) changed, or after the refresh icon, and
    never while a tile is selected / a transfer is in progress;
  - `.div_rectangular` click, booked branch (was empty) →
    `onRunningTableTapped()`; blank branch → `onBlankTablePicked()` (forces
    the Dine In order type when the landing started with another default,
    same state changes as the Dine In handler minus the popup);
  - `.set_quick_action` with no active table → `armQuickAction()` instead of
    the "please select a table" error; the armed id is applied by
    `onRunningTableTapped()` by re-triggering that quick action once the
    tile is active, so the legacy handler code runs unchanged (Transfer
    Table: arm → tap source → tap free target);
  - Take Away / Delivery branches of the order-type handler →
    `clearPickedTable()` — the order JSON always sends `#hidden_table_id`,
    so a table picked on the landing must not ride along on a take-away;
  - `.get_area_table` click now copies `data-ordered_*_color` to the
    `#ordered_*_color_hidden` inputs (they were never written before);
    `setOrderTabless` adds `.pos_table_running`.
  - landing: `posTableView.open()` in a `setTimeout(0)` at the end of the
    IIFE section — the area / tile handlers are bound further down the file.
  - (superseded by §0: the floor plan is a grid now, `fitCanvas()` became
    `refreshScroll()`, and the running tile shows total + age, the order
    number is in its tooltip.)

## 3. Gotchas

- `#show_tables_modal2` keeps the `modal` class on purpose: the overlay click
  handler `$(".modal").removeClass("active")` and the `inActive` juggling
  still target it. The legacy open handler fades the overlay in; `afterOpen()`
  (bound after it) stops and hides it.
- The X of the old popup (`#table_modal_cancel_button2`) walked 4 parents,
  which was the `<body>`, i.e. it never removed `active` from the modal — it
  is gone now.
- A floor plan saved while a tile was selected in the designer carries
  `div_rectangular_active` in `tbl_areas.table_design_content` (G06 on the
  local DB). That tile was "selected" forever, so the class used to be
  stripped at start-up. Since §0 the POS builds tiles from `tbl_tables` and
  no longer reads the saved layout, so the strip was removed.
- `frequent_changing/table_design/jquery-ui.structure.css` is linked **after**
  `custom_pos.css` and sets the floor pane (`.all-dineIn-table`) to
  `calc(100% - 32px)`: that was the white strip under the floor. Overridden
  with `height:100% !important` (and `margin-left:0 !important` for
  `.pos_ml_10`). The pane carries perfect-scrollbar (`.ps`, `overflow:hidden`),
  so `fitCanvas()` calls `all_dineIn_table.update()` after each render.
- The JS `.get_area_table` handler in the new block is bound **before** the
  legacy render handler (which sits further down the file), so anything that
  must see the rendered floor runs in a `setTimeout(0)`.
- Tested headless (Chrome CDP, 1366/1024/800 wide): landing, More menus,
  blank tap forces Dine In + badge, Take Away clears the table, real order
  on G15 → reload shows `1 running · 14 free` + ring, armed Transfer keeps
  the view open in transfer mode, plain tap opens the order as Update Order.
- `main_screen.php`, the JS, the CSS and the language files are CRLF; the
  edits were applied with scripts that keep that.
- Language keys added to all 4 languages: `more_menus`, `table_view`,
  `table_view_hint`, `running`, `free`, `blank_table`, `running_table`,
  `now_tap_a_table_for_action`.
