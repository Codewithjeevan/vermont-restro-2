# Invoice number format — `INV-<company_id>-<n>`

New POS bills are numbered `INV-1-1`, `INV-1-2`, `INV-1-3`, … The number after the
company id starts at 1 and only ever increments — it never resets on a new day or a
new year.

**Deployment step (required):** run `Update/sale_no_format_migration.sql` once per
database. Without the `tbl_sale_no_counters` table the POS cannot reserve a number and
will refuse to create a bill (it shows *"Could not get an invoice number from the
server"*) rather than fall back to a number that might already be in use.

Existing invoices keep their old numbers. Nothing renumbers, and no query sorts or
parses `sale_no`, so reports, KOT, printing and the running-order screens are unaffected.

## Why the number is assigned by the server

`sale_no` was built in the browser (`aGY260728-003` = short username + YYMMDD + a
`localStorage` counter). That stayed unique only because the username was baked into it.
A per-company sequence has no such separator, so two counters/terminals billing at the
same time would produce the same `INV-1-<n>` twice — and `sale_no` is the key that ties
`tbl_kitchen_sales`, `tbl_sales`, `tbl_orders_table` and `tbl_running_orders` together,
so a collision merges two unrelated orders.

The counter now lives in one row per company:

| file | what it does |
| --- | --- |
| `Update/sale_no_format_migration.sql` | creates `tbl_sale_no_counters`, seeds each company from the highest `INV-` number already on record |
| `reserveCompanySaleNumbers()` — `application/helpers/my_helper.php` | hands out the next N numbers atomically |
| `Sale::reserve_sale_numbers()` — `application/controllers/Sale.php` | AJAX endpoint the POS calls (capped at 50 per request) |
| `generateSaleNo()` & friends — `frequent_changing/js/pos_script_v7.3.js` | keeps the terminal's buffer, consumes one number per bill |

Atomicity comes from `UPDATE ... SET last_no = LAST_INSERT_ID(last_no + n)`. The InnoDB
row lock makes read-and-increment a single indivisible step, so two terminals asking at
the same moment get two different ranges instead of the same number twice.

## Why the terminal buffers numbers

The POS is offline-first: a bill is written to IndexedDB and its KOT is printed
immediately, then pushed to the server by `push_online()` on a 10-second interval. The
number therefore has to exist *before* the server is necessarily reachable.

Each terminal keeps `SALE_NO_POOL_SIZE` (20) reserved numbers in
`localStorage["sale_no_pool_<company_id>"]` and tops back up once it drops to
`SALE_NO_POOL_MIN` (10). Both constants are at the top of the numbering block in
`pos_script_v7.3.js`.

Consequences worth knowing:

- With several terminals the sequence **interleaves** — terminal A bills 1–20 while
  terminal B bills 21–40. Numbers are unique and increasing per terminal, not globally
  chronological.
- Numbers left in the buffer of a terminal that is retired, or whose browser storage is
  cleared, **stay unused** — a permanent gap in the sequence. This is the price of being
  able to bill offline; it was the accepted trade-off when the feature was specified.
- If the buffer runs dry *and* the server is unreachable, the bill is **blocked** with an
  error toast. Inventing a number locally would duplicate an invoice number, so stopping
  is the only safe option.

To start a company somewhere other than 1 (e.g. to continue an existing paper series),
set `last_no` before the first POS bill — see the commented example at the end of the
migration file.

## Not changed

Only POS bills were in scope. These still use their own numbering:

- Waiter app orders — `Waiter_app.php`, zero-padded sale id (`000123`)
- Website / online self-orders — `assets/website/js/order.js`, customer name + date + counter
- `Sale::Save()` — legacy count-based number, not reachable from the POS screen

Split bills are unaffected in shape: the server still appends a suffix to the parent's
number, so a split of `INV-1-42` becomes `INV-1-42-001`. The seeding query in the
migration accounts for this and reads such a row as 42, not 42001.
