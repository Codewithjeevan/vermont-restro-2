# Direct Print (No Popup) — Feature Notes

Goal: stop the browser print flow from locking the cashier out of the POS, and
give each **browser printer** a switch that prints the receipt from the POS
page itself instead of opening a popup window.

**Status: implemented.** Migration `Update/browser_direct_print_migration.sql`
is a hard prerequisite (adds `tbl_printers.browser_direct_print`). Without it
the setting is not shown and the POS keeps the (fixed) popup behaviour.

## 1. What was wrong

Every browser-side print in the POS (invoice, bill, KOT — 4 call sites in
`frequent_changing/js/pos_script_v7.3.js`) did:

```js
var popup = window.open("", "popup","width=100","height=600"); // "height=600" was a 4th arg -> ignored
popup.document.write(html); popup.document.close(); popup.focus();
// ... and the html called window.print() after 1s
```

That popup is a separate top-level window, and two things followed from it:

1. **The POS overlay froze.** `reset_finalize_modal()` starts
   `$(".pos__modal__overlay").fadeOut(300)` right before the popup opens. The
   popup takes the focus; Windows Chrome treats the now-covered POS window as
   *hidden*, `requestAnimationFrame` stops, and the jQuery fade is paused
   half-transparent over the whole screen — nothing in the POS is clickable
   until the window becomes visible again.
2. **The popup stayed open.** With the print dialog in it, in front of the
   POS, until somebody closed it by hand.

## 2. The fix (both modes)

`browserPrintHtml(html, direct_print, opts)` is now the single print target;
all four sites route through it.

- It calls `$(".pos__modal__overlay").stop(true, true)` first — any running
  fade is completed synchronously, so a paused animation can never leave the
  overlay over the POS.
- **Popup mode** (`browser_direct_print = No`, the default) still opens the
  popup (proper `width=420,height=600`) but closes it and hands focus back to
  the POS as soon as the print dialog is gone — printed, saved as PDF or
  cancelled. Two independent triggers, de-duplicated, whichever fires first:
  1. `afterprint` on the popup window (Chrome dispatches it when the preview
     dialog closes — `PrintRenderFrameHelper::OnPrintPreviewDialogClosed`);
  2. `popup.print` is wrapped: the receipt html's own `window.print()` call
     resolves to the wrapper, and since `window.print()` blocks on desktop
     browsers until the dialog is dismissed, the wrapper closes the popup when
     the native call returns. A call that returns at once (< 500 ms, i.e. a
     non-blocking `print()` as in Safari) is left to `afterprint`, closing
     then would tear the dialog down before it is shown. The real `print` is
     kept in `popup.__pos_native_print` so a reused named popup does not
     chain wrappers.
  Verified on the real POS (re-print + Quick Invoice → KOT + invoice) with a
  clean Chrome profile: Cancel → popup gone in ~1 s; Save as PDF → Save As
  dialog → save → popup gone; Save As → cancel → preview re-shown → Cancel →
  popup gone. If the popup blocked it falls back to in-page printing instead
  of throwing.
- **Direct mode** (`browser_direct_print = Yes`) writes the same html into a
  hidden `<iframe id="pos_print_frame">` inside the POS page and prints that
  (`visibility:hidden` + zero size, *not* `display:none` — a frame without
  layout prints blank). `afterprint` removes the frame 2 s later (the dialog
  closes before the job finishes spooling). One frame at a time: a new print
  replaces the previous frame.

### Silent printing

A browser cannot print without a dialog on its own. With direct mode on, run
the POS in Chrome started as

```
chrome.exe --kiosk-printing
```

and every receipt goes straight to the **default printer** with no dialog
at all — the cashier never leaves the POS screen. (Set the thermal printer
as Windows default printer, and disable header/footer once in Chrome's print
settings.) Without the flag the print dialog opens in the POS tab and
`Enter` prints it; the POS is back the moment the dialog closes.

## 3. Setting

Printer → Add / Edit Printer → **Direct Print (No Popup)** (Yes/No). It sits
next to *Print Format* and is only shown for *Browser Popup Print* printers;
a *Direct Print* (print-server / ESC-POS) printer always stores `No`
(`Printer::addEditPrinter`).

Where the flag is read:

| Print | Source of the flag |
|---|---|
| Invoice (`call_print_invoice`) | counter's invoice printer → session `browser_direct_print` → `#browser_direct_print` |
| Bill (`print_bill`) | counter's bill printer → session `browser_direct_print_bill` → `#browser_direct_print_bill` |
| KOT, online batch (`print_kot_popup_print`) | the printer rows the server sends (`tbl_printers.*` via `Common_model::getOrderedPrinter`) — in-page if **any** printer in the batch is `Yes` (the batch is one document) |
| KOT, manual / offline (`print_kot_print`) | has no printer row → `#browser_direct_print_kot` = `getKotBrowserDirectPrint(outlet_id)` (any kitchen printer of the outlet with `Yes`) |

The session copy is rebuilt by `setCounterPrinterSession()` (helper) and by
`Sale::POS()` on every POS load, so a changed setting is picked up on the
next POS load; `browserDirectPrintOf($printer_row)` normalises it and
tolerates a row read before the migration ran.

## 4. Files

- `Update/browser_direct_print_migration.sql`
- `application/controllers/Printer.php` — save the flag
- `application/views/printer/addPrinter.php`, `editPrinter.php`, `printers.php`
- `application/helpers/my_helper.php` — `browserDirectPrintOf()`,
  `getKotBrowserDirectPrint()`, session set/unset
- `application/controllers/Sale.php` — session copy in `POS()`
- `application/views/sale/POS/hidden_input_html.php` — 3 hidden inputs
- `frequent_changing/js/pos_script_v7.3.js` — `browserPrintHtml()`,
  `printHtmlInPopup()`, `printHtmlInFrame()`, `kotBrowserDirectPrint()`
- lang keys `browser_direct_print`, `browser_direct_print_hint` (4 languages)

Not touched: the ESC-POS print server path (`printing_choice = direct_print`),
the admin sale-list print pages (`sale/print_invoice*.php`), kitchen panel.

## 5. Setup flow — silent print on the counter's physical printer

One POS terminal (Windows, Chrome/Edge) + one thermal printer. Kiosk mode
prints to the *default* printer only; a separate silent kitchen printer goes
through Printing Choice = Direct Print (ESC/POS print server) instead.

1. **Windows** — install the printer, print a Windows test page, *Set as
   default* (turn off "Let Windows manage my default printer"), driver paper
   size 80 x 297 mm (58 x 297 for 58 mm rolls).
2. **Printer → Edit** — Printing Choice = Browser Popup Print, Print Format =
   Thermal 80mm / 56mm, **Direct Print (No Popup) = Yes**. Check: list shows
   `web_browser_popup (Direct Print (No Popup))`. Migration must be applied
   first or the field is not shown.
3. **Counter → Edit** — Invoice Printer and Bill Printer = that printer
   (Kitchen → Printer too if KOT prints here). **Reload the POS** (flag is
   copied into the session on POS load). Check: a print no longer opens the
   small `about:blank` popup; the dialog opens inside the POS tab.
4. **Teach Chrome the print settings once** (Chrome in normal mode): print a
   bill, in the dialog pick the thermal printer, paper 80 x 297 mm, Margins
   None, Scale 100, Headers and footers OFF, Background graphics ON, then
   Print. Kiosk mode reuses the last-used settings, so this dialog never
   shows again.
5. **Shortcut** — close every Chrome window, then create a desktop shortcut:
   `"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing "http://<pos-url>/Sale/POS"`
   (optional `--kiosk`, `--user-data-dir="C:\pos-chrome"`; Edge: same flag on
   `msedge.exe`). Check: `chrome://version` → Command Line contains
   `--kiosk-printing`.
6. **Live test** — Place Order (KOT), Invoice, Print Last Invoice: all three
   print with no dialog and the POS stays clickable throughout.

Troubleshooting: dialog appears → Chrome started without the flag (another
window was open); `about:blank` popup → step 2/3 or POS not reloaded; wrong
printer / Save as PDF → Windows default printer; cut-off or A4-sized receipt
→ redo step 4 without the flag; URL/date on top → headers and footers;
nothing prints → Windows print queue (offline / paper).

Shareable Hinglish version of this flow: Claude artifact "Trul Resto Direct Print".
