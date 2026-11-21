// POS "Email bill" in a real (headless) Chrome. Run by pos_browser_test.php, which prepares
// the database, the session and the fake SMTP server, and checks the results afterwards.
//   node tests/email_invoice/pos_browser.mjs <base_url> <session_id> <user_id> <outlet_id> <customer_id> <shot_dir>
// Prints one JSON line per check: {"ok":bool,"label":"..."}, then {"done":true,...}.
import { launch } from './cdp.mjs';

const [base, sid, userId, outletId, customerId, shotDir] = process.argv.slice(2);
const host = new URL(base).hostname;
const out = (o) => console.log(JSON.stringify(o));
const check = (ok, label) => out({ ok: !!ok, label });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const b = await launch(9333 + Math.floor(Math.random() * 500));
try {
    await b.send('Network.setCookie', { name: 'sess', value: sid, domain: host, path: '/' });
    // the POS prints through a popup / print dialog: make both harmless in headless Chrome
    await b.send('Page.addScriptToEvaluateOnNewDocument', { source: 'window.print = function () {}; window.alert = function () {};' });
    await b.goto(`${base}Sale/POS/${userId}/${outletId}`);
    await b.waitFor('window.jQuery && window.posEmailBill && document.querySelector("#email_bill_box") && document.querySelector(".single_item")', 30000, 'POS loaded');
    // IndexedDB + menu settle
    await sleep(2500);
    await b.shot(`${shotDir}/1-pos.png`);
    check(true, 'POS loaded with the Email bill box');

    // one sale: Take Away, a customer with a saved email, one item, Direct invoice -> payment screen
    async function newBill(customer) {
        // the POS opens on the Table View: its Take Away button goes to the menu
        await b.eval('jQuery("#table_view_take_away:visible").click(), true');
        await sleep(700);
        await b.eval(`(function(){
            var $ = jQuery;
            $(".take_away_button").first().click();
            var c = ${JSON.stringify(String(customer))};
            if (!$("#walk_in_customer option[value='" + c + "']").length) {
                $("#walk_in_customer").append(new Option("Test customer " + c, c, false, false));
            }
            $("#walk_in_customer").val(c).trigger("change");
            var w = $("#select_waiter option").filter(function () { return this.value; }).first().val();
            if (w) { $("#select_waiter").val(w).trigger("change"); }
            $(".single_item:visible").first().click();
            return true;
        })()`);
        await sleep(800);
        await b.waitFor('jQuery(".order_holder .single_order").length > 0', 10000, 'item in cart');
        if (process.env.EI_DEBUG) {
            out({ debug: await b.eval(`({ items: (window.items || []).length, rows: jQuery(".order_holder .single_order").map(function(){ return this.id + "|" + jQuery(this).text().replace(/\\s+/g, " ").slice(0, 60); }).get() })`) });
        }
        // "Quick Invoice" = place_order_operation data-type 1
        await b.eval('jQuery(".select2-container--open").length && jQuery("body").click(), jQuery(".place_order_operation[data-type=1]").first().click(), true');
        await b.waitFor('jQuery("#order_payment_modal").hasClass("active")', 15000, 'payment screen open');
    }

    // ---- 1. saved customer email is offered and ticked
    await newBill(customerId);
    await b.waitFor('jQuery("#email_bill_to").val() !== ""', 8000, 'email prefilled');
    const pre = await b.eval('({ v: jQuery("#email_bill_to").val(), c: jQuery("#check_send_email").is(":checked") })');
    check(pre.v === 'saved-cust@example.com' && pre.c, `payment screen: customer email prefilled + ticked (${pre.v}, ${pre.c})`);
    await b.shot(`${shotDir}/2-payment.png`);

    // ---- 2. a bad address keeps the screen open
    await b.eval('jQuery("#email_bill_to").val("bad@").trigger("input"), jQuery("#check_send_email").prop("checked", true), true');
    await b.eval('jQuery("#finalize_order_button").click(), true');
    await sleep(600);
    const stillOpen = await b.eval('jQuery("#order_payment_modal").hasClass("active") && jQuery("#email_bill_to").hasClass("email_bill_invalid")');
    check(stillOpen, 'bad address: payment screen stays open, field marked');

    // ---- 3. pay with the cashier's own address
    await b.eval('jQuery("#email_bill_to").val("typed-cust@example.com").trigger("input"), true');
    const ticked = await b.eval('jQuery("#check_send_email").is(":checked")');
    check(ticked, 'typing an address ticks the box');
    await b.eval('jQuery("#finalize_order_button").click(), true');
    await b.waitFor('!jQuery("#order_payment_modal").hasClass("active")', 10000, 'payment screen closed');
    check(true, 'bill 1 paid with Email bill ticked');
    await sleep(1500);

    // ---- 4. walk-in, box untouched: no email
    await newBill(1);
    await sleep(800);
    const walk = await b.eval('({ v: jQuery("#email_bill_to").val(), c: jQuery("#check_send_email").is(":checked") })');
    check(walk.v === '' && !walk.c, 'walk-in customer: box empty and not ticked (reset from the last bill)');
    // a walk-in bill must be paid in full: quick cash for the payable, then Add
    await b.eval(`(function(){ var $ = jQuery, p = $("#finalize_total_payable").text();
        $("#finalize_given_amount_input").val(p).trigger("keyup");
        $("#finalize_amount_input").val(p).trigger("keyup");
        return true; })()`);
    await sleep(300);
    await b.eval('jQuery("#add_payment").click(), true');
    await b.waitFor('Number(jQuery("#finalize_total_due").text()) === 0', 5000, 'paid in full');
    await b.eval('jQuery("#finalize_order_button").click(), true');
    await b.waitFor('!jQuery("#order_payment_modal").hasClass("active")', 10000, 'payment screen closed');
    check(true, 'bill 2 paid without email');

    // push_online runs every 10 s: wait for both bills to reach the server
    await sleep(25000);
    await b.shot(`${shotDir}/3-after.png`);
    check(b.errors.length === 0, 'no JavaScript errors' + (b.errors.length ? ': ' + b.errors.slice(0, 3).join(' | ') : ''));
    out({ done: true });
} catch (e) {
    try { await b.shot(`${shotDir}/error.png`); } catch (x) { /* ignore */ }
    check(false, 'browser run stopped: ' + e.message + (b.errors.length ? ' | js errors: ' + b.errors.slice(0, 3).join(' | ') : ''));
    out({ done: true });
} finally {
    await b.close();
}
