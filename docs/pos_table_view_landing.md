# POS Table View landing + "More menus" toggle — Feature Notes

Goal (client request, 2026-09-24): when a user opens the POS the **table
selection shows first**; tapping a table goes to the item / cart screen with
that table set. Take Away / Delivery are still one tap away. Second part: the
POS had both a **Dine In** and a **Table** button doing the same thing, and
the top icon bar (register, reports, hold, recent sales, ...) ate space, so
the Table button is now a **More menus** toggle that expands / collapses that
bar and gives the POS the room.

**Status: implemented** on `main` (no migration, no new tables).

## 1. What changed for the cashier

| before | after |
| --- | --- |
| POS opens on the cart / items | POS opens on the **Table View** (areas on the left, floor plan on the right) |
| `Dine In` and `Table` both opened the centred "Tables" popup | `Dine In` opens the Table View; the `Table` button is gone |
| top icon bar always visible | bar collapsed by default; **More menus** (4th button of the order-type row, and in the Table View header) toggles it. Remembered per browser |
| picking a table gave no visible feedback | the `Dine In` button shows the picked table as a badge (`Dine In  G05`) |
| running tables looked like blank ones unless the area had "ordered" colours configured (never copied to the JS, and NULL on every area here) | running tables get a red ring (`.pos_table_running`) + the legacy waiter / order-number text; area colours are now copied to the hidden inputs too |
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
    Delivery / Take Away, counts, legend + hint); the bottom Cancel bar and
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
  - `fitCanvas()` (after every area render) sets `min-width/height` on
    `#canvas` from its farthest tile and `.all-dineIn-table` is
    `overflow:auto`, so tablets scroll to far tables instead of clipping
    them (`#canvas` is `overflow:hidden`).
  - the order number on a running tile is the **last** segment of the sale
    no (`split_order[split_order.length-1]`); `[1]` showed the company id
    since sale numbers became `INV-<company>-<n>`.

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
  local DB). Every render copies that markup, so the class is stripped from
  the hidden `.set_design` copies once at start-up; otherwise that tile is
  "selected" forever: a quick action applied to it without any tap and the
  running-orders refresh never redrew the floor.
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
