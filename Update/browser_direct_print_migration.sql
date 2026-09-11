-- ============================================================
-- Feature: Direct Print (No Popup) for browser printers
--
-- tbl_printers.browser_direct_print
--    Only meaningful for printers whose printing_choice is
--    "web_browser_popup" (browser print). 'No' (default) keeps the
--    old behaviour: every invoice / bill / KOT opens a popup window
--    and prints from there.
--    'Yes' renders the same receipt into a hidden iframe INSIDE the
--    POS page and prints it from there: no popup window, no focus
--    change, the POS is never locked behind a stray print window.
--    With Chrome started as  chrome.exe --kiosk-printing  the receipt
--    goes straight to the default printer without any print dialog.
--
-- Set from Printer -> Add / Edit Printer -> "Direct Print (No Popup)".
-- Cashiers pick it up on next POS load (the printer row is copied
-- into the session when the POS opens).
--
-- Safe / additive. Apply once per database.
-- ============================================================

ALTER TABLE tbl_printers
    ADD COLUMN browser_direct_print VARCHAR(10) NOT NULL DEFAULT 'No' AFTER print_format;

-- Rollback:
-- ALTER TABLE tbl_printers DROP COLUMN browser_direct_print;
