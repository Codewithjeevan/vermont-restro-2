(function () {
    "use strict";

    document.querySelectorAll("form.outlet-form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            if (event.defaultPrevented) {
                return;
            }

            // Hundreds of menu prices can exceed PHP's max_input_vars.
            // Keep the normal form/CSRF submission, but send menus as one value.
            var menu = { item_check: [] };
            var fields = Array.prototype.filter.call(form.elements, function (field) {
                return !field.disabled && /^(item_check\[\]|price_(?:ta_|de_)?\d+|delivery_person\d+\[\]|sale_price_delivery_json\d+\[\])$/.test(field.name);
            });

            fields.forEach(function (field) {
                if (field.type === "checkbox" && !field.checked) {
                    return;
                }
                if (field.name.slice(-2) === "[]") {
                    var name = field.name.slice(0, -2);
                    if (!menu[name]) {
                        menu[name] = [];
                    }
                    menu[name].push(field.value);
                } else {
                    menu[field.name] = field.value;
                }
            });

            form.querySelector('[name="outlet_menu_data"]').value = JSON.stringify(menu);
            fields.forEach(function (field) { field.disabled = true; });
            // Restore controls if another handler cancels submission or on Back.
            setTimeout(function () {
                fields.forEach(function (field) { field.disabled = false; });
            }, 0);
        });
    });
}());
