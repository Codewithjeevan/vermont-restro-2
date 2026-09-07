-- ============================================================
-- Optional: pull each company's invoice counter back to the highest
-- INV-<company_id>-<n> actually in use.
--
-- Until the "one number per bill" change, every terminal kept a private
-- buffer of 20 reserved numbers, so tbl_sale_no_counters.last_no ran
-- well ahead of the last bill and the next bill would continue after
-- that hole. Running this once closes the hole: the next bill becomes
-- max(n) + 1.
--
-- RUN ONLY AFTER every POS terminal has reloaded the POS page with the
-- new script (their old buffers are discarded on load). A terminal still
-- on the old script could otherwise bill a buffered number that this
-- statement hands out again. Sale::save_running_order also bumps the
-- counter past any number it sees, which limits the damage, but does not
-- replace the reload.
-- ============================================================

UPDATE tbl_sale_no_counters c
JOIN (
    SELECT company_id, MAX(seq) AS max_seq FROM (
        SELECT company_id,
               CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
          FROM tbl_sales
         WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
        UNION ALL
        SELECT company_id,
               CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED)
          FROM tbl_kitchen_sales
         WHERE company_id IS NOT NULL AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
        UNION ALL
        SELECT company_id,
               CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED)
          FROM tbl_running_orders
         WHERE company_id > 0 AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
    ) t
    GROUP BY company_id
) m ON m.company_id = c.company_id
SET c.last_no = m.max_seq
WHERE c.last_no > m.max_seq;

-- A company with a counter row but no INV- bill at all starts from 1 again.
UPDATE tbl_sale_no_counters c
LEFT JOIN tbl_kitchen_sales k ON k.company_id = c.company_id AND k.sale_no REGEXP CONCAT('^INV-', c.company_id, '-[0-9]+')
LEFT JOIN tbl_sales s ON s.company_id = c.company_id AND s.sale_no REGEXP CONCAT('^INV-', c.company_id, '-[0-9]+')
SET c.last_no = 0
WHERE k.id IS NULL AND s.id IS NULL;
