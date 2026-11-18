# AI Insight: go-live checklist (Phase 6)

**For:** whoever puts branch `feature/ai-insight` on the live server. **Plan and build log:** `docs/ai_insight_plan.md`.
Tick each box in order. Steps marked **STOP** must pass before you continue.

---

## 1. Before you start

- [ ] **PHP 8.1 or newer, for both the website and the command line.** On cPanel the CLI `php` can be an older version than the site's MultiPHP version. Check both:
  - Website: MultiPHP Manager shows the domain's version.
  - Command line: `php -v`, or the full path cPanel uses, for example `/opt/cpanel/ea-php82/root/usr/bin/php -v`. Note the path that shows 8.1+; the cron in step 8 needs it.
- [ ] PHP extensions `curl`, `openssl`, `mbstring`, `json` are on (MultiPHP INI Editor or `php -m`).
- [ ] cPanel → Cron Jobs is available. If not, see the web cron in step 8.
- [ ] **Backup the database and the code folder.**
- [ ] The live code is the same as `main`. This branch also changes 5 shared files: `.htaccess`, `application/config/autoload.php`, `application/controllers/Purchase.php`, `application/models/Common_model.php` and `application/views/userHome.php`. If someone edited any of these on the server, merge by hand instead of overwriting.

## 2. Anthropic Console

- [ ] Create a **new API key** for production (Console → API keys). Do not reuse the test key: it was shared in a plain text file (`d:\Mustafa\AI-doc.md`). **Revoke the test key** once the new one works.
- [ ] Set a **monthly spend limit** for the workspace (Console → Limits). This is the last safety net above the per-company limits in the app. Expected spend: about **$1.5–3 per outlet per month** (daily report about $0.75 + chat about $0.1–1).

## 3. Build the upload package (on the dev PC, Git Bash, repo root)

```bash
FILES=$(git diff --name-only main...feature/ai-insight | grep -v "^application/third_party/anthropic/" | grep -v "^docs/\|^tests/\|^\.gitignore$")
git archive -o ai_insight_upload.zip feature/ai-insight $FILES application/third_party/anthropic
```

- [ ] The zip has about **3,972 files**: 39 app files plus the Anthropic SDK (`application/third_party/anthropic/`, 3,933 files including `vendor/`).
- [ ] It does **not** contain `application/.env` (git-ignored, so `git archive` never adds it). Never upload your local `.env`: it holds the test key.

## 4. Upload

- [ ] Extract the zip into the app's root folder on the server, keeping the folder structure (it overwrites the 5 shared files from step 1).
- [ ] `docs/` and `tests/` are not needed on the server.

## 5. The secret file

- [ ] On the server, copy `application/.env.example` to `application/.env` (same folder) and set:
  ```
  ANTHROPIC_API_KEY=<the new production key>
  AI_CRON_WEB_TOKEN=
  AI_MOCK=0
  ```
- [ ] File permission 600 or 640 (File Manager → Permissions), so other hosting accounts cannot read it.
- [ ] **STOP:** open `https://<site>/application/.env` and `https://<site>/.env` in a browser. Both must be refused (403 Forbidden or 404). If the key text shows, delete the file at once, revoke the key, and fix the `.htaccess` rules before trying again.

## 6. Database

- [ ] **STOP:** check that the fixed ids are free (phpMyAdmin → SQL):
  ```sql
  SELECT id, module_name, function_name, label_name FROM tbl_access WHERE id BETWEEN 437 AND 442;
  SELECT * FROM tbl_main_modules WHERE id = 14;
  ```
  Either no rows, or only the AI Insight rows (`ai_insight`, `view`, `chat`, `regenerate`, `ai_settings`, `update` / `ai_insight`). If another module uses these ids, stop: the ids are hard-coded in `Ai_insight.php` and `userHome.php`.
- [ ] Run `Update/ai_insight_migration.sql` (phpMyAdmin → select the database → Import, or paste into SQL). It is safe to run twice. It creates 6 `tbl_ai_*` tables, the access rows and 6 indexes. It also copies `consumption_unit_cost` to `average_consumption_per_unit` on ingredients (plan F1).
- [ ] Verify:
  ```sql
  SHOW TABLES LIKE 'tbl_ai_%';              -- 6 tables
  SHOW COLUMNS FROM tbl_ai_snapshots LIKE 'regen_count';
  SHOW COLUMNS FROM tbl_ai_chat_messages LIKE 'display_text';
  ```

## 7. Switch it on

- [ ] Log in as **company 1, role Admin** (the main admin). Sidebar → AI Insight → **AI Companies**.
- [ ] "API key: Set on the server" is shown. If it says "Not set", step 5 is wrong (file name, folder, or `ANTHROPIC_API_KEY=` spelling).
- [ ] Press **Test connection**: "Connection OK: Claude Haiku 5.5 replied in … seconds". It costs a fraction of a cent.
- [ ] For the client company (DESIRE CATERING), tick **On**, set the monthly limit in USD (default 5), keep the report model on Sonnet 5.5 and the chat model on Haiku 5.5, tick **Ask AI** and set questions per user per day (default 30). Press **Save**.
- [ ] Every other company stays off: they get no reports and no API calls.

## 8. Hourly cron

- [ ] cPanel → Cron Jobs → add, **every hour** (minute 0). Use the PHP path from step 1:
  ```
  0 * * * * cd /home/<account>/public_html/<app> && /opt/cpanel/ea-php82/root/usr/bin/php index.php Ai_cron run >/dev/null 2>&1
  ```
  Each company gets one report per outlet per day, after 03:00 its own local time. A run that is still busy blocks the next one (lock), so overlaps are safe.
- [ ] **No CLI cron on this host?** Use the web trigger instead:
  1. Put a random token of 32+ characters in `application/.env`: `AI_CRON_WEB_TOKEN=...`
  2. Call `https://<site>/Ai_cron/web/<token>` every hour from the host's URL cron or an outside cron service.

  Without the token that URL answers 404.
- [ ] **STOP:** after the next full hour, check that the cron ran:
  ```sql
  SELECT id, started_at, finished_at, companies, outlets_done, errors FROM tbl_ai_cron_log ORDER BY id DESC LIMIT 5;
  ```
  A row must appear every hour. Before 03:00 local time `outlets_done` is 0. If SSH is available, `php index.php Ai_cron status` prints the same, plus today's reports and this month's spend.

## 9. Roles and permissions

- [ ] Admin users see everything at once.
- [ ] For each other role (Role → Edit), tick as needed:
  - **AI Insight → View:** the page.
  - **AI Insight → Ask AI:** the chat.
  - **AI Insight → Regenerate report:** remakes today's report, at most 3 times per outlet per day, and costs about $0.02 each time.
  - **AI Settings → Update:** target food cost and thresholds.
- [ ] Users of those roles must **log out and log in again** (permissions are read at login).

## 10. First report and page check

- [ ] Don't want to wait for 03:00? Open AI Insight (with the right outlet selected) and press **Regenerate report** (10–30 s). Or over SSH: `php index.php Ai_cron outlet <company_id> <outlet_id>`.
- [ ] The report line says "Written by AI". "Standard text" means the AI was not used, and the line says why.
- [ ] All sections show: Top 5, Menu performance, Price check, Purchase vs usage, Profit & loss, Sales prediction, Data Health. A warehouse outlet shows only the stock sections.
- [ ] Toggle **Last 7 days**; open it on a phone.
- [ ] **Ask AI:** ask one suggested question; the answer appears and the counter goes down ("29 of 30 questions left today").
- [ ] **AI Settings:** the target food cost (30 %) and thresholds are right for this client.

## 11. The client's data (the AI is only as good as this)

On the 24 Oct copy of the live data, **0 of 186 menus had a recipe** and there were no purchases, waste or sales. Until that changes, most sections show "needs more data", and Data Health lists exactly what is missing. Send the client this list:

- [ ] A recipe (ingredients + quantities) for every menu item. Upload Recipe takes up to 1000 menus at a time.
- [ ] Every purchase with its price, on the day it arrives, including the warehouse.
- [ ] Transfers between WAREHOUSE Zone and the cafeteria, on the day they happen.
- [ ] Waste every day.
- [ ] A stock count once a month (Inventory Adjustment).
- [ ] Predictions start after 4 weeks of sales (8 weeks for good accuracy).

## 12. First week: watch

- [ ] Every day: cron rows and errors.
  ```sql
  SELECT * FROM tbl_ai_cron_log WHERE errors > 0 ORDER BY id DESC LIMIT 10;
  SELECT outlet_id, report_date, narrative_status, attempts, error FROM tbl_ai_snapshots ORDER BY id DESC LIMIT 20;
  ```
  `narrative_status` should be `ok`. `template` + an error such as `call: budget` means the monthly limit was reached.
- [ ] Spend per company this month. The AI Companies page shows the same in "Spent this month".
  ```sql
  SELECT company_id, feature, COUNT(*) AS calls, ROUND(SUM(cost_usd), 4) AS usd
  FROM tbl_ai_usage WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') GROUP BY company_id, feature;
  ```
- [ ] Failed calls: `SELECT created_at, feature, status, error FROM tbl_ai_usage WHERE status IN ('error', 'refusal') ORDER BY id DESC LIMIT 20;` The errors never contain the key.
- [ ] Ask the client after a few days whether the top 5 and the suggested prices make sense. If a recipe is wrong, the numbers are wrong too.

## 13. Switch off / roll back

- **One company:** AI Companies → untick **On** → Save. No more calls for that company; the page shows "not switched on".
- **Chat only:** untick **Ask AI** for the company.
- **Everything:** remove the cron line and empty `ANTHROPIC_API_KEY` in `application/.env`; every call is then refused before it leaves the server.
- **Code:** restore the 5 shared files from the backup; the new files and `tbl_ai_*` tables are unused without them. Do not undo the `Purchase.php` change unless you also want the old ingredient cost drift back.

## 14. Security checks on the live site (plan section 9)

| Check | How | Expected |
|---|---|---|
| Key not reachable | Step 5 browser test; View Source of the AI pages | 403/404; no `sk-ant` anywhere in HTML, JS or the DB |
| Cron URL closed | Open `https://<site>/Ai_cron/run` and `/Ai_cron/web/x` | 404 |
| Another company | A user of a company that is switched off opens `/Ai_insight/index` | "not switched on" message |
| Role without permission | A role without AI Insight → View opens `/Ai_insight/index` | Sent back with "no permission" |
| Forged form | Not testable by hand; covered by `tests/ai/page_test.php` and `chat_test.php` (no token / other site → refused) | — |
| Monthly limit | Set a test company's limit to 0, press Regenerate | "Standard text" + "This month's AI limit … reached"; no new `ok` row in `tbl_ai_usage` |

## 15. Not done in this build (decide later)

- **No separate usage page.** Spend and calls per company for the month are on the AI Companies page, and the SQL in step 12 gives the detail. Build a page only if the main admin needs history by month.
- **Main admin = company 1 + role Admin.** Company 1 is DESIRE CATERING's own account on this install, so all its Admin users can open AI Companies. To limit it to one person, change `ai_is_main_admin()` in `application/helpers/ai_helper.php` to check a specific `user_id`.
- **POS bug found during the build (F18, not AI code):** a combo that contains a resale item records no stock use for that item. Fix it separately; until then those items show less usage than real.
- **Chat answers are not number-checked** like the daily report. The prompt makes the model call its own calculations "estimates".
- Known limits of the analysis (recipes decide everything, no delivery commission, no seasonality): plan section 14.
