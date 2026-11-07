# Monthly Inventory report + Stock, POS, Purchase, Waste updates: plan

Status: **saare steps ban gaye aur local par test ho gaye (6 Nov 2026), abhi commit nahi hue.** Branch `feature/monthly-inventory-report`, `origin/main` (`ad160955`) se. Commit aur PR tabhi jab bola jaye.

**Migration zaroori hai:** `Update/inventory_stock_updates_migration.sql` (local `restodb` par chal chuka hai).

## Pehle yeh padhein: production par ek bada bug

Main me PR #9 (`dca1cb38`, 16 Oct 2026) se `Purchase::syncIngredientPrice` me bug hai: **har purchase save hone par poori `tbl_ingredients` table ke saare ingredients ka `purchase_price` aur `consumption_unit_cost` us purchase ke price se overwrite ho jaata hai.**

Kaaran: `where('id', ...)` ke baad `update()` ke andar `getAmtP()` chalta tha, jo khud query builder query chalata hai aur pending WHERE ko kha jaata hai. Isse update bina WHERE ke chalta tha. Is branch me theek ho gaya hai, par jab tak deploy na ho, production par har purchase yeh dobara karegi.

Production par check:

```sql
SELECT purchase_price, consumption_unit_cost, COUNT(*) n
FROM tbl_ingredients GROUP BY purchase_price, consumption_unit_cost ORDER BY n DESC LIMIT 5;
```

Bahut saare ingredients ka ek hi price dikhe to data kharab ho chuka hai. Wapas lane ke raaste: backup, MySQL binlog (agar ON hai), ya har item ki last `tbl_purchase_ingredients.unit_price` se. Recipe cost aur waste loss ke numbers bhi inhi prices par chalte hain.

## Kaam ke dauran mile baaki bugs (sab theek)

| Jagah | Problem |
|---|---|
| `Purchase.php:205` | Paid = 0 wali credit purchase PHP 8 par crash hoti thi: `htmlspecialcharscustom('0')` khaali string deta hai, phir `grand_total - ''` TypeError |
| `purchase/_tax_fields.php` | Bina tax wali company me Tax % box khaali dikhta tha (`escape_output(0)` khaali deta hai), aur Save bina message ke ruk jaata tha |
| `add_waste.js` submit | Validation fail par `return true` tha, isliye form phir bhi post ho jaata tha |
| Step 4 ki table | Edit Waste ka JS band hona, PHP 8 crash, delete button, aur baaki |
| `auditLogReport.php:128` | Time me minutes ki jagah mahina dikhta tha |

## Kaam ki list

| # | Kaam | Kahan | DB change |
|---|---|---|---|
| R | Monthly Inventory report (client ki Excel jaisi) | Report menu, naya page | Nahi, item 1 ka column use karega |
| 1 | Opening Stock input | Stock menu, naya page | `tbl_inventory_adjustment.is_opening` |
| 2 | Remove item par Reason | POS password popup | Nahi, item 3 ke columns use karega |
| 3 | Audit Log Report: Details hatake 4 columns | Audit Log Report | `tbl_audit_logs` me 4 columns |
| 4 | Stock page: 2 naye columns, "/Amount" hatana | Stock page + labels | Nahi |
| 5 | Paid auto-fill | Add/Edit Purchase | Nahi |
| 6 | Waste ka food menu flow theek karna | Add/Edit Waste | `tbl_wastes.food_menu_waste_qty` FLOAT |

## Faisle jo is plan me maan liye gaye hain

| Faisla | Kya hoga | Kyun |
|---|---|---|
| Month-end shift | Mahine ka closing stock next month ka Opening apne aap banega. Koi cron ya snapshot nahi | Dono ek hi formula se nikalte hain, isliye `Opening(Nov) = Closing(Oct)` rahega. Purani date ki entry aane par dono saath badlenge. Ek hi exception: kisi item ka Opening Stock row usi mahine ki date ka ho, to uska Opening utna zyada hoga |
| Admin/Manager ka remove | Bina password ke sirf Reason wala popup | Taaki Audit report me har running-order removal ka Reason ho |
| Physical Count | Period end tak ka system stock. Ginti ka fark usi mahine ki date ke Inventory Adjustment se daala jayega | Excel ka note "tally by the stock page" isi se match karta hai. Naya table nahi lagta |
| Unit Price | Us outlet ki period end tak ki last Live purchase ka price, warna ingredient ka `purchase_price` | Nayi purchase aane par purani report nahi badlegi |
| Pack Size | Conversion rate se auto: "1 Pkt = 14 Pcs" | System me pack size ka field nahi hai. DB change nahi lagega |
| Opening Stock ki jagah | Stock menu me alag page, Add Ingredient form me nahi | Ingredient company-wide hota hai, stock outlet-wise. Purane items ke liye har item alag edit karna padta |
| Opening Stock ka storage | Inventory Adjustment (Plus) ki row, ek flag ke saath | Stock formula ki kam se kam 12 copies me adjustment pehle se juda hai. Naya table in sab ko badalwata |
| Reason | Remove item par zaroori, max 255 characters | Khaali reason se audit ka matlab nahi rehta |

## Stock ka common hisaab

Stock page ka formula (`application/views/inventory/inventory.php:45`) hi sach maana jayega:

```text
Stock (consumption unit me) = (Purchase + Transfer In - Transfer Out + Production) x Conversion Rate
                              - Sale consumption - Modifier consumption - Waste
                              + Adjustment Plus - Adjustment Minus
```

Purchase, Transfer aur Production purchase unit me hote hain. Consumption, Waste aur Adjustment consumption unit me hote hain.

Consumption rows me date nahi hoti, isliye mahina parent record se decide hoga:

| Movement | Date |
|---|---|
| Purchase | `tbl_purchase.date` |
| Transfer (sirf `status=1` Received, `transfer_type=1`) | `tbl_transfer.received_date`, khaali ho to `date` |
| Sale + modifier consumption | `sales_id` se `tbl_sales.sale_date` (order khulne ki date) |
| Mess consumption | `mess_close_id` se `tbl_mess_day_close.close_date` |
| Production | `production_id` se `tbl_production.date` |
| Waste | `tbl_wastes.date` |
| Inventory Adjustment | `tbl_inventory_adjustment.date` |

Delete hone par child rows bhi `Deleted` ho jati hain (`Common_model::deleteStatusChangeWithChild`), isliye child ka `del_status='Live'` kaafi hai. Parent sirf date ke liye join hoga. `tbl_transfer_received_ingredients` me koi insert nahi karta, us par date filter nahi lagana.

**Naya helper:** `getIngredientPricesAsOf($outlet_id, $as_of = '')`, `application/helpers/my_helper.php` me. Yeh ek outlet ke saare items ka price ek query me deta hai; jo item list me na ho uska `purchase_price` lagta hai. Stock page aur report dono yahi use karte hain, taaki value tally ho.

Purana `getLastPurchaseAmount()` (`my_helper.php:2213`) nahi badlenge, kyunki Waste aur Adjustment forms use karte hain. Woh sab outlets aur Deleted lines me se newest row uthata hai, isliye naye kaam me use nahi hoga.

## Step 1: Stock page (item 4)

Abhi: columns `inventory.php:26-33`, value `inventory.php:54-56`, top "Stock Value" `frequent_changing/js/inventory.js:6`.

**Naye columns:** SN | Ingredient(Code) | Category | Stock Qty | Low Qty | Purchase Unit Amount | Total Amount

- **Purchase Unit Amount** = `getIngredientPricesAsOf()`, per Pkt/Kg wala price.
- **Total Amount** = (`$totalStock` ÷ Conversion Rate) × Purchase Unit Amount.
- `$total_stock_in_float` hat jayega. Woh "3 Pkt 5 Pcs" ko 3.5 Pkt padhta hai. 14 Pcs/Pkt aur price 3.50 par sahi value 11.75 hai, abhi 12.25 aati hai.
- Top Stock Value = Total Amount column ka sum. Negative stock wali row red dikhegi aur total me ghategi. Abhi `inventory.php:55` negative rows chhod deta hai; yeh badlega taaki column total, top value aur Monthly report ka total tally karein.

**"/Amount" hatana:** `stock_qty_amount` aur `alert_qty_amount` chaaron language files me badlenge.

| Language | Stock | Low/Alert |
|---|---|---|
| English | Stock Qty | Low Qty |
| Arabic | كمية المخزون | كمية تنبيهات المخزون |
| French | Quantité en stock | Quantité d alerte |
| Spanish | Cantidad de stock | Cantidad de alerta |

Yeh labels `inventory.php`, `inventory_food_menu.php`, `report/inventoryReport.php` aur `report/inventoryAlertList.php` me bhi aate hain, wahan bhi badal jayenge. Purchase/Waste forms ka `quantity_amount` nahi chhedna.

Naya lang key: `purchase_unit_amount`. `total_amount` pehle se hai.

**Pass tab:** ek item 3 Pkt 5 Pcs, price 3.50 par Total Amount 11.75 dikhaye, aur column ka sum top Stock Value ke barabar ho.

## Step 2: Opening Stock (item 1)

**Page:** `Inventory::openingStock()`, Stock menu me (`userHome.php:586` aur `:1138`, dono jagah). Access `add-131` aur `checkAccess("131","add")`, yaani jo Inventory Adjustment add kar sakta hai. Naya `tbl_access` nahi lagega.

**Screen:** upar Outlet (session wala) aur Opening Date (zaroori, default mahine ki 1 tareekh). Neeche grid:

SN | Ingredient(Code) | Category | Purchase Unit | Conversion Rate | Current Stock (read-only) | Opening Stock (input)

Input purchase unit me hoga aur decimal chalega (6.5 Pkt), jaise Excel me hai.

**Save:**
1. JS sirf badli hui rows ko ek JSON field me bhejega. DataTables paging doosre page ki rows DOM se hata deta hai, aur `max_input_vars` ki limit bhi hoti hai.
2. Server check karega: qty 0 ya usse zyada ho, aur ingredient isi company ka ho.
3. Transaction me: us outlet + ingredient ki purani opening row `Deleted`, phir qty > 0 ho to nayi row `consumption_amount = qty × conversion_rate`, status `Plus`, ek naye `tbl_inventory_adjustment` doc ke neeche (`is_opening=1`, note "Opening Stock").
4. Conversion Rate 0 ya khaali ho to row lock rahegi, message: pehle rate set karein.

**Iska asar:**
- Stock page, Alert list, Dashboard: kuch nahi badalna, adjustment formula me pehle se hai.
- `Inventory_adjustment::inventoryAdjustments` (`Inventory_adjustment.php:83`) ki list me `is_opening=1` docs nahi dikhenge, taaki galti se edit na hon.
- `Waste_model::getIngredientList` (`Waste_model.php:25`) sirf purchased items dikhata hai. Opening wale items bhi dikhane honge, warna unka waste nahi ban payega.
- Migration na chala ho to `Purchase_model::taxEnabled()` ki tarah `field_exists` se page notice dikhayega, crash nahi hoga.

**Dhyan rakhein:** Opening pehle se bane stock ke upar judta hai. Agar kisi item ka Stock page abhi 50 Pkt dikha raha hai aur Opening 83 daala, to stock 133 ho jayega. Isliye grid me Current Stock saath me dikhega. Sirf aaj ka stock theek karna ho to Inventory Adjustment use karein.

**Pass tab:** Long Chicken (conversion 14) par Opening 83 daalne se Stock page 83 Pkt badhe, Adjustment list me doc na dikhe, aur dobara 80 save karne par stock 3 Pkt ghate.

## Step 3: Monthly Inventory report

**Files:** `Report::monthlyInventoryReport`, `Report_model::monthlyInventoryReport($start, $end, $outlet_id)`, `views/report/monthlyInventoryReport.php`. Access `171` (Inventory Report wala, `Report.php:59`), menu `userHome.php:758` aur `:1310`. Naya `tbl_access` nahi, isliye re-login nahi lagega.

**Filters:** Period Start, Period End, Outlet. Sabhi Live ingredients dikhenge, zero wale bhi, jaise Excel me hain. Order: category, phir naam.

Har movement source ke liye ek `GROUP BY ingredient_id` query, aur PHP me merge. Per-row subquery nahi, warna bade data par page dheema hoga.

`conv` = Conversion Rate (0 ya khaali ho to 1, aur row flag hogi).

| Col | Column | Calculation |
|---|---|---|
| A | No | Serial |
| B | Item Description | `tbl_ingredients.name` |
| C | Unit | Purchase unit |
| D | Pack Size | "1 C = F E", jaise "1 Pkt = 14 Pcs" |
| E | Consumption Unit | `unit_id` wali unit |
| F | Conversion Rate | `conversion_rate` |
| G | Opening Stock | (Period start se pehle ke saare movements + `is_opening` rows jinki date ≤ period end) ÷ conv |
| H | Receiving | Period ki purchase qty + production qty |
| I | Transfer In/(Out) | Period me Received: aaya − gaya, negative brackets me |
| J | Total Stock | G + H + I |
| K | Unit Price | `getIngredientPricesAsOf(outlet, period_end)`, warna `purchase_price` |
| L | Total Stock Value | J × K |
| M | Physical Count | Period end tak ke saare movements ÷ conv |
| N | Physical Count Value | M × K |
| O | Usage | J − M |
| P | Usage Value | O × K |
| Q | Check | Neeche dekhein |
| R | Remarks | Sirf auto text, haath se likhne ki jagah nahi |

**Opening row ka rule:** `is_opening` row hamesha G me jayegi, Usage me nahi, chahe uski date period ke beech ki ho. Isse beech mahine jude item ka Usage galat nahi aayega. M me woh waise bhi shamil hai, isliye `O = period ki consumption + modifier + waste + (adj minus − adj plus)`, sab ÷ conv.

**Check flags:**
- "No unit price": J > 0 aur K = 0
- "Count higher than stock": O < −0.0001
- "Negative stock": M < 0
- "No conversion rate": conv 0 ya khaali
- "Listed twice": same naam do items par (Excel me Straw aur Vinegr)

**TOTAL row:** L, N, P ka sum aur flagged items ki count. Grouped header (Item, Quantity, Total Stock Value, Physical Count, Usage, Review) Excel jaisa. Copy/Excel/PDF/Print export ke saath grouped header test karna hai.

**Excel se farak (bug nahi hai):**
- Usage Value 23,317.48 benchmark nahi hai. Excel khud batata hai ki kai prices per-piece hain aur stock packet me. Long Chicken: 596 Pkt × 3.50 = 2,086, jabki 37.80/Pkt par lagbhag 22,529.
- Unit hamesha purchase unit hogi. Kinza agar Carton (30 cans) me kharida jata hai to 175 cans 5.83 Carton dikhenge.
- Mahine ki boundary par order khulne ki date chalegi. 31 Oct ko khula aur 1 Nov ko paid order October me ginega.

**Pass tab:**
- End date aaj rakhne par har item ka M × conv Stock page ke `$totalStock` ke barabar ho.
- Har item par `Opening(Nov) = Closing(Oct)`.
- Har row par L = N + P.

## Step 4: Waste flow (item 6)

**Chahiye:** food menu chuno, qty likho, us menu ke saare ingredients qty × recipe ke hisaab se aa jayein, aur save par stock se minus hon. Add aur Edit dono par.

Stock wala hissa code me sahi hai: `tbl_waste_ingredients` me Live row banti hai aur formula minus kar deta hai. Waste delete karne par stock wapas aata hai. Dikkat form me hai:

| # | Jagah | Problem | Theek |
|---|---|---|---|
| 1 | `waste/editWaste.php:21` + `add_waste.js:15` | Dono jagah `let ingredient_id_container`. Edit page par `add_waste.js` SyntaxError se load hi nahi hota, poora JS band (node me reproduce kiya) | Inline value `window` par, JS usse padhe |
| 2 | `waste/editWaste.php:228` | `loss_amount / food_menu_waste_qty`. Bina food menu wale waste me qty NULL, PHP 8 par `DivisionByZeroError` aur page crash (PHP 8.2 par confirm) | Unit price = loss ÷ waste amount, guard ke saath |
| 3 | `waste/editWaste.php:229-236` | `<td>` aur delete wala `<a>` tag ka HTML tuta hua, delete kaam nahi karta | HTML theek |
| 4 | `controllers/Waste.php:225` | Sirf `tbl_food_menus_ingredients` padhta hai. Combo aur direct items ki list khaali. Local me 23 me se sirf 6 menus ki recipe hai, baaki 17 par chup-chaap kuch nahi aata | POS jaisa teenon types expand (`Sale.php:2329-2364`). Khaali ho to clear message. `del_status` filter aur `$_GET['id']` ka int cast |
| 5 | `add_waste.js:107` | `last_purchase_price` me row number chala jaata hai aur DB me save hota hai | `v.consumption_unit_cost` |
| 6 | `add_waste.js:125` | `#waste_quantity` keyup me `e` undefined. Edit page par qty badalne se ingredients update nahi honge | Handler theek |
| 7 | `add_waste.js:23, 41, 155` | Delete All ke baad food menu aur qty reset nahi hote, dropdowns disabled reh jate hain. Popup band karne par menu selected rehta hai aur rows nahi aati. Same menu dobara nahi chun sakte | Reset function, dono dropdowns wapas enable |
| 8 | `add_waste.js:21` | `suffix` 0 se shuru, Edit page par naye rows ke ids purane se takrate hain | `#suffix_hidden_field` se shuru |
| 9 | `add_waste.js` qty popup | Sirf khaali check hai, 0 aur negative chal jate hain. `tbl_wastes.food_menu_waste_qty` INT hai, 1.5 kat jata hai | qty > 0 check, column FLOAT |
| 10 | `addWaste.php:9`, `editWaste.php:23` | Script par `?v=` nahi, browser purana JS chalata rahega | `filemtime` version |
| 11 | `add_waste.js` submit | Validation fail par `return true`, form phir bhi post hota tha. Popup ka qty box bhi cart row samjha jaata tha | `return false`, sirf `#waste_cart` ki rows check |

Add page par qty box ab editable hai aur badalne par saari rows recalculate hoti hain. Enter dabane par popup ka form post nahi hota.

**Ek waste entry me ek hi food menu rahega,** kyunki `tbl_wastes.food_menu_id` ek hi column hai.

**Pass tab:**
- Recipe wale menu par qty 3: har ingredient recipe × 3 aaye, loss sahi ho, Stock page utna ghate, aur delete par wapas aaye.
- Combo aur direct item par bhi yahi ho.
- Bina recipe wale menu par message aaye.
- Edit par qty badle to rows aur loss update hon.
- Purana bina-menu waste Edit par khule, crash na ho.

## Step 5: Paid auto-fill (item 5)

**Files:** `add_purchase.js:166-235`, `edit_purchase.js:167-231`, `addPurchase.php:179-181`, `editPurchase.php:191-193`.

- Ek flag `paid_is_manual`. `calculateAll()` me grand total ke baad, flag false ho to `#paid = grand_total`. Isse Item, Other aur Tax kuch bhi badalne par Paid saath chalega aur Due 0 rahega.
- User ne Paid badla to flag true, phir auto nahi chalega. `#paid` par inline `onkeyup="return calculateAll()"` delegated handler se pehle chalta hai, isliye flag usi inline call me set hoga, warna typed value turant overwrite ho jayegi. Sirf value badalne par flag lage, Tab dabane se nahi.
- `deleter()` (`add_purchase.js:253`, `edit_purchase.js:249`) `#paid` khaali karta hai. Ab flag false karke `calculateAll()`.
- **Edit page:** load par saved Paid = saved Grand Total ho to auto, warna manual. Page khulne se partial payment nahi badlega.
- `addPurchase.php:181` me `set_value('paid')` bina `value=` ke attribute ban raha hai, isliye server validation fail hone par typed Paid kho jata hai. Yeh theek karna zaroori hai, warna credit purchase ka 0 chup-chaap full paid ban sakta hai.
- `#paid` khaali chhodkar bahar nikalne par Paid wapas Grand Total ke saath chalne lagta hai.
- Server par sirf Paid = 0 wala crash theek hua (`Purchase.php:205`). Due pehle ki tarah wahan dobara nikalta hai.

**Dhyan rakhein:** credit purchase me cashier ko Paid khud 0 karna padega, warna purchase full paid save hogi aur supplier due nahi banega.

**Pass tab:**
- Item add karne par Paid = Grand Total, Due 0.
- Paid 100 likhne ke baad item badle to Paid 100 hi rahe.
- Paid 0 save ho, poora Due bane.
- Partial wali purchase Edit par khulne se na badle.
- Tax on/off dono me total sahi ho.

## Step 6: POS remove item par Reason (item 2)

**Files:** `main_screen.php:1741-1764` (popup), `pos_script_v7.3.js:6279, 6299, 16677, 16686, 16700`, `Authentication.php:2670`.

- Popup me Reason ka input, default hidden. `requestAdminPassword(message, on_success, options)` me `{require_reason: true}` aane par dikhega. Clear Cart wala flow waisa hi rahega.
- Submit par pehle Reason check: khaali ho to red border aur message, password AJAX nahi jayega. Popup khulte hi purani value saaf.
- `on_success(reason)`, phir `removeCartItemConfirmed(..., reason)`, phir `remove_item_checking` me `reason` jayega.
- **Admin/Manager:** `{require_reason: true, skip_password: true}`, password box nahi dikhta, Reason zaroori.
- Naye lang keys chaaron languages me: `reason`, `reason_required`, `reason_required_to_remove_item`.

**Pehle se chal raha behaviour (nahi badlega):**
- Item cart se hatta hai aur log turant banta hai. Cashier ne Update Order nahi dabaya to item wapas aa sakta hai, par log rahega.
- Running order me naya, abhi na bheja gaya item bina popup hatta hai aur log me Reason khaali rahega.
- Qty minus button par purana hard block hi rahega.

**Pass tab:** non-admin user running order ka item hataye: Reason ke bina submit na ho, galat password par item na hate, sahi par hate aur audit me Reason aaye. Clear Cart me Reason na dikhe.

## Step 7: Audit Log Report (item 3)

**Columns:** SN | Outlet | Done By | Event Title | Date Time | Invoice No | Item Name | Qty | Reason

| Event | Invoice No | Item Name / Qty | Reason |
|---|---|---|---|
| Remove Item (`Authentication.php:2676`) | `sale_no` | Ek item, uski qty | Popup se |
| Cancelled Sale, POS se (`Sale.php:1892`) | `sale_no` | Sabhi items | Pehle se aata hai |
| Cancelled Sale, list se (`Sale.php:3171`) | Sale se | Sabhi items | Khaali |
| Deleted Sale (`Sale.php:441`) | Sale se | Sabhi items | Khaali |
| Refund Sale (`Sale.php:245`) | `sale_no` | Refund items | Khaali |
| AI Insight settings aur baaki | Khaali | Khaali | `details` ka text, taaki jaankari gum na ho |

- **Kai items wali entry:** ek hi report row, Item Name aur Qty alag lines me aligned.
- **Storage:** `putAuditLog()` (`my_helper.php:646`) me optional `$extra` array. Purane callers chalte rahenge. `details` DB me likhna jari rahega, sirf screen se hatega. Customer aur totals jaisi jaankari ab report me nahi dikhegi, par DB me rahegi.
- **Migration na chala ho** to `field_exists` guard, audit likhna band nahi hoga.
- **Purani rows:** helper `auditLogColumns($row)` `details` text se Sale No, items, qty aur Reason nikalega. DB data nahi badlega. Local me 28 Remove Item aur 14 Cancelled rows par test.
- **Security:** `details` abhi bina escape print hota hai (`auditLogReport.php:129`). Naye columns `escape_output` se jayenge, kyunki Reason free text hai.
- **Bug fix:** `auditLogReport.php:128` me `h:m A` hai, jo minutes ki jagah mahina dikhata hai. `h:i A` karna hai.

**Pass tab:** paanchon events par columns sahi bharein, purani rows bhi dikhein, aur Reason me `<script>` likhne par text hi dikhe.

## Migration: `Update/inventory_stock_updates_migration.sql`

```sql
ALTER TABLE tbl_inventory_adjustment
  ADD COLUMN is_opening TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE tbl_audit_logs
  ADD COLUMN sale_no VARCHAR(50) NULL,
  ADD COLUMN item_name TEXT NULL,
  ADD COLUMN qty TEXT NULL,
  ADD COLUMN reason VARCHAR(255) NULL;

ALTER TABLE tbl_wastes
  MODIFY food_menu_waste_qty FLOAT NULL;
```

`item_name` aur `qty` me kai items newline se alag rahenge.

## Kaam ka order

Step 1, 2, 3 isi order me, kyunki Stock page ka helper aur Opening ka flag report me lagta hai. Step 6 ke baad Step 7, kyunki Reason wahin dikhta hai. Step 4 aur 5 alag hain, beech me kabhi bhi ho sakte hain.

Shuru me: branch banana aur migration local `restodb` par chalana.

## Testing

**Local par ho chuka (6 Nov 2026), saaf data par poora suite: 351 model checks + 118 browser checks, 0 fail.**

- **Report:** purchases, Deleted purchase, Received/Sent transfers, waste, adjustments aur Opening, teen mahino par. Har item par L = N + P aur `Opening = pichhla Closing`, aur end date aaj rakhne par har item ka stock aur total value Stock page se tally.
- **Browser (headless Chrome, normal login):** Opening Stock, Waste (recipe, combo, direct, Edit, bina menu wala), Purchase (auto-fill, part payment, credit, Edit), POS popup ke teenon case, Audit report aur purani rows.
- Test data baad me hata diya; test se pehle aur baad ke row counts same hain.

**Abhi baaki:**

- Client ka production DB dump mile to October report Excel ke saath column-wise compare. Local me purchases 0 thi, last sale 28 Sep ki, aur item naam Excel se alag.
- POS test me running order ki pehchaan wale markers (`update_kitchen_status`, `data-sale-no`) script se lagaye gaye the. Ek baar asli Modify Order se item hatakar dekh lena chahiye.
- Print aur Excel download ke buttons dikhte hain aur data sahi hai, par banne wali file aankh se nahi dekhi.

## Deploy checklist

1. Migration chalana, phir files upload.
2. Files:
   - Controllers: `Inventory`, `Report`, `Waste`, `Purchase`, `Authentication`, `Sale`, `Inventory_adjustment`
   - Models: `Report_model`, `Waste_model`, `Inventory_model`, `Inventory_adjustment_model`
   - `helpers/my_helper.php`, aur 4 language files
   - Views: `inventory/inventory`, `inventory/opening_stock` (naya), `report/monthlyInventoryReport` (naya), `report/auditLogReport`, `waste/addWaste`, `waste/editWaste`, `purchase/addPurchase`, `purchase/editPurchase`, `purchase/_tax_fields`, `sale/POS/main_screen`, `sale/POS/hidden_input_html`, `userHome`
   - JS: `add_waste.js`, `add_purchase.js`, `edit_purchase.js`, `pos_script_v7.3.js`, `opening_stock.js` (naya)
   - CSS: `opening_stock.css`, `monthly_inventory_report.css` (dono naye)
3. Master data: Conversion Rate khaali items theek karna (Excel me Salt, Garbage Bag, Gloves). Rate 0 par Stock page purchase ko zero ginta hai.
4. Har outlet ka Opening Stock daalna, us date ke saath jahan se system ka data sahi hai.

## Is plan me nahi hai

- `Report/inventoryReport` ki value purane logic par rahegi. Sirf uske labels badlenge.
- Stock formula ki 12 copies me Conversion Rate 0 wala bug. Master data theek karna hi rasta hai; report aise items ko flag karega.
- Physical count type karne ka screen aur variance column.
- Month lock (purani date ki entry rokna).
- Opening Stock ka Excel upload.
- Qty minus par Reason.

Physical Count, Unit Price aur Pack Size ke faisle upar wali recommended table ke hisaab se lagaye gaye hain; badalne ho to code me ek-ek jagah hain.
