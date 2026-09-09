-- ============================================================
-- DAILY SALE REPORT  (one day, one outlet)
--
-- Change only the two lines below, then run the whole file:
--     mysql -uroot --database=<db> --table < docs/daily_sale_report.sql
--
-- Why DATE(date_time) and not sale_date:
--   sale_date is the invoice date the POS put on the bill and it can be
--   back-dated, so it does not always mean "sold on this day".
--   date_time is when the bill was actually made, and it is the same basis
--   the register report counts money on - the two totals agree.
--   See docs/report_date_semantics notes / the memory of the same name.
-- ============================================================

SET @day    := '2026-09-08';   -- <<< business day
SET @outlet := 3;              -- <<< outlet id


-- ---------- 1. SUMMARY ----------
SELECT
  DATE_FORMAT(@day,'%d-%b-%Y')                            AS Business_Day,
  COUNT(*)                                                AS Total_Bills,
  SUM(s.total_items)                                      AS Total_Items,
  CONCAT('AED ', FORMAT(SUM(s.sub_total),2))              AS Sub_Total,
  CONCAT('AED ', FORMAT(SUM(s.total_discount_amount),2))  AS Discount,
  CONCAT('AED ', FORMAT(SUM(s.vat),2))                    AS Tax,
  CONCAT('AED ', FORMAT(SUM(s.total_payable),2))          AS NET_SALE
FROM tbl_sales s
WHERE s.outlet_id = @outlet AND s.order_status = '3' AND s.del_status = 'Live'
  AND DATE(s.date_time) = @day;


-- ---------- 2. PAYMENT METHOD WISE ----------
SELECT
  IF(GROUPING(pm.name),'>>> TOTAL', IFNULL(pm.name,'(none)')) AS Payment_Method,
  COUNT(*)                                                    AS Transactions,
  CONCAT('AED ', FORMAT(SUM(sp.amount),2))                    AS Amount
FROM tbl_sale_payments sp
JOIN tbl_sales s                 ON s.id = sp.sale_id AND s.order_status = '3' AND s.del_status = 'Live'
LEFT JOIN tbl_payment_methods pm ON pm.id = sp.payment_id
WHERE s.outlet_id = @outlet AND sp.del_status = 'Live' AND sp.currency_type IS NULL
  AND DATE(s.date_time) = @day
GROUP BY pm.name WITH ROLLUP;


-- ---------- 3. ORDER TYPE WISE ----------
SELECT
  IF(GROUPING(s.order_type),'>>> TOTAL',
     CASE s.order_type WHEN 1 THEN 'Dine In' WHEN 2 THEN 'Take Away' WHEN 3 THEN 'Delivery' ELSE 'Other' END) AS Order_Type,
  COUNT(*)                                        AS Bills,
  CONCAT('AED ', FORMAT(SUM(s.total_payable),2))  AS Amount
FROM tbl_sales s
WHERE s.outlet_id = @outlet AND s.order_status = '3' AND s.del_status = 'Live'
  AND DATE(s.date_time) = @day
GROUP BY s.order_type WITH ROLLUP;


-- ---------- 4. CASHIER WISE ----------
SELECT
  IF(GROUPING(u.full_name),'>>> TOTAL', IFNULL(u.full_name,'(unknown)')) AS Cashier,
  COUNT(*)                                        AS Bills,
  CONCAT('AED ', FORMAT(SUM(s.total_payable),2))  AS Amount
FROM tbl_sales s
LEFT JOIN tbl_users u ON u.id = s.user_id
WHERE s.outlet_id = @outlet AND s.order_status = '3' AND s.del_status = 'Live'
  AND DATE(s.date_time) = @day
GROUP BY u.full_name WITH ROLLUP;


-- ---------- 5. BILL WISE DETAIL ----------
SELECT
  s.sale_no                           AS Bill_No,
  DATE_FORMAT(s.date_time,'%h:%i %p') AS Time,
  u.full_name                         AS Cashier,
  CASE s.order_type WHEN 1 THEN 'Dine In' WHEN 2 THEN 'Take Away' WHEN 3 THEN 'Delivery' ELSE '-' END AS Order_Type,
  s.total_items                       AS Items,
  FORMAT(s.sub_total,2)               AS Sub_Total,
  FORMAT(s.total_discount_amount,2)   AS Discount,
  FORMAT(s.vat,2)                     AS Tax,
  FORMAT(s.total_payable,2)           AS Total,
  (SELECT GROUP_CONCAT(pm.name ORDER BY pm.name SEPARATOR '+')
     FROM tbl_sale_payments sp
     LEFT JOIN tbl_payment_methods pm ON pm.id = sp.payment_id
    WHERE sp.sale_id = s.id AND sp.del_status = 'Live') AS Paid_By
FROM tbl_sales s
LEFT JOIN tbl_users u ON u.id = s.user_id
WHERE s.outlet_id = @outlet AND s.order_status = '3' AND s.del_status = 'Live'
  AND DATE(s.date_time) = @day
ORDER BY s.date_time;


-- ---------- 6. SANITY CHECK: bills made today but dated another day ----------
-- Should return no rows. Any row here is a bill whose printed invoice date
-- does not match the day it was actually made, which is what makes the
-- Sale Report and the Register Report disagree.
SELECT
  s.sale_no                                    AS Bill_No,
  DATE_FORMAT(s.date_time,'%d-%b-%Y %h:%i %p') AS Actually_Made_On,
  DATE_FORMAT(s.sale_date,'%d-%b-%Y')          AS Date_Printed_On_Bill,
  CONCAT('AED ', FORMAT(s.total_payable,2))    AS Amount,
  u.full_name                                  AS Cashier
FROM tbl_sales s
LEFT JOIN tbl_users u ON u.id = s.user_id
WHERE s.outlet_id = @outlet AND s.order_status = '3' AND s.del_status = 'Live'
  AND DATE(s.date_time) = @day
  AND s.sale_date <> DATE(s.date_time);
