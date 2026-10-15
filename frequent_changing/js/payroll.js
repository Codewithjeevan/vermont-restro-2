/*
 | Payroll module pages. Plain JS on purpose: the list pages load a second
 | jQuery (assets/datatable_custom/jquery-3.3.1.js) that replaces window.$,
 | so nothing here depends on a jQuery plugin. Bootstrap 5 modals are opened
 | through data-bs-toggle, this file only fills them.
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function num(v) {
        v = parseFloat(String(v).replace(/,/g, ''));
        return isNaN(v) ? 0 : v;
    }

    function setField(form, name, value) {
        var el = form.querySelector('[name="' + name + '"]');
        if (el) {
            el.value = value === null || value === undefined ? '' : value;
        }
    }

    function setText(root, selector, text) {
        var el = root.querySelector(selector);
        if (el) {
            el.textContent = text;
        }
    }

    function fmt(v) {
        var p = parseInt(val('pay_precision'), 10);
        return num(v).toFixed(isNaN(p) ? 2 : p);
    }

    onReady(function () {
        // confirm before destructive links / buttons (the viewer shows no native dialog loop issues here)
        document.addEventListener('click', function (e) {
            var el = e.target.closest('.pay-confirm');
            if (!el) {
                return;
            }
            var msg = el.getAttribute('data-confirm') || val('pay_txt_are_you_sure') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        // payroll sheet: fill the edit modal from the row
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-pay-edit]');
            if (!btn) {
                return;
            }
            var d = JSON.parse(btn.getAttribute('data-pay-edit'));
            var modal = document.getElementById('payEditModal');
            if (!modal) {
                return;
            }
            var form = modal.querySelector('form');
            setField(form, 'item_id', d.id);
            setField(form, 'ovr_absent_days', d.ovr_absent_days);
            setField(form, 'ovr_worked_hours', d.ovr_worked_hours);
            setField(form, 'ovr_overtime_hours', d.ovr_overtime_hours);
            setField(form, 'sheet_bonus', num(d.sheet_bonus) ? d.sheet_bonus : '');
            setField(form, 'sheet_deduction', num(d.sheet_deduction) ? d.sheet_deduction : '');
            setField(form, 'note', d.note);
            setText(modal, '.js-emp', d.name);
            setText(modal, '.js-calc-absent', d.absent_days);
            setText(modal, '.js-calc-hours', d.worked_hours);
            setText(modal, '.js-calc-ot', d.overtime_hours);
            var hourly = d.salary_type === 'hourly';
            modal.querySelector('.js-row-absent').style.display = hourly ? 'none' : '';
            modal.querySelector('.js-row-hours').style.display = hourly ? '' : 'none';
        });

        // payroll sheet: fill the pay modal from the row
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-pay-pay]');
            if (!btn) {
                return;
            }
            var d = JSON.parse(btn.getAttribute('data-pay-pay'));
            var modal = document.getElementById('payPayModal');
            if (!modal) {
                return;
            }
            var form = modal.querySelector('form');
            setField(form, 'item_id', d.id);
            setField(form, 'amount', fmt(d.remaining));
            setText(modal, '.js-emp', d.name);
            setText(modal, '.js-net', fmt(d.net));
            setText(modal, '.js-paid', fmt(d.paid));
            setText(modal, '.js-remaining', fmt(d.remaining));
            if (d.payment_mode) {
                var methods = form.querySelector('[name="payment_id"]');
                var wanted = d.payment_mode === 'bank' ? /bank|transfer/i : /cash/i;
                for (var i = 0; methods && i < methods.options.length; i++) {
                    if (wanted.test(methods.options[i].text)) {
                        methods.selectedIndex = i;
                        break;
                    }
                }
            }
        });

        // "paid from till" only makes sense when it is also an expense
        document.querySelectorAll('.js-record-expense').forEach(function (box) {
            var form = box.closest('form');
            var till = form ? form.querySelector('.js-from-till') : null;
            if (!till) {
                return;
            }
            var sync = function () {
                till.disabled = !box.checked;
                if (!box.checked) {
                    till.checked = false;
                }
            };
            box.addEventListener('change', sync);
            sync();
        });

        // adjustments: advance-only fields
        var typeSel = document.getElementById('pay_adj_type');
        if (typeSel) {
            var syncType = function () {
                var adv = typeSel.value === 'advance';
                document.querySelectorAll('.js-advance-only').forEach(function (el) {
                    el.style.display = adv ? '' : 'none';
                });
            };
            typeSel.addEventListener('change', syncType);
            if (window.jQuery) {
                window.jQuery(typeSel).on('change', syncType); // select2 fires jQuery events only
            }
            syncType();
        }

        // salary setup: labels + live preview
        var salaryForm = document.getElementById('paySalaryForm');
        if (salaryForm) {
            var typeEl = salaryForm.querySelector('[name="salary_type"]');
            var refresh = function () {
                var type = typeEl.value;
                var basic = num(salaryForm.querySelector('[name="basic_salary"]').value);
                var allow = 0;
                ['housing_allowance', 'transport_allowance', 'food_allowance', 'other_allowance'].forEach(function (n) {
                    allow += num(salaryForm.querySelector('[name="' + n + '"]').value);
                });
                var label = salaryForm.querySelector('.js-basic-label');
                label.textContent = label.getAttribute('data-' + type);
                var hours = num(val('pay_standard_hours')) || 8;
                var mult = num(val('pay_ot_multiplier')) || 1;
                var gross;
                var perDay;
                var ot;
                if (type === 'monthly') {
                    gross = basic + allow;
                    perDay = gross / 30;
                    ot = basic / 30 / hours * mult;
                } else if (type === 'daily') {
                    gross = basic * 26 + allow;
                    perDay = basic;
                    ot = basic / hours * mult;
                } else {
                    gross = basic * hours * 26 + allow;
                    perDay = basic * hours;
                    ot = basic * mult;
                }
                var otRate = num(salaryForm.querySelector('[name="overtime_rate"]').value);
                setText(salaryForm, '.js-gross', fmt(gross));
                setText(salaryForm, '.js-perday', fmt(perDay));
                setText(salaryForm, '.js-ot', fmt(otRate > 0 ? otRate : ot));
                salaryForm.querySelectorAll('.js-gross-note').forEach(function (el) {
                    el.style.display = el.getAttribute('data-type') === type ? '' : 'none';
                });
            };
            salaryForm.addEventListener('input', refresh);
            salaryForm.addEventListener('change', refresh);
            if (window.jQuery) {
                window.jQuery(typeEl).on('change', refresh);
            }
            refresh();
        }

        // bank fields only for bank payment mode
        var modeSel = document.getElementById('pay_payment_mode');
        if (modeSel) {
            var syncMode = function () {
                document.querySelectorAll('.js-bank-only').forEach(function (el) {
                    el.style.display = modeSel.value === 'bank' ? '' : 'none';
                });
            };
            modeSel.addEventListener('change', syncMode);
            if (window.jQuery) {
                window.jQuery(modeSel).on('change', syncMode);
            }
            syncMode();
        }

        // auto submit filters
        document.querySelectorAll('.js-autosubmit').forEach(function (el) {
            var sent = false;
            var go = function () {
                if (!sent) {
                    sent = true;
                    el.form.submit();
                }
            };
            el.addEventListener('change', go);
            if (window.jQuery) {
                window.jQuery(el).on('change', go);
            }
        });
    });
})();
