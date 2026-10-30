<?php
/*
  ###########################################################
  # This is Ai_metrics_model Model
  ###########################################################
  # The AI Insight calculation engine: every number the page
  # shows or the AI explains is computed here.
  # Rules (docs/ai_insight_plan.md sections 4 and 6):
  # - never reads the session: company, outlet and dates are
  #   arguments, because the daily cron has no logged-in user
  # - query bindings / query builder only
  # - cost = consumption x tbl_ingredients.consumption_unit_cost
  #   (never SUM(tbl_sale_consumptions_of_menus.cost), plan F2)
  ###########################################################
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Ai_metrics_model extends CI_Model {

    /**
     * Today's date in the company's own timezone (plan F9).
     * @param int $company_id
     * @return string Y-m-d
     */
    public function companyToday($company_id) {
        $zone = $this->db->query("SELECT zone_name FROM tbl_companies WHERE id = ?", array((int) $company_id))->row('zone_name');
        try {
            $now = new DateTime('now', new DateTimeZone($zone ? $zone : date_default_timezone_get()));
        } catch (Exception $e) {
            $now = new DateTime('now');
        }
        return $now->format('Y-m-d');
    }

    /**
     * Shift a Y-m-d date by whole days.
     * @param string $date
     * @param int $days
     * @return string
     */
    public function addDays($date, $days) {
        return date('Y-m-d', strtotime($date . ' ' . ($days >= 0 ? '+' : '') . (int) $days . ' day'));
    }

    /**
     * Food menu ids sold at the outlet, the same rule as getFMIds() but without the session:
     * the outlet's own list, or every company menu when it has none.
     * @param int $company_id
     * @param int $outlet_id
     * @return int[]
     */
    public function outletMenuIds($company_id, $outlet_id) {
        $company = $this->db->query("SELECT language_manifesto FROM tbl_companies WHERE id = ?", array((int) $company_id))->row();
        $ids = array();
        if (!$company || str_rot13((string) $company->language_manifesto) != 'fgjgldkfg') {
            $csv = $this->db->query("SELECT food_menus FROM tbl_outlets WHERE id = ? AND company_id = ? AND del_status = 'Live'",
                array((int) $outlet_id, (int) $company_id))->row('food_menus');
            foreach (explode(',', (string) $csv) as $id) {
                if ((int) $id > 0) {
                    $ids[(int) $id] = (int) $id;
                }
            }
        }
        $this->db->select('id');
        $this->db->from('tbl_food_menus');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('del_status', 'Live');
        if ($ids) {
            $this->db->where_in('id', array_values($ids));
        }
        return array_map('intval', array_column($this->db->get()->result_array(), 'id'));
    }

    /**
     * Live plate cost of food menus (plan F1, 6.2): recipe x latest ingredient cost,
     * combos from their items, resale items from their linked ingredient.
     * @param int $company_id
     * @param int[] $menu_ids
     * @return array food_menu_id => object {id, name, product_type, cost, has_recipe, missing_price}
     */
    public function menuCosts($company_id, $menu_ids) {
        $menu_ids = array_values(array_unique(array_filter(array_map('intval', (array) $menu_ids))));
        if (!$menu_ids) {
            return array();
        }
        $menus = $this->loadMenus($company_id, $menu_ids);

        // combo items may not be on the outlet list, so cost them too
        $combo_ids = array();
        foreach ($menus as $m) {
            if ($m->product_type == 2) {
                $combo_ids[] = $m->id;
            }
        }
        $combo_lines = array();
        if ($combo_ids) {
            $this->db->select('food_menu_id, added_food_menu_id, quantity');
            $this->db->from('tbl_combo_food_menus');
            $this->db->where('del_status', 'Live');
            $this->db->where_in('food_menu_id', $combo_ids);
            foreach ($this->db->get()->result() as $line) {
                $combo_lines[(int) $line->food_menu_id][] = $line;
                if (!isset($menus[(int) $line->added_food_menu_id])) {
                    $menus += $this->loadMenus($company_id, array((int) $line->added_food_menu_id));
                }
            }
        }

        $all_ids = array_keys($menus);
        $recipe = array();
        if ($all_ids) {
            $this->db->select('fmi.food_menu_id, fmi.consumption, i.consumption_unit_cost');
            $this->db->from('tbl_food_menus_ingredients fmi');
            $this->db->join('tbl_ingredients i', "i.id = fmi.ingredient_id AND i.del_status = 'Live'", 'inner');
            $this->db->where('fmi.del_status', 'Live');
            $this->db->where_in('fmi.food_menu_id', $all_ids);
            foreach ($this->db->get()->result() as $line) {
                $recipe[(int) $line->food_menu_id][] = $line;
            }
        }
        $resale = array();
        if ($all_ids) {
            $this->db->select('food_id, consumption_unit_cost');
            $this->db->from('tbl_ingredients');
            $this->db->where('company_id', (int) $company_id);
            $this->db->where('del_status', 'Live');
            $this->db->where_in('food_id', $all_ids);
            foreach ($this->db->get()->result() as $ing) {
                $resale[(int) $ing->food_id] = (float) $ing->consumption_unit_cost;
            }
        }

        $single = function ($m) use ($recipe, $resale) {
            $out = (object) array('cost' => 0.0, 'has_recipe' => false, 'missing_price' => 0);
            if ($m->product_type == 3) {
                $out->has_recipe = isset($resale[$m->id]);
                $out->cost = $out->has_recipe ? $resale[$m->id] : 0.0;
                $out->missing_price = $out->has_recipe && $out->cost <= 0 ? 1 : 0;
                return $out;
            }
            foreach (isset($recipe[$m->id]) ? $recipe[$m->id] : array() as $line) {
                $out->has_recipe = true;
                $out->cost += (float) $line->consumption * (float) $line->consumption_unit_cost;
                if ((float) $line->consumption_unit_cost <= 0) {
                    $out->missing_price++;
                }
            }
            return $out;
        };

        $result = array();
        foreach ($menu_ids as $id) {
            if (!isset($menus[$id])) {
                continue;
            }
            $m = $menus[$id];
            if ($m->product_type == 2) {
                $c = (object) array('cost' => 0.0, 'has_recipe' => isset($combo_lines[$id]), 'missing_price' => 0);
                foreach (isset($combo_lines[$id]) ? $combo_lines[$id] : array() as $line) {
                    $child_id = (int) $line->added_food_menu_id;
                    if (!isset($menus[$child_id])) {
                        $c->missing_price++;
                        continue;
                    }
                    $child = $single($menus[$child_id]);
                    $c->cost += $child->cost * (float) $line->quantity;
                    if (!$child->has_recipe || $child->cost <= 0) {
                        $c->missing_price++;
                    }
                }
            } else {
                $c = $single($m);
            }
            $vat_rate = 0.0;
            foreach ((array) json_decode((string) $m->tax_information, true) as $tax) {
                $vat_rate += isset($tax['tax_field_percentage']) ? (float) $tax['tax_field_percentage'] : 0;
            }
            $result[$id] = (object) array(
                'id' => $id,
                'name' => $this->text($m->name),
                'product_type' => (int) $m->product_type,
                'parent_id' => (int) $m->parent_id,
                'sale_price' => (float) $m->sale_price,
                'vat_rate' => $vat_rate,
                'cost' => round($c->cost, 4),
                'has_recipe' => $c->has_recipe,
                'missing_price' => $c->missing_price,
            );
        }
        return $result;
    }

    private function loadMenus($company_id, $ids) {
        $this->db->select('id, name, product_type, parent_id, sale_price, tax_information');
        $this->db->from('tbl_food_menus');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('del_status', 'Live');
        $this->db->where_in('id', $ids);
        $menus = array();
        foreach ($this->db->get()->result() as $m) {
            $m->id = (int) $m->id;
            $m->product_type = (int) $m->product_type;
            $menus[$m->id] = $m;
        }
        return $menus;
    }

    /**
     * Quantity sold per food menu on settled sales of the outlet (sale_date semantics, plan F8).
     * @param int $outlet_id
     * @param string $from Y-m-d
     * @param string $to Y-m-d
     * @return array food_menu_id => qty
     */
    public function soldQtyByMenu($outlet_id, $from, $to) {
        $rows = $this->db->query("SELECT d.food_menu_id, SUM(d.qty) AS qty
            FROM tbl_sales s
            JOIN tbl_sales_details d ON d.sales_id = s.id AND d.del_status = 'Live'
            WHERE s.outlet_id = ? AND s.order_status = 3 AND s.del_status = 'Live'
              AND s.sale_date >= ? AND s.sale_date <= ?
            GROUP BY d.food_menu_id", array((int) $outlet_id, $from, $to))->result();
        $qty = array();
        foreach ($rows as $r) {
            $qty[(int) $r->food_menu_id] = (float) $r->qty;
        }
        return $qty;
    }

    /**
     * Current stock per ingredient of the outlet, in consumption units: the same formula
     * as views/report/inventoryReport.php, so both screens always agree (plan F5).
     * @param int $company_id
     * @param int $outlet_id
     * @return array ingredient_id => object {id, name, stock}
     */
    public function ingredientStock($company_id, $outlet_id) {
        $o = (int) $outlet_id;
        $sum = function ($sql, $binds) {
            $out = array();
            foreach ($this->db->query($sql, $binds)->result() as $r) {
                $out[(int) $r->ingredient_id] = (float) $r->q;
            }
            return $out;
        };
        $purchase = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_purchase_ingredients
            WHERE outlet_id = ? AND del_status = 'Live' GROUP BY ingredient_id", array($o));
        $used = $sum("SELECT ingredient_id, SUM(consumption) q FROM tbl_sale_consumptions_of_menus
            WHERE outlet_id = ? AND del_status = 'Live' GROUP BY ingredient_id", array($o));
        $used_modifiers = $sum("SELECT ingredient_id, SUM(consumption) q FROM tbl_sale_consumptions_of_modifiers_of_menus
            WHERE outlet_id = ? AND del_status = 'Live' GROUP BY ingredient_id", array($o));
        $waste = $sum("SELECT ingredient_id, SUM(waste_amount) q FROM tbl_waste_ingredients
            WHERE outlet_id = ? AND del_status = 'Live' GROUP BY ingredient_id", array($o));
        $adjust_plus = $sum("SELECT ingredient_id, SUM(consumption_amount) q FROM tbl_inventory_adjustment_ingredients
            WHERE outlet_id = ? AND del_status = 'Live' AND consumption_status = 'Plus' GROUP BY ingredient_id", array($o));
        $adjust_minus = $sum("SELECT ingredient_id, SUM(consumption_amount) q FROM tbl_inventory_adjustment_ingredients
            WHERE outlet_id = ? AND del_status = 'Live' AND consumption_status = 'Minus' GROUP BY ingredient_id", array($o));
        $production = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_production_ingredients
            WHERE outlet_id = ? AND del_status = 'Live' AND status = 1 GROUP BY ingredient_id", array($o));
        $transfer_in = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_transfer_ingredients
            WHERE to_outlet_id = ? AND del_status = 'Live' AND status = 1 AND transfer_type = 1 GROUP BY ingredient_id", array($o));
        $transfer_out = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_transfer_ingredients
            WHERE from_outlet_id = ? AND del_status = 'Live' AND status = 1 AND transfer_type = 1 GROUP BY ingredient_id", array($o));
        $received_in = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_transfer_received_ingredients
            WHERE to_outlet_id = ? AND del_status = 'Live' AND status = 1 GROUP BY ingredient_id", array($o));
        $received_out = $sum("SELECT ingredient_id, SUM(quantity_amount) q FROM tbl_transfer_received_ingredients
            WHERE from_outlet_id = ? AND del_status = 'Live' AND status = 1 GROUP BY ingredient_id", array($o));

        $get = function ($arr, $id) {
            return isset($arr[$id]) ? $arr[$id] : 0.0;
        };
        $stock = array();
        $ingredients = $this->db->query("SELECT i.id, i.name, i.conversion_rate, u.unit_name FROM tbl_ingredients i
            LEFT JOIN tbl_units u ON u.id = i.unit_id
            WHERE i.company_id = ? AND i.del_status = 'Live' ORDER BY i.name", array((int) $company_id))->result();
        foreach ($ingredients as $i) {
            $id = (int) $i->id;
            $cr = (float) $i->conversion_rate; // raw value, exactly like the inventory report
            $stock[$id] = (object) array(
                'id' => $id,
                'name' => $this->text($i->name),
                'unit' => (string) $i->unit_name,
                'stock' => $get($purchase, $id) * $cr - $get($used, $id) - $get($used_modifiers, $id) - $get($waste, $id)
                    + $get($adjust_plus, $id) - $get($adjust_minus, $id)
                    + $get($transfer_in, $id) * $cr - $get($transfer_out, $id) * $cr
                    + $get($received_in, $id) * $cr - $get($received_out, $id) * $cr
                    + $get($production, $id) * $cr,
            );
        }
        return $stock;
    }

    /**
     * Data Health of one outlet (plan 6.9): what is missing, and which sections it blocks.
     * @param int $company_id
     * @param int $outlet_id
     * @param string $today Y-m-d, company local date
     * @return array
     */
    public function dataHealth($company_id, $outlet_id, $today) {
        $h = ai_config('ai_health');
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        $window = (int) $h['window_days'];
        $to = $this->addDays($today, -1);
        $from = $this->addDays($today, -$window);

        $outlet = $this->db->query("SELECT id, outlet_name, has_kitchen FROM tbl_outlets WHERE id = ? AND company_id = ?",
            array($outlet_id, $company_id))->row();
        $menu_ids = $this->outletMenuIds($company_id, $outlet_id);
        $sold = $this->soldQtyByMenu($outlet_id, $from, $to);
        $costs = $this->menuCosts($company_id, array_merge($menu_ids, array_keys($sold)));
        $total_sold = array_sum($sold);
        $stock_only = $total_sold <= 0 && (!$outlet || $outlet->has_kitchen != 'Yes');

        $checks = array();

        // 1. sold quantity with a recipe cost
        $covered = 0;
        $no_cost_items = array();
        foreach ($sold as $id => $qty) {
            if (isset($costs[$id]) && $costs[$id]->cost > 0) {
                $covered += $qty;
            } else {
                $no_cost_items[] = array('id' => $id, 'name' => isset($costs[$id]) ? $costs[$id]->name : ('#' . $id), 'qty' => $qty);
            }
        }
        usort($no_cost_items, function ($a, $b) {
            return $b['qty'] <=> $a['qty'];
        });
        if ($total_sold <= 0) {
            $checks['recipe_cover'] = $this->check('na', sprintf(lang('ai_h_no_sales'), $window), '', array('menu', 'price', 'pnl'), 'FoodMenu/foodMenus');
        } else {
            $pct = round($covered / $total_sold * 100, 1);
            $status = $pct >= $h['recipe_cover_green'] ? 'good' : ($pct >= $h['recipe_cover_amber'] ? 'watch' : 'missing');
            $checks['recipe_cover'] = $this->check($status, sprintf(lang('ai_h_recipe_cover_val'), $pct, $window),
                $status == 'good' ? '' : lang('ai_h_recipe_cover_fix'), array('menu', 'price', 'pnl'), 'FoodMenu/foodMenus');
            $checks['recipe_cover']['value'] = $pct;
        }

        // 2. outlet menus with a recipe cost
        $menus_total = count($menu_ids);
        $menus_costed = 0;
        foreach ($menu_ids as $id) {
            if (isset($costs[$id]) && $costs[$id]->cost > 0) {
                $menus_costed++;
            }
        }
        if ($menus_total == 0 || $stock_only) {
            $checks['menus_recipe'] = $this->check('na', sprintf(lang('ai_h_menus_recipe_val'), $menus_costed, $menus_total), '', array('price', 'menu'), 'FoodMenu/foodMenus');
        } else {
            $status = $menus_costed == $menus_total ? 'good' : ($menus_costed >= $menus_total / 2 ? 'watch' : 'missing');
            $checks['menus_recipe'] = $this->check($status, sprintf(lang('ai_h_menus_recipe_val'), $menus_costed, $menus_total),
                $status == 'good' ? '' : lang('ai_h_menus_recipe_fix'), array('price', 'menu'), 'FoodMenu/foodMenus');
        }

        // 3. prices of the ingredients the outlet's recipes use
        $recipe_ings = array();
        if ($menu_ids) {
            $this->db->select('DISTINCT i.id, i.consumption_unit_cost', false);
            $this->db->from('tbl_food_menus_ingredients fmi');
            $this->db->join('tbl_ingredients i', "i.id = fmi.ingredient_id AND i.del_status = 'Live'", 'inner');
            $this->db->where('fmi.del_status', 'Live');
            $this->db->where_in('fmi.food_menu_id', $menu_ids);
            $recipe_ings = $this->db->get()->result();
        }
        $no_price = 0;
        foreach ($recipe_ings as $i) {
            if ((float) $i->consumption_unit_cost <= 0) {
                $no_price++;
            }
        }
        if (!$recipe_ings) {
            $checks['ing_price'] = $this->check('na', lang('ai_h_ing_price_none'), '', array('price', 'pnl', 'purchase'), 'Ingredient/ingredients');
        } elseif ($no_price) {
            $checks['ing_price'] = $this->check('missing', sprintf(lang('ai_h_ing_price_val'), $no_price, count($recipe_ings)),
                lang('ai_h_ing_price_fix'), array('price', 'pnl', 'purchase'), 'Ingredient/ingredients');
        } else {
            $checks['ing_price'] = $this->check('good', sprintf(lang('ai_h_ing_price_ok'), count($recipe_ings)), '', array('price', 'pnl', 'purchase'), 'Ingredient/ingredients');
        }

        // 4. purchases entered (tbl_purchase.date is a Y-m-d string)
        $p_from = $this->addDays($today, -(int) $h['purchase_days']);
        $p = $this->db->query("SELECT SUM(date >= ? AND date <= ?) AS recent, MAX(date) AS last_date
            FROM tbl_purchase WHERE outlet_id = ? AND del_status = 'Live'", array($p_from, $today, $outlet_id))->row();
        if (!$p || !$p->last_date) {
            $checks['purchases'] = $this->check('missing', lang('ai_h_purchases_none'), lang('ai_h_purchases_fix'), array('purchase', 'forecast'), 'Purchase/purchases');
        } elseif ((int) $p->recent > 0) {
            $checks['purchases'] = $this->check('good', sprintf(lang('ai_h_purchases_val'), (int) $p->recent, $h['purchase_days'], $p->last_date), '', array('purchase', 'forecast'), 'Purchase/purchases');
        } else {
            $checks['purchases'] = $this->check('watch', sprintf(lang('ai_h_purchases_old'), $h['purchase_days'], $p->last_date), lang('ai_h_purchases_fix'), array('purchase', 'forecast'), 'Purchase/purchases');
        }

        // 5. waste entered
        $w_from = $this->addDays($today, -(int) $h['waste_days']);
        $w = $this->db->query("SELECT SUM(date >= ? AND date <= ?) AS recent, MAX(date) AS last_date
            FROM tbl_wastes WHERE outlet_id = ? AND del_status = 'Live'", array($w_from, $today, $outlet_id))->row();
        if ($w && (int) $w->recent > 0) {
            $checks['waste'] = $this->check('good', sprintf(lang('ai_h_waste_val'), (int) $w->recent, $h['waste_days'], $w->last_date), '', array('waste', 'purchase'), 'Waste/wastes');
        } else {
            $checks['waste'] = $this->check('watch', sprintf(lang('ai_h_waste_none'), $h['waste_days']), lang('ai_h_waste_fix'), array('waste', 'purchase'), 'Waste/wastes');
        }

        // 6. negative stock
        $negative = array();
        foreach ($this->ingredientStock($company_id, $outlet_id) as $i) {
            if ($i->stock < -0.0001) {
                $negative[] = array('name' => $i->name, 'stock' => round($i->stock, 3), 'unit' => $i->unit);
            }
        }
        if (!$negative) {
            $checks['negative'] = $this->check('good', lang('ai_h_negative_ok'), '', array('purchase', 'forecast'), 'Inventory/index');
        } else {
            $checks['negative'] = $this->check(count($negative) > $h['negative_stock_red'] ? 'missing' : 'watch',
                sprintf(lang('ai_h_negative_val'), count($negative)), lang('ai_h_negative_fix'), array('purchase', 'forecast'), 'Inventory/index');
        }

        // 7. sales history: the prediction needs recent trading days, 4 weeks minimum and 8 weeks for good
        // accuracy, so count days with sales in those windows (an old first sale alone is not enough)
        $long = (int) $h['history_days_good'];
        $short = (int) $h['history_days_min'];
        $sh = $this->db->query("SELECT MIN(sale_date) AS first_date,
                COUNT(DISTINCT CASE WHEN sale_date >= ? AND sale_date <= ? THEN sale_date END) AS days_long,
                COUNT(DISTINCT CASE WHEN sale_date >= ? AND sale_date <= ? THEN sale_date END) AS days_short
            FROM tbl_sales WHERE outlet_id = ? AND order_status = 3 AND del_status = 'Live'",
            array($this->addDays($today, -$long), $to, $this->addDays($today, -$short), $to, $outlet_id))->row();
        if ($stock_only) {
            $checks['history'] = $this->check('na', sprintf(lang('ai_h_no_sales'), $window), '', array('forecast'), '');
        } elseif (!$sh || !$sh->first_date) {
            $checks['history'] = $this->check('missing', sprintf(lang('ai_h_no_sales'), $long), lang('ai_h_history_fix'), array('forecast'), '');
        } else {
            $ratio = (float) $h['history_active_ratio'];
            $status = $sh->days_long >= $long * $ratio ? 'good' : ($sh->days_short >= $short * $ratio ? 'watch' : 'missing');
            $checks['history'] = $this->check($status, sprintf(lang('ai_h_history_val'), (int) $sh->days_long, $long, $sh->first_date),
                $status == 'good' ? '' : lang('ai_h_history_fix'), array('forecast'), '');
        }

        // 8. last stock count (inventory adjustment)
        $last_count = $this->db->query("SELECT MAX(date) AS d FROM tbl_inventory_adjustment WHERE outlet_id = ? AND del_status = 'Live'",
            array($outlet_id))->row('d');
        if (!$last_count) {
            $checks['stock_count'] = $this->check('watch', lang('ai_h_count_none'), lang('ai_h_count_fix'), array('shrink'), 'Inventory_adjustment/inventoryAdjustments');
        } else {
            $age = (int) floor((strtotime($today) - strtotime($last_count)) / 86400);
            $checks['stock_count'] = $this->check($age <= $h['stock_count_days'] ? 'good' : 'watch', sprintf(lang('ai_h_count_val'), $last_count, $age),
                $age <= $h['stock_count_days'] ? '' : lang('ai_h_count_fix'), array('shrink'), 'Inventory_adjustment/inventoryAdjustments');
        }

        return array(
            'outlet_id' => $outlet_id,
            'outlet_name' => $outlet ? $outlet->outlet_name : '',
            'today' => $today,
            'window' => array('from' => $from, 'to' => $to, 'days' => $window),
            'stock_only' => $stock_only,
            'checks' => $checks,
            'gates' => $this->gates($checks, $stock_only),
            'no_cost_items' => array_slice($no_cost_items, 0, 10),
            'negative_items' => array_slice($negative, 0, 10),
        );
    }

    private function check($status, $result, $fix, $used_for, $link) {
        return array('status' => $status, 'result' => $result, 'fix' => $fix, 'used_for' => $used_for, 'link' => $link);
    }

    /**
     * Which page sections the data allows: ready / limited / blocked.
     */
    private function gates($c, $stock_only) {
        $level = function ($status) {
            return $status == 'good' ? 'ready' : ($status == 'watch' ? 'limited' : 'blocked');
        };
        $worst = function () {
            $order = array('ready' => 0, 'limited' => 1, 'blocked' => 2);
            $levels = func_get_args();
            usort($levels, function ($a, $b) use ($order) {
                return $order[$b] <=> $order[$a];
            });
            return $levels[0];
        };
        if ($stock_only) {
            return array(
                'menu' => 'blocked',
                'price' => 'blocked',
                'purchase' => $worst($level($c['purchases']['status']), $c['negative']['status'] == 'missing' ? 'limited' : 'ready'),
                'pnl' => 'blocked',
                'forecast' => 'blocked',
            );
        }
        $price = $c['menus_recipe']['status'] == 'na' ? 'blocked' : $level($c['menus_recipe']['status']);
        if ($c['ing_price']['status'] == 'missing' && $price == 'ready') {
            $price = 'limited';
        }
        return array(
            'menu' => $c['recipe_cover']['status'] == 'na' ? 'blocked' : $level($c['recipe_cover']['status']),
            'price' => $price,
            'purchase' => $worst($level($c['purchases']['status']), $c['negative']['status'] == 'good' ? 'ready' : 'limited'),
            'pnl' => $c['recipe_cover']['status'] == 'good' ? 'ready' : ($c['recipe_cover']['status'] == 'na' ? 'blocked' : 'limited'),
            'forecast' => $c['history']['status'] == 'na' ? 'blocked' : $level($c['history']['status']),
        );
    }

    /* =================================================================
     * The full report of one outlet (plan section 6)
     * ================================================================= */

    /**
     * Every number of the AI Insight page for one outlet.
     * @param int $company_id
     * @param int $outlet_id
     * @param string $today Y-m-d company local date; every window ends yesterday
     * @param object|array $settings tbl_ai_settings row (target %, rounding, thresholds)
     * @return array
     */
    public function build($company_id, $outlet_id, $today, $settings) {
        $company_id = (int) $company_id;
        $outlet_id = (int) $outlet_id;
        $s = (object) $settings;
        $company = $this->companyInfo($company_id);
        $w = $this->windows($today);
        $health = $this->dataHealth($company_id, $outlet_id, $today);

        $menu_ids = $this->outletMenuIds($company_id, $outlet_id);
        $sales = array();
        foreach (array('cur', 'prev', 'cur7', 'prev7') as $k) {
            $sales[$k] = $this->itemSales($company, $outlet_id, $w[$k]['from'], $w[$k]['to']);
        }
        $all_ids = $menu_ids;
        foreach ($sales as $block) {
            $all_ids = array_merge($all_ids, array_keys($block['items']));
        }
        $costs = $this->menuCosts($company_id, $all_ids);
        $prices = $this->outletPrices($outlet_id);
        $stock = $this->ingredientStock($company_id, $outlet_id);

        $menu = $this->menuPerformance($sales['cur'], $sales['prev'], $costs, $menu_ids);
        $menu7 = $this->menuPerformance($sales['cur7'], $sales['prev7'], $costs, $menu_ids);
        $price = $this->priceCheck($company, $s, $costs, $prices, $menu_ids, $sales['cur']);
        $purchase = $this->purchaseUsage($company_id, $outlet_id, $w['cur']['from'], $w['cur']['to'], $w['cur']['days'], $stock, $s);
        $purchase7 = $this->purchaseUsage($company_id, $outlet_id, $w['cur7']['from'], $w['cur7']['to'], $w['cur7']['days'], $stock, $s);
        $pnl = array(
            'cur' => $this->profitLoss($company_id, $outlet_id, $w['cur']['from'], $w['cur']['to']),
            'prev' => $this->profitLoss($company_id, $outlet_id, $w['prev']['from'], $w['prev']['to']),
        );
        $pnl7 = array(
            'cur' => $this->profitLoss($company_id, $outlet_id, $w['cur7']['from'], $w['cur7']['to']),
            'prev' => $this->profitLoss($company_id, $outlet_id, $w['prev7']['from'], $w['prev7']['to']),
        );
        $forecast = $this->forecast($company_id, $outlet_id, $today, $health, $sales['cur'], $costs, $prices, $company, $stock, $purchase, $s);
        $issues = $this->issues($menu, $price, $purchase, $pnl, $health['gates'], $health['checks']);

        return array(
            'meta' => array(
                'company_id' => $company_id,
                'outlet_id' => $outlet_id,
                'outlet_name' => $health['outlet_name'],
                'currency' => $company->currency,
                'precision' => $company->precision,
                'tax_inclusive' => $company->tax_inclusive,
                'today' => $today,
                'generated_at' => date('Y-m-d H:i:s'),
                'target_food_cost_pct' => (float) $s->target_food_cost_pct,
            ),
            'windows' => $w,
            'health' => $health,
            'yesterday' => $this->yesterday($outlet_id, $w),
            'menu' => $menu,
            'menu7' => $menu7,
            'price' => $price,
            'purchase' => $purchase,
            'purchase7' => $purchase7,
            'pnl' => $this->withChange($pnl),
            'pnl7' => $this->withChange($pnl7),
            'forecast' => $forecast,
            'issues' => $issues['all'],
            'top5' => $issues['top5'],
        );
    }

    /**
     * Company values the engine needs; tax_type 2 = prices include VAT.
     */
    public function companyInfo($company_id) {
        $c = $this->db->query("SELECT id, business_name, currency, `precision`, tax_type, collect_tax, zone_name
            FROM tbl_companies WHERE id = ?", array((int) $company_id))->row();
        return (object) array(
            'id' => (int) $company_id,
            'name' => $c ? $c->business_name : '',
            'currency' => $c ? (string) $c->currency : '',
            'precision' => $c && $c->precision !== null ? (int) $c->precision : 2,
            'tax_inclusive' => $c && (int) $c->tax_type == 2,
        );
    }

    /**
     * cur = last 28 days ending yesterday, prev = the 28 before; cur7 / prev7 the same for 7 days.
     */
    public function windows($today) {
        $y = $this->addDays($today, -1);
        $make = function ($days, $offset) use ($today) {
            return array(
                'from' => $this->addDays($today, -($days * ($offset + 1))),
                'to' => $this->addDays($today, -($days * $offset) - 1),
                'days' => $days,
            );
        };
        return array(
            'cur' => $make(28, 0),
            'prev' => $make(28, 1),
            'cur7' => $make(7, 0),
            'prev7' => $make(7, 1),
            'yesterday' => array('from' => $y, 'to' => $y, 'days' => 1),
            'last_week_same_day' => array('from' => $this->addDays($y, -7), 'to' => $this->addDays($y, -7), 'days' => 1),
        );
    }

    /**
     * Outlet dine-in price per menu from tbl_outlets.food_menu_prices ({"tmp<id>": "dine||take||..."}).
     */
    public function outletPrices($outlet_id) {
        $json = $this->db->query("SELECT food_menu_prices FROM tbl_outlets WHERE id = ?", array((int) $outlet_id))->row('food_menu_prices');
        $prices = array();
        foreach ((array) json_decode((string) $json, true) as $key => $value) {
            if (strpos($key, 'tmp') === 0) {
                $parts = explode('||', (string) $value);
                if (is_numeric($parts[0]) && (float) $parts[0] > 0) {
                    $prices[(int) substr($key, 3)] = (float) $parts[0];
                }
            }
        }
        return $prices;
    }

    /**
     * Settled sale lines of a window, per food menu (plan F7):
     * net revenue = line price after item discount - share of the bill discount - share of the VAT
     * (VAT only when prices include it). Complimentary / free lines and staff meals are giveaways.
     * @return array items => [id => paid_qty, giveaway_qty, net_revenue, gross_revenue], totals
     */
    public function itemSales($company, $outlet_id, $from, $to) {
        $rows = $this->db->query("SELECT d.food_menu_id, d.qty, d.menu_price_with_discount AS line_total,
                d.menu_price_without_discount AS line_list, d.is_complementary, d.is_free_item,
                s.sub_total, s.sub_total_discount_amount, s.vat, s.is_staff_meal
            FROM tbl_sales s
            JOIN tbl_sales_details d ON d.sales_id = s.id AND d.del_status = 'Live'
            WHERE s.outlet_id = ? AND s.order_status = 3 AND s.del_status = 'Live'
              AND s.sale_date >= ? AND s.sale_date <= ?", array((int) $outlet_id, $from, $to))->result();
        $items = array();
        $totals = array('paid_qty' => 0.0, 'giveaway_qty' => 0.0, 'net_revenue' => 0.0, 'giveaway_list_value' => 0.0);
        foreach ($rows as $r) {
            $id = (int) $r->food_menu_id;
            if (!isset($items[$id])) {
                $items[$id] = array('id' => $id, 'paid_qty' => 0.0, 'giveaway_qty' => 0.0, 'net_revenue' => 0.0, 'gross_revenue' => 0.0);
            }
            $qty = (float) $r->qty;
            if ((int) $r->is_staff_meal === 1 || (int) $r->is_complementary === 1 || (int) $r->is_free_item === 1) {
                $items[$id]['giveaway_qty'] += $qty;
                $totals['giveaway_qty'] += $qty;
                $totals['giveaway_list_value'] += (float) $r->line_list;
                continue;
            }
            $line = (float) $r->line_total;
            $sub_total = (float) $r->sub_total;
            $net = $line;
            if ($sub_total > 0) {
                $net -= $line * (float) $r->sub_total_discount_amount / $sub_total;
                if ($company->tax_inclusive) {
                    $net -= (float) $r->vat * $line / $sub_total;
                }
            }
            $items[$id]['paid_qty'] += $qty;
            $items[$id]['net_revenue'] += $net;
            $items[$id]['gross_revenue'] += $line;
            $totals['paid_qty'] += $qty;
            $totals['net_revenue'] += $net;
        }
        return array('items' => $items, 'totals' => $totals);
    }

    /**
     * Menu engineering (plan 6.3): popularity vs contribution margin per plate.
     */
    public function menuPerformance($cur, $prev, $costs, $menu_ids) {
        $rows = array();
        $costed_qty = 0.0;
        $costed_cm = 0.0;
        $costed_n = 0;
        foreach ($cur['items'] as $id => $it) {
            if ($it['paid_qty'] <= 0) {
                continue;
            }
            $cost = isset($costs[$id]) ? $costs[$id]->cost : 0.0;
            $avg = $it['net_revenue'] / $it['paid_qty'];
            $prev_qty = isset($prev['items'][$id]) ? $prev['items'][$id]['paid_qty'] : 0.0;
            $row = array(
                'id' => $id,
                'name' => isset($costs[$id]) ? $costs[$id]->name : ('#' . $id),
                'qty' => $it['paid_qty'],
                'giveaway_qty' => $it['giveaway_qty'],
                'net_revenue' => round($it['net_revenue'], 2),
                'avg_price' => round($avg, 2),
                'plate_cost' => round($cost, 2),
                'cm' => round($avg - $cost, 2),
                'total_cm' => round(($avg - $cost) * $it['paid_qty'], 2),
                'prev_qty' => $prev_qty,
                'prev_net_revenue' => isset($prev['items'][$id]) ? round($prev['items'][$id]['net_revenue'], 2) : 0.0,
                'qty_change_pct' => $prev_qty > 0 ? round(($it['paid_qty'] - $prev_qty) / $prev_qty * 100, 1) : null,
                'has_cost' => $cost > 0,
                'class' => null,
                'qty_share_pct' => 0.0,
            );
            if ($cost > 0) {
                $costed_qty += $it['paid_qty'];
                $costed_cm += ($avg - $cost) * $it['paid_qty'];
                $costed_n++;
            }
            $rows[$id] = $row;
        }
        $avg_cm = $costed_qty > 0 ? $costed_cm / $costed_qty : 0.0;
        $pop_line = $costed_n > 0 ? 0.7 / $costed_n * 100 : 0.0;
        foreach ($rows as $id => $row) {
            if (!$row['has_cost']) {
                continue;
            }
            $share = $row['qty'] / $costed_qty * 100;
            $popular = $share >= $pop_line;
            $high_cm = ($row['net_revenue'] / $row['qty'] - $costs[$id]->cost) >= $avg_cm;
            $rows[$id]['qty_share_pct'] = round($share, 2);
            $rows[$id]['class'] = $popular ? ($high_cm ? 'star' : 'plowhorse') : ($high_cm ? 'puzzle' : 'dog');
        }
        usort($rows, function ($a, $b) {
            return $b['total_cm'] <=> $a['total_cm'];
        });

        // "never sold" only means something when the outlet did sell in the window:
        // a new outlet or a stock-only outlet would otherwise list its whole menu for removal
        $unsold = array();
        $outlet_sold = $cur['totals']['paid_qty'] + $cur['totals']['giveaway_qty'] > 0;
        foreach ($outlet_sold ? $menu_ids : array() as $id) {
            $sold = isset($cur['items'][$id]) && ($cur['items'][$id]['paid_qty'] + $cur['items'][$id]['giveaway_qty']) > 0;
            if (!$sold && isset($costs[$id])) {
                $unsold[] = array('id' => $id, 'name' => $costs[$id]->name, 'plate_cost' => round($costs[$id]->cost, 2));
            }
        }
        usort($unsold, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        return array(
            'items' => $rows,
            'avg_cm' => round($avg_cm, 2),
            'popularity_line_pct' => round($pop_line, 2),
            'costed_items' => $costed_n,
            'unsold' => $unsold,
            'totals' => array(
                'paid_qty' => $cur['totals']['paid_qty'],
                'giveaway_qty' => $cur['totals']['giveaway_qty'],
                'net_revenue' => round($cur['totals']['net_revenue'], 2),
            ),
        );
    }

    /**
     * Food cost % per item against the target, with a suggested price (plan 6.4).
     */
    public function priceCheck($company, $s, $costs, $prices, $menu_ids, $cur) {
        $target = max(1.0, (float) $s->target_food_cost_pct);
        $step = (float) $s->price_rounding > 0 ? (float) $s->price_rounding : 0.5;
        $rows = array();
        foreach (array_unique(array_merge($menu_ids, array_keys($cur['items']))) as $id) {
            if (!isset($costs[$id]) || $costs[$id]->cost <= 0) {
                continue;
            }
            $c = $costs[$id];
            $list = isset($prices[$id]) ? $prices[$id] : $c->sale_price;
            if ($list <= 0) {
                continue;
            }
            $vat_factor = $company->tax_inclusive ? 1 + $c->vat_rate / 100 : 1.0;
            $net_list = $list / $vat_factor;
            $pct = $c->cost / $net_list * 100;
            $qty = isset($cur['items'][$id]) ? $cur['items'][$id]['paid_qty'] : 0.0;
            $realised = $qty > 0 && $cur['items'][$id]['net_revenue'] > 0 ? $c->cost / ($cur['items'][$id]['net_revenue'] / $qty) * 100 : null;
            if ($pct >= 100) {
                $band = 'loss';
            } elseif ($pct > 50) {
                $band = 'critical';
            } elseif ($pct > $target + 5) {
                $band = 'high';
            } elseif ($pct > $target) {
                $band = 'watch';
            } else {
                $band = 'good';
            }
            $row = array(
                'id' => $id,
                'name' => $c->name,
                'list_price' => round($list, 2),
                'net_price' => round($net_list, 2),
                'plate_cost' => round($c->cost, 2),
                'food_cost_pct' => round($pct, 1),
                'realised_pct' => $realised === null ? null : round($realised, 1),
                'band' => $band,
                'qty' => $qty,
                'suggested_price' => null,
                'impact' => 0.0,
            );
            if ($band != 'good') {
                $suggested = $this->roundUp($c->cost / ($target / 100) * $vat_factor, $step);
                $row['suggested_price'] = round($suggested, 2);
                $row['impact'] = round(max(0, $suggested / $vat_factor - $net_list) * $qty, 2);
            }
            $rows[] = $row;
        }
        $order = array('loss' => 0, 'critical' => 1, 'high' => 2, 'watch' => 3, 'good' => 4);
        usort($rows, function ($a, $b) use ($order) {
            return $order[$a['band']] <=> $order[$b['band']] ?: $b['impact'] <=> $a['impact'];
        });
        return array('target_pct' => $target, 'rounding' => $step, 'items' => $rows);
    }

    /** round up to the next multiple of $step */
    public function roundUp($value, $step) {
        return ceil(round($value / $step, 6)) * $step;
    }

    /**
     * Purchase vs usage per ingredient for a window (plan 6.5). Quantities in consumption units.
     */
    public function purchaseUsage($company_id, $outlet_id, $from, $to, $days, $stock, $s) {
        $o = (int) $outlet_id;
        $sum = function ($sql, $binds, $with_value = false) {
            $out = array();
            foreach ($this->db->query($sql, $binds)->result() as $r) {
                $out[(int) $r->ingredient_id] = $with_value ? array((float) $r->q, (float) $r->v) : (float) $r->q;
            }
            return $out;
        };
        $purchased = $sum("SELECT pi.ingredient_id, SUM(pi.quantity_amount) q, SUM(pi.total) v FROM tbl_purchase_ingredients pi
            JOIN tbl_purchase p ON p.id = pi.purchase_id AND p.del_status = 'Live'
            WHERE pi.outlet_id = ? AND pi.del_status = 'Live' AND p.date >= ? AND p.date <= ? GROUP BY pi.ingredient_id", array($o, $from, $to), true);
        $used = $sum("SELECT c.ingredient_id, SUM(c.consumption) q FROM tbl_sale_consumptions_of_menus c
            JOIN tbl_sales s ON s.id = c.sales_id AND s.del_status = 'Live' AND s.order_status = 3
            WHERE c.outlet_id = ? AND c.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ? GROUP BY c.ingredient_id", array($o, $from, $to));
        $used_mod = $sum("SELECT c.ingredient_id, SUM(c.consumption) q FROM tbl_sale_consumptions_of_modifiers_of_menus c
            JOIN tbl_sales s ON s.id = c.sales_id AND s.del_status = 'Live' AND s.order_status = 3
            WHERE c.outlet_id = ? AND c.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ? GROUP BY c.ingredient_id", array($o, $from, $to));
        $used_mess = $sum("SELECT c.ingredient_id, SUM(c.consumption) q FROM tbl_sale_consumptions_of_menus c
            JOIN tbl_mess_day_close mc ON mc.id = c.mess_close_id
            WHERE c.outlet_id = ? AND c.del_status = 'Live' AND c.mess_close_id > 0 AND mc.close_date >= ? AND mc.close_date <= ? GROUP BY c.ingredient_id", array($o, $from, $to));
        $wasted = $sum("SELECT wi.ingredient_id, SUM(wi.waste_amount) q, SUM(wi.loss_amount) v FROM tbl_waste_ingredients wi
            JOIN tbl_wastes w ON w.id = wi.waste_id AND w.del_status = 'Live'
            WHERE wi.outlet_id = ? AND wi.del_status = 'Live' AND w.date >= ? AND w.date <= ? GROUP BY wi.ingredient_id", array($o, $from, $to), true);
        $adj_minus = $sum("SELECT ai.ingredient_id, SUM(ai.consumption_amount) q FROM tbl_inventory_adjustment_ingredients ai
            JOIN tbl_inventory_adjustment a ON a.id = ai.inventory_adjustment_id AND a.del_status = 'Live'
            WHERE ai.outlet_id = ? AND ai.del_status = 'Live' AND ai.consumption_status = 'Minus' AND a.date >= ? AND a.date <= ?
            GROUP BY ai.ingredient_id", array($o, $from, $to));
        $t_in = $sum("SELECT ti.ingredient_id, SUM(ti.quantity_amount) q FROM tbl_transfer_ingredients ti
            JOIN tbl_transfer t ON t.id = ti.transfer_id AND t.del_status = 'Live'
            WHERE ti.to_outlet_id = ? AND ti.del_status = 'Live' AND ti.status = 1 AND ti.transfer_type = 1 AND t.date >= ? AND t.date <= ?
            GROUP BY ti.ingredient_id", array($o, $from, $to));
        $t_out = $sum("SELECT ti.ingredient_id, SUM(ti.quantity_amount) q FROM tbl_transfer_ingredients ti
            JOIN tbl_transfer t ON t.id = ti.transfer_id AND t.del_status = 'Live'
            WHERE ti.from_outlet_id = ? AND ti.del_status = 'Live' AND ti.status = 1 AND ti.transfer_type = 1 AND t.date >= ? AND t.date <= ?
            GROUP BY ti.ingredient_id", array($o, $from, $to));

        $ingredients = $this->db->query("SELECT id, name, conversion_rate, consumption_unit_cost, purchase_price, unit_id, purchase_unit_id
            FROM tbl_ingredients WHERE company_id = ? AND del_status = 'Live'", array((int) $company_id))->result();
        $units = array();
        foreach ($this->db->query("SELECT id, unit_name FROM tbl_units")->result() as $u) {
            $units[(int) $u->id] = $u->unit_name;
        }
        $g = function ($arr, $id, $i = null) {
            if (!isset($arr[$id])) {
                return 0.0;
            }
            return $i === null ? $arr[$id] : $arr[$id][$i];
        };
        $factor = (float) $s->overbuy_factor > 0 ? (float) $s->overbuy_factor : 1.2;
        $cover_limit = (int) $s->cover_days_limit > 0 ? (int) $s->cover_days_limit : 14;
        $rows = array();
        $totals = array('purchased_value' => 0.0, 'usage_value' => 0.0, 'waste_value' => 0.0, 'shrink_value' => 0.0, 'excess_value' => 0.0);
        foreach ($ingredients as $i) {
            $id = (int) $i->id;
            $cr = (float) $i->conversion_rate;
            $unit_cost = (float) $i->consumption_unit_cost;
            $row = array(
                'id' => $id,
                'name' => $this->text($i->name),
                'unit' => isset($units[(int) $i->unit_id]) ? $units[(int) $i->unit_id] : '',
                'purchase_unit' => isset($units[(int) $i->purchase_unit_id]) ? $units[(int) $i->purchase_unit_id] : '',
                'conversion_rate' => $cr,
                'unit_cost' => $unit_cost,
                'purchased' => $g($purchased, $id, 0) * $cr,
                'purchased_value' => $g($purchased, $id, 1),
                'transfer_in' => $g($t_in, $id) * $cr,
                'transfer_out' => $g($t_out, $id) * $cr,
                'used' => $g($used, $id) + $g($used_mod, $id) + $g($used_mess, $id),
                'wasted' => $g($wasted, $id, 0),
                'waste_value' => $g($wasted, $id, 1),
                'shrink' => $g($adj_minus, $id),
                'stock' => isset($stock[$id]) ? $stock[$id]->stock : 0.0,
            );
            if (!$row['purchased'] && !$row['transfer_in'] && !$row['transfer_out'] && !$row['used'] && !$row['wasted'] && !$row['shrink'] && abs($row['stock']) < 0.0001) {
                continue;
            }
            $row['usage_value'] = $row['used'] * $unit_cost;
            $row['shrink_value'] = $row['shrink'] * $unit_cost;
            $outflow = $row['used'] + $row['transfer_out'];
            $row['daily_use'] = $outflow / max(1, $days);
            $row['cover_days'] = $row['daily_use'] > 0 ? round($row['stock'] / $row['daily_use'], 1) : null;
            $row['waste_pct'] = $row['used'] > 0 ? round($row['wasted'] / $row['used'] * 100, 1) : null;

            // over-purchase: bought clearly more than went out, and stock covers more days than the limit
            $row['overbuy'] = false;
            $row['excess_qty'] = 0.0;
            $went_out = $row['used'] + $row['wasted'] + $row['transfer_out'];
            if ($row['purchased'] + $row['transfer_in'] > $went_out * $factor && $row['stock'] > 0) {
                $excess = $row['daily_use'] > 0 ? $row['stock'] - $cover_limit * $row['daily_use'] : $row['stock'];
                if ($excess > 0) {
                    $row['overbuy'] = true;
                    $row['excess_qty'] = $excess;
                }
            }
            $row['excess_value'] = $row['excess_qty'] * $unit_cost;
            $row['high_waste'] = false;
            foreach (array('purchased', 'transfer_in', 'transfer_out', 'used', 'wasted', 'shrink', 'stock', 'daily_use', 'excess_qty') as $k) {
                $row[$k] = round($row[$k], 3);
            }
            foreach (array('purchased_value', 'usage_value', 'waste_value', 'shrink_value', 'excess_value') as $k) {
                $row[$k] = round($row[$k], 2);
                $totals[$k] += $row[$k];
            }
            $row['negative'] = $row['stock'] < -0.0001;
            $rows[$id] = $row;
        }
        // high waste: more than 5 % of the usage value, or among the 5 biggest waste values
        $by_waste = array_filter($rows, function ($r) {
            return $r['waste_value'] > 0;
        });
        uasort($by_waste, function ($a, $b) {
            return $b['waste_value'] <=> $a['waste_value'];
        });
        $top_waste = array_slice(array_keys($by_waste), 0, 5);
        foreach ($rows as $id => $r) {
            if ($r['waste_value'] > 0 && ($r['usage_value'] <= 0 || $r['waste_value'] > 0.05 * $r['usage_value'] || in_array($id, $top_waste))) {
                $rows[$id]['high_waste'] = true;
            }
        }
        $rows = array_values($rows);
        usort($rows, function ($a, $b) {
            return max($b['excess_value'], $b['waste_value'], $b['usage_value']) <=> max($a['excess_value'], $a['waste_value'], $a['usage_value']);
        });
        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }
        return array('from' => $from, 'to' => $to, 'days' => $days, 'cover_days_limit' => $cover_limit, 'items' => $rows, 'totals' => $totals);
    }

    /**
     * Profit & loss of a window (plan 6.6).
     */
    public function profitLoss($company_id, $outlet_id, $from, $to) {
        $o = (int) $outlet_id;
        $sale = $this->db->query("SELECT COUNT(*) AS orders, COALESCE(SUM(total_payable), 0) AS gross, COALESCE(SUM(vat), 0) AS vat,
                COALESCE(SUM(tips_amount_actual_charge), 0) AS tips, COALESCE(SUM(total_discount_amount), 0) AS discounts,
                COALESCE(SUM(CASE WHEN is_staff_meal = 1 THEN total_discount_amount ELSE 0 END), 0) AS staff_meals
            FROM tbl_sales WHERE outlet_id = ? AND order_status = 3 AND del_status = 'Live' AND sale_date >= ? AND sale_date <= ?",
            array($o, $from, $to))->row();
        $complimentary = (float) $this->db->query("SELECT COALESCE(SUM(d.discount_amount), 0) AS v FROM tbl_sales s
            JOIN tbl_sales_details d ON d.sales_id = s.id AND d.del_status = 'Live' AND d.is_complementary = 1
            WHERE s.outlet_id = ? AND s.order_status = 3 AND s.del_status = 'Live' AND (s.is_staff_meal IS NULL OR s.is_staff_meal <> 1)
              AND s.sale_date >= ? AND s.sale_date <= ?", array($o, $from, $to))->row('v');
        $refunds = (float) $this->db->query("SELECT COALESCE(SUM(total_refund), 0) AS v FROM tbl_sales
            WHERE outlet_id = ? AND order_status = 3 AND del_status = 'Live' AND DATE(refund_date_time) >= ? AND DATE(refund_date_time) <= ?",
            array($o, $from, $to))->row('v');
        $mess = (float) $this->db->query("SELECT COALESCE(SUM(CASE WHEN type = 'refund' THEN -amount ELSE amount END), 0) AS v
            FROM tbl_mess_payments WHERE outlet_id = ? AND del_status = 'Live' AND type IN ('payment', 'refund')
              AND only_date >= ? AND only_date <= ?", array($o, $from, $to))->row('v');
        $food_pos = (float) $this->db->query("SELECT COALESCE(SUM(x.q * i.consumption_unit_cost), 0) AS v FROM (
                SELECT c.ingredient_id, SUM(c.consumption) AS q FROM tbl_sale_consumptions_of_menus c
                JOIN tbl_sales s ON s.id = c.sales_id AND s.del_status = 'Live' AND s.order_status = 3
                WHERE c.outlet_id = ? AND c.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ? GROUP BY c.ingredient_id
                UNION ALL
                SELECT c.ingredient_id, SUM(c.consumption) AS q FROM tbl_sale_consumptions_of_modifiers_of_menus c
                JOIN tbl_sales s ON s.id = c.sales_id AND s.del_status = 'Live' AND s.order_status = 3
                WHERE c.outlet_id = ? AND c.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ? GROUP BY c.ingredient_id
            ) x JOIN tbl_ingredients i ON i.id = x.ingredient_id", array($o, $from, $to, $o, $from, $to))->row('v');
        $food_mess = (float) $this->db->query("SELECT COALESCE(SUM(c.consumption * i.consumption_unit_cost), 0) AS v
            FROM tbl_sale_consumptions_of_menus c
            JOIN tbl_mess_day_close mc ON mc.id = c.mess_close_id
            JOIN tbl_ingredients i ON i.id = c.ingredient_id
            WHERE c.outlet_id = ? AND c.del_status = 'Live' AND c.mess_close_id > 0 AND mc.close_date >= ? AND mc.close_date <= ?",
            array($o, $from, $to))->row('v');
        $waste = (float) $this->db->query("SELECT COALESCE(SUM(total_loss), 0) AS v FROM tbl_wastes
            WHERE outlet_id = ? AND del_status = 'Live' AND date >= ? AND date <= ?", array($o, $from, $to))->row('v');
        $salaries = (float) $this->db->query("SELECT COALESCE(SUM(amount), 0) AS v FROM tbl_payroll_payments
            WHERE company_id = ? AND outlet_id = ? AND del_status = 'Live' AND pay_date >= ? AND pay_date <= ?",
            array((int) $company_id, $o, $from, $to))->row('v');
        $expense_rows = $this->db->query("SELECT COALESCE(c.name, '-') AS category, SUM(e.amount) AS amount FROM tbl_expenses e
            LEFT JOIN tbl_expense_items c ON c.id = e.category_id
            WHERE e.outlet_id = ? AND e.del_status = 'Live' AND e.date >= ? AND e.date <= ?
              AND e.id NOT IN (SELECT expense_id FROM tbl_payroll_payments WHERE expense_id > 0 AND del_status = 'Live')
            GROUP BY c.name ORDER BY amount DESC", array($o, $from, $to))->result();
        $expenses = array();
        $expenses_total = 0.0;
        foreach ($expense_rows as $r) {
            $expenses[] = array('category' => $this->text($r->category), 'amount' => round((float) $r->amount, 2));
            $expenses_total += (float) $r->amount;
        }
        $gross = (float) $sale->gross;
        $net_sales = $gross - (float) $sale->vat - (float) $sale->tips;
        $food = $food_pos + $food_mess;
        $revenue = $net_sales + $mess;
        $net_profit = $revenue - $refunds - $food - $waste - $salaries - $expenses_total;
        $other_discounts = (float) $sale->discounts - (float) $sale->staff_meals - $complimentary;
        return array(
            'from' => $from,
            'to' => $to,
            'orders' => (int) $sale->orders,
            'gross_sales' => round($gross, 2),
            'vat' => round((float) $sale->vat, 2),
            'tips' => round((float) $sale->tips, 2),
            'net_sales' => round($net_sales, 2),
            'discounts' => round($other_discounts, 2),
            'complimentary' => round($complimentary, 2),
            'staff_meals' => round((float) $sale->staff_meals, 2),
            'refunds' => round($refunds, 2),
            'mess_revenue' => round($mess, 2),
            'food_cost' => round($food, 2),
            'food_cost_pct' => $revenue > 0 ? round($food / $revenue * 100, 1) : null,
            'waste' => round($waste, 2),
            'salaries' => round($salaries, 2),
            'expenses' => $expenses,
            'expenses_total' => round($expenses_total, 2),
            'net_profit' => round($net_profit, 2),
            'avg_ticket' => (int) $sale->orders > 0 ? round($gross / (int) $sale->orders, 2) : null,
        );
    }

    /** add value and % change of every money line: cur vs prev */
    private function withChange($pnl) {
        $change = array();
        foreach ($pnl['cur'] as $k => $v) {
            if (is_numeric($v) && isset($pnl['prev'][$k]) && is_numeric($pnl['prev'][$k]) && !in_array($k, array('from', 'to'))) {
                $p = (float) $pnl['prev'][$k];
                $change[$k] = array('value' => round($v - $p, 2), 'pct' => abs($p) > 0.0001 ? round(($v - $p) / abs($p) * 100, 1) : null);
            }
        }
        $pnl['change'] = $change;
        return $pnl;
    }

    /** headline: yesterday vs the same weekday a week earlier */
    private function yesterday($outlet_id, $w) {
        $q = function ($day) use ($outlet_id) {
            return $this->db->query("SELECT COUNT(*) AS orders, COALESCE(SUM(total_payable), 0) AS gross FROM tbl_sales
                WHERE outlet_id = ? AND order_status = 3 AND del_status = 'Live' AND sale_date = ?", array((int) $outlet_id, $day))->row();
        };
        $y = $q($w['yesterday']['from']);
        $l = $q($w['last_week_same_day']['from']);
        return array(
            'date' => $w['yesterday']['from'],
            'orders' => (int) $y->orders,
            'gross_sales' => round((float) $y->gross, 2),
            'last_week_orders' => (int) $l->orders,
            'last_week_gross_sales' => round((float) $l->gross, 2),
            'change_pct' => (float) $l->gross > 0 ? round(((float) $y->gross - (float) $l->gross) / (float) $l->gross * 100, 1) : null,
        );
    }

    /* -----------------------------------------------------------------
     * prediction (plan 6.7)
     * ----------------------------------------------------------------- */

    /**
     * Daily quantity per item (paid + giveaway: the kitchen cooks both) between two dates.
     * @return array food_menu_id => [Y-m-d => qty]
     */
    public function dailyQty($outlet_id, $from, $to) {
        $rows = $this->db->query("SELECT s.sale_date, d.food_menu_id, SUM(d.qty) AS qty FROM tbl_sales s
            JOIN tbl_sales_details d ON d.sales_id = s.id AND d.del_status = 'Live'
            WHERE s.outlet_id = ? AND s.order_status = 3 AND s.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ?
            GROUP BY s.sale_date, d.food_menu_id", array((int) $outlet_id, $from, $to))->result();
        $series = array();
        foreach ($rows as $r) {
            $series[(int) $r->food_menu_id][substr($r->sale_date, 0, 10)] = (float) $r->qty;
        }
        return $series;
    }

    /**
     * Forecast of one item for one day from the days before it only:
     * same weekday of the last 8 weeks (min 4, never before the first sale), the last 4 weeks count
     * double, with 6+ values the highest and the lowest are dropped; times a trend factor
     * (last 28 days / the 28 before, kept within 0.8-1.2).
     * @return float|null null when there are fewer than 4 weeks
     */
    public function forecastItemDay($series_item, $date, $first_sale) {
        $values = array();
        for ($k = 1; $k <= 8; $k++) {
            $d = $this->addDays($date, -7 * $k);
            if ($d < $first_sale) {
                break;
            }
            $values[] = array('v' => isset($series_item[$d]) ? $series_item[$d] : 0.0, 'w' => $k <= 4 ? 2 : 1);
        }
        if (count($values) < 4) {
            return null;
        }
        if (count($values) >= 6) {
            $sorted = $values;
            usort($sorted, function ($a, $b) {
                return $a['v'] <=> $b['v'];
            });
            $min = $sorted[0]['v'];
            $max = $sorted[count($sorted) - 1]['v'];
            $dropped_min = $dropped_max = false;
            $kept = array();
            foreach ($values as $v) {
                if (!$dropped_min && $v['v'] == $min) {
                    $dropped_min = true;
                    continue;
                }
                if (!$dropped_max && $v['v'] == $max) {
                    $dropped_max = true;
                    continue;
                }
                $kept[] = $v;
            }
            $values = $kept;
        }
        $sum = 0.0;
        $weights = 0;
        foreach ($values as $v) {
            $sum += $v['v'] * $v['w'];
            $weights += $v['w'];
        }
        $avg = $weights ? $sum / $weights : 0.0;
        $last = $prior = 0.0;
        foreach ($series_item as $d => $q) {
            if ($d >= $this->addDays($date, -28) && $d < $date) {
                $last += $q;
            } elseif ($d >= $this->addDays($date, -56) && $d < $this->addDays($date, -28)) {
                $prior += $q;
            }
        }
        $trend = $prior > 0 ? min(1.2, max(0.8, $last / $prior)) : 1.0;
        return round($avg * $trend, 2);
    }

    /**
     * Next 7 days per item, accuracy on the last 7 days (back-test with the same rule),
     * and the suggested purchase for 7 days + safety stock.
     */
    public function forecast($company_id, $outlet_id, $today, $health, $cur, $costs, $prices, $company, $stock, $purchase, $s) {
        $gate = $health['gates']['forecast'];
        $out = array('available' => $gate != 'blocked', 'gate' => $gate, 'days' => array(), 'items' => array(),
            'accuracy_pct' => null, 'purchase' => array(), 'purchase_total' => 0.0);
        if ($gate == 'blocked') {
            return $out;
        }
        $first_sale = $this->db->query("SELECT MIN(sale_date) AS d FROM tbl_sales WHERE outlet_id = ? AND order_status = 3 AND del_status = 'Live'",
            array((int) $outlet_id))->row('d');
        $first_sale = substr((string) $first_sale, 0, 10);
        $series = $this->dailyQty($outlet_id, $this->addDays($today, -63), $this->addDays($today, -1));

        // back-test: what the same rule would have said for each of the last 7 days
        $err = $actual_sum = 0.0;
        for ($i = 7; $i >= 1; $i--) {
            $d = $this->addDays($today, -$i);
            $f_day = $a_day = 0.0;
            $any = false;
            foreach ($series as $item_series) {
                $f = $this->forecastItemDay($item_series, $d, $first_sale);
                if ($f !== null) {
                    $f_day += $f;
                    $any = true;
                }
                $a_day += isset($item_series[$d]) ? $item_series[$d] : 0.0;
            }
            if ($any) {
                $err += abs($f_day - $a_day);
                $actual_sum += $a_day;
            }
        }
        $out['accuracy_pct'] = $actual_sum > 0 ? round($err / $actual_sum * 100, 1) : null;

        // next 7 days
        $item_totals = array();
        for ($i = 0; $i < 7; $i++) {
            $d = $this->addDays($today, $i);
            $qty_day = $value_day = 0.0;
            foreach ($series as $id => $item_series) {
                $f = $this->forecastItemDay($item_series, $d, $first_sale);
                if ($f === null || $f <= 0) {
                    continue;
                }
                $qty_day += $f;
                if (!isset($item_totals[$id])) {
                    $item_totals[$id] = array_fill(0, 7, 0.0);
                }
                $item_totals[$id][$i] = $f;
                $value_day += $f * $this->unitNetPrice($id, $cur, $costs, $prices, $company);
            }
            $out['days'][] = array('date' => $d, 'weekday' => date('D', strtotime($d)), 'qty' => round($qty_day, 1), 'sales_value' => round($value_day, 2));
        }
        foreach ($item_totals as $id => $days) {
            $out['items'][] = array('id' => $id, 'name' => isset($costs[$id]) ? $costs[$id]->name : ('#' . $id),
                'days' => array_map(function ($v) {
                    return round($v, 1);
                }, $days), 'total' => round(array_sum($days), 1));
        }
        usort($out['items'], function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // suggested purchase: recipe need of the forecast + mess/modifier use + waste allowance + safety days - stock
        $per_plate = $this->perPlateUsage($company_id, array_keys($item_totals));
        $need = array();
        foreach ($item_totals as $id => $days) {
            foreach (isset($per_plate[$id]) ? $per_plate[$id] : array() as $ing => $per) {
                $need[$ing] = (isset($need[$ing]) ? $need[$ing] : 0.0) + array_sum($days) * $per;
            }
        }
        $by_ing = array();
        foreach ($purchase['items'] as $r) {
            $by_ing[$r['id']] = $r;
        }
        // mess and modifier use is not in the item forecast: add their daily average of the last 28 days
        $extra = $this->extraDailyUse($outlet_id, $this->addDays($today, -28), $this->addDays($today, -1));
        foreach ($extra as $ing => $daily) {
            $need[$ing] = (isset($need[$ing]) ? $need[$ing] : 0.0) + $daily * 7;
        }
        $safety = max(0, (int) $s->safety_days);
        $ing_rows = $this->db->query("SELECT i.id, i.name, i.conversion_rate, i.purchase_price, i.consumption_unit_cost, u.unit_name, pu.unit_name AS purchase_unit
            FROM tbl_ingredients i LEFT JOIN tbl_units u ON u.id = i.unit_id LEFT JOIN tbl_units pu ON pu.id = i.purchase_unit_id
            WHERE i.company_id = ? AND i.del_status = 'Live'", array((int) $company_id))->result();
        $ing_info = array();
        foreach ($ing_rows as $r) {
            $ing_info[(int) $r->id] = $r;
        }
        foreach ($need as $ing => $qty) {
            if ($qty <= 0 || !isset($ing_info[$ing])) {
                continue;
            }
            $info = $ing_info[$ing];
            $waste_pct = isset($by_ing[$ing]) && $by_ing[$ing]['used'] > 0 ? min(0.3, $by_ing[$ing]['wasted'] / $by_ing[$ing]['used']) : 0.0;
            $with_waste = $qty * (1 + $waste_pct);
            $target = $with_waste + $safety * $with_waste / 7;
            $on_hand = isset($stock[$ing]) ? max(0.0, $stock[$ing]->stock) : 0.0;
            $short = $target - $on_hand;
            if ($short <= 0.0001) {
                continue;
            }
            $cr = (float) $info->conversion_rate;
            if ($cr > 0) {
                $units = ceil(round($short / $cr, 6));
                $cost = $units * (float) $info->purchase_price;
            } else {
                $units = ceil(round($short, 6));
                $cost = $units * (float) $info->consumption_unit_cost;
            }
            $out['purchase'][] = array(
                'id' => $ing,
                'name' => $this->text($info->name),
                'need_7d' => round($with_waste, 2),
                'safety' => round($target - $with_waste, 2),
                'stock' => round($on_hand, 2),
                'short' => round($short, 2),
                'unit' => (string) $info->unit_name,
                'buy_qty' => $units,
                'buy_unit' => $cr > 0 ? (string) $info->purchase_unit : (string) $info->unit_name,
                'est_cost' => round($cost, 2),
            );
            $out['purchase_total'] += $cost;
        }
        usort($out['purchase'], function ($a, $b) {
            return $b['est_cost'] <=> $a['est_cost'];
        });
        $out['purchase_total'] = round($out['purchase_total'], 2);
        return $out;
    }

    /** average net selling price of the last 28 days, else the outlet list price net of VAT */
    private function unitNetPrice($id, $cur, $costs, $prices, $company) {
        if (isset($cur['items'][$id]) && $cur['items'][$id]['paid_qty'] > 0) {
            return $cur['items'][$id]['net_revenue'] / $cur['items'][$id]['paid_qty'];
        }
        if (!isset($costs[$id])) {
            return 0.0;
        }
        $list = isset($prices[$id]) ? $prices[$id] : $costs[$id]->sale_price;
        return $company->tax_inclusive ? $list / (1 + $costs[$id]->vat_rate / 100) : $list;
    }

    /**
     * Ingredient use per plate of each menu: the recipe; combos from their items; resale items 1 x their ingredient.
     * @return array food_menu_id => [ingredient_id => consumption]
     */
    public function perPlateUsage($company_id, $menu_ids) {
        $menu_ids = array_values(array_unique(array_filter(array_map('intval', (array) $menu_ids))));
        if (!$menu_ids) {
            return array();
        }
        $menus = $this->loadMenus($company_id, $menu_ids);
        $combo = array();
        $ids = $menu_ids;
        $combo_ids = array_keys(array_filter($menus, function ($m) {
            return $m->product_type == 2;
        }));
        if ($combo_ids) {
            $this->db->select('food_menu_id, added_food_menu_id, quantity');
            $this->db->from('tbl_combo_food_menus');
            $this->db->where('del_status', 'Live');
            $this->db->where_in('food_menu_id', $combo_ids);
            foreach ($this->db->get()->result() as $line) {
                $combo[(int) $line->food_menu_id][] = $line;
                $ids[] = (int) $line->added_food_menu_id;
            }
            $menus += $this->loadMenus($company_id, array_diff($ids, array_keys($menus)) ?: array(0));
        }
        $recipe = array();
        $this->db->select('food_menu_id, ingredient_id, consumption');
        $this->db->from('tbl_food_menus_ingredients');
        $this->db->where('del_status', 'Live');
        $this->db->where_in('food_menu_id', array_unique($ids));
        foreach ($this->db->get()->result() as $r) {
            $recipe[(int) $r->food_menu_id][(int) $r->ingredient_id] = (isset($recipe[(int) $r->food_menu_id][(int) $r->ingredient_id]) ? $recipe[(int) $r->food_menu_id][(int) $r->ingredient_id] : 0) + (float) $r->consumption;
        }
        $this->db->select('id, food_id');
        $this->db->from('tbl_ingredients');
        $this->db->where('company_id', (int) $company_id);
        $this->db->where('del_status', 'Live');
        $this->db->where_in('food_id', array_unique($ids));
        foreach ($this->db->get()->result() as $r) {
            if (isset($menus[(int) $r->food_id]) && $menus[(int) $r->food_id]->product_type == 3) {
                $recipe[(int) $r->food_id] = array((int) $r->id => 1.0);
            }
        }
        $out = array();
        foreach ($menu_ids as $id) {
            if (!isset($menus[$id])) {
                continue;
            }
            if ($menus[$id]->product_type == 2) {
                $out[$id] = array();
                foreach (isset($combo[$id]) ? $combo[$id] : array() as $line) {
                    foreach (isset($recipe[(int) $line->added_food_menu_id]) ? $recipe[(int) $line->added_food_menu_id] : array() as $ing => $q) {
                        $out[$id][$ing] = (isset($out[$id][$ing]) ? $out[$id][$ing] : 0) + $q * (float) $line->quantity;
                    }
                }
            } else {
                $out[$id] = isset($recipe[$id]) ? $recipe[$id] : array();
            }
        }
        return $out;
    }

    /** daily average of mess and modifier ingredient use between two dates */
    private function extraDailyUse($outlet_id, $from, $to) {
        $days = max(1, (int) round((strtotime($to) - strtotime($from)) / 86400) + 1);
        $rows = $this->db->query("SELECT x.ingredient_id, SUM(x.q) AS q FROM (
                SELECT c.ingredient_id, SUM(c.consumption) AS q FROM tbl_sale_consumptions_of_menus c
                JOIN tbl_mess_day_close mc ON mc.id = c.mess_close_id
                WHERE c.outlet_id = ? AND c.del_status = 'Live' AND c.mess_close_id > 0 AND mc.close_date >= ? AND mc.close_date <= ?
                GROUP BY c.ingredient_id
                UNION ALL
                SELECT c.ingredient_id, SUM(c.consumption) AS q FROM tbl_sale_consumptions_of_modifiers_of_menus c
                JOIN tbl_sales s ON s.id = c.sales_id AND s.del_status = 'Live' AND s.order_status = 3
                WHERE c.outlet_id = ? AND c.del_status = 'Live' AND s.sale_date >= ? AND s.sale_date <= ?
                GROUP BY c.ingredient_id
            ) x GROUP BY x.ingredient_id", array((int) $outlet_id, $from, $to, (int) $outlet_id, $from, $to))->result();
        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r->ingredient_id] = (float) $r->q / $days;
        }
        return $out;
    }

    /* -----------------------------------------------------------------
     * top 5 improvements (plan 6.8)
     * ----------------------------------------------------------------- */

    public function issues($menu, $price, $purchase, $pnl, $gates, $checks) {
        $per30 = 30 / 28;
        $conf = function ($gate) {
            return $gate == 'ready' ? 'high' : ($gate == 'limited' ? 'medium' : 'low');
        };
        $list = array();
        $add = function ($type, $subject_key, $subject, $impact, $confidence, $numbers, $action) use (&$list) {
            $list[] = array(
                'id' => $type . ':' . $subject_key,
                'type' => $type,
                'subject_key' => $subject_key,
                'subject' => $subject,
                'impact_30d' => round(max(0, $impact), 2),
                'confidence' => $confidence,
                'numbers' => $numbers,
                'action' => $action,
            );
        };

        if ($gates['price'] != 'blocked') {
            foreach ($price['items'] as $p) {
                if (in_array($p['band'], array('loss', 'critical', 'high', 'watch')) && $p['qty'] > 0 && $p['impact'] > 0) {
                    $type = $p['band'] == 'loss' ? 'loss_item' : 'underpriced';
                    $add($type, 'menu' . $p['id'], $p['name'], $p['impact'] * $per30, $conf($gates['price']),
                        array('price' => $p['list_price'], 'plate_cost' => $p['plate_cost'], 'food_cost_pct' => $p['food_cost_pct'],
                            'suggested_price' => $p['suggested_price'], 'qty_28d' => $p['qty']),
                        sprintf('Raise the price of %s from %s to about %s, or cut its recipe cost (now %s per plate, %s%% food cost).',
                            $p['name'], $this->num($p['list_price']), $this->num($p['suggested_price']), $this->num($p['plate_cost']), $p['food_cost_pct']));
                }
            }
        }
        if ($gates['purchase'] != 'blocked') {
            foreach ($purchase['items'] as $r) {
                if ($r['overbuy'] && $r['excess_value'] > 0) {
                    $add('overbuy', 'ing' . $r['id'], $r['name'], $r['excess_value'], $conf($gates['purchase']),
                        array('purchased' => $r['purchased'], 'used' => $r['used'], 'stock' => $r['stock'], 'unit' => $r['unit'],
                            'cover_days' => $r['cover_days'], 'excess_value' => $r['excess_value']),
                        sprintf('Stop buying %s until the stock comes down: about %s worth (%s %s) is more than %s days of use.',
                            $r['name'], $this->num($r['excess_value']), $this->num($r['excess_qty']), $r['unit'], $purchase['cover_days_limit']));
                }
                if ($r['high_waste'] && $r['waste_value'] > 0) {
                    $add('waste', 'ing' . $r['id'], $r['name'], $r['waste_value'] * $per30, $checks['waste']['status'] == 'good' ? 'high' : 'medium',
                        array('wasted' => $r['wasted'], 'unit' => $r['unit'], 'waste_value' => $r['waste_value'], 'waste_pct' => $r['waste_pct']),
                        sprintf('Find out why %s is wasted (%s %s, worth %s in 28 days): check storage, portion sizes and how much is prepared ahead.',
                            $r['name'], $this->num($r['wasted']), $r['unit'], $this->num($r['waste_value'])));
                }
            }
        }
        $cur = $pnl['cur'];
        $prev = $pnl['prev'];
        $discounts = $cur['discounts'];
        $before_discounts = $cur['gross_sales'] + $discounts;
        if ($before_discounts > 0 && $discounts > 0.05 * $before_discounts) {
            $add('discounts', 'all', 'Discounts', ($discounts - 0.05 * $before_discounts) * $per30, 'high',
                array('discounts' => $discounts, 'discount_pct' => round($discounts / $before_discounts * 100, 1)),
                sprintf('Discounts are %s%% of sales (%s in 28 days). Limit who can give them and review the reasons.',
                    round($discounts / $before_discounts * 100, 1), $this->num($discounts)));
        }
        if ($prev['net_sales'] > 0 && $cur['net_sales'] < $prev['net_sales'] * 0.9) {
            $add('sales_drop', 'all', 'Total sales', ($prev['net_sales'] - $cur['net_sales']) * $per30, 'high',
                array('net_sales' => $cur['net_sales'], 'prev_net_sales' => $prev['net_sales']),
                sprintf('Net sales fell from %s to %s compared with the previous 28 days. Check which days and items dropped.',
                    $this->num($prev['net_sales']), $this->num($cur['net_sales'])));
        }
        foreach ($menu['items'] as $m) {
            if ($m['prev_net_revenue'] > 0 && $m['qty_change_pct'] !== null && $m['qty_change_pct'] <= -20) {
                $add('item_drop', 'menu' . $m['id'], $m['name'], ($m['prev_net_revenue'] - $m['net_revenue']) * $per30, 'high',
                    array('qty' => $m['qty'], 'prev_qty' => $m['prev_qty'], 'qty_change_pct' => $m['qty_change_pct']),
                    sprintf('%s sold %s, down from %s (%s%%). Check quality, price and how it is shown on the menu.',
                        $m['name'], $this->num($m['qty']), $this->num($m['prev_qty']), $m['qty_change_pct']));
            }
        }
        $prev_exp = array();
        foreach ($prev['expenses'] as $e) {
            $prev_exp[$e['category']] = $e['amount'];
        }
        foreach ($cur['expenses'] as $e) {
            $p = isset($prev_exp[$e['category']]) ? $prev_exp[$e['category']] : 0.0;
            if ($p > 0 && $e['amount'] > $p * 1.2) {
                $add('expense_jump', 'exp' . md5($e['category']), $e['category'], ($e['amount'] - $p) * $per30, 'high',
                    array('amount' => $e['amount'], 'prev_amount' => $p),
                    sprintf('%s expenses rose from %s to %s compared with the previous 28 days.', $e['category'], $this->num($p), $this->num($e['amount'])));
            }
        }
        foreach ($menu['items'] as $m) {
            if ($m['class'] == 'dog') {
                $add('dog_item', 'menu' . $m['id'], $m['name'], 0, $conf($gates['menu']),
                    array('qty' => $m['qty'], 'cm' => $m['cm']),
                    sprintf('%s sells little and earns little per plate. Consider removing it or reworking the recipe.', $m['name']));
            }
        }
        foreach ($menu['unsold'] as $u) {
            $add('unsold_item', 'menu' . $u['id'], $u['name'], 0, 'high', array(),
                sprintf('%s did not sell at all in 28 days. Consider removing it from this outlet\'s menu.', $u['name']));
        }

        usort($list, function ($a, $b) {
            return $b['impact_30d'] <=> $a['impact_30d'] ?: strcmp($a['id'], $b['id']);
        });
        // one issue per item / ingredient: keep the biggest
        $seen = array();
        $top = array();
        foreach ($list as $issue) {
            $key = $issue['subject_key'] == 'all' ? $issue['type'] : $issue['subject_key'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $top[] = $issue;
            if (count($top) == 5) {
                break;
            }
        }
        return array('all' => $list, 'top5' => $top);
    }

    private function num($v) {
        return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    }

    /** names are saved HTML-escaped by the input forms (htmlspecialcharscustom): give plain text, views escape again */
    private function text($s) {
        return html_entity_decode((string) $s, ENT_QUOTES, 'UTF-8');
    }

    /* -----------------------------------------------------------------
     * trimmed copy for the AI (plan 6.10)
     * ----------------------------------------------------------------- */

    /**
     * What the daily report model receives: top N rows, 2 decimals, no free-text notes, no personal data.
     * @param array $m result of build()
     * @return array
     */
    public function aiPayload($m) {
        $pick = function ($rows, $n, $keys) {
            $out = array();
            foreach (array_slice($rows, 0, $n) as $r) {
                $row = array();
                foreach ($keys as $k) {
                    if (array_key_exists($k, $r)) {
                        $row[$k] = is_float($r[$k]) ? round($r[$k], 2) : $r[$k];
                    }
                }
                $out[] = $row;
            }
            return $out;
        };
        $pnl_keys = array('orders', 'gross_sales', 'net_sales', 'discounts', 'complimentary', 'staff_meals', 'refunds', 'mess_revenue',
            'food_cost', 'food_cost_pct', 'waste', 'salaries', 'expenses_total', 'net_profit', 'avg_ticket');
        $pnl = function ($p) use ($pnl_keys) {
            $out = array();
            foreach ($pnl_keys as $k) {
                $out[$k] = $p[$k];
            }
            return $out;
        };
        $checks = array();
        foreach ($m['health']['checks'] as $k => $c) {
            $checks[$k] = array('status' => $c['status'], 'result' => $c['result']);
        }
        return array(
            'outlet' => $m['meta']['outlet_name'],
            'currency' => $m['meta']['currency'],
            'period' => $m['windows']['cur']['from'] . ' to ' . $m['windows']['cur']['to'],
            'target_food_cost_pct' => $m['meta']['target_food_cost_pct'],
            'data_health' => array('checks' => $checks, 'sections' => $m['health']['gates']),
            'yesterday' => $m['yesterday'],
            'top_issues' => $pick($m['top5'], 5, array('id', 'type', 'subject', 'impact_30d', 'confidence', 'numbers', 'action')),
            'menu' => array(
                'avg_cm' => $m['menu']['avg_cm'],
                'items' => $pick($m['menu']['items'], 15, array('name', 'class', 'qty', 'avg_price', 'plate_cost', 'cm', 'total_cm', 'qty_change_pct')),
                'unsold' => array_slice(array_column($m['menu']['unsold'], 'name'), 0, 15),
            ),
            'price_check' => $pick(array_values(array_filter($m['price']['items'], function ($p) {
                return $p['band'] != 'good';
            })), 15, array('name', 'list_price', 'plate_cost', 'food_cost_pct', 'band', 'suggested_price', 'qty', 'impact')),
            'purchase_vs_usage' => array(
                'totals' => $m['purchase']['totals'],
                'items' => $pick($m['purchase']['items'], 10, array('name', 'unit', 'purchased', 'used', 'wasted', 'stock', 'cover_days',
                    'waste_pct', 'overbuy', 'high_waste', 'excess_value', 'waste_value', 'shrink_value')),
            ),
            'profit_loss' => array(
                'last_28_days' => $pnl($m['pnl']['cur']),
                'previous_28_days' => $pnl($m['pnl']['prev']),
                'expenses_by_category' => array_slice($m['pnl']['cur']['expenses'], 0, 8),
                'last_7_days' => $pnl($m['pnl7']['cur']),
                'previous_7_days' => $pnl($m['pnl7']['prev']),
            ),
            'forecast' => $m['forecast']['available'] ? array(
                'accuracy_last_7_days_pct' => $m['forecast']['accuracy_pct'],
                'days' => $m['forecast']['days'],
                'top_items' => $pick($m['forecast']['items'], 10, array('name', 'total')),
                'purchase' => $pick($m['forecast']['purchase'], 10, array('name', 'buy_qty', 'buy_unit', 'est_cost')),
                'purchase_total' => $m['forecast']['purchase_total'],
            ) : array('available' => false, 'reason' => $m['health']['checks']['history']['result']),
        );
    }
}
