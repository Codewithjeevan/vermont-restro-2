// Run: node tests/outlet_form_test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const fields = [];
function field(name, value, type = 'text', checked = false) {
    const control = { name, value, type, checked, disabled: false };
    fields.push(control);
    return control;
}
const csrf = field('csrf_token', 'token');
const name = field('outlet_name', 'Updated outlet');
const payload = field('outlet_menu_data', '');
for (let id = 1; id <= 600; id++) {
    field('item_check[]', String(id), 'checkbox', id !== 2);
    field('price_' + id, '10.00');
    field('price_ta_' + id, '0');
    field('price_de_' + id, '12.00');
    field('delivery_person' + id + '[]', '7', 'hidden');
    field('sale_price_delivery_json' + id + '[]', '15.00');
}
const sentinel = field('outlet_form_complete', '1', 'hidden');
let handler;
let restore;
const form = {
    elements: fields,
    addEventListener: (event, callback) => { assert.equal(event, 'submit'); handler = callback; },
    querySelector: selector => { assert.equal(selector, '[name="outlet_menu_data"]'); return payload; }
};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../frequent_changing/js/outlet_form.js'), 'utf8'), {
    document: { querySelectorAll: () => [form] },
    setTimeout: callback => { restore = callback; }
});
handler({ defaultPrevented: true });
assert.equal(payload.value, '');
handler({ defaultPrevented: false });
const menu = JSON.parse(payload.value);
assert.equal(menu.item_check.length, 599);
assert(!menu.item_check.includes('2'));
assert(menu.item_check.includes('600'));
assert.equal(menu.price_ta_600, '0');
assert.deepEqual(menu.delivery_person600, ['7']);
assert.deepEqual(menu.sale_price_delivery_json600, ['15.00']);
assert.deepEqual(fields.filter(control => !control.disabled), [csrf, name, payload, sentinel]);
restore();
assert(fields.every(control => !control.disabled));
handler({ defaultPrevented: false });
assert.deepEqual(JSON.parse(payload.value), menu, 'Repeat submissions must retain prices');
restore();
fields.filter(control => control.type === 'checkbox').forEach(control => { control.checked = false; });
handler({ defaultPrevented: false });
assert.deepEqual(JSON.parse(payload.value).item_check, []);
console.log('PASS: 3,600 menu controls packed, CSRF preserved, unchecked items, zero prices and repeat submits');
