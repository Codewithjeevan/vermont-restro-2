<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Canonical Order DTO  ->  POS tables.
 *
 * This is the headless twin of Sale::add_kitchen_sale_by_ajax(). That method
 * reads session('outlet_id' / 'company_id' / 'user_id') throughout, and a
 * webhook has no session, so it cannot be reused - but the rows it writes are
 * the contract, and this class writes exactly the same shape.
 *
 * It knows POS tables and nothing about any aggregator: no provider name, no
 * provider field, ever. Everything it needs arrives on the DTO or the config.
 *
 * Writes, in one transaction:
 *   tbl_kitchen_sales
 *   tbl_kitchen_sales_details
 *   tbl_kitchen_sales_details_modifiers
 *
 * The billing pair (tbl_sales / tbl_sales_details) is deliberately NOT written -
 * that happens when the cashier settles, exactly as for a walk-in order.
 *
 * Timezone note: the tbl_integration_* tables store UTC, but tbl_kitchen_sales
 * is read by POS screens that assume application-local time, so this class
 * calls setDefaultTimezone() and writes local. Mixing the two conventions in
 * one table is what makes reports disagree.
 */
class Order_ingestor
{
    /** @var CI_Controller */
    protected $CI;

    /** VAT-inclusive back-computation is only exact to the cent; warn past this. */
    const TOTAL_TOLERANCE = 0.05;

    public function __construct()
    {
        $this->CI = & get_instance();
        $this->CI->load->model('Integration_model');
    }

    /* =================================================================== main */

    /**
     * @param  array  $dto                   Canonical Order DTO (doc section 2.1)
     * @param  object $config                tbl_integration_configs row
     * @param  int    $integration_order_id  tbl_integration_orders.id claimed by the webhook
     * @return array  ['ok','error','error_code','kitchen_sale_id','sale_no','unmapped','warnings']
     */
    public function ingest($dto, $config, $integration_order_id = null)
    {
        $result = array(
            'ok' => false, 'error' => '', 'error_code' => '',
            'kitchen_sale_id' => 0, 'sale_no' => '', 'unmapped' => array(), 'warnings' => array(),
        );

        $company_id = (int) $config->company_id;
        $outlet_id  = (int) $config->outlet_id;

        if (empty($dto['items']) || !is_array($dto['items'])) {
            $result['error']      = 'Order has no items';
            $result['error_code'] = 'NO_ITEMS';
            return $result;
        }

        /* ---- 1. resolve their SKUs to our menu ids ------------------------
         * Rule: an unmapped item rejects the whole order. Dropping a line
         * silently is a refund and a rating hit; a rejection is recoverable. */
        $resolved = $this->resolve_items($dto['items'], $config);
        if (!empty($resolved['unmapped'])) {
            $result['unmapped']   = $resolved['unmapped'];
            $result['error_code'] = 'UNMAPPED_ITEM';
            $result['error']      = 'Unmapped item(s): '.implode(', ', array_map(function ($u) {
                return $u['external_item_id'].($u['name'] !== '' ? ' ('.$u['name'].')' : '');
            }, $resolved['unmapped']));
            return $result;
        }
        $lines = $resolved['lines'];

        /* ---- 2. price + tax each line ---------------------------------- */
        $price_includes_tax = (isset($config->price_includes_tax) && $config->price_includes_tax === 'Yes');
        $sub_total = 0.0;
        $total_vat = 0.0;
        $vat_buckets = array();   // tax_field_id => ['type'=>..,'amount'=>..]

        foreach ($lines as $i => $line) {
            $priced = $this->price_line($line, $config, $price_includes_tax);
            $lines[$i] = array_merge($line, $priced);
            $sub_total += $priced['line_net'];
            $total_vat += $priced['line_vat'];
            foreach ($priced['tax_objects'] as $tax) {
                $id = (string) $tax['tax_field_id'];
                if (!isset($vat_buckets[$id])) {
                    $vat_buckets[$id] = array('type' => $tax['tax_field_name'], 'amount' => 0.0);
                }
                $vat_buckets[$id]['amount'] += (float) $tax['item_vat_amount_for_all_quantity'];
            }
        }

        $charges  = isset($dto['charges']) ? $dto['charges'] : array();
        $discount = round($this->f($charges, 'discount'), 2);
        $delivery = round($this->f($charges, 'delivery'), 2);
        $tip      = round($this->f($charges, 'tip'), 2);
        $service  = round($this->f($charges, 'service'), 2);

        $sub_total = round($sub_total, 2);
        $total_vat = round($total_vat, 2);
        $sub_total_with_discount = round($sub_total - $discount, 2);
        $computed_total = round($sub_total_with_discount + $total_vat + $delivery + $tip + $service, 2);

        // The aggregator's number is what the customer actually paid, so it
        // wins. A gap means our menu prices or tax mode disagree with theirs -
        // surface it rather than quietly booking a different amount.
        $provider_total = isset($dto['totals']['total_payable']) ? round((float) $dto['totals']['total_payable'], 2) : null;
        $total_payable  = $computed_total;
        if ($provider_total !== null && $provider_total > 0) {
            $total_payable = $provider_total;
            if (abs($provider_total - $computed_total) > self::TOTAL_TOLERANCE) {
                $result['warnings'][] = sprintf(
                    'Total mismatch: provider %.2f vs computed %.2f - check price_mode / price_includes_tax / item mapping.',
                    $provider_total, $computed_total
                );
            }
        }

        /* ---- 3. bill number from the server counter, never invented ----- */
        $numbers = reserveCompanySaleNumbers($company_id, 1);
        if (empty($numbers)) {
            $result['error']      = 'Could not reserve a sale number for company '.$company_id;
            $result['error_code'] = 'NO_SALE_NO';
            return $result;
        }
        $sale_no = $numbers[0];

        /* ---- 4. who / when --------------------------------------------- */
        setDefaultTimezone();                  // POS screens read local time
        $now_local  = date('Y-m-d H:i:s');
        $sale_date  = date('Y-m-d');
        $order_time = date('H:i:s');

        $customer_id = $this->channel_customer_id($config, $dto);
        $user_id     = $this->ingest_user_id($config);
        $auto_accept = (isset($config->auto_accept) && $config->auto_accept === 'Yes');

        $customer      = isset($dto['customer']) ? $dto['customer'] : array();
        $customer_name = $this->s($customer, 'name', 'Online Customer');
        $del_address   = trim($this->s($customer, 'address'));
        if ($this->s($customer, 'phone') !== '') {
            $del_address = trim($del_address."\n".$this->s($customer, 'phone'));
        }
        if (isset($dto['note']) && $dto['note'] !== '') {
            $del_address = trim($del_address."\n".$dto['note']);
        }

        $sale_vat_objects = array();
        foreach ($vat_buckets as $id => $bucket) {
            $sale_vat_objects[] = array(
                'tax_field_id'     => (string) $id,
                'tax_field_type'   => $bucket['type'],
                'tax_field_amount' => number_format(round($bucket['amount'], 2), 2, '.', ''),
            );
        }

        $header = array(
            'sale_no'     => $sale_no,
            'customer_id' => (string) $customer_id,
            'counter_id'  => (int) (isset($config->counter_id) ? $config->counter_id : 0),
            'user_id'     => $user_id,
            'waiter_id'   => 0,
            'outlet_id'   => $outlet_id,
            'company_id'  => $company_id,

            'total_items'                => count($lines),
            'sub_total'                  => $sub_total,
            'vat'                        => $total_vat,
            'total_payable'              => $total_payable,
            'total_item_discount_amount' => 0,
            'sub_total_with_discount'    => $sub_total_with_discount,
            'sub_total_discount_amount'  => $discount,
            'total_discount_amount'      => $discount,
            'sub_total_discount_value'   => $discount > 0 ? (string) $discount : '',
            'sub_total_discount_type'    => 'fixed',
            'charge_type'                => 'fixed',
            'delivery_charge'                => (string) $delivery,
            'delivery_charge_actual_charge'  => $delivery,
            'tips_amount'                    => (string) $tip,
            'tips_amount_actual_charge'      => $tip,
            'rounding_amount_hidden'         => 0,
            'previous_due_tmp'               => 0,
            'sale_vat_objects'               => json_encode($sale_vat_objects),

            'sale_date'  => $sale_date,
            'date_time'  => $now_local,
            'order_time' => $order_time,
            'close_time' => '00:00:00',

            'order_status'       => 1,     // running
            'order_type'         => (int) (isset($dto['order_type']) && $dto['order_type'] ? $dto['order_type'] : 3),
            'future_sale_status' => 1,
            'status'             => 'Pending',
            'del_status'         => 'Live',
            'is_pickup_sale'     => 1,
            'modified'           => 'No',

            // this is what makes it an incoming order rather than a counter sale
            'is_online_order'    => 'Yes',
            'is_self_order'      => 'No',
            'is_accept'          => $auto_accept ? 1 : 2,
            'self_order_status'  => $auto_accept ? 'Approved' : 'Pending',
            'token_number'       => substr((string) $this->s($dto, 'external_order_no', ''), 0, 50),
            'random_code'        => 'INTG-'.bin2hex(random_bytes(8)),

            'del_address'         => $del_address,
            'delivery_partner_id' => $config->delivery_partner_id ? (int) $config->delivery_partner_id : null,
            'payment_method_id'   => $config->payment_method_id ? (int) $config->payment_method_id : null,
            'orders_table_text'   => '',

            // routing: which POS user's tray this lands in
            'online_self_order_receiving_id' => (int) $this->online_receiving_id($outlet_id),
            'order_receiving_id'             => $user_id,
            'order_receiving_id_admin'       => (int) $this->company_admin_id($company_id),
            'pull_update'                    => 1,
            'pull_update_admin'              => 1,
            'pull_update_cashier'            => 1,

            // channel tagging, so every existing report can filter by channel
            'channel_code'         => $this->s($dto, 'provider_code'),
            'external_order_id'    => $this->s($dto, 'external_order_id'),
            'integration_order_id' => $integration_order_id ? (int) $integration_order_id : null,
        );

        // Prepaid aggregator orders are already settled on their side.
        if (!empty($dto['is_prepaid'])) {
            $header['paid_amount'] = $total_payable;
            $header['due_amount']  = 0;
        }

        /* ---- 5. write ---------------------------------------------------- */
        $this->CI->db->trans_begin();
        try {
            $this->CI->db->insert('tbl_kitchen_sales', $header);
            $kitchen_sale_id = (int) $this->CI->db->insert_id();
            if ($kitchen_sale_id <= 0) {
                throw new Exception('Insert into tbl_kitchen_sales returned no id');
            }

            $order_object_items = array();
            foreach ($lines as $line) {
                $order_object_items[] = $this->write_line($kitchen_sale_id, $line, $outlet_id, $user_id, $customer_id);
            }

            // self_order_content is what the POS parses to rebuild the cart when
            // the cashier pulls this order in (see pos_script: parseJSON(self_order_content)).
            // Without it the order exists but cannot be opened.
            $this->CI->db->where('id', $kitchen_sale_id);
            $this->CI->db->update('tbl_kitchen_sales', array(
                'self_order_content' => json_encode($this->build_order_object(
                    $header, $order_object_items, $customer_name, $sale_vat_objects
                )),
            ));

            $this->CI->db->trans_commit();
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            $result['error']      = $e->getMessage();
            $result['error_code'] = 'DB_WRITE_FAILED';
            return $result;
        }

        if ($this->CI->db->trans_status() === false) {
            $this->CI->db->trans_rollback();
            $result['error']      = 'Transaction failed writing the kitchen sale';
            $result['error_code'] = 'DB_WRITE_FAILED';
            return $result;
        }

        $result['ok']              = true;
        $result['kitchen_sale_id'] = $kitchen_sale_id;
        $result['sale_no']         = $sale_no;
        return $result;
    }

    /* ============================================================ item mapping */

    /**
     * Map every external SKU to a food_menu_id. Unresolved SKUs are recorded on
     * tbl_integration_item_map (unmapped) so the mapping screen can show them.
     */
    protected function resolve_items($items, $config)
    {
        $item_ids = array();
        $mod_ids  = array();
        foreach ($items as $item) {
            $item_ids[] = $this->s($item, 'external_item_id');
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) {
                    $mod_ids[] = $this->s($mod, 'external_id');
                }
            }
        }

        $item_map = $this->CI->Integration_model->getItemMap($config->id, $item_ids, 'item');
        $mod_map  = $this->CI->Integration_model->getItemMap($config->id, $mod_ids, 'modifier');

        $lines    = array();
        $unmapped = array();

        foreach ($items as $item) {
            $ext  = $this->s($item, 'external_item_id');
            $name = $this->s($item, 'menu_name');

            // a driver may pre-resolve; otherwise the map decides
            $food_menu_id = (int) $this->s($item, 'food_menu_id', 0);
            if ($food_menu_id <= 0 && isset($item_map[$ext]) && $item_map[$ext]->food_menu_id) {
                $food_menu_id = (int) $item_map[$ext]->food_menu_id;
            }

            if ($food_menu_id <= 0) {
                $this->CI->Integration_model->touchUnmapped($config->id, $ext, $name, 'item');
                $unmapped[] = array('type' => 'item', 'external_item_id' => $ext, 'name' => $name);
                continue;
            }

            $mods = array();
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) {
                    $mext  = $this->s($mod, 'external_id');
                    $mname = $this->s($mod, 'name');
                    $mid   = (int) $this->s($mod, 'modifier_id', 0);
                    if ($mid <= 0 && isset($mod_map[$mext]) && $mod_map[$mext]->modifier_id) {
                        $mid = (int) $mod_map[$mext]->modifier_id;
                    }
                    if ($mid <= 0) {
                        $this->CI->Integration_model->touchUnmapped($config->id, $mext, $mname, 'modifier');
                        $unmapped[] = array('type' => 'modifier', 'external_item_id' => $mext, 'name' => $mname);
                        continue;
                    }
                    $mods[] = array(
                        'modifier_id' => $mid,
                        'name'        => $mname,
                        'price'       => (float) $this->s($mod, 'price', 0),
                        'qty'         => max(1, (int) $this->s($mod, 'qty', 1)),
                    );
                }
            }

            $lines[] = array(
                'external_item_id' => $ext,
                'food_menu_id'     => $food_menu_id,
                'menu_name'        => $name,
                'qty'              => max(1, (int) $this->s($item, 'qty', 1)),
                'unit_price'       => $this->s($item, 'unit_price', null),
                'note'             => (string) $this->s($item, 'note', ''),
                'modifiers'        => $mods,
            );
        }

        return array('lines' => $lines, 'unmapped' => $unmapped);
    }

    /* ================================================================ pricing */

    /**
     * Decide the unit price and split VAT out of it.
     *
     * price_source = provider (default): the customer already paid their price,
     * so their number is authoritative. price_source = pos: use our own menu
     * price, chosen by price_mode.
     */
    protected function price_line($line, $config, $price_includes_tax)
    {
        $menus = $this->CI->Integration_model->getMenusByIds(array($line['food_menu_id']));
        $menu  = isset($menus[$line['food_menu_id']]) ? $menus[$line['food_menu_id']] : null;

        $unit = null;
        if ((!isset($config->price_source) || $config->price_source === 'provider') && $line['unit_price'] !== null && $line['unit_price'] !== '') {
            $unit = (float) $line['unit_price'];
        }
        if ($unit === null && $menu) {
            $unit = $this->menu_price($menu, $config);
        }
        $unit = round((float) $unit, 2);

        $tax_info = $menu && $menu->tax_information ? json_decode($menu->tax_information, true) : array();
        $tax_info = is_array($tax_info) ? $tax_info : array();

        $split = $this->split_tax($unit, $line['qty'], $tax_info, $price_includes_tax);

        // modifiers carry their own price and their own taxes
        $mod_out = array();
        $mod_ids = array();
        foreach ($line['modifiers'] as $mod) {
            $mod_ids[] = $mod['modifier_id'];
        }
        $mod_rows = $this->CI->Integration_model->getModifiersByIds($mod_ids);

        foreach ($line['modifiers'] as $mod) {
            $row = isset($mod_rows[$mod['modifier_id']]) ? $mod_rows[$mod['modifier_id']] : null;
            $mod_unit = ($mod['price'] > 0 || $row === null) ? (float) $mod['price'] : (float) $row->price;
            $mod_tax_info = ($row && $row->tax_information) ? json_decode($row->tax_information, true) : array();
            $mod_tax_info = is_array($mod_tax_info) ? $mod_tax_info : array();

            $mod_split = $this->split_tax(round($mod_unit, 2), $line['qty'], $mod_tax_info, $price_includes_tax);

            $mod_out[] = array(
                'modifier_id' => $mod['modifier_id'],
                'name'        => $mod['name'] !== '' ? $mod['name'] : ($row ? $row->name : ''),
                'unit_net'    => $mod_split['unit_net'],
                'tax_objects' => $mod_split['tax_objects'],
                'line_net'    => $mod_split['line_net'],
                'line_vat'    => $mod_split['line_vat'],
            );
        }

        // a modifier's money is part of the sale, so fold it into the line totals
        $line_net = $split['line_net'];
        $line_vat = $split['line_vat'];
        $tax_objects = $split['tax_objects'];
        foreach ($mod_out as $mod) {
            $line_net += $mod['line_net'];
            $line_vat += $mod['line_vat'];
            $tax_objects = $this->merge_tax_objects($tax_objects, $mod['tax_objects']);
        }

        return array(
            'unit_net'    => $split['unit_net'],
            'unit_gross'  => $unit,
            'tax_objects' => $split['tax_objects'],
            'line_net'    => round($line_net, 2),
            'line_vat'    => round($line_vat, 2),
            'all_tax_objects' => $tax_objects,
            'modifiers'   => $mod_out,
        );
    }

    /**
     * Menu price for this channel. sale_price_delivery is frequently left at 0
     * in the wild (it is in this dataset), and booking a 0-price line would be
     * worse than using the counter price - so fall back rather than trust it.
     */
    protected function menu_price($menu, $config)
    {
        $mode = isset($config->price_mode) ? $config->price_mode : 'delivery';
        if ($mode === 'delivery' && (float) $menu->sale_price_delivery > 0) {
            return (float) $menu->sale_price_delivery;
        }
        return (float) $menu->sale_price;
    }

    /**
     * Split a unit price into net + per-tax-field amounts.
     *
     * UAE aggregator menu prices are VAT-inclusive, so with
     * price_includes_tax = Yes we back-compute: the gross is what the customer
     * paid and must not move; the net is derived and the VAT is the remainder,
     * which keeps net + vat === gross exactly at 2 decimals.
     */
    protected function split_tax($unit_price, $qty, $tax_information, $price_includes_tax)
    {
        $qty  = max(1, (int) $qty);
        $rate = 0.0;
        foreach ($tax_information as $tax) {
            $rate += (float) (isset($tax['tax_field_percentage']) ? $tax['tax_field_percentage'] : 0);
        }

        if ($rate <= 0) {
            return array(
                'unit_net'    => round($unit_price, 2),
                'unit_vat'    => 0.0,
                'line_net'    => round($unit_price * $qty, 2),
                'line_vat'    => 0.0,
                'tax_objects' => array(),
            );
        }

        if ($price_includes_tax) {
            $unit_net = round($unit_price / (1 + ($rate / 100)), 2);
            $unit_vat = round($unit_price - $unit_net, 2);
        } else {
            $unit_net = round($unit_price, 2);
            $unit_vat = round($unit_net * $rate / 100, 2);
        }

        // spread the VAT across the tax fields proportionally; the last field
        // absorbs the rounding so the parts always sum to the whole
        $tax_objects = array();
        $allocated   = 0.0;
        $count       = count($tax_information);
        foreach ($tax_information as $i => $tax) {
            $pct  = (float) (isset($tax['tax_field_percentage']) ? $tax['tax_field_percentage'] : 0);
            $part = ($i === $count - 1) ? round($unit_vat - $allocated, 2) : round($unit_vat * ($pct / $rate), 2);
            $allocated += $part;

            $tax_objects[] = array(
                'tax_field_id'                     => isset($tax['tax_field_id']) ? (string) $tax['tax_field_id'] : '0',
                'tax_field_company_id'             => isset($tax['tax_field_company_id']) ? (string) $tax['tax_field_company_id'] : '',
                'tax_field_name'                   => isset($tax['tax_field_name']) ? $tax['tax_field_name'] : 'VAT',
                'tax_field_percentage'             => (string) $pct,
                'item_vat_amount_for_unit_item'    => number_format($part, 2, '.', ''),
                'item_vat_amount_for_all_quantity' => number_format(round($part * $qty, 2), 2, '.', ''),
            );
        }

        return array(
            'unit_net'    => $unit_net,
            'unit_vat'    => $unit_vat,
            'line_net'    => round($unit_net * $qty, 2),
            'line_vat'    => round($unit_vat * $qty, 2),
            'tax_objects' => $tax_objects,
        );
    }

    protected function merge_tax_objects($a, $b)
    {
        $by_id = array();
        foreach (array_merge($a, $b) as $tax) {
            $id = $tax['tax_field_id'];
            if (!isset($by_id[$id])) {
                $by_id[$id] = $tax;
                continue;
            }
            $by_id[$id]['item_vat_amount_for_unit_item'] = number_format(
                (float) $by_id[$id]['item_vat_amount_for_unit_item'] + (float) $tax['item_vat_amount_for_unit_item'], 2, '.', ''
            );
            $by_id[$id]['item_vat_amount_for_all_quantity'] = number_format(
                (float) $by_id[$id]['item_vat_amount_for_all_quantity'] + (float) $tax['item_vat_amount_for_all_quantity'], 2, '.', ''
            );
        }
        return array_values($by_id);
    }

    /* ================================================================= writing */

    /**
     * One detail row + its modifier rows. Returns the item as the POS order
     * object expects it, for self_order_content.
     */
    protected function write_line($kitchen_sale_id, $line, $outlet_id, $user_id, $customer_id)
    {
        $qty = $line['qty'];

        $item_data = array(
            'food_menu_id'                => $line['food_menu_id'],
            'menu_name'                   => substr($line['menu_name'], 0, 50),
            'qty'                         => $qty,
            'tmp_qty'                     => $qty,    // whole qty is new -> whole qty prints on the KOT
            'void_qty'                    => 0,
            'menu_price_without_discount' => round($line['unit_net'] * $qty, 2),
            'menu_price_with_discount'    => round($line['unit_net'] * $qty, 2),
            'menu_unit_price'             => $line['unit_net'],
            'menu_vat_percentage'         => 0,
            'menu_taxes'                  => json_encode($line['tax_objects']),
            'menu_discount_value'         => '',
            'discount_type'               => 'fixed',
            'menu_note'                   => substr($line['note'], 0, 150),
            'menu_combo_items'            => '',
            'discount_amount'             => 0,
            'item_type'                   => 'Kitchen Item',
            'cooking_status'              => 'New',
            'cooking_start_time'          => '0000-00-00 00:00:00',
            'cooking_done_time'           => '0000-00-00 00:00:00',
            'previous_id'                 => 0,
            'loyalty_point_earn'          => 0,
            'sales_id'                    => $kitchen_sale_id,
            'order_status'                => 0,
            'user_id'                     => $user_id,
            'outlet_id'                   => $outlet_id,
            'is_free_item'                => 0,
            'is_complementary'            => 0,
            'is_print'                    => 1,     // belongs to the first KOT batch
            'del_status'                  => 'Live',
        );

        $this->CI->db->insert('tbl_kitchen_sales_details', $item_data);
        $details_id = (int) $this->CI->db->insert_id();

        // POS points previous_id at the row itself for a non-combo line
        $this->CI->db->where('id', $details_id);
        $this->CI->db->update('tbl_kitchen_sales_details', array('previous_id' => $details_id));

        $mod_ids = $mod_names = $mod_prices = $mod_vats = array();
        foreach ($line['modifiers'] as $mod) {
            $this->CI->db->insert('tbl_kitchen_sales_details_modifiers', array(
                'modifier_id'         => $mod['modifier_id'],
                'modifier_price'      => $mod['unit_net'],
                'food_menu_id'        => $line['food_menu_id'],
                'sales_id'            => $kitchen_sale_id,
                'order_status'        => 0,
                'sales_details_id'    => $details_id,
                'menu_vat_percentage' => 0,
                'menu_taxes'          => json_encode($mod['tax_objects']),
                'user_id'             => $user_id,
                'outlet_id'           => $outlet_id,
                'customer_id'         => (int) $customer_id,
                'is_print'            => 1,
            ));
            $mod_ids[]    = $mod['modifier_id'];
            $mod_names[]  = $mod['name'];
            $mod_prices[] = $mod['unit_net'];
            $mod_vats[]   = json_encode($mod['tax_objects']);
        }

        return array(
            'food_menu_id'                => (string) $line['food_menu_id'],
            'menu_name'                   => $line['menu_name'],
            'is_print'                    => '1',
            'is_free'                     => '0',
            'rounding_amount_hidden'      => '0',
            'item_vat'                    => $line['tax_objects'],
            'menu_discount_value'         => '0',
            'discount_type'               => 'fixed',
            'menu_price_without_discount' => (string) round($line['unit_net'] * $qty, 2),
            'menu_unit_price'             => (string) $line['unit_net'],
            'qty'                         => (string) $qty,
            'tmp_qty'                     => (string) $qty,
            'p_qty'                       => '0',
            'item_previous_id'            => (string) $details_id,
            'item_cooking_done_time'      => '',
            'item_cooking_start_time'     => '',
            'item_cooking_status'         => 'New',
            'item_type'                   => 'Kitchen Item',
            'menu_price_with_discount'    => (string) round($line['unit_net'] * $qty, 2),
            'item_discount_amount'        => '0',
            'modifiers_id'                => implode(',', $mod_ids),
            'modifiers_name'              => implode(',', $mod_names),
            'modifiers_price'             => implode(',', $mod_prices),
            // Sale.php explodes this on '|||' - it is a delimited string, not an array
            'modifier_vat'                => implode('|||', $mod_vats),
            'item_note'                   => $line['note'],
            'menu_combo_items'            => '',
        );
    }

    /**
     * Rebuild the exact JSON the POS cart is restored from. The shape is
     * dictated by pos_script's parseJSON(self_order_content) - see the
     * order_info builder in frequent_changing/js/pos_script_v7.3.js.
     */
    protected function build_order_object($header, $items, $customer_name, $sale_vat_objects)
    {
        return array(
            'sale_no'                       => $header['sale_no'],
            'customer_id'                   => (string) $header['customer_id'],
            'customer_name'                 => $customer_name,
            'customer_address'              => $header['del_address'],
            'user_name'                     => 'Online',
            'user_id'                       => (string) $header['user_id'],
            'delivery_partner_id'           => (string) $header['delivery_partner_id'],
            'rounding_amount_hidden'        => '0',
            'previous_due_tmp'              => '0',
            'waiter_id'                     => '0',
            'waiter_name'                   => '',
            'counter_id'                    => (string) $header['counter_id'],
            'open_invoice_date_hidden'      => $header['sale_date'],
            'total_items_in_cart'           => (string) $header['total_items'],
            'sub_total'                     => (string) $header['sub_total'],
            'sale_date'                     => $header['sale_date'],
            'date_time'                     => $header['date_time'],
            'order_time'                    => $header['order_time'],
            'charge_type'                   => $header['charge_type'],
            'total_vat'                     => (string) $header['vat'],
            'total_payable'                 => (string) $header['total_payable'],
            'total_item_discount_amount'    => '0',
            'sub_total_with_discount'       => (string) $header['sub_total_with_discount'],
            'sub_total_discount_amount'     => (string) $header['sub_total_discount_amount'],
            'total_discount_amount'         => (string) $header['total_discount_amount'],
            'delivery_charge'               => (string) $header['delivery_charge'],
            'tips_amount'                   => (string) $header['tips_amount'],
            'delivery_charge_actual_charge' => (string) $header['delivery_charge_actual_charge'],
            'tips_amount_actual_charge'     => (string) $header['tips_amount_actual_charge'],
            'sub_total_discount_value'      => (string) $header['sub_total_discount_value'],
            'sub_total_discount_type'       => $header['sub_total_discount_type'],
            'order_type'                    => (string) $header['order_type'],
            'order_status'                  => (string) $header['order_status'],
            'sale_vat_objects'              => $sale_vat_objects,
            'orders_table'                  => array(),
            'total_orders_table'            => '0',
            'orders_table_text'             => '',
            'self_order_table_id'           => '',
            'self_order_table_person'       => '0',
            'random_code'                   => $header['random_code'],
            'items'                         => $items,
        );
    }

    /* ================================================================ lookups */

    protected function channel_customer_id($config, $dto)
    {
        if (!empty($config->customer_id)) {
            return (int) $config->customer_id;
        }
        $name = ucfirst($this->s($dto, 'provider_code', 'Online')).' Customer';
        $id   = $this->CI->Integration_model->getOrCreateChannelCustomer($config->company_id, $name);
        $this->CI->Integration_model->updateConfig($config->id, array('customer_id' => $id));
        $config->customer_id = $id;
        return $id;
    }

    /**
     * The POS user an injected sale is booked under. Falls back to the company
     * Admin so a config that was never filled in still produces a usable sale.
     */
    protected function ingest_user_id($config)
    {
        if (!empty($config->ingest_user_id)) {
            return (int) $config->ingest_user_id;
        }
        return (int) $this->company_admin_id($config->company_id);
    }

    protected function company_admin_id($company_id)
    {
        $row = $this->CI->db->get_where('tbl_users', array(
            'company_id' => (int) $company_id,
            'role'       => 'Admin',
            'del_status' => 'Live',
        ))->row();
        return $row ? (int) $row->id : 1;
    }

    protected function online_receiving_id($outlet_id)
    {
        $row = $this->CI->db->get_where('tbl_outlets', array('id' => (int) $outlet_id))->row();
        return ($row && !empty($row->online_self_order_receiving_id)) ? (int) $row->online_self_order_receiving_id : 0;
    }

    /* ================================================================ tiny bits */

    protected function s($arr, $key, $default = '')
    {
        if (is_array($arr) && isset($arr[$key]) && $arr[$key] !== null) {
            return is_scalar($arr[$key]) ? (string) $arr[$key] : $default;
        }
        return $default;
    }

    protected function f($arr, $key, $default = 0.0)
    {
        return (is_array($arr) && isset($arr[$key])) ? (float) $arr[$key] : $default;
    }
}
