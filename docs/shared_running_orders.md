# Shared Running Orders + POS action-row layout — Feature Notes

Goal: every user of the company sees the **same "Running Orders" sidebar** in
the POS, on any terminal, with no per-user browser cache and no "save / pull
your running orders" prompt at logout. Second goal: the cart action row
(Cancel / Draft / Quick Invoice / Place Order) stays on screen at every
viewport size.

**Status: implemented** on branch `feature/shared-running-orders-pos-buttons`.
Migration `Update/shared_running_orders_migration.sql` is a hard prerequisite:
the sync endpoints write columns that do not exist without it.

## 1. How running orders were stored before

- The sidebar rendered from the browser's IndexedDB store
  `irestora_plus.sales`, filtered to `user_id == logged-in user`
  (`displayOrderList()` in `frequent_changing/js/pos_script_v7.3.js`).
- The server only had `tbl_kitchen_sales` (kitchen copy, no cart blob) and
  `tbl_running_orders`, which was written **only** when the user pressed
  Submit in the "Logout Alert" modal (`Sale::pull_running_order`) and read back
  by the red "Pull your running orders" header icon, for that one user.
- Result: another user or terminal never saw the order, and the same user on a
  second browser had to pull by hand.

## 2. Design: server mirror, IndexedDB stays the working copy

`tbl_running_orders` is now a live mirror, one row per `(sale_no, company_id)`:

| column | meaning |
| --- | --- |
| `order_content` | the cart blob (what IndexedDB stores in `order`) |
| `record_meta` | the rest of the IndexedDB record as JSON (`kot_print`, `is_invoice`, `hidden_table_id`, `pre_or_post_payment`, ...) so a terminal can rebuild the exact record |
| `outlet_id`, `company_id` | scope of the list (taken from the session, never from the client) |
| `version` | bumped on every save; terminals compare it with their local `server_version` |
| `updated_at` | last save |

Endpoints in `application/controllers/Sale.php` (next to the old pull code):

- `get_running_orders` — all Live rows of the session's outlet + company.
- `save_running_order` — atomic `INSERT ... ON DUPLICATE KEY UPDATE`, returns the new version.
- `remove_running_order` — deletes the row and the sale's `tbl_running_order_tables` bookings (the terminal that settles may never have had them locally).

Client side, block *"Shared running orders (server mirror)"* right after
`displayOrderList()`:

- Every local write site now mirrors: `pushRunningOrderToServer(record)` after
  an IndexedDB add/update, `removeRunningOrderFromServer(sale_no)` before a
  delete. Sites: place order (add + re-order update), cart item / table edits,
  KOT-printed flag, invoiced flag in post-payment mode, settle (pre-payment
  delete), cancel with reason, admin cancel, split-bill delete, waiter-app
  close / update / delete.
- `syncRunningOrdersFromServer(force)` runs when IndexedDB opens, on the
  sidebar refresh icon, when the tab becomes visible, and on the existing 7 s
  `all_time_interval_operation` tick (never more often than every 5 s). It
  rides that tick on purpose: `reset_time_interval()` kills every interval on
  the page, and the 7 s one is the only interval that gets re-registered.
- `reconcileRunningOrders()` walks the local store by `sale_no`:
  - on the server, not local → add locally;
  - server `version` differs from local `server_version` → server copy wins;
  - local has a `server_version` but the server row is gone → settled or
    cancelled elsewhere: delete locally; if that order is open in the cart
    (`#update_sale_id`) the cart is cleared and the toast
    `running_order_removed_elsewhere` is shown;
  - local never confirmed (`server_version` undefined) → push it now. This is
    also how existing orders migrate: the first POS load after the update
    pushes every terminal's running orders, nothing to do by hand.
  - a sale_no this terminal removed in the last 20 s is never re-added, so a
    sync overlapping a settle cannot resurrect the order for one tick.
- `displayOrderList(keep_selection)` shows every order of the outlet (filter
  by `outlet_id`, not `user_id`); a sync re-render keeps the highlighted order
  and the search filter the cashier typed.
- Logout: `.logout_for_user` just navigates. The "Logout Alert" modal, the pull
  header icon, the synchronous pull XHR on page load and `$data['users']` are
  removed. Closing the register keeps its own open-orders guard.

Known trade-off: last write wins per order, same rule as the kitchen copy. Two
cashiers editing the same order at the same moment overwrite each other; the
sidebar itself is consistent within one tick.

## 3. Why the bottom buttons were cut off

- `.main_center` had fixed heights (`calc(100vh - 245px)` and friends per
  breakpoint) that did not add up to the real header + toolbar heights, and a
  stray **visible** text input (`#inv_collect_tax` in
  `views/sale/POS/hidden_input_html.php`, `type="text"`) pushed the whole page
  down by 21 px. Net effect: the action row ended 9-10 px below the fold on
  every desktop size.
- Fix, CSS only (`assets/POS/css/custom_pos.css`, last block):
  `#main-wrapper-content` and `.main_middle` are flex columns, `#main_part`
  and `.main_center` take the remaining height (`flex: 1 1 auto;
  min-height: 0`), `#bottom_absolute` keeps its own height, and the input is
  `type="hidden"`.
- Verified headless at 1917x1030, 1920x1080, 1536x864, 1366x768, 1280x720,
  1280x600, 1280x500, 1024x768, 800x1280 and 414x896: the Place Order button
  is fully inside the viewport in all of them.

## 4. Testing recipe (headless Chrome over CDP, Node 22, no extra packages)

- `pos_script_v7.3.js` is one big IIFE, so its functions are **not** reachable
  from `Runtime.evaluate`. Drive the UI instead: click `.single_item`, pick a
  waiter (`jQuery('#select_waiter').val(id).trigger('change')` — without a
  waiter, Place Order only opens the waiter dropdown), click
  `.place_order_operation[data-type="2"]`. Read the local store directly with
  `indexedDB.open('irestora_plus')`.
- Two `--user-data-dir` profiles = two terminals. A live `sess` cookie from
  `tbl_sessions` works (`sess_match_ip` is FALSE).
- The KOT print popup throws `Cannot read properties of null` in headless
  Chrome because `window.open` is blocked there. Pre-existing, harmless.
- Clean up: a placed test order leaves a `tbl_kitchen_sales` row (+ details)
  and the shared row; remove both.

## 5. Files

- `Update/shared_running_orders_migration.sql`
- `application/controllers/Sale.php` — three endpoints, `$data['users']` removed
- `frequent_changing/js/pos_script_v7.3.js` — sync block, hooks, logout, pull code removed
- `application/views/sale/POS/main_screen.php` — pull icon and logout modal removed
- `application/views/sale/POS/hidden_input_html.php` — hidden `#inv_collect_tax`, toast text
- `application/language/*/*_lang.php` — `running_order_removed_elsewhere`
- `assets/POS/css/custom_pos.css` — flex layout block
