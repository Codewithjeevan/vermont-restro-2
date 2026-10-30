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
            $result[$id] = (object) array(
                'id' => $id,
                'name' => $m->name,
                'product_type' => (int) $m->product_type,
                'cost' => round($c->cost, 4),
                'has_recipe' => $c->has_recipe,
                'missing_price' => $c->missing_price,
            );
        }
        return $result;
    }

    private function loadMenus($company_id, $ids) {
        $this->db->select('id, name, product_type');
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
                'name' => $i->name,
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
}
