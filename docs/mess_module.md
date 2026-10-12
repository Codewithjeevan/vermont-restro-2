# Mess module (monthly tiffin) — implementation notes

User guide with a workflow diagram: [How to use Mess](mess_how_to_use.html).

Branch `feature/mess-module`. Plan: [mess_system_plan.html](mess_system_plan.html). This file covers
what was built, how to switch it on, and what is still open.

## Switch it on

1. Run the migration once per database:
   `mysql -uroot restodb < Update/mess_module_migration.sql`
   It creates 12 `tbl_mess_*` tables, adds `tbl_register.total_mess_collection` +
   `mess_collection_json` and `tbl_sale_consumptions_of_menus.mess_close_id`, adds main module 12
   "mess" (Role screen block) and access rows 371–411. The last query must print `41`.
2. Admin sees the **Mess** menu immediately (Admin's menu is now built from `tbl_access` on every
   page, see `userHome.php`). Other roles: tick the Mess permissions on Settings > Role, then the
   user logs in again (`function_access` is still built at login for non-Admin).
3. Mess > Settings: slot times, cycle days, alert window, open-register rule, WhatsApp country code
   and text templates.
4. Mess > Plans, then Members / Subscriptions.

The register close keeps working before the migration runs: every mess query checks
`Sale_model::messInstalled()` / `table_exists` first.

## What is built

| Area | Controller / view | Notes |
|---|---|---|
| Today dashboard | `Mess::today` | Headcount per slot and diet, pickup/delivery, extras, expiring, dues, today's collection, menu, "not closed" meals |
| Plans | `Mess::plans / addEditPlan` | Price per person per cycle, included slots, per-meal rate = price / (cycle days × meals a day) |
| Members | `Mess::members / addEditMember / member` | Auto code `M-0001`, duplicate phone warning, profile with cycles, pauses, ledger, 30-day attendance |
| Subscriptions | `Mess::addEditSubscription / subscriptions / expiring` | Meals × persons per slot, suggested vs agreed price, pro-rata, pause (moves end date), renew (prefilled next cycle), cancel, overlap warning, "collect now" advance |
| Payments | `Mess::addPayment / payments / dues / paymentReceipt` | Payment / refund / adjustment (credit or debit), auto-picks the oldest unpaid cycle, 80 mm receipt, WhatsApp due reminder |
| Register | `Sale::registerDetailCalculationToShow / closeRegister`, `Report::zReport`, `registerReport.php` | "Mess Collection (+)" per payment method inside the closing formula, a Mess Collection card, split saved on the closed register, Z report line + section |
| Daily menu | `Mess_daily::menu / saveMenu / copyMenu` | Date × slot × diet, Food Menu dish (recipe) or free text, portion per person, buffer, live planned portions, copy yesterday / last week / any date, WhatsApp menu text |
| Meal register | `Mess_daily::register / mark / bulkMark / flipDiet / skip` | One tap per member (AJAX, only that row redraws), partial serve, undo, advance skip ("kal nahi aaunga"), Veg/Non-veg switch for "Both" members, bulk mark |
| Extras | `Mess_daily::extras / addExtra / extraStatus / deleteExtra` | Food Menu item or custom line; pay now (creates a mess payment → register) or on account (member balance) |
| Daily sheet | `Mess_daily::sheet` (+ `?print=1` A4) | Menu, headcount, production plan (recipe × portions), stock check with shortage, tick sheet, extras |
| Day close | `Mess_daily::dayClose / closeDay / reopenDay` | Cooked and leftover per dish; posts consumption and waste to the stock ledger; reopen reverses it |
| Reports | `Mess_report::*` | Daily served summary, attendance matrix (member × day), collection by day × method, day close & food cost |

Access ids (hard coded in the controllers, `const ACCESS_*`): 371 dashboard, 373 plans, 378 members,
383 subscriptions, 388 payments, 392 daily menu, 395 meal register, 398 extras, 402 daily sheet,
404 day close (view / update / reopen), 408 reports, 410 settings.

## Money rules

* Member balance = Σ agreed price of non-cancelled cycles + Σ non-cancelled extras
  − Σ payments − Σ credit adjustments + Σ refunds + Σ debit adjustments. No ledger table; it is
  computed (`Mess_model::balanceSelect`, `getLedger`).
* A pay-now extra is both a charge and a payment, so it nets to zero and still shows on the ledger.
* Payments carry `counter_id`, `outlet_id`, `date_time` exactly like `tbl_customer_due_receives`.
  Refunds count negative in the register; adjustments never touch the register.
* A payment whose register is already closed can only be deleted by Admin.
* "Require an open register" is on by default. Off means money can be taken without a register,
  and it will not reach any register close.

## Stock rule (day close)

Closing a slot posts, per dish with a recipe:

* consumption = (cooked − wasted leftover) × recipe → `tbl_sale_consumptions_of_menus`
  (`sales_id = 0`, `order_status = 3`, `mess_close_id` = the close)
* waste = wasted leftover × recipe → one `tbl_wastes` entry (`MW-…`) + `tbl_waste_ingredients`

So the ingredients leave the stock exactly once (consumption + waste = cooked). "Carry to next meal"
and "Staff meal" leftover count as used. Reopen soft-deletes both, re-close posts again. The
stock screens, low-stock alert and inventory report pick it up with no change. The existing
Consumption Report joins `tbl_sales`, so mess consumption appears in the Mess "Day close & food
cost" report, not there (same as Production).

The daily sheet's "short" treats a negative book stock as 0 (it shows what to buy for today's
cooking, not the bookkeeping gap).

## Defaults used for the 9 open client questions (plan §9)

| Question | Default built |
|---|---|
| Skip = money back? | No credit; setting exists, credit is a manual adjustment |
| Persons or tiffins | Persons = portions cooked |
| Veg / non-veg menus | Two menus per slot, copy tools |
| Delivery charge | None; included in the agreed price |
| Open register for mess money | Required (setting) |
| Who marks attendance | Counter, Meal Register works on a tablet |
| Breakfast | All three slots on, breakfast 06:30–10:00 (setting) |
| Import the Excel | Not built yet (see below) |
| Mess dishes on POS | Not hidden yet (see below) |

## Improvements over the plan

* Admin sees new menus without logging out (`userHome.php` builds the Admin menu live).
* Module tabs on every Mess page, filtered by the same `data-access` rule as the sidebar.
* Payment receipt (80 mm) with auto print, member statement (A4), WhatsApp reminders
  (`wa.me` links; local `05x` numbers get the country code).
* Payment form suggests the amount: the unpaid cycle's due, never more than the member's balance.
* Duplicate phone warning, overlap warning, pending-rows warning on day close, "not closed" list
  on the dashboard.
* Language keys live in their own `mess_lang.php` per language (autoloaded English fallback +
  loaded in the user's language by `Common_model`), so the four big language files stay untouched.
* Mess group in the POS side menu (Today, Meal Register, Receive Payment, Add Extra, Members).

## Still open

* Excel import of the client's sheet (needs the sheet cleaned first).
* Hide "mess only" dishes from the POS grid.
* Weekly menu template (copy from any date covers most of it).
* Mess P&L (revenue recognised per day vs food cost) — the day close report has the cost side.

## Gotchas met while building

* CodeIgniter 3 on PHP 8 never expires flashdata (`'old' < time()` is false for a string), which is
  why every view here unsets a flash message after showing it. `views/mess/_top.php` does the same.
* A test session id in `tbl_sessions` must be exactly 32 characters, or CI rejects it and redirects to login.
* `userHome.php` has the sidebar twice (LTR and RTL); both got the Mess block.

## Local test data

The local `restodb` Mess data was cleared on 5 October 2026 for manual testing. All 12
`tbl_mess_*` tables, Mess stock-consumption rows and `MW-` waste rows were verified empty.
The module schema, access permissions and other POS data remain available. See the
[How to use guide](mess_how_to_use.html) for a fresh end-to-end test.
