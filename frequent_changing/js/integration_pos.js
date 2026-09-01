/**
 * POS incoming-orders widget for the integration platform.
 *
 * Shows channel orders that landed but have not been accepted yet
 * (tbl_kitchen_sales.is_accept = 2) and gives the cashier Accept / Reject.
 *
 * Accepting flips the order to is_accept = 1, which is exactly what the POS
 * already does for a self-order - the existing getWaiterOrders poll then pulls
 * it into the running-orders list and the KOT prints. So this file adds a
 * surface, not a second order pipeline.
 *
 * Deliberately self-contained: it injects its own CSS and DOM, so wiring it up
 * is one <script> tag in main_screen.php rather than edits scattered through a
 * 17k-line pos_script.
 */
(function () {
    'use strict';

    var POLL_MS = 15000;
    var seen = {};          // order id -> true, so the alert only fires for genuinely new ones
    var firstRun = true;
    var polling = null;

    function baseUrl() {
        var el = document.getElementById('base_url_pos');
        if (el && el.value) { return el.value; }
        return (typeof base_url !== 'undefined') ? base_url : '/';
    }

    function t(key, fallback) {
        var box = document.getElementById('integration_pos_lang');
        if (box && box.dataset && box.dataset[key]) { return box.dataset[key]; }
        return fallback;
    }

    function injectStyles() {
        if (document.getElementById('integration_pos_styles')) { return; }
        var css = ''
            + '#integration_pos_fab{position:fixed;right:18px;bottom:18px;z-index:10050;'
            + 'background:#1f6feb;color:#fff;border:none;border-radius:28px;padding:12px 18px;'
            + 'font-size:14px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.25);cursor:pointer;display:none;}'
            + '#integration_pos_fab.has_orders{display:block;background:#e5484d;animation:integration_pulse 1.6s infinite;}'
            + '@keyframes integration_pulse{0%{box-shadow:0 0 0 0 rgba(229,72,77,.6);}'
            + '70%{box-shadow:0 0 0 14px rgba(229,72,77,0);}100%{box-shadow:0 0 0 0 rgba(229,72,77,0);}}'
            + '#integration_pos_panel{position:fixed;right:18px;bottom:78px;z-index:10050;width:390px;max-height:70vh;'
            + 'overflow-y:auto;background:#fff;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.28);display:none;}'
            + '#integration_pos_panel .ip_head{padding:12px 16px;border-bottom:1px solid #eaecef;font-weight:600;'
            + 'display:flex;justify-content:space-between;align-items:center;}'
            + '#integration_pos_panel .ip_close{cursor:pointer;font-size:20px;line-height:1;color:#888;}'
            + '.ip_order{padding:12px 16px;border-bottom:1px solid #f1f3f5;}'
            + '.ip_order .ip_top{display:flex;justify-content:space-between;font-weight:600;margin-bottom:4px;}'
            + '.ip_order .ip_meta{font-size:12px;color:#666;margin-bottom:8px;word-break:break-word;}'
            + '.ip_order .ip_wait{color:#e5484d;font-weight:600;}'
            + '.ip_order button{border:none;border-radius:5px;padding:6px 14px;font-size:13px;cursor:pointer;margin-right:6px;}'
            + '.ip_accept{background:#2da44e;color:#fff;}'
            + '.ip_reject{background:#e5484d;color:#fff;}'
            + '.ip_order button[disabled]{opacity:.5;cursor:default;}'
            + '.ip_empty{padding:18px 16px;color:#888;font-size:13px;}'
            + '.ip_chan{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:4px;'
            + 'padding:1px 6px;font-size:11px;text-transform:uppercase;margin-right:6px;}';

        var style = document.createElement('style');
        style.id = 'integration_pos_styles';
        style.appendChild(document.createTextNode(css));
        document.head.appendChild(style);
    }

    function injectDom() {
        if (document.getElementById('integration_pos_fab')) { return; }

        var fab = document.createElement('button');
        fab.id = 'integration_pos_fab';
        fab.type = 'button';
        document.body.appendChild(fab);

        var panel = document.createElement('div');
        panel.id = 'integration_pos_panel';
        panel.innerHTML = '<div class="ip_head"><span>' + t('incoming', 'Incoming channel orders') +
                          '</span><span class="ip_close">&times;</span></div><div id="integration_pos_list"></div>';
        document.body.appendChild(panel);

        fab.addEventListener('click', function () {
            panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
        });
        panel.querySelector('.ip_close').addEventListener('click', function () {
            panel.style.display = 'none';
        });
    }

    /** A short beep, so a busy counter notices without needing an audio asset. */
    function beep() {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) { return; }
            var ctx = new Ctx();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) { /* audio is a nicety, never a failure */ }
    }

    function esc(text) {
        return String(text === null || text === undefined ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function render(orders) {
        var fab = document.getElementById('integration_pos_fab');
        var list = document.getElementById('integration_pos_list');
        if (!fab || !list) { return; }

        if (!orders.length) {
            fab.className = '';
            fab.style.display = 'none';
            list.innerHTML = '<div class="ip_empty">' + t('none', 'No incoming orders.') + '</div>';
            document.getElementById('integration_pos_panel').style.display = 'none';
            return;
        }

        fab.className = 'has_orders';
        fab.style.display = 'block';
        fab.textContent = orders.length + ' ' + t('incoming', 'Incoming channel orders');

        var html = '';
        var isNew = false;
        orders.forEach(function (order) {
            if (!seen[order.id]) { isNew = true; seen[order.id] = true; }
            html += '<div class="ip_order" data-id="' + order.id + '">'
                 +    '<div class="ip_top"><span><span class="ip_chan">' + esc(order.provider) + '</span>#'
                 +      esc(order.order_no) + '</span><span>' + Number(order.total_payable).toFixed(2) + '</span></div>'
                 +    '<div class="ip_meta">' + esc(order.sale_no) + ' &middot; ' + order.total_items + ' items'
                 +      ' &middot; <span class="ip_wait">' + order.waiting_mins + ' ' + t('mins', 'min') + ' ' + t('waiting', 'waiting') + '</span>'
                 +      (order.address ? '<br>' + esc(order.address) : '')
                 +    '</div>'
                 +    '<button type="button" class="ip_accept">' + t('accept', 'Accept') + '</button>'
                 +    '<button type="button" class="ip_reject">' + t('reject', 'Reject') + '</button>'
                 +  '</div>';
        });
        list.innerHTML = html;

        // Do not shout on the very first poll after a page load - every open
        // order would look new and the counter would be beeped at for nothing.
        if (isNew && !firstRun) {
            beep();
            document.getElementById('integration_pos_panel').style.display = 'block';
        }
        firstRun = false;
    }

    function poll() {
        $.ajax({
            url: baseUrl() + 'Integration/posPendingOrders',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.status === 'ok') { render(res.orders || []); }
            }
            // a failed poll is silent on purpose: the POS must not show an error
            // banner every 15s because an integration screen is unreachable
        });
    }

    function act(orderId, isReject, button) {
        var reason = '';
        if (isReject) {
            reason = window.prompt(t('rejectPrompt', 'Reason for rejecting this order?'), 'ITEM_86');
            if (reason === null) { return; }
        }
        button.disabled = true;

        var data = { order_id: orderId, reason: reason };
        var csrfName = $('#csrf_name_').val();
        if (csrfName) { data[csrfName] = $('#csrf_value_').val(); }

        $.ajax({
            url: baseUrl() + 'Integration/' + (isReject ? 'posRejectOrder' : 'posAcceptOrder'),
            method: 'POST',
            dataType: 'json',
            data: data,
            success: function (res) {
                if (res && res.status === 'ok') {
                    if (typeof toastr !== 'undefined') { toastr.success(res.message || 'OK', ''); }
                    delete seen[orderId];
                    poll();
                } else {
                    if (typeof toastr !== 'undefined') { toastr.error((res && res.message) || 'Failed', ''); }
                    else { alert((res && res.message) || 'Failed'); }
                    button.disabled = false;
                }
            },
            error: function () {
                if (typeof toastr !== 'undefined') { toastr.error('Request failed', ''); }
                button.disabled = false;
            }
        });
    }

    $(function () {
        if (typeof $ === 'undefined') { return; }
        injectStyles();
        injectDom();

        $(document).on('click', '#integration_pos_list .ip_accept, #integration_pos_list .ip_reject', function () {
            var row = $(this).closest('.ip_order');
            act(row.data('id'), $(this).hasClass('ip_reject'), this);
        });

        poll();
        polling = setInterval(poll, POLL_MS);
        $(window).on('beforeunload', function () { clearInterval(polling); });
    });
}());
