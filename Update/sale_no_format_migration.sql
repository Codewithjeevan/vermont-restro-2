-- ============================================================
-- Feature: New invoice number format  INV-<company_id>-<n>
--
-- The POS used to build sale_no in the browser from a localStorage
-- counter (aGY260728-003 style), which cannot stay unique once more
-- than one counter/terminal bills at the same time. The number is now
-- handed out by the server from a single per-company counter.
--
-- <n> starts at 1 and simply keeps incrementing - it never resets.
-- Safe / additive. Apply once per database.
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbl_sale_no_counters` (
    `company_id` INT(11) NOT NULL,
    `last_no`    BIGINT(20) NOT NULL DEFAULT 0,
    PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Seed each company from the highest INV- number already on record, so
-- re-running this file (or applying it to a database that has already
-- billed with the new format) can never hand out a number twice.
-- Split bills look like INV-1-42-001, so only the segment right after
-- the company id counts.
INSERT INTO `tbl_sale_no_counters` (`company_id`, `last_no`)
SELECT company_id, MAX(seq) FROM (
    SELECT company_id,
           CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
      FROM tbl_sales
     WHERE company_id IS NOT NULL
       AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
    UNION ALL
    SELECT company_id,
           CAST(SUBSTRING_INDEX(SUBSTRING(sale_no, CHAR_LENGTH(CONCAT('INV-', company_id, '-')) + 1), '-', 1) AS UNSIGNED) AS seq
      FROM tbl_kitchen_sales
     WHERE company_id IS NOT NULL
       AND sale_no REGEXP CONCAT('^INV-', company_id, '-[0-9]+')
) t
GROUP BY company_id
ON DUPLICATE KEY UPDATE `last_no` = GREATEST(`tbl_sale_no_counters`.`last_no`, VALUES(`last_no`));

-- Optional: start a company's numbering somewhere other than 1, e.g. to
-- continue an existing paper series. Run before the first POS bill.
--   INSERT INTO tbl_sale_no_counters (company_id, last_no) VALUES (1, 5000)
--   ON DUPLICATE KEY UPDATE last_no = 5000;

-- Rollback:
-- DROP TABLE tbl_sale_no_counters;
