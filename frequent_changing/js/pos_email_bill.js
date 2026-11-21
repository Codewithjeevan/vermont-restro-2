/*
 | POS "Email bill" (docs/email_invoice.md).
 | The box exists only when Settings > Email Settings is on (views/sale/POS/main_screen.php),
 | so every function here does nothing without it. pos_script_v7.3.js calls:
 |   checkSMSDisabled(customer_id)  -> posEmailBill.prepare(customer_id)   payment screen opened
 |   #finalize_order_button click    -> posEmailBill.validate()            false = keep the screen open
 |   add_to_recent_sale_by_ajax()    -> posEmailBill.attach(order_json)    adds send_email_status + email_bill_to
 |   push_online success             -> posEmailBill.kick()                asks the server to send it now
 | The sale itself never waits for email: the server only queues it, sending happens in
 | Email_invoice/process (here, async) or in the Email_cron safety net.
 */
window.posEmailBill = (function ($) {
    "use strict";
    var base = $("#base_url_pos").val() || $("base").attr("data-base") || "";
    var lookupSeq = 0;
    var kickTimer = null;
    var kickBusy = false;

    function present() {
        return $("#email_bill_box").length > 0;
    }
    function autoTick() {
        return String($("#email_bill_box").attr("data-auto_tick")) === "1";
    }
    function valid(email) {
        email = $.trim(email || "");
        return email.length <= 150 && /^[^\s@,;<>]+@[^\s@,;<>]+\.[^\s@,;<>]{2,}$/.test(email);
    }
    function markInput() {
        var email = $.trim($("#email_bill_to").val());
        var bad = $("#check_send_email").is(":checked") && !valid(email);
        $("#email_bill_to").toggleClass("email_bill_invalid", bad && email !== "");
        return !bad;
    }

    /** payment screen opened: clear the box, then fill in the customer's saved email */
    function prepare(customer_id) {
        if (!present()) {
            return;
        }
        var seq = ++lookupSeq;
        $("#check_send_email").prop("checked", false);
        $("#email_bill_to").val("").removeClass("email_bill_invalid");
        customer_id = Number(customer_id);
        if (!customer_id || customer_id === 1 || !navigator.onLine) {
            return; // walk-in customer: the cashier types the address
        }
        $.ajax({
            url: base + "Email_invoice/customerEmail",
            data: { id: customer_id },
            dataType: "json",
            timeout: 5000
        }).done(function (res) {
            // a later prepare() or the cashier typing wins over a slow answer
            if (seq !== lookupSeq || !res || !res.email || $.trim($("#email_bill_to").val()) !== "") {
                return;
            }
            $("#email_bill_to").val(res.email);
            if (autoTick()) {
                $("#check_send_email").prop("checked", true);
            }
        });
    }

    /**
     * Submit pressed: a ticked box needs a valid address. The address is captured here,
     * at the click, because the order json is built a moment later (after an IndexedDB read)
     * and by then the cashier may already be on the next bill.
     */
    var armed; // undefined = validate() not run, null = no email, string = email for the next attach()
    function validate() {
        armed = null;
        if (!present() || !$("#check_send_email").is(":checked")) {
            return true;
        }
        if (markInput()) {
            armed = $.trim($("#email_bill_to").val());
            return true;
        }
        $("#email_bill_to").addClass("email_bill_invalid").focus();
        if (typeof toastr !== "undefined") {
            toastr["error"]($("#ei_pos_invalid").val(), "");
        }
        return false;
    }

    /** add the email fields to the order json; unchanged when no email was asked for */
    function attach(order_json) {
        var email = armed;
        armed = undefined;
        try {
            if (email === undefined) {
                // not reached through the submit button: read the box as it is now
                email = present() && $("#check_send_email").is(":checked") ? $.trim($("#email_bill_to").val()) : null;
            }
            if (!email || !valid(email)) {
                return order_json;
            }
            var order = JSON.parse(order_json);
            order.send_email_status = 1;
            order.email_bill_to = email;
            return JSON.stringify(order);
        } catch (e) {
            return order_json; // never lose a sale because of the email box
        }
    }

    /** ask the server to send what is queued; at most one call at a time */
    function kick(delay) {
        if (!present()) {
            return;
        }
        clearTimeout(kickTimer);
        kickTimer = setTimeout(function () {
            if (kickBusy || !navigator.onLine) {
                return;
            }
            kickBusy = true;
            $.ajax({
                url: base + "Email_invoice/process",
                method: "POST",
                dataType: "json",
                timeout: 60000
            }).done(function (res) {
                // more waiting than one call sends: go again
                if (res && res.ok && res.left > 0 && (res.sent + res.failed + res.retry) > 0) {
                    kickBusy = false;
                    kick(2000);
                }
            }).always(function () {
                kickBusy = false;
            });
        }, typeof delay === "number" ? delay : 1500);
    }

    $(function () {
        if (!present()) {
            return;
        }
        $(document).on("input", "#email_bill_to", function () {
            var email = $.trim($(this).val());
            // typing an address ticks the box, clearing it unticks
            $("#check_send_email").prop("checked", email !== "");
            $(this).removeClass("email_bill_invalid");
        });
        $(document).on("blur", "#email_bill_to", markInput);
        $(document).on("change", "#check_send_email", function () {
            if ($(this).is(":checked") && $.trim($("#email_bill_to").val()) === "") {
                $("#email_bill_to").focus();
            }
            $("#email_bill_to").removeClass("email_bill_invalid");
        });
        // bills queued while this screen was closed, and retries
        kick(15000);
        setInterval(function () { kick(0); }, 180000);
    });

    return { prepare: prepare, validate: validate, attach: attach, kick: kick };
})(jQuery);
