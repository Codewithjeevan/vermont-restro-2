<?php
/*
  ###########################################################
  # PRODUCT NAME: 	iRestora PLUS - Next Gen Restaurant POS
  ###########################################################
  # AUTHER:		Doorsoft
  ###########################################################
  # EMAIL:		info@doorsoft.co
  ###########################################################
  # COPYRIGHTS:		RESERVED BY Door Soft
  ###########################################################
  # WEBSITE:		http://www.doorsoft.co
  ###########################################################
  # This is Report_model Model
  ###########################################################
 */
class Report_model extends CI_Model {
    /**
     * daily Summary Report
     * @access public
     * @return object
     * @param string
     * @param int
     */
    public function dailySummaryReport($selectedDate,$outlet_id='') {
        $this->db->select('*');
        $this->db->from('tbl_purchase');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $purchases = $this->db->get()->result();

        //daily sales
        $this->db->select('*');
        $this->db->from('tbl_sales');
        if ($selectedDate != '') {
            $this->db->where('sale_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('sale_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where("del_status", 'Live');
        $sales = $this->db->get()->result();


        //daily supplier due payments
        $this->db->select('*');
        $this->db->from('tbl_supplier_payments');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $supplier_due_payments = $this->db->get()->result();

        //daily customer due receives
        $this->db->select('*');
        $this->db->from('tbl_customer_due_receives');
        if ($selectedDate != '') {
            $this->db->where('only_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('only_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $customer_due_receives = $this->db->get()->result();

        //daily expenses
        $this->db->select('*');
        $this->db->from('tbl_expenses');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $expenses = $this->db->get()->result();

        //daily wastes
        $this->db->select('*');
        $this->db->from('tbl_wastes');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $wastes = $this->db->get()->result();

        $result = array();
        $result['purchases'] = $purchases;
        $result['sales'] = $sales;
        $result['supplier_due_payments'] = $supplier_due_payments;
        $result['customer_due_receives'] = $customer_due_receives;
        $result['expenses'] = $expenses;
        $result['wastes'] = $wastes;

        return $result;
    }
    public function zReportRegisters($selectedDate,$outlet_id='') {
        $this->db->select('*');
        $this->db->from('tbl_purchase');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $purchases = $this->db->get()->result();

        //daily sales
        $this->db->select('*');
        $this->db->from('tbl_sales');
        if ($selectedDate != '') {
            $this->db->where('sale_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('sale_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where("del_status", 'Live');
        $sales = $this->db->get()->result();


        //daily supplier due payments
        $this->db->select('*');
        $this->db->from('tbl_supplier_payments');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $supplier_due_payments = $this->db->get()->result();

        //daily customer due receives
        $this->db->select('*');
        $this->db->from('tbl_customer_due_receives');
        if ($selectedDate != '') {
            $this->db->where('only_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('only_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $customer_due_receives = $this->db->get()->result();

        //daily expenses
        $this->db->select('*');
        $this->db->from('tbl_expenses');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $expenses = $this->db->get()->result();

        //daily wastes
        $this->db->select('*');
        $this->db->from('tbl_wastes');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $wastes = $this->db->get()->result();

        $result = array();
        $result['purchases'] = $purchases;
        $result['sales'] = $sales;
        $result['supplier_due_payments'] = $supplier_due_payments;
        $result['customer_due_receives'] = $customer_due_receives;
        $result['expenses'] = $expenses;
        $result['wastes'] = $wastes;

        return $result;
    }
    /**
     * daily Consumption Report
     * @access public
     * @return object
     * @param string
     */
    public function dailyConsumptionReport($selectedDate) {
        $outlet_id = $this->session->userdata('outlet_id');

        //daily sale consumption of menu
        $this->db->select('*');
        $this->db->from('tbl_sale_consumptions_of_menus');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $sale_consumptions_of_menu = $this->db->get()->result();

        //daily sale consumption of menu's modifier
        $this->db->select('*');
        $this->db->from('tbl_sales');
        if ($selectedDate != '') {
            $this->db->where('sale_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('sale_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where("del_status", 'Live');
        $sale_consumption_of_menu_modifier = $this->db->get()->result();

        $result = array();
        $result['sale_consumptions_of_menu'] = $sale_consumptions_of_menu;
        $result['sale_consumption_of_menu_modifier'] = $sale_consumption_of_menu_modifier;

        return $result;
    }
    /**
     * today Summary Report
     * @access public
     * @return object
     * @param string
     */
    public function todaySummaryReport($selectedDate) {
        $outlet_id = $this->session->userdata('outlet_id');

        //purchase report
        $this->db->select('sum(paid) as total_purchase_amount');
        $this->db->from('tbl_purchase');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $purchase = $this->db->get()->result();
        //end purchase report
        //Sales report
        $this->db->select('sum(paid_amount) as total_sales_amount,sum(vat) as total_sales_vat');
        $this->db->from('tbl_sales');
        if ($selectedDate != '') {
            $this->db->where('sale_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('sale_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where("del_status", 'Live');
        $sales = $this->db->get()->result();
        //end Sales report
        //Waste report
        $this->db->select('sum(total_loss) as total_loss_amount');
        $this->db->from('tbl_wastes');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $waste = $this->db->get()->result();
        //end Waste report
        //Expense report
        $this->db->select('sum(amount) as expense_amount');
        $this->db->from('tbl_expenses');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $expense = $this->db->get()->result();

        //end expense report
        //Supplier payment report
        $this->db->select('sum(amount) as supplier_payment_amount');
        $this->db->from('tbl_supplier_payments');
        if ($selectedDate != '') {
            $this->db->where('date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $supplier_payment = $this->db->get()->result();
        //end expense report
        //Supplier payment report
        $this->db->select('sum(amount) as customer_receive_amount');
        $this->db->from('tbl_customer_due_receives');
        if ($selectedDate != '') {
            $this->db->where('only_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('only_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $customer_receive = $this->db->get()->result();
        //end Supplier payment report
        $allTotal = 0;
        $allTotal = $purchase[0]->total_purchase_amount + $sales[0]->total_sales_amount + $waste[0]->total_loss_amount + $expense[0]->expense_amount + $supplier_payment[0]->supplier_payment_amount;
        $result['total_purchase_amount'] = isset($purchase[0]->total_purchase_amount) && $purchase[0]->total_purchase_amount ? getAmtP($purchase[0]->total_purchase_amount) : getAmtP(0);
        $result['total_sales_amount'] = isset($sales[0]->total_sales_amount) && $sales[0]->total_sales_amount ? getAmtP($sales[0]->total_sales_amount) : getAmtP(0);
        $result['total_sales_vat'] = isset($sales[0]->total_sales_vat) && $sales[0]->total_sales_vat ? getAmtP($sales[0]->total_sales_vat) : getAmtP(0);
        $result['total_loss_amount'] = isset($waste[0]->total_loss_amount) && $waste[0]->total_loss_amount ? getAmtP($waste[0]->total_loss_amount) : getAmtP(0);
        $result['expense_amount'] = isset($expense[0]->expense_amount) && $expense[0]->expense_amount ? getAmtP($expense[0]->expense_amount) : getAmtP(0);
        $result['supplier_payment_amount'] = isset($supplier_payment[0]->supplier_payment_amount) && $supplier_payment[0]->supplier_payment_amount ? getAmtP($supplier_payment[0]->supplier_payment_amount) : getAmtP(0);
        $result['customer_receive_amount'] = isset($customer_receive[0]->customer_receive_amount) && $customer_receive[0]->customer_receive_amount ? getAmtP($customer_receive[0]->customer_receive_amount) : getAmtP(0);
        $result['allTotal'] = isset($allTotal) && $allTotal ? getAmtP($allTotal) : getAmtP(0);
        $balance = (($result['total_sales_amount'] + $result['customer_receive_amount']) - ($result['total_purchase_amount'] + $result['supplier_payment_amount'] + $result['expense_amount']));
        $result['balance'] = isset($balance) && $balance ? getAmtP($balance) : getAmtP(0);
        return $result;
    }
    /**
     * daily Summary Report Payment Method
     * @access public
     * @return object
     * @param string
     */
    public function dailySummaryReportPaymentMethod($selectedDate) {

        $outlet_id = $this->session->userdata('outlet_id');
        //payment method report
        $this->db->select('sum(total_payable) as total_sales_amount,payment_method_id');
        $this->db->from('tbl_sales');
        if ($selectedDate != '') {
            $this->db->where('sale_date =', $selectedDate);
        } else {
            $today = date('Y-m-d');
            $this->db->where('sale_date =', $today);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where("del_status", 'Live');
        $this->db->where('order_status', 3);
        $this->db->group_by('payment_method_id', "DESC");
        $paymentMethod = $this->db->get()->result();
        return $paymentMethod;
        //end purchase report
    }
    /**
     * today Summery Report
     * @access public
     * @return object
     * @param no
     */
    public function todaySummeryReport() {

        $outlet_id = $this->session->userdata('outlet_id');
        //payment method report
        $this->db->select('sum(tbl_sales.total_payable) as total_sales_amount, tbl_payment_methods.id,tbl_payment_methods.name');
        $this->db->from('tbl_sales');
        $today = date('Y-m-d');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_sales.payment_method_id', 'left');
        $this->db->where('sale_date =', $today);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->where("tbl_sales.del_status", 'Live');
        $this->db->group_by('payment_method_id', "DESC");
        $paymentMethod = $this->db->get()->result();
        return $paymentMethod;
        //end purchase report
    }
    /**
     * get Inventory
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function getInventory($category_id = "", $ingredient_id = "", $food_id = "",$outlet_id='') {
        $company_id = $this->session->userdata('company_id');
        $where = '';
        $where1 = '';
        if($food_id!=''){
            $getFMIds = $food_id;
        }else{
            $getFMIds = getFMIds($outlet_id);
        }
        if($category_id!=''){
            $where1.= "  AND ingr_tbl.category_id = '$category_id'";
        }
        if($ingredient_id!=''){
            $where1.= "  AND i.id = '$ingredient_id'";
        }
        //get selected food menu ids
        if($food_id){
            $result = $this->db->query("SELECT ingr_tbl.*,i.id as food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name,ingr_unit_tbl2.unit_name as unit_name2, (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
        (select SUM(consumption) from tbl_sale_consumptions_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_consumption,
        (select SUM(consumption) from tbl_sale_consumptions_of_modifiers_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND  del_status='Live') total_modifiers_consumption,
        (select SUM(waste_amount) from tbl_waste_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND tbl_waste_ingredients.del_status='Live') total_waste,
        (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Plus') total_consumption_plus,
        (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Minus') total_consumption_minus,
        (select SUM(quantity_amount) from tbl_production_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_production_ingredients.del_status='Live' AND tbl_production_ingredients.status=1) total_production,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND tbl_transfer_ingredients.status=1  AND tbl_transfer_ingredients.transfer_type=1) total_transfer_plus,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=1) total_transfer_minus,
        (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND  tbl_transfer_received_ingredients.status=1) total_transfer_plus_2,
        (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND (tbl_transfer_received_ingredients.status=1)) total_transfer_minus_2
        FROM tbl_food_menus_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.ingredient_id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id  LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id WHERE FIND_IN_SET(`food_menu_id`, '$getFMIds') AND i.company_id= '$company_id' AND i.del_status='Live' $where  GROUP BY i.ingredient_id")->result();
            return $result;
        }else{
            $result = $this->db->query("SELECT ingr_tbl.*,i.id as food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name,ingr_unit_tbl2.unit_name as unit_name2, (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
        (select SUM(consumption) from tbl_sale_consumptions_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_consumption,
        (select SUM(consumption) from tbl_sale_consumptions_of_modifiers_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND  del_status='Live') total_modifiers_consumption,
        (select SUM(waste_amount) from tbl_waste_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND tbl_waste_ingredients.del_status='Live') total_waste,
        (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Plus') total_consumption_plus,
        (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Minus') total_consumption_minus,
        (select SUM(quantity_amount) from tbl_production_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_production_ingredients.del_status='Live' AND tbl_production_ingredients.status=1) total_production,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND tbl_transfer_ingredients.status=1  AND tbl_transfer_ingredients.transfer_type=1) total_transfer_plus,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=1) total_transfer_minus,
        (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND  tbl_transfer_received_ingredients.status=1) total_transfer_plus_2,
        (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND (tbl_transfer_received_ingredients.status=1)) total_transfer_minus_2

        FROM tbl_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id  LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id WHERE i.company_id= '$company_id' AND i.del_status='Live' $where1 GROUP BY i.id")->result();
            return $result;
        }
    }
    /**
     * Monthly Inventory report: the stock page formula split by date. Quantities are in purchase
     * units, the same way the client's sheet counts them. Physical Count is the system stock at
     * the period end, so next month's Opening is always this month's Physical Count.
     * @access public
     * @return array rows and totals
     * @param string Y-m-d
     * @param string Y-m-d
     * @param int
     */
    public function monthlyInventoryReport($start_date, $end_date, $outlet_id) {
        $company_id = (int)$this->session->userdata('company_id');
        $outlet_id = (int)$outlet_id;
        $ingredients = $this->db->query("SELECT i.id, i.name, i.code, i.conversion_rate, i.purchase_price,
                u.unit_name AS consumption_unit, pu.unit_name AS purchase_unit
            FROM tbl_ingredients i
            LEFT JOIN tbl_ingredient_categories c ON c.id = i.category_id
            LEFT JOIN tbl_units u ON u.id = i.unit_id
            LEFT JOIN tbl_units pu ON pu.id = i.purchase_unit_id
            WHERE i.company_id = $company_id AND i.del_status = 'Live'
            ORDER BY c.category_name, i.name")->result();

        $moves = $this->monthlyInventoryMovements($start_date, $end_date, $outlet_id);
        $prices = getIngredientPricesAsOf($outlet_id, $end_date);

        $name_count = array();
        foreach ($ingredients as $ingredient) {
            $name = strtolower(trim((string)$ingredient->name));
            $name_count[$name] = isset($name_count[$name]) ? $name_count[$name] + 1 : 1;
        }

        $rows = array();
        $totals = array('total_stock_value' => 0, 'physical_count_value' => 0, 'usage_value' => 0, 'flagged' => 0);
        foreach ($ingredients as $ingredient) {
            $id = $ingredient->id;
            $has_rate = (float)$ingredient->conversion_rate > 0;
            $rate = $has_rate ? (float)$ingredient->conversion_rate : 1;
            $before = function ($source) use ($moves, $id) {
                return isset($moves[$source][$id]) ? $moves[$source][$id]['before_start'] : 0;
            };
            $period = function ($source) use ($moves, $id) {
                return isset($moves[$source][$id]) ? $moves[$source][$id]['in_period'] : 0;
            };

            //an Opening Stock row is always Opening, never Usage, even when dated inside the period
            $stock_before = ($before('purchase') + $before('production') + $before('transfer_in') - $before('transfer_out')) * $rate
                - $before('consumption') - $before('modifier') - $before('waste')
                + $before('adjust_plus') - $before('adjust_minus')
                + $before('opening') + $period('opening');
            $used = $period('consumption') + $period('modifier') + $period('waste') + $period('adjust_minus') - $period('adjust_plus');

            $opening_stock = round($stock_before / $rate, 6);
            $receiving = round($period('purchase') + $period('production'), 6);
            $transfer = round($period('transfer_in') - $period('transfer_out'), 6);
            $total_stock = round($opening_stock + $receiving + $transfer, 6);
            $usage = round($used / $rate, 6);
            $physical_count = round($total_stock - $usage, 6);
            $unit_price = isset($prices[$id]) ? $prices[$id] : (float)$ingredient->purchase_price;

            $flags = array();
            if ($total_stock > 0 && $unit_price == 0) {
                $flags[] = 'no_unit_price';
            }
            if ($usage < -0.0001) {
                $flags[] = 'count_higher';
            }
            if ($physical_count < -0.0001) {
                $flags[] = 'negative_stock';
            }
            if (!$has_rate) {
                $flags[] = 'no_conversion';
            }
            if ($name_count[strtolower(trim((string)$ingredient->name))] > 1) {
                $flags[] = 'listed_twice';
            }

            $row = (object)array(
                'name' => $ingredient->name,
                'code' => $ingredient->code,
                'purchase_unit' => $ingredient->purchase_unit,
                'consumption_unit' => $ingredient->consumption_unit,
                'conversion_rate' => $has_rate ? $rate : 0,
                'opening_stock' => $opening_stock,
                'receiving' => $receiving,
                'transfer' => $transfer,
                'total_stock' => $total_stock,
                'unit_price' => $unit_price,
                'total_stock_value' => $total_stock * $unit_price,
                'physical_count' => $physical_count,
                'physical_count_value' => $physical_count * $unit_price,
                'usage' => $usage,
                'usage_value' => $usage * $unit_price,
                'flags' => $flags,
            );
            $rows[] = $row;
            $totals['total_stock_value'] += $row->total_stock_value;
            $totals['physical_count_value'] += $row->physical_count_value;
            $totals['usage_value'] += $row->usage_value;
            $totals['flagged'] += $flags ? 1 : 0;
        }
        return array('rows' => $rows, 'totals' => $totals);
    }
    /**
     * every stock movement of an outlet, per ingredient, before the period and inside it. Same
     * tables and filters as the stock page; only the date comes from the parent record, because
     * consumption rows have none. A row without any date counts as before the period, so the
     * stock at the end still tallies with the stock page.
     * @access private
     * @return array source => ingredient_id => before_start, in_period
     * @param string
     * @param string
     * @param int
     */
    private function monthlyInventoryMovements($start_date, $end_date, $outlet_id) {
        $live = "t.del_status = 'Live'";
        $transfer_date = "IF(x.received_date IS NULL OR x.received_date < '2000-01-01', x.date, x.received_date)";
        $transfer_join = "LEFT JOIN tbl_transfer x ON x.id = t.transfer_id";
        $sale_join = "LEFT JOIN tbl_sales s ON s.id = t.sales_id
            LEFT JOIN tbl_sale_consumptions sc ON sc.id = t.sale_consumption_id
            LEFT JOIN tbl_sales s2 ON s2.id = sc.sale_id";
        $adjust_join = "LEFT JOIN tbl_inventory_adjustment p ON p.id = t.inventory_adjustment_id";

        $menu_join = $sale_join . " LEFT JOIN tbl_production pr ON pr.id = t.production_id";
        $menu_date = "COALESCE(s.sale_date, pr.date, s2.sale_date)";
        if ($this->db->field_exists('mess_close_id', 'tbl_sale_consumptions_of_menus')) {
            $menu_join .= " LEFT JOIN tbl_mess_day_close m ON m.id = t.mess_close_id";
            $menu_date = "COALESCE(s.sale_date, m.close_date, pr.date, s2.sale_date)";
        }

        $has_opening = $this->db->field_exists('is_opening', 'tbl_inventory_adjustment');
        $not_opening = $has_opening ? " AND IFNULL(p.is_opening, 0) = 0" : "";

        $sources = array(
            'purchase' => array(array("tbl_purchase_ingredients t LEFT JOIN tbl_purchase p ON p.id = t.purchase_id", "t.quantity_amount", "p.date", "t.outlet_id = $outlet_id AND $live")),
            'production' => array(array("tbl_production_ingredients t LEFT JOIN tbl_production p ON p.id = t.production_id", "t.quantity_amount", "p.date", "t.outlet_id = $outlet_id AND $live AND t.status = 1")),
            'transfer_in' => array(
                array("tbl_transfer_ingredients t $transfer_join", "t.quantity_amount", $transfer_date, "t.to_outlet_id = $outlet_id AND $live AND t.status = 1 AND t.transfer_type = 1"),
                array("tbl_transfer_received_ingredients t $transfer_join", "t.quantity_amount", $transfer_date, "t.to_outlet_id = $outlet_id AND $live AND t.status = 1"),
            ),
            'transfer_out' => array(
                array("tbl_transfer_ingredients t $transfer_join", "t.quantity_amount", $transfer_date, "t.from_outlet_id = $outlet_id AND $live AND t.status = 1 AND t.transfer_type = 1"),
                array("tbl_transfer_received_ingredients t $transfer_join", "t.quantity_amount", $transfer_date, "t.from_outlet_id = $outlet_id AND $live AND t.status = 1"),
            ),
            'consumption' => array(array("tbl_sale_consumptions_of_menus t $menu_join", "t.consumption", $menu_date, "t.outlet_id = $outlet_id AND $live")),
            'modifier' => array(array("tbl_sale_consumptions_of_modifiers_of_menus t $sale_join", "t.consumption", "COALESCE(s.sale_date, s2.sale_date)", "t.outlet_id = $outlet_id AND $live")),
            'waste' => array(array("tbl_waste_ingredients t LEFT JOIN tbl_wastes p ON p.id = t.waste_id", "t.waste_amount", "p.date", "t.outlet_id = $outlet_id AND $live")),
            'adjust_plus' => array(array("tbl_inventory_adjustment_ingredients t $adjust_join", "t.consumption_amount", "p.date", "t.outlet_id = $outlet_id AND $live AND t.consumption_status = 'Plus'$not_opening")),
            'adjust_minus' => array(array("tbl_inventory_adjustment_ingredients t $adjust_join", "t.consumption_amount", "p.date", "t.outlet_id = $outlet_id AND $live AND t.consumption_status = 'Minus'$not_opening")),
        );
        if ($has_opening) {
            $sources['opening'] = array(array("tbl_inventory_adjustment_ingredients t $adjust_join", "t.consumption_amount", "p.date", "t.outlet_id = $outlet_id AND $live AND t.consumption_status = 'Plus' AND p.is_opening = 1"));
        }

        $start = $this->db->escape($start_date);
        $end = $this->db->escape($end_date);
        $moves = array();
        foreach ($sources as $source => $queries) {
            $moves[$source] = array();
            foreach ($queries as $query) {
                list($from, $amount, $date, $where) = $query;
                $result = $this->db->query("SELECT t.ingredient_id,
                        SUM(CASE WHEN $date IS NULL OR $date < $start THEN $amount ELSE 0 END) AS before_start,
                        SUM(CASE WHEN $date >= $start AND $date <= $end THEN $amount ELSE 0 END) AS in_period
                    FROM $from
                    WHERE $where
                    GROUP BY t.ingredient_id")->result();
                foreach ($result as $row) {
                    if (!isset($moves[$source][$row->ingredient_id])) {
                        $moves[$source][$row->ingredient_id] = array('before_start' => 0, 'in_period' => 0);
                    }
                    $moves[$source][$row->ingredient_id]['before_start'] += (float)$row->before_start;
                    $moves[$source][$row->ingredient_id]['in_period'] += (float)$row->in_period;
                }
            }
        }
        return $moves;
    }
    /**
     * get Inventory Alert List
     * @access public
     * @return object
     * @param no
     */
    public function getInventoryAlertList() {
        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');

        $where = '';
        $getFMIds = getFMIds($outlet_id);

        $result = $this->db->query("SELECT ingr_tbl.*,i.food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name, (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
(select SUM(consumption) from tbl_sale_consumptions_of_menus where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND del_status='Live') total_consumption,
(select SUM(consumption) from tbl_sale_consumptions_of_modifiers_of_menus where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  del_status='Live') total_modifiers_consumption,
(select SUM(waste_amount) from tbl_waste_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND tbl_waste_ingredients.del_status='Live') total_waste,
(select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Plus') total_consumption_plus,
(select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Minus') total_consumption_minus,
(select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND tbl_transfer_ingredients.status=1  AND tbl_transfer_ingredients.transfer_type=1) total_transfer_plus,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=1) total_transfer_minus,
(select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND  tbl_transfer_received_ingredients.status=1) total_transfer_plus_2,
(select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND (tbl_transfer_received_ingredients.status=1)) total_transfer_minus_2

FROM tbl_food_menus_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.ingredient_id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.purchase_unit_id WHERE FIND_IN_SET(`food_menu_id`, '$getFMIds') AND i.company_id= '$company_id' AND i.del_status='Live' $where  GROUP BY i.ingredient_id")->result();
        return $result;
    }
    /**
     * sale Report By Month
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function saleReportByMonth($startMonth = '', $endMonth = '', $user_id = '') {
        if ($startMonth || $endMonth || $user_id):
            $outlet_id = $this->session->userdata('outlet_id');
            $this->db->select('sale_date,sum(total_payable) as total_payable');
            $this->db->from('tbl_sales');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }

            if ($user_id != '') {
                $this->db->where('user_id', $user_id);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->group_by('month(sale_date)');
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * vat Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function vatReport($startDate = '', $endDate = '',$outlet_id='') {
        if ($startDate || $endDate):
            $this->db->select('sale_no,sale_vat_objects,sale_date,sum(total_payable) as total_payable,sum(vat) as total_vat');
            $this->db->from('tbl_sales');

            if ($startDate != '' && $endDate != '') {
                $this->db->where('sale_date>=', $startDate);
                $this->db->where('sale_date <=', $endDate);
            }
            if ($startDate != '' && $endDate == '') {
                $this->db->where('sale_date', $startDate);
            }
            if ($startDate == '' && $endDate != '') {
                $this->db->where('sale_date', $endDate);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->group_by('id');
            $this->db->where('order_status', 3);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    public function vatExpense($startDate = '', $endDate = '', $outlet_id = '') {
        if ($startDate || $endDate) {
            $this->db->select('e.*, s.name as supplier_name, c.name as customer_name');
            $this->db->from('tbl_expenses e');
            $this->db->join('tbl_customers c', 'c.id = e.customer_id', 'left');
            $this->db->join('tbl_suppliers s', 's.id = e.supplier_id', 'left');
            if ($startDate != '' && $endDate != '') {
                $this->db->where('e.date >=', $startDate);
                $this->db->where('e.date <=', $endDate);
            } elseif ($startDate != '') {
                $this->db->where('e.date', $startDate);
            } elseif ($endDate != '') {
                $this->db->where('e.date', $endDate);
            }
            if ($outlet_id != '') {
                $this->db->where('e.outlet_id', $outlet_id);
            }
            $this->db->where('e.del_status', "Live");
            $this->db->group_by('e.id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        }
        return [];
    }
    public function vatPurchase($startDate = '', $endDate = '', $outlet_id = '') {
        if ($startDate || $endDate) {
            $this->db->select('p.*, s.name as supplier_name, COALESCE(SUM(pi.total), 0) as total_with_tax, COALESCE(SUM(pi.tax_amount), 0) as only_tax');
            $this->db->from('tbl_purchase p');
            $this->db->join('tbl_suppliers s', 's.id = p.supplier_id', 'left');
            $this->db->join('tbl_purchase_ingredients pi', 'p.id = pi.purchase_id', 'left');
            if ($startDate != '' && $endDate != '') {
                $this->db->where('p.date >=', $startDate);
                $this->db->where('p.date <=', $endDate);
            } elseif ($startDate != '') {
                $this->db->where('p.date', $startDate);
            } elseif ($endDate != '') {
                $this->db->where('p.date', $endDate);
            }
            if ($outlet_id != '') {
                $this->db->where('p.outlet_id', $outlet_id);
            }
            $this->db->where('p.del_status', "Live");
            $this->db->group_by('p.id'); 
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        }
        return [];

    }
    public function totalTaxDiscountChargeTips($startDate = '',$outlet_id='') {
        $this->db->select('sum(vat) as total_tax,sum(total_discount_amount) as total_discount,sum(delivery_charge_actual_charge) as total_charge,sum(tips_amount_actual_charge) as total_tips');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startDate);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where('del_status', "Live");
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function totalCharge($startDate = '',$outlet_id = '',$type = '') {
        $this->db->select('sum(delivery_charge_actual_charge) as total_charge');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startDate);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('order_status', 3);
        $this->db->where('charge_type', $type);
        $this->db->where('del_status', "Live");
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    /**
     * tips Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function tipsReport($startDate = '', $endDate = '',$outlet_id='',$waiter_id = '') {
        if ($startDate || $endDate):
            $this->db->select('sale_no,sale_date,total_payable,tips_amount_actual_charge as total_tips');
            $this->db->from('tbl_sales');

            if ($startDate != '' && $endDate != '') {
                $this->db->where('sale_date>=', $startDate);
                $this->db->where('sale_date <=', $endDate);
            }
            if ($startDate != '' && $endDate == '') {
                $this->db->where('sale_date', $startDate);
            }
            if ($startDate == '' && $endDate != '') {
                $this->db->where('sale_date', $endDate);
            }
            if ($waiter_id != '') {
                $this->db->where('tbl_sales.waiter_id', $waiter_id);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->group_by('id');
            $this->db->where('order_status', 3);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * sale Report ByDate
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function saleReportByDate($startDate = '', $endDate = '', $user_id = '',$outlet_id='') {
        if ($startDate || $endDate || $user_id):
            $this->db->select('sale_date,sum(total_payable) as total_payable,sum(total_refund) as total_refund');
            $this->db->from('tbl_sales');

            if ($startDate != '' && $endDate != '') {
                $this->db->where('sale_date>=', $startDate);
                $this->db->where('sale_date <=', $endDate);
            }
            if ($startDate != '' && $endDate == '') {
                $this->db->where('sale_date', $startDate);
            }
            if ($startDate == '' && $endDate != '') {
                $this->db->where('sale_date', $endDate);
            }

            if ($user_id != '') {
                $this->db->where('user_id', $user_id);
            }
            $this->db->where('order_status', '3');
            $this->db->where('outlet_id', $outlet_id);
            $this->db->group_by('sale_date');
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * profit Loss Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function profitLossReport($start_date, $end_date,$outlet_id='') {
        if ($start_date || $end_date):
            //purchase report
            $this->db->select('sum(total_cost) as total_cost,sum(total_sale_amount) as total_sale_amount,sum(total_tax) as total_tax,date');
            $this->db->from('tbl_transfer_ingredients');
            $this->db->join('tbl_transfer', 'tbl_transfer.id = tbl_transfer_ingredients.transfer_id', 'left');
            $this->db->where('tbl_transfer.date>=', $start_date);
            $this->db->where('tbl_transfer.date <=', $end_date);
            $this->db->where('tbl_transfer_ingredients.from_outlet_id', $outlet_id);
            $this->db->where('tbl_transfer_ingredients.del_status', 'Live');
            $this->db->where('tbl_transfer_ingredients.transfer_type', '1');
            $this->db->where('tbl_transfer_ingredients.status', 1);
            $transferred_out =  $this->db->get()->result();


            $this->db->select_sum('(consumption*average_consumption_per_unit)', 'total_price');
            $this->db->from('tbl_sale_consumptions_of_menus');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_consumptions_of_menus.sales_id', 'left');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_menus.ingredient_id', 'left');
            $this->db->where('sale_date>=', $start_date);
            $this->db->where('sale_date <=', $end_date);
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->where('tbl_sales.del_status', 'Live');
            $total_ing_cost_used =  $this->db->get()->result();


            $this->db->select_sum('(consumption*average_consumption_per_unit)', 'total_price');
            $this->db->from('tbl_sale_consumptions_of_modifiers_of_menus');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_consumptions_of_modifiers_of_menus.sales_id', 'left');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_modifiers_of_menus.ingredient_id', 'left');
            $this->db->where('sale_date>=', $start_date);
            $this->db->where('sale_date <=', $end_date);
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->where('tbl_sales.del_status', 'Live');
            $total_modi_ing_cost_used =  $this->db->get()->result();



            //end purchase report
            //Sales report
            $this->db->select('sum(total_payable) as total_sales_amount,sum(vat) as total_sales_vat');
            $this->db->from('tbl_sales');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $sales = $this->db->get()->result();
            //end Sales report
            $this->db->select('sum(total_refund) as total_total_refund');
            $this->db->from('tbl_sales');
            if ($start_date != '' && $end_date != '') {
                $this->db->where(("DATE(refund_date_time) >= '".$start_date."'"));
                $this->db->where(("DATE(refund_date_time) <= '".$end_date."'"));
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where(("DATE(refund_date_time) = '".$start_date."'"));
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where(("DATE(refund_date_time) = '".$end_date."'"));
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $sales_refund = $this->db->get()->result();


            //Waste report
            $this->db->select('sum(total_loss) as total_loss_amount');
            $this->db->from('tbl_wastes');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }

            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $waste = $this->db->get()->result();
            //end Waste report
            //Expense report
            $this->db->select('sum(amount) as expense_amount');
            $this->db->from('tbl_expenses');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $expense = $this->db->get()->result();


            $this->db->select('sum(total_loss) as wastes_amount');
            $this->db->from('tbl_wastes');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $wastes = $this->db->get()->result();
            //end expense report
            //Supplier payment report
            $this->db->select('sum(amount) as supplier_payment_amount');
            $this->db->from('tbl_supplier_payments');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $supplier_payment = $this->db->get()->result();
            //end expense report
            //Customer payment report
            $this->db->select('sum(amount) as customer_receive_amount');
            $this->db->from('tbl_customer_due_receives');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('only_date>=', $start_date);
                $this->db->where('only_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('only_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('only_date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $customer_receive = $this->db->get()->result();
            //end Supplier payment report

            $result['profit_1'] = isset($sales[0]->total_sales_amount) && $sales[0]->total_sales_amount ? $sales[0]->total_sales_amount : 0;
            $result['profit_2'] = 0;
            $result['profit_3'] = isset($total_ing_cost_used[0]->total_price) && $total_ing_cost_used[0]->total_price ? ($total_ing_cost_used[0]->total_price + $total_modi_ing_cost_used[0]->total_price): 0;;
            $result['profit_4'] = isset($transferred_out[0]->total_cost) && $transferred_out[0]->total_cost ? $transferred_out[0]->total_cost : 0;
            $result['profit_5'] = ($result['profit_1'] + $result['profit_2']) - ($result['profit_3']+$result['profit_4']);

            $profit_6_1 = isset($transferred_out[0]->total_tax) && $transferred_out[0]->total_tax ? $transferred_out[0]->total_tax : 0;
            $profit_6 = isset($sales[0]->total_sales_vat) && $sales[0]->total_sales_vat ? ($sales[0]->total_sales_vat) : 0;
            $result['profit_6'] = $profit_6 + $profit_6_1;
            $result['profit_7'] = isset($wastes[0]->wastes_amount) && $wastes[0]->wastes_amount ? $wastes[0]->wastes_amount : '0.0';
            $result['profit_8'] = isset($expense[0]->expense_amount) && $expense[0]->expense_amount ? $expense[0]->expense_amount : '0.0';
            $result['profit_8_1'] = isset($sales_refund[0]->total_total_refund) && $sales_refund[0]->total_total_refund ? $sales_refund[0]->total_total_refund : '0.0';
            $result['profit_9'] = ($result['profit_5']) - ($profit_6 + $profit_6_1 + $result['profit_7'] + $result['profit_8'] + $result['profit_8_1']);
            return $result;
        endif;
    }
    public function profitLossReportBackup($start_date, $end_date,$outlet_id='') {
        if ($start_date || $end_date):

            //purchase report
            $this->db->select('sum(paid) as total_purchase_amount');
            $this->db->from('tbl_purchase');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $purchase = $this->db->get()->result();
            //end purchase report
            //Sales report
            $this->db->select('sum(paid_amount) as total_sales_amount,sum(vat) as total_sales_vat');
            $this->db->from('tbl_sales');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $sales = $this->db->get()->result();
            //end Sales report
            //Waste report
            $this->db->select('sum(total_loss) as total_loss_amount');
            $this->db->from('tbl_wastes');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }

            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $waste = $this->db->get()->result();
            //end Waste report
            //Expense report
            $this->db->select('sum(amount) as expense_amount');
            $this->db->from('tbl_expenses');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $expense = $this->db->get()->result();
            //end expense report
            //Supplier payment report
            $this->db->select('sum(amount) as supplier_payment_amount');
            $this->db->from('tbl_supplier_payments');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $supplier_payment = $this->db->get()->result();
            //end expense report
            //Customer payment report
            $this->db->select('sum(amount) as customer_receive_amount');
            $this->db->from('tbl_customer_due_receives');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('only_date>=', $start_date);
                $this->db->where('only_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('only_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('only_date', $end_date);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $customer_receive = $this->db->get()->result();
            //end Supplier payment report
            $allTotal = 0;
            $allTotal = $purchase[0]->total_purchase_amount + $sales[0]->total_sales_amount + $waste[0]->total_loss_amount + $expense[0]->expense_amount + $supplier_payment[0]->supplier_payment_amount;

            $gross_profit = (($sales[0]->total_sales_amount + $customer_receive[0]->customer_receive_amount) - ($purchase[0]->total_purchase_amount + $waste[0]->total_loss_amount + $expense[0]->expense_amount + $supplier_payment[0]->supplier_payment_amount));

            $net_profit = (($sales[0]->total_sales_amount + $customer_receive[0]->customer_receive_amount) - ($purchase[0]->total_purchase_amount + $waste[0]->total_loss_amount + $expense[0]->expense_amount + $supplier_payment[0]->supplier_payment_amount) - $sales[0]->total_sales_vat);

            $result['total_purchase_amount'] = isset($purchase[0]->total_purchase_amount) && $purchase[0]->total_purchase_amount ? $purchase[0]->total_purchase_amount : '0.0';
            $result['total_sales_amount'] = isset($sales[0]->total_sales_amount) && $sales[0]->total_sales_amount ? $sales[0]->total_sales_amount : '0.0';
            $result['total_sales_vat'] = isset($sales[0]->total_sales_vat) && $sales[0]->total_sales_vat ? $sales[0]->total_sales_vat : '0.0';
            $result['total_loss_amount'] = isset($waste[0]->total_loss_amount) && $waste[0]->total_loss_amount ? $waste[0]->total_loss_amount : '0.0';
            $result['expense_amount'] = isset($expense[0]->expense_amount) && $expense[0]->expense_amount ? $expense[0]->expense_amount : '0.0';
            $result['supplier_payment_amount'] = isset($supplier_payment[0]->supplier_payment_amount) && $supplier_payment[0]->supplier_payment_amount ? $supplier_payment[0]->supplier_payment_amount : '0.0';
            $result['customer_receive_amount'] = isset($customer_receive[0]->customer_receive_amount) && $customer_receive[0]->customer_receive_amount ? $customer_receive[0]->customer_receive_amount : '0.0';

            $result['net_profit'] = isset($net_profit) && $net_profit ? $net_profit : '0.0';
            $result['gross_profit'] = isset($gross_profit) && $gross_profit ? $gross_profit : '0.0';
            $result['allTotal'] = isset($allTotal) && $allTotal ? $allTotal : '0.0';
            return $result;
        endif;
    }
    /**
     * supplier Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function supplierReport($startMonth = '', $endMonth = '', $supplier_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $supplier_id):

            $this->db->select('date,grand_total,paid,due,reference_no');
            $this->db->from('tbl_purchase');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            if ($supplier_id != '') {
                $this->db->where('supplier_id', $supplier_id);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * customer Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function customerReport($startMonth = '', $endMonth = '', $customer_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $customer_id):
            $this->db->select('sale_date,total_payable,paid_amount,due_amount,sale_no');
            $this->db->from('tbl_sales');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }

            if ($customer_id != '') {
                $this->db->where('customer_id', $customer_id);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    public function availableLoyaltyPointReport($customer_id = '') {
        $this->db->select('*');
        $this->db->from('tbl_customers');
        if ($customer_id != '') {
            $this->db->where('id', $customer_id);
        }
        $this->db->where('del_status', "Live");
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function usageLoyaltyPointReport($startMonth = '', $endMonth = '', $customer_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $customer_id):
        $this->db->select('tbl_sale_payments.*,tbl_customers.name, tbl_customers.phone,tbl_sales.sale_no,tbl_sales.date_time');
        $this->db->from('tbl_sale_payments');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_payments.sale_id', 'left');
        $this->db->join('tbl_customers', 'tbl_customers.id = tbl_sales.customer_id', 'left');

        if ($startMonth != '' && $endMonth != '') {
            $this->db->where('sale_date>=', $startMonth);
            $this->db->where('sale_date <=', $endMonth);
        }
        if ($startMonth != '' && $endMonth == '') {
            $this->db->where('sale_date', $startMonth);
        }
        if ($startMonth == '' && $endMonth != '') {
            $this->db->where('sale_date', $endMonth);
        }

        if ($customer_id != '') {
            $this->db->where('customer_id', $customer_id);
        }

        $this->db->where('tbl_sales.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_payments.payment_id', 5);
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->where('tbl_sale_payments.del_status', 'Live');
        $query_result = $this->db->get();
        $data = $query_result->result();
            return $data;
        endif;

    }
    /**
     * supplier Due Payment Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function supplierDuePaymentReport($startMonth = '', $endMonth = '', $supplier_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $supplier_id):
            $this->db->select('date,amount,note');
            $this->db->from('tbl_supplier_payments');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            if ($supplier_id != '') {
                $this->db->where('supplier_id', $supplier_id);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * customer Due Receive Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function customerDueReceiveReport($startMonth = '', $endMonth = '', $customer_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $customer_id):
            $this->db->select('date,amount,note');
            $this->db->from('tbl_customer_due_receives');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            if ($customer_id != '') {
                $this->db->where('customer_id', $customer_id);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * food Menu Sales
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function foodMenuSales($startMonth = '', $endMonth = '',$outlet_id='',$top_less='',$is_direct_food='') {
        if ($startMonth || $endMonth):
            $this->db->select('sum(qty) as totalQty,food_menu_id,menu_name,code,sale_date,tbl_sales.sale_no,tbl_sales.id as sale_id,tbl_food_menu_categories.category_name');
            $this->db->from('tbl_sales_details');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
            $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');
            $this->db->join('tbl_food_menu_categories', 'tbl_food_menu_categories.id = tbl_food_menus.category_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }
            if ($is_direct_food != '') {
                $this->db->where('tbl_food_menus.product_type', $is_direct_food);
            }
            $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
            $this->db->where('tbl_sales_details.del_status', 'Live');
            $this->db->where('tbl_sales.order_status', '3');
            $this->db->group_by('tbl_sales_details.food_menu_id');
            $this->db->order_by('totalQty', $top_less);
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    public function totalFoodSales($startMonth = '', $endMonth = '',$outlet_id='',$top_less='') {
        if ($startMonth || $endMonth):
            $this->db->select('sum(qty) as totalQty,sum(menu_price_without_discount) net_sales, food_menu_id,menu_name,code,sale_date');
            $this->db->from('tbl_sales_details');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
            $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }
            $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
            $this->db->where('tbl_sales_details.del_status', 'Live');
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->order_by('totalQty', $top_less);
            $this->db->group_by('tbl_sales_details.food_menu_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    public function totalFoodRefunds($startMonth = '', $endMonth = '',$outlet_id='',$top_less='') {
        if ($startMonth || $endMonth):
            $this->db->select('sum(total_refund) as total_refund');
            $this->db->from('tbl_sales');
            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('refund_date_time>=', $startMonth);
                $this->db->where('refund_date_time <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('refund_date_time', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('refund_date_time', $endMonth);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', 'Live');
            $this->db->where('order_status', 3);
            $query_result = $this->db->get();
            $result = $query_result->row();
            return $result;
        endif;
    }
    public function sub_total_foods($startMonth = '',$outlet_id='') {
        $this->db->select('sum(menu_price_without_discount) sub_total_foods,sale_date');
        $this->db->from('tbl_sales_details');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
        $this->db->where('tbl_sales_details.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function sub_total_modifiers($startMonth = '',$outlet_id='') {
        $this->db->select('sum(modifier_price) sub_total_foods,sale_date');
        $this->db->from('tbl_sales_details_modifiers');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details_modifiers.sales_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sales_details_modifiers.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function waiter_tips_foods($startMonth = '',$outlet_id='') {
        $this->db->select('sum(tips_amount_actual_charge) as tips_total');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $this->db->where('order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function taxes_foods($startMonth = '',$outlet_id='') {
        $this->db->select('sale_vat_objects');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $this->db->where('order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->result();

        $array_tax = array();
        foreach ($result as $value){
            foreach (json_decode($value->sale_vat_objects) as $tax){
                if((float)$tax->tax_field_amount){
                    $preview_vat_amount = isset($array_tax[$tax->tax_field_type]) && $array_tax[$tax->tax_field_type]?$array_tax[$tax->tax_field_type]:0;
                    $array_tax[$tax->tax_field_type] = $preview_vat_amount + ($tax->tax_field_amount);
                }
            }
        }
        return (Object)$array_tax;
    }
    public function delivery_charge_foods($startMonth = '',$outlet_id = '',$type = '') {
        $this->db->select('sum(delivery_charge_actual_charge) as delivery_charge');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('charge_type', $type);
        $this->db->where('del_status', 'Live');
        $this->db->where('order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function total_discount_amount_foods($startMonth = '',$outlet_id='') {
        $this->db->select('sum(total_discount_amount) as total_discount_amount_foods');
        $this->db->from('tbl_sales');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $this->db->where('order_status', 3);
        $query_result = $this->db->get();
        $result = $query_result->row();
        return $result;
    }
    public function totalDueReceived($startMonth = '',$outlet_id='') {
        $this->db->select("sum(amount) as total_amount");
        $this->db->from('tbl_customer_due_receives');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where('only_date', $startMonth);
        $this->db->where('del_status', 'Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function totalFoodIngSales($startMonth = '',$outlet_id='',$top_less='') {
        $this->db->select('sum(consumption) as totalQty,sum(consumption*consumption_unit_cost) cost_of_sales,tbl_ingredients.name as menu_name,tbl_units.unit_name as unit_name');
        $this->db->from('tbl_sale_consumptions_of_menus');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_consumptions_of_menus.sales_id', 'left');
        $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_menus.ingredient_id', 'left');
        $this->db->join('tbl_units', 'tbl_units.id = tbl_ingredients.unit_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sale_consumptions_of_menus.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_consumptions_of_menus.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->order_by('consumption', $top_less);
        $this->db->group_by('tbl_sale_consumptions_of_menus.ingredient_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllSalePaymentZReport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(amount) as total_amount,sum(usage_point) as usage_point,tbl_payment_methods.name,payment_id');
        $this->db->from('tbl_sale_payments');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_payments.sale_id', 'left');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_sale_payments.payment_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sale_payments.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_payments.currency_type', null);
        $this->db->where('tbl_sale_payments.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllPurchasePaymentZreport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(paid) as total_amount,tbl_payment_methods.name,payment_id');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_purchase.payment_id', 'left');
        $this->db->where('date', $startMonth);
        $this->db->where('tbl_purchase.outlet_id', $outlet_id);
        $this->db->where('tbl_purchase.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllExpensePaymentZreport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(amount) as total_amount,tbl_payment_methods.name,payment_id');
        $this->db->from('tbl_expenses');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_expenses.payment_id', 'left');
        $this->db->where('date', $startMonth);
        $this->db->where('tbl_expenses.outlet_id', $outlet_id);
        $this->db->where('tbl_expenses.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllSupplierPaymentZreport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(amount) as total_amount,tbl_payment_methods.name,payment_id');
        $this->db->from('tbl_supplier_payments');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_supplier_payments.payment_id', 'left');
        $this->db->where('date', $startMonth);
        $this->db->where('tbl_supplier_payments.outlet_id', $outlet_id);
        $this->db->where('tbl_supplier_payments.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllCustomerDueReceiveZreport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(amount) as total_amount,tbl_payment_methods.name,payment_id');
        $this->db->from('tbl_customer_due_receives');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_customer_due_receives.payment_id', 'left');
        $this->db->where('only_date', $startMonth);
        $this->db->where('tbl_customer_due_receives.outlet_id', $outlet_id);
        $this->db->where('tbl_customer_due_receives.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllOtherSalePaymentZReport($startMonth = '',$outlet_id='') {
        $this->db->select('sum(amount) as total_amount,multi_currency,payment_id');
        $this->db->from('tbl_sale_payments');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_payments.sale_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sale_payments.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_payments.currency_type', 1);
        $this->db->where('tbl_sale_payments.del_status', 'Live');
        $this->db->group_by('payment_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function totalModifierIngSales($startMonth = '',$outlet_id='',$top_less='') {
        $this->db->select('sum(consumption) as totalQty,sum(consumption*consumption_unit_cost) cost_of_sales,tbl_ingredients.name as menu_name,tbl_units.unit_name as unit_name');
        $this->db->from('tbl_sale_consumptions_of_modifiers_of_menus');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_consumptions_of_modifiers_of_menus.sales_id', 'left');
        $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_modifiers_of_menus.ingredient_id', 'left');
        $this->db->join('tbl_units', 'tbl_units.id = tbl_ingredients.unit_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sale_consumptions_of_modifiers_of_menus.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_consumptions_of_modifiers_of_menus.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->order_by('consumption', $top_less);
        $this->db->group_by('tbl_sale_consumptions_of_modifiers_of_menus.ingredient_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function totalFoodModifierSales($startMonth = '', $outlet_id='',$top_less='') {
        $this->db->select('sum(tbl_sales_details.qty) as totalQty,sum(modifier_price) net_sales,tbl_modifiers.name as menu_name');
        $this->db->from('tbl_sales_details_modifiers');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details_modifiers.sales_id', 'left');
        $this->db->join('tbl_sales_details', 'tbl_sales_details.id = tbl_sales_details_modifiers.sales_details_id', 'left');
        $this->db->join('tbl_modifiers', 'tbl_modifiers.id = tbl_sales_details_modifiers.modifier_id', 'left');
        $this->db->where('sale_date', $startMonth);
        $this->db->where('tbl_sales_details_modifiers.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->order_by('totalQty', $top_less);
        $this->db->group_by('tbl_sales_details_modifiers.modifier_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * food Menu Sales
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function foodMenuSaleByCategories($startMonth = '', $endMonth = '',$outlet_id='',$cat_id='') {
        $this->db->select('sum(qty) as totalQty,food_menu_id,menu_name,code,sale_date,tbl_food_menu_categories.category_name,tbl_food_menus.sale_price as menu_unit_price');
        $this->db->from('tbl_sales_details');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
        $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');
        $this->db->join('tbl_food_menu_categories', 'tbl_food_menu_categories.id = tbl_food_menus.category_id', 'left');

        if ($startMonth != '' && $endMonth != '') {
            $this->db->where('sale_date>=', $startMonth);
            $this->db->where('sale_date <=', $endMonth);
        }
        if ($startMonth != '' && $endMonth == '') {
            $this->db->where('sale_date', $startMonth);
        }
        if ($startMonth == '' && $endMonth != '') {
            $this->db->where('sale_date', $endMonth);
        }
        if ($cat_id!= '') {
            $this->db->where('tbl_food_menus.category_id', $cat_id);
        }
        $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
        $this->db->where('tbl_sales_details.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->order_by('totalQty', 'DESC');
        $this->db->group_by('tbl_sales_details.food_menu_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * food Menu Sales
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */

    /**
     * food Menu Sales
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function foodMenuSaleDetailsByCategories($startMonth = '', $endMonth = '',$outlet_id='',$cat_id='') {
        $this->db->select('sum(qty) as totalQty,food_menu_id,menu_name,code,sale_date,tbl_food_menu_categories.category_name,tbl_food_menus.sale_price');
        $this->db->from('tbl_sales_details');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
        $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');
        $this->db->join('tbl_food_menu_categories', 'tbl_food_menu_categories.id = tbl_food_menus.category_id', 'left');

        if ($startMonth != '' && $endMonth != '') {
            $this->db->where('sale_date>=', $startMonth);
            $this->db->where('sale_date <=', $endMonth);
        }
        if ($startMonth != '' && $endMonth == '') {
            $this->db->where('sale_date', $startMonth);
        }
        if ($startMonth == '' && $endMonth != '') {
            $this->db->where('sale_date', $endMonth);
        }
        if ($cat_id!= '') {
            $this->db->where('tbl_food_menus.category_id', $cat_id);
        }
        $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
        $this->db->where('tbl_sales_details.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->order_by('totalQty', 'DESC');
        $this->db->group_by('tbl_sales_details.food_menu_id');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }

    /**
     * consumption Menus
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function consumptionMenus($start_date = '', $end_date = '',$outlet_id='') {
        if ($start_date || $end_date):
            $this->db->select('sum(consumption*cost) as total_consumption, sum(consumption) as total_consumption_qty, ingredient_id,tbl_ingredients.name as ingredient_name,tbl_ingredients.code as ingredient_code,tbl_ingredients.purchase_price,tbl_ingredients.conversion_rate');
            $this->db->from('tbl_sales');
            $this->db->join('tbl_sale_consumptions_of_menus', 'tbl_sale_consumptions_of_menus.sales_id = tbl_sales.id', 'inner');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_menus.ingredient_id', 'inner');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.del_status', 'Live');
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->order_by('tbl_ingredients.name', 'ASC');
            $this->db->group_by('tbl_sale_consumptions_of_menus.ingredient_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * consumption Report
     * @access public
     * @return object
     * @param string
     * @param string
     */
    public function consumptionReport($start_date = '', $end_date = '') {
        if ($start_date || $end_date):
            $outlet_id = $this->session->userdata('outlet_id');
            $this->db->select('sum(consumption) as total_consumption, ingredient_id');
            $this->db->from('tbl_sale_consumptions_of_menus');
            $this->db->join('tbl_sale_consumptions', 'tbl_sale_consumptions.id = tbl_sale_consumptions_of_menus.sale_consumption_id', 'left');
            $this->db->join('tbl_sales', 'tbl_sale_consumptions.sale_id = tbl_sales.id', 'left');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_menus.ingredient_id', 'left');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->where('tbl_sale_consumptions_of_menus.outlet_id', $outlet_id);
            $this->db->where('tbl_sale_consumptions_of_menus.del_status', 'Live');
            $this->db->order_by('tbl_ingredients.name', 'ASC');
            $this->db->group_by('tbl_sale_consumptions_of_menus.ingredient_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * consumption Modifiers
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function consumptionModifiers($start_date = '', $end_date = '',$outlet_id='') {
        if ($start_date || $end_date):
            $this->db->select('sum(consumption*cost) as total_consumption, sum(consumption) as total_consumption_qty, ingredient_id,tbl_ingredients.name as ingredient_name,tbl_ingredients.code as ingredient_code,tbl_ingredients.purchase_price,tbl_ingredients.conversion_rate');
            $this->db->from('tbl_sales');
            $this->db->join('tbl_sale_consumptions_of_modifiers_of_menus', 'tbl_sale_consumptions_of_modifiers_of_menus.sales_id = tbl_sales.id', 'inner');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_sale_consumptions_of_modifiers_of_menus.ingredient_id', 'inner');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.del_status', 'Live');
            $this->db->order_by('tbl_ingredients.name', 'ASC');
            $this->db->where('tbl_sales.order_status', 3);
            $this->db->group_by('tbl_sale_consumptions_of_modifiers_of_menus.ingredient_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * detailed SaleReport
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function detailedSaleReport($startMonth = '', $endMonth = '', $user_id = '',$outlet_id='',$waiter_id='',$staff_meal='') {
        if ($startMonth || $endMonth || $user_id || $staff_meal !== ''):
            $this->db->select('tbl_sales.*,tbl_users.full_name,tbl_payment_methods.name');
            $this->db->from('tbl_sales');
            $this->db->join('tbl_users', 'tbl_users.id = tbl_sales.user_id', 'left');
            $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_sales.payment_method_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }

            if ($user_id != '') {
                $this->db->where('tbl_sales.user_id', $user_id);
            }
            if ($waiter_id != '') {
                $this->db->where('tbl_sales.waiter_id', $waiter_id);
            }
            /*staff meal filter, column only exists after the staff meal migration is run*/
            if ($staff_meal !== '' && $this->db->field_exists('is_staff_meal', 'tbl_sales')) {
                if ($staff_meal == 1) {
                    $this->db->where('tbl_sales.is_staff_meal', 1);
                } else {
                    $this->db->where('(tbl_sales.is_staff_meal IS NULL OR tbl_sales.is_staff_meal != 1)', NULL, FALSE);
                }
            }
            $this->db->where('order_status', '3');
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.del_status', 'Live');
            $this->db->order_by('sale_date', 'ASC');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * get Last Day In Date Month
     * @access public
     * @return object
     * @param string
     */
    public function getLastDayInDateMonth($month) {
        $returnValue = 0;
        if ($month == "02") {
            $returnValue = "28";
        } elseif ($month == "01" || $month == "03" || $month == "05" || $month == "07" || $month == "08" || $month == "10" || $month == "12") {
            $returnValue = "31";
        } else {
            $returnValue = "30";
        }
        return $returnValue;
    }
    /**
     * purchase Report By Month
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function purchaseReportByMonth($startMonth = '', $endMonth = '', $user_id = '') {
        if ($startMonth || $endMonth || $user_id):
            $outlet_id = $this->session->userdata('outlet_id');
            $this->db->select('date,sum(grand_total) as total_payable');
            $this->db->from('tbl_purchase');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }

            if ($user_id != '') {
                $this->db->where('user_id', $user_id);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->group_by('month(date)');
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * purchaseReportByDate
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function purchaseReportByDate($startDate = '', $endDate = '',$outlet_id='') {
        if ($startDate || $endDate):
            $this->db->select('*');
            $this->db->from('tbl_purchase');

            if ($startDate != '' && $endDate != '') {
                $this->db->where('date>=', $startDate);
                $this->db->where('date <=', $endDate);
            }
            if ($startDate != '' && $endDate == '') {
                $this->db->where('date', $startDate);
            }
            if ($startDate == '' && $endDate != '') {
                $this->db->where('date', $endDate);
            }
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where('del_status', "Live");
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    public function productAnalysisReportTotal($startMonth = '', $endMonth = '',$outlet_id='',$category_id='') {
        if ($startMonth || $endMonth):
            $this->db->select('sum(menu_price_with_discount) as totalSale,sum(qty) as total_qty,food_menu_id,menu_name,code,sale_date');
            $this->db->from('tbl_sales_details');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
            $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }
            $this->db->where('tbl_food_menus.category_id', $category_id);
            $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
            $this->db->where('tbl_sales_details.del_status', 'Live');
            $query_result = $this->db->get();
            $result = $query_result->row();
            return $result;
        endif;
    }
    public function productAnalysisReport($startMonth = '', $endMonth = '',$outlet_id='',$category_id='') {

        if ($startMonth || $endMonth):
            $this->db->select('sum(menu_price_with_discount) as totalSale,sum(qty) as total_qty,food_menu_id,menu_name,code,sale_date,total_cost,category_id');
            $this->db->from('tbl_sales_details');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
            $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('sale_date>=', $startMonth);
                $this->db->where('sale_date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('sale_date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('sale_date', $endMonth);
            }
            $this->db->where('tbl_food_menus.category_id', $category_id);
            $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
            $this->db->where('tbl_sales_details.del_status', 'Live');
            $this->db->order_by('total_qty', 'DESC');
            $this->db->group_by('tbl_sales_details.food_menu_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * purchase Report By Ingredient
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function purchaseReportByIngredient($startMonth = '', $endMonth = '', $ingredient_id = '') {
        if ($startMonth || $endMonth || $ingredient_id):
            $outlet_id = $this->session->userdata('outlet_id');
            $this->db->select('sum(quantity_amount) as totalQuantity_amount,ingredient_id,tbl_ingredients.name,tbl_ingredients.code,date');
            $this->db->from('tbl_purchase_ingredients');
            $this->db->join('tbl_purchase', 'tbl_purchase.id = tbl_purchase_ingredients.purchase_id', 'left');
            $this->db->join('tbl_ingredients', 'tbl_ingredients.id = tbl_purchase_ingredients.ingredient_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            if ($ingredient_id != '') {
                $this->db->where('ingredient_id', $ingredient_id);
            }
            $this->db->where('tbl_purchase.outlet_id', $outlet_id);
            $this->db->where('tbl_purchase_ingredients.del_status', 'Live');
            $this->db->order_by('date', 'ASC');
            $this->db->group_by('tbl_purchase_ingredients.ingredient_id');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * detailed Purchase Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function detailedPurchaseReport($startMonth = '', $endMonth = '', $user_id = '') {
        if ($startMonth || $endMonth || $user_id):
            $outlet_id = $this->session->userdata('outlet_id');
            $this->db->select('tbl_purchase.*,tbl_users.full_name');
            $this->db->from('tbl_purchase');
            $this->db->join('tbl_users', 'tbl_users.id = tbl_purchase.user_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            if ($user_id != '') {
                $this->db->where('user_id', $user_id);
            }
            $this->db->where('tbl_purchase.outlet_id', $outlet_id);
            $this->db->where('tbl_purchase.del_status', 'Live');
            $this->db->order_by('date', 'ASC');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * waste Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function wasteReport($startMonth = '', $endMonth = '', $user_id = '',$outlet_id='') {
        if ($startMonth || $endMonth || $user_id):
            $this->db->select('tbl_wastes.*,emp.full_name as EmployeedName');
            $this->db->from('tbl_wastes');
            $this->db->join('tbl_users as emp', 'emp.id = tbl_wastes.employee_id', 'left');

            if ($startMonth != '' && $endMonth != '') {
                $this->db->where('date>=', $startMonth);
                $this->db->where('date <=', $endMonth);
            }
            if ($startMonth != '' && $endMonth == '') {
                $this->db->where('date', $startMonth);
            }
            if ($startMonth == '' && $endMonth != '') {
                $this->db->where('date', $endMonth);
            }

            // if ($user_id != '') {
            //     $this->db->where('tbl_wastes.user_id', $user_id);
            // }
            if ($user_id != '') {
                $this->db->where('tbl_wastes.employee_id', $user_id);
            }
            $this->db->where('tbl_wastes.outlet_id', $outlet_id);
            $this->db->where('tbl_wastes.del_status', 'Live');
            $this->db->order_by('date', 'ASC');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;
        endif;
    }
    /**
     * supplier Due Report
     * @access public
     * @return object
     * @param int
     */
    public function supplierDueReport($outlet_id) {

        $this->db->select('sum(due) as totalDue,supplier_id,date,name');
        $this->db->from('tbl_purchase');
        $this->db->join('tbl_suppliers', 'tbl_suppliers.id = tbl_purchase.supplier_id', 'left');
        $this->db->order_by('totalDue desc');
        $this->db->where('tbl_purchase.outlet_id', $outlet_id);
        $this->db->where('tbl_purchase.del_status', 'Live');
        $this->db->group_by('tbl_purchase.supplier_id');
        return $this->db->get()->result();
    }
    /**
     * customer Due Report
     * @access public
     * @return object
     * @param int
     */
    public function customerDueReport($outlet_id) {
        $this->db->select('sum(due_amount) as totalDue,customer_id,sale_date,name');
        $this->db->from('tbl_sales');
        $this->db->join('tbl_customers', 'tbl_customers.id = tbl_sales.customer_id', 'left');
        $this->db->order_by('totalDue desc');
        $this->db->where('tbl_sales.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->group_by('tbl_sales.customer_id');
        $data =  $this->db->get()->result();
        return $data;
    }
    public function customerDueReportNew($outlet_id) {
        $this->db->select('tbl_sales.customer_id as customer_id,tbl_customers.name as name');
        $this->db->from('tbl_sales');
        $this->db->join('tbl_customers', 'tbl_customers.id = tbl_sales.customer_id', 'left');
        $this->db->where('tbl_sales.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', 3);
        $this->db->group_by('tbl_sales.customer_id');
        $data =  $this->db->get()->result();
        return $data;
    }
    /**
     * get Register Information
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     * @param int
     */
    public function getRegisterInformation($start_date,$end_date,$user_id='',$outlet_id=''){
        $this->db->select("tbl_register.*,tbl_counters.name as counter_name");
        $this->db->from('tbl_register');
        $this->db->join('tbl_counters', 'tbl_counters.id = tbl_register.counter_id', 'left');
        if($user_id!=''){
            $this->db->where("tbl_register.user_id", $user_id);
        }
        if($outlet_id!=''){
            $this->db->where("tbl_register.outlet_id", $outlet_id);
        }
        $this->db->where("DATE(tbl_register.opening_balance_date_time)>=", $start_date);
        $this->db->where("DATE(tbl_register.opening_balance_date_time)<=", $end_date);
        $this->db->order_by('tbl_register.id', 'DESC');
        return $this->db->get()->result();
    }
    /**
     * get Users
     * @access public
     * @return object
     * @param int
     */
    public function getUsers($outlet_id){
        $result = $this->db->query("SELECT * FROM tbl_users WHERE del_status='Live' AND FIND_IN_SET('$outlet_id' , outlets)")->result();
        return $result;
    }
    /**
     * expenseReport
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param int
     */
    public function expenseReport($startMonth='',$endMonth='',$category_id='',$outlet_id=''){
        if($startMonth || $endMonth || $category_id):
        $this->db->select('tbl_expenses.*,emp.full_name as EmployeedName,tbl_expense_items.name as categoryName');
        $this->db->from('tbl_expenses');
        $this->db->join('tbl_users as emp', 'emp.id = tbl_expenses.employee_id','left');
        $this->db->join('tbl_expense_items', 'tbl_expense_items.id = tbl_expenses.category_id','left');

        if($startMonth!='' && $endMonth!=''){
            $this->db->where('date>=', $startMonth);
            $this->db->where('date <=', $endMonth);
        }
        if($startMonth!='' && $endMonth==''){
            $this->db->where('date', $startMonth);
        }
        if($startMonth=='' && $endMonth!=''){
            $this->db->where('date', $endMonth);
        }

        if($category_id!=''){
            $this->db->where('tbl_expenses.category_id', $category_id);
        }
        $this->db->where('tbl_expenses.outlet_id',$outlet_id);
        $this->db->where('tbl_expenses.del_status','Live');
        $this->db->order_by('date', 'ASC');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
        endif;
    }
    /**
     * kitchen Performance Report
     * @access public
     * @return object
     * @param string
     * @param string
     * @param int
     */
    public function kitchenPerformanceReport($start_date='',$end_date='',$outlet_id=''){
        if($start_date || $end_date):

        $this->db->select('*');
        $this->db->from('tbl_sales');

        if($start_date!='' && $end_date!=''){
            $this->db->where('sale_date>=', $start_date);
            $this->db->where('sale_date <=', $end_date);
        }
        if($start_date!='' && $end_date==''){
            $this->db->where('sale_date', $start_date);
        }
        if($start_date=='' && $end_date!=''){
            $this->db->where('sale_date', $end_date);
        }

        $this->db->where('tbl_sales.outlet_id',$outlet_id);
        $this->db->where('tbl_sales.del_status','Live');
        $this->db->where('tbl_sales.order_status','3');
        $this->db->order_by('id', 'DESC');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
        endif;
    }
    /**
     * attendanceReport
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function attendanceReport($start_date = '', $end_date = '', $employee_id = '') {
        if ($start_date || $end_date || $employee_id):
            $this->db->select('tbl_attendance.*, emp.full_name as employee_name');
            $this->db->from('tbl_attendance');
            $this->db->join('tbl_users as emp', 'emp.id = tbl_attendance.employee_id', 'left');

            if ($start_date != '' && $end_date != '') {
                $this->db->where('date>=', $start_date);
                $this->db->where('date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('date', $end_date);
            }

            if ($employee_id != '') {
                $this->db->where('tbl_attendance.employee_id', $employee_id);
            }

            $this->db->where('tbl_attendance.del_status', 'Live');
            $this->db->order_by('date', 'ASC');
            $query_result = $this->db->get();
            $result = $query_result->result();
            return $result;

        endif;
    }

    public function auditLogReport($startDate = '', $endDate = '',$user_id='',$event_title='',$outlet_id='') {
        $this->db->select('tbl_audit_logs.*,tbl_outlets.outlet_name');
        $this->db->from('tbl_audit_logs');
        $this->db->join('tbl_outlets', 'tbl_outlets.id = tbl_audit_logs.outlet_id', 'left');
        if ($startDate != '' && $endDate != '') {
            $this->db->where('date>=', $startDate);
            $this->db->where('date<=', $endDate);
        }
        if ($startDate != '' && $endDate == '') {
            $this->db->where('date', $startDate);
        }
        if ($startDate == '' && $endDate != '') {
            $this->db->where('date', $endDate);
        }
        if($user_id!=''){
            $this->db->where('user_id', $user_id);
        }
        if($event_title!=''){
            $this->db->where('event_title',$event_title);
        }
        if($outlet_id!=''){
            $this->db->where('outlet_id',$outlet_id);
        }
        $this->db->where('tbl_audit_logs.del_status', "Live");
        $this->db->order_by("tbl_audit_logs.id", 'asc');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getAllSaleByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(amount) as total_amount, sum(usage_point) as total_usage_point");
        $this->db->from('tbl_sale_payments');
        $this->db->where("payment_id", $payment_id);
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("Date(date_time)", $date);
        $this->db->where("currency_type", null);
        $data =  $this->db->get()->row();
        if($payment_id!=5){
            return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
        }else{
            return (isset($data->total_usage_point) && $data->total_usage_point?$data->total_usage_point:0);
        }
    }
    public function getAllSaleReturnByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(total_refund) as total_amount");
        $this->db->from('tbl_sales');
        $this->db->where("refund_payment_id", $payment_id);
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("refund_date_time", $date);
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function getAllSalePayment($date,$payment_id)
    {
        $user_id = $this->session->userdata('user_id');
        $outlet_id = $this->session->userdata('outlet_id');
        $this->db->select("tbl_sales.paid_amount,tbl_sales.payment_method_id,tbl_sales.user_id,tbl_sales.outlet_id,tbl_payment_methods.name as payment_name");
        $this->db->from('tbl_sales');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_sales.payment_method_id', 'left');
        $this->db->where("tbl_sales.user_id", $user_id);
        $this->db->where("tbl_sales.outlet_id", $outlet_id);
        $this->db->where("tbl_sales.payment_method_id", $payment_id);
        $this->db->where("tbl_sales.date_time>=", $date);
        $this->db->where("tbl_sales.date_time<=", date('Y-m-d H:i:s'));
        $this->db->where('tbl_sales.order_status', 3);
        return $this->db->get()->result();
    }
    public function getAllPurchaseByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(paid) as total_amount");
        $this->db->from('tbl_purchase');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where("Date(added_date_time)", $date);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    /**
     * Mess module money of one payment method on a day (only_date), refunds negative
     * @access public
     * @return float
     */
    public function getAllMessByPayment($date,$payment_id,$outlet_id='')
    {
        if(!$this->db->table_exists('tbl_mess_payments')){
            return 0;
        }
        $this->db->select("SUM(CASE WHEN type='refund' THEN -amount ELSE amount END) as total_amount");
        $this->db->from('tbl_mess_payments');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where_in("type", array('payment','refund'));
        $this->db->where("only_date", $date);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    /**
     * Mess module money of a day, all methods
     * @access public
     * @return float
     */
    public function totalMessCollection($startMonth = '',$outlet_id='') {
        if(!$this->db->table_exists('tbl_mess_payments')){
            return 0;
        }
        $this->db->select("SUM(CASE WHEN type='refund' THEN -amount ELSE amount END) as total_amount");
        $this->db->from('tbl_mess_payments');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where_in("type", array('payment','refund'));
        $this->db->where('only_date', $startMonth);
        $this->db->where('del_status', 'Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    /**
     * Mess module money of a day grouped by payment method
     * @access public
     * @return array
     */
    public function getAllMessPaymentZreport($startMonth = '',$outlet_id='') {
        if(!$this->db->table_exists('tbl_mess_payments')){
            return array();
        }
        $this->db->select("SUM(CASE WHEN tbl_mess_payments.type='refund' THEN -tbl_mess_payments.amount ELSE tbl_mess_payments.amount END) as total_amount,tbl_payment_methods.name,payment_id");
        $this->db->from('tbl_mess_payments');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_mess_payments.payment_id', 'left');
        $this->db->where('only_date', $startMonth);
        $this->db->where('tbl_mess_payments.outlet_id', $outlet_id);
        $this->db->where_in('tbl_mess_payments.type', array('payment','refund'));
        $this->db->where('tbl_mess_payments.del_status', 'Live');
        $this->db->group_by('payment_id');
        return $this->db->get()->result();
    }
    public function getAllDueReceiveByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(amount) as total_amount");
        $this->db->from('tbl_customer_due_receives');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where("only_date", $date);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function getAllDuePaymentByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(amount) as total_amount");
        $this->db->from('tbl_supplier_payments');
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where("date", $date);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function getAllExpenseByPayment($date,$payment_id,$outlet_id='')
    {
        $this->db->select("sum(amount) as total_amount");
        $this->db->from('tbl_expenses');
        $this->db->where("date", $date);
        $this->db->where("outlet_id", $outlet_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function getAllSaleByPaymentMultiCurrency($date,$payment_id)
    {
        $user_id = $this->session->userdata('user_id');

        $this->db->select("sum(amount) as total_amount");
        $this->db->from('tbl_sale_payments');
        $this->db->where("user_id", $user_id);
        $this->db->where("payment_id", $payment_id);
        $this->db->where("date_time	>=", $date);
        $this->db->where("date_time	<=", date('Y-m-d H:i:s'));
        $this->db->where("currency_type", 1);
        $this->db->where('del_status','Live');
        $data =  $this->db->get()->row();
        return (isset($data->total_amount) && $data->total_amount?$data->total_amount:0);
    }
    public function allSaleByDateTime($date)
    {
        $user_id = $this->session->userdata('user_id');
        $outlet_id = $this->session->userdata('outlet_id');
        $this->db->select("tbl_sales.paid_amount,tbl_sales.payment_method_id,tbl_sales.user_id,tbl_sales.outlet_id");
        $this->db->from('tbl_sales');
        $this->db->where("tbl_sales.user_id", $user_id);
        $this->db->where("tbl_sales.outlet_id", $outlet_id);
        $this->db->where("tbl_sales.date_time>=", $date);
        $this->db->where("tbl_sales.date_time<=", date('Y-m-d H:i:s'));
        $this->db->where('tbl_sales.order_status', 3);
        return $this->db->get()->result();
    }
    public function getCustomerOpeningDueByDate($customer_id,$date,$outlet_id='') {
        $customer_due = $this->db->query("SELECT SUM(due_amount) as due FROM tbl_sales WHERE customer_id=$customer_id and outlet_id=$outlet_id and del_status='Live' and sale_date<'$date' ")->row();
        $customer_payment = $this->db->query("SELECT SUM(amount) as amount FROM tbl_customer_due_receives WHERE customer_id=$customer_id and outlet_id=$outlet_id and del_status='Live' and only_date<'$date'")->row();
        $remaining_due = $customer_due->due - $customer_payment->amount;
        return $remaining_due;
    }
    public function getCustomerGrantTotalByDate($customer_id,$date,$outlet_id='') {
        $purchase_info= $this->db->query("SELECT SUM(total_payable) as total,SUM(paid_amount) as paid,SUM(due_amount) as due FROM tbl_sales WHERE customer_id=$customer_id and outlet_id=$outlet_id and del_status='Live' and sale_date='$date' ")->row();
        return $purchase_info;
    }
    public function getCustomerDuePaymentByDate($customer_id,$date,$outlet_id='') {
        $supplier_payment = $this->db->query("SELECT SUM(amount) as amount FROM tbl_customer_due_receives WHERE customer_id=$customer_id and outlet_id=$outlet_id and del_status='Live' and only_date='$date'")->row();
        $due_payment =$supplier_payment->amount;
        return $due_payment;
    }

    public function getSupplierDuePaymentByDate($supplier_id,$date,$outlet_id='') {
        $supplier_payment = $this->db->query("SELECT SUM(amount) as amount FROM tbl_supplier_payments WHERE supplier_id=$supplier_id and outlet_id=$outlet_id and del_status='Live' and date='$date'")->row();
        $due_payment =$supplier_payment->amount;
        return $due_payment;
    }
    public function getSupplierGrantTotalByDate($supplier_id,$date,$outlet_id='') {
        $purchase_info= $this->db->query("SELECT SUM(grand_total) as total,SUM(paid) as paid,SUM(due) as due FROM tbl_purchase WHERE supplier_id=$supplier_id and outlet_id=$outlet_id and del_status='Live' and date='$date' ")->row();
        return $purchase_info;
    }
    public function getSupplierOpeningDueByDate($supplier_id,$date,$outlet_id='') {
        $supplier_due = $this->db->query("SELECT SUM(due) as due FROM tbl_purchase WHERE supplier_id=$supplier_id and outlet_id=$outlet_id and del_status='Live' and date<'$date' ")->row();
        $supplier_payment = $this->db->query("SELECT SUM(amount) as amount FROM tbl_supplier_payments WHERE supplier_id=$supplier_id and outlet_id=$outlet_id and del_status='Live' and date<'$date'")->row();
        $remaining_due = $supplier_due->due - $supplier_payment->amount;
        return $remaining_due;
    }
    public function transferReport($startMonth = '', $endMonth = '',$from_outlet_id='',$to_outlet_id='') {
        $this->db->select('*');
        $this->db->from('tbl_transfer');
        if ($startMonth != '' && $endMonth != '') {
            $this->db->where('received_date>=', $startMonth);
            $this->db->where('received_date <=', $endMonth);
        }
        if ($startMonth != '' && $endMonth == '') {
            $this->db->where('received_date', $startMonth);
        }
        if ($startMonth == '' && $endMonth != '') {
            $this->db->where('received_date', $endMonth);
        }
        if ($from_outlet_id!= '') {
            $this->db->where('from_outlet_id', $from_outlet_id);
        }
        if ($to_outlet_id!= '') {
            $this->db->where('to_outlet_id', $to_outlet_id);
        }
        $this->db->where('status', '1');
        $this->db->where('del_status', 'Live');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    public function getTotalTransaction($start_date, $end_date,$outlet_id='') {
        if ($start_date || $end_date):
            $this->db->select('count(id) as total_transaction');
            $this->db->from('tbl_sales');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $sales = $this->db->get()->row();
            return $sales;
        endif;
    }
    public function getTotalCustomer($start_date, $end_date,$outlet_id='') {
        if ($start_date || $end_date):
            //end purchase report
            //Sales report
            $this->db->select('count(id) as total_customer');
            $this->db->from('tbl_sales');
            if ($start_date != '' && $end_date != '') {
                $this->db->where('sale_date>=', $start_date);
                $this->db->where('sale_date <=', $end_date);
            }
            if ($start_date != '' && $end_date == '') {
                $this->db->where('sale_date', $start_date);
            }
            if ($start_date == '' && $end_date != '') {
                $this->db->where('sale_date', $end_date);
            }
            $this->db->where('order_status', 3);
            $this->db->where('outlet_id', $outlet_id);
            $this->db->where("del_status", 'Live');
            $sales = $this->db->get()->row();
            return $sales;
        endif;
    }
    public function productionReport($startMonth = '', $endMonth = '') {
        $outlet_id = $this->session->userdata('outlet_id');
        $this->db->select('*');
        $this->db->from('tbl_production');
        if ($startMonth != '' && $endMonth != '') {
            $this->db->where('date>=', $startMonth);
            $this->db->where('date <=', $endMonth);
        }
        if ($startMonth != '' && $endMonth == '') {
            $this->db->where('date', $startMonth);
        }
        if ($startMonth == '' && $endMonth != '') {
            $this->db->where('date', $endMonth);
        }
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('status', '1');
        $this->db->where('del_status', 'Live');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * item wise cost report
     * one row per sold food menu: qty sold, unit cost and cost x qty.
     * unit cost is tbl_food_menus.total_cost - the recipe/purchase cost kept on the
     * menu itself (same source productAnalysisReport uses), so it is the cost as of
     * today, not the cost at the moment of the sale.
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param string
     * @param string
     */
    public function itemWiseCostReport($startDate = '', $endDate = '', $outlet_id = '', $category_id = '', $product_type = '') {
        $this->db->select('tbl_sales_details.food_menu_id,tbl_sales_details.menu_name,tbl_food_menus.code,tbl_food_menus.total_cost as unit_cost,tbl_food_menus.sale_price as unit_sale_price,tbl_food_menu_categories.category_name,sum(tbl_sales_details.qty) as total_qty,sum(tbl_sales_details.menu_price_with_discount) as total_sale');
        $this->db->from('tbl_sales_details');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
        $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');
        $this->db->join('tbl_food_menu_categories', 'tbl_food_menu_categories.id = tbl_food_menus.category_id', 'left');

        if ($startDate != '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date>=', $startDate);
            $this->db->where('tbl_sales.sale_date <=', $endDate);
        }
        if ($startDate != '' && $endDate == '') {
            $this->db->where('tbl_sales.sale_date', $startDate);
        }
        if ($startDate == '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date', $endDate);
        }
        if ($category_id != '') {
            $this->db->where('tbl_food_menus.category_id', $category_id);
        }
        if ($product_type != '') {
            $this->db->where('tbl_food_menus.product_type', $product_type);
        }
        $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
        $this->db->where('tbl_sales_details.del_status', 'Live');
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', '3');
        $this->db->group_by('tbl_sales_details.food_menu_id');
        $this->db->order_by('total_qty', 'DESC');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * customer activity report
     * one row per customer: how many bills, how much money, first/last visit.
     * item level detail comes from customerActivityItems().
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param string
     */
    public function customerActivityReport($startDate = '', $endDate = '', $outlet_id = '', $customer_id = '') {
        $this->db->select('tbl_sales.customer_id,tbl_customers.name as customer_name,tbl_customers.phone,tbl_customers.email,count(tbl_sales.id) as total_visits,sum(tbl_sales.total_payable) as total_purchase,sum(tbl_sales.paid_amount) as total_paid,sum(tbl_sales.due_amount) as total_due,sum(tbl_sales.total_discount_amount) as total_discount,count(distinct tbl_sales.sale_date) as total_days,min(tbl_sales.sale_date) as first_visit,max(tbl_sales.sale_date) as last_visit');
        $this->db->from('tbl_sales');
        $this->db->join('tbl_customers', 'tbl_customers.id = tbl_sales.customer_id', 'left');

        if ($startDate != '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date>=', $startDate);
            $this->db->where('tbl_sales.sale_date <=', $endDate);
        }
        if ($startDate != '' && $endDate == '') {
            $this->db->where('tbl_sales.sale_date', $startDate);
        }
        if ($startDate == '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date', $endDate);
        }
        if ($customer_id != '') {
            $this->db->where('tbl_sales.customer_id', $customer_id);
        }
        $this->db->where('tbl_sales.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', '3');
        $this->db->group_by('tbl_sales.customer_id');
        $this->db->order_by('total_purchase', 'DESC');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * what each customer bought, one row per customer + food menu.
     * ordered qty desc so the first row of a customer is the item they buy most.
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     * @param string
     */
    public function customerActivityItems($startDate = '', $endDate = '', $outlet_id = '', $customer_id = '') {
        $this->db->select('tbl_sales.customer_id,tbl_sales_details.food_menu_id,tbl_sales_details.menu_name,tbl_food_menu_categories.category_name,sum(tbl_sales_details.qty) as total_qty,sum(tbl_sales_details.menu_price_with_discount) as total_amount,count(distinct tbl_sales.id) as order_count,max(tbl_sales.sale_date) as last_ordered');
        $this->db->from('tbl_sales_details');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id', 'left');
        $this->db->join('tbl_food_menus', 'tbl_food_menus.id = tbl_sales_details.food_menu_id', 'left');
        $this->db->join('tbl_food_menu_categories', 'tbl_food_menu_categories.id = tbl_food_menus.category_id', 'left');

        if ($startDate != '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date>=', $startDate);
            $this->db->where('tbl_sales.sale_date <=', $endDate);
        }
        if ($startDate != '' && $endDate == '') {
            $this->db->where('tbl_sales.sale_date', $startDate);
        }
        if ($startDate == '' && $endDate != '') {
            $this->db->where('tbl_sales.sale_date', $endDate);
        }
        if ($customer_id != '') {
            $this->db->where('tbl_sales.customer_id', $customer_id);
        }
        $this->db->where('tbl_sales_details.outlet_id', $outlet_id);
        $this->db->where('tbl_sales_details.del_status', 'Live');
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sales.order_status', '3');
        $this->db->group_by(array('tbl_sales.customer_id', 'tbl_sales_details.food_menu_id'));
        $this->db->order_by('total_qty', 'DESC');
        $query_result = $this->db->get();
        $result = $query_result->result();
        return $result;
    }
    /**
     * date filter used by sales summary report (same rule as Detailed Sale Report)
     * @access private
     * @return void
     * @param string
     * @param string
     * @param string
     */
    private function salesSummaryDateWhere($start_date, $end_date, $column) {
        if ($start_date != '' && $end_date != '') {
            $this->db->where($column.' >=', $start_date);
            $this->db->where($column.' <=', $end_date);
        }
        if ($start_date != '' && $end_date == '') {
            $this->db->where($column, $start_date);
        }
        if ($start_date == '' && $end_date != '') {
            $this->db->where($column, $end_date);
        }
    }
    /**
     * group of a payment method for sales summary report
     * @access public
     * @return string card|cash|online|other
     * @param string
     */
    public function salesSummaryPaymentGroup($payment_name) {
        $name = strtolower(trim($payment_name));
        if (strpos($name, 'card') !== false) {
            return 'card';
        }
        if (strpos($name, 'cash') !== false) {
            return 'cash';
        }
        foreach (array('talabat', 'keeta', 'noon', 'smiles', 'deliveroo') as $platform) {
            if (strpos($name, $platform) !== false) {
                return 'online';
            }
        }
        return 'other';
    }
    private $ssr_company = null;
    /**
     * amount for sales summary report, shown exactly as saved (no round off), never less decimals than company precision
     * @access public
     * @return string
     * @param float
     */
    public function salesSummaryAmount($amount) {
        if ($this->ssr_company === null) {
            $this->ssr_company = getCompanyInfo();
        }
        $company = $this->ssr_company;
        $precision = isset($company->precision) && is_numeric($company->precision) ? (int)$company->precision : 2;
        $decimals_separator = isset($company->decimals_separator) && $company->decimals_separator ? $company->decimals_separator : '.';
        $thousands_separator = isset($company->thousands_separator) && $company->thousands_separator ? $company->thousands_separator : '';
        //sale amounts are saved with up to 4 decimals, so 4 decimals keep every saved digit
        $text = number_format(abs((float)$amount), 4, '.', '');
        $parts = explode('.', $text);
        $fraction = rtrim($parts[1], '0');
        if (strlen($fraction) < $precision) {
            $fraction = str_pad($fraction, $precision, '0');
        }
        $result = number_format((float)$parts[0], 0, '', $thousands_separator);
        if ($fraction !== '') {
            $result .= $decimals_separator.$fraction;
        }
        $is_negative = (float)$amount < 0 && (float)$text != 0;
        return ($is_negative ? '-' : '').$result;
    }
    /**
     * sales summary report: payment method summary, total checks, issues and daily reconciliation
     * every amount is used exactly as saved, nothing is rounded off
     * @access public
     * @return array|false
     * @param string
     * @param string
     * @param int
     * @param boolean total items of every invoice (only Invoice Detail Report needs it)
     */
    public function salesSummaryReport($start_date = '', $end_date = '', $outlet_id = '', $with_items = false) {
        if (!$start_date && !$end_date) {
            return false;
        }
        //only to ignore binary float noise of php sums, far below any saved decimal
        $epsilon = 0.000001;
        $is_zero = function ($value) use ($epsilon) {
            return abs($value) < $epsilon;
        };
        //exact sum of a float column in database, same digits php gets for every row
        $exact_sum = function ($column) {
            return "SUM(CAST(CAST(".$column." AS CHAR) AS DECIMAL(20,6)))";
        };
        //same rule as POS: tax is added on top of the bill only for tax_type 1, otherwise it is already inside the item price
        $company = getCompanyInfo();
        $tax_on_top = $company && $company->tax_type == 1;

        //invoices, same filter as Detailed Sale Report
        $this->db->select('id,sale_no,sale_date,sub_total,sub_total_discount_amount,total_discount_amount,vat,charge_type,delivery_charge_actual_charge,tips_amount_actual_charge,rounding_amount_hidden,total_payable');
        $this->db->from('tbl_sales');
        $this->salesSummaryDateWhere($start_date, $end_date, 'sale_date');
        $this->db->where('order_status', '3');
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $this->db->order_by('sale_date', 'ASC');
        $this->db->order_by('id', 'ASC');
        $invoices = $this->db->get()->result();

        //payments of those invoices
        $this->db->select('tbl_sale_payments.sale_id,tbl_sale_payments.payment_id,tbl_sale_payments.amount,tbl_sale_payments.usage_point,tbl_payment_methods.name as payment_name');
        $this->db->from('tbl_sale_payments');
        $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sale_payments.sale_id');
        $this->db->join('tbl_payment_methods', 'tbl_payment_methods.id = tbl_sale_payments.payment_id', 'left');
        $this->salesSummaryDateWhere($start_date, $end_date, 'tbl_sales.sale_date');
        $this->db->where('tbl_sales.order_status', '3');
        $this->db->where('tbl_sales.outlet_id', $outlet_id);
        $this->db->where('tbl_sales.del_status', 'Live');
        $this->db->where('tbl_sale_payments.outlet_id', $outlet_id);
        $this->db->where('tbl_sale_payments.del_status', 'Live');
        $this->db->order_by('tbl_sale_payments.id', 'ASC');
        $payment_rows = $this->db->get()->result();

        //total items of those invoices
        $items_by_sale = array();
        if ($with_items) {
            $this->db->select('tbl_sales_details.sales_id,SUM(tbl_sales_details.qty) as total_qty', FALSE);
            $this->db->from('tbl_sales_details');
            $this->db->join('tbl_sales', 'tbl_sales.id = tbl_sales_details.sales_id');
            $this->salesSummaryDateWhere($start_date, $end_date, 'tbl_sales.sale_date');
            $this->db->where('tbl_sales.order_status', '3');
            $this->db->where('tbl_sales.outlet_id', $outlet_id);
            $this->db->where('tbl_sales.del_status', 'Live');
            $this->db->where('tbl_sales_details.del_status', 'Live');
            $this->db->group_by('tbl_sales_details.sales_id');
            foreach ($this->db->get()->result() as $row) {
                $items_by_sale[$row->sales_id] = (int)$row->total_qty;
            }
        }

        //totals straight from database, used in total checks
        $this->db->select('COUNT(id) as total_invoices, '.$exact_sum('sub_total').' as sub_total, '.$exact_sum('total_discount_amount').' as total_discount, '.$exact_sum('vat').' as total_vat, '.$exact_sum('total_payable').' as total_payable', FALSE);
        $this->db->from('tbl_sales');
        $this->salesSummaryDateWhere($start_date, $end_date, 'sale_date');
        $this->db->where('order_status', '3');
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $db_totals = $this->db->get()->row();

        //Daily Sale Report figures (same filter as Daily Sale Report, exact sum)
        $this->db->select('sale_date, '.$exact_sum('total_payable').' as total_payable', FALSE);
        $this->db->from('tbl_sales');
        $this->salesSummaryDateWhere($start_date, $end_date, 'sale_date');
        $this->db->where('order_status', '3');
        $this->db->where('outlet_id', $outlet_id);
        $this->db->where('del_status', 'Live');
        $this->db->group_by('sale_date');
        $daily_report_rows = $this->db->get()->result();
        $daily_report = array();
        $daily_report_total = 0;
        foreach ($daily_report_rows as $row) {
            $daily_report[$row->sale_date] = (float)$row->total_payable;
            $daily_report_total += (float)$row->total_payable;
        }

        $payments_by_sale = array();
        foreach ($payment_rows as $row) {
            $payments_by_sale[$row->sale_id][] = $row;
        }

        //all days of the selected range
        $days = array();
        $first_day = $start_date != '' ? $start_date : $end_date;
        $last_day = ($start_date != '' && $end_date != '') ? $end_date : $first_day;
        $day_time = strtotime($first_day);
        $last_time = strtotime($last_day);
        while ($day_time && $last_time && $day_time <= $last_time) {
            $days[date('Y-m-d', $day_time)] = array('methods' => array(), 'collected' => 0, 'g_total' => 0, 'unrecorded' => 0, 'invoices' => 0, 'problem_invoices' => 0);
            $day_time = strtotime('+1 day', $day_time);
        }

        $methods = array();
        $issues = array();
        $invoice_rows = array();
        $sale_no_count = array();
        $total = array('g_total' => 0, 'collected' => 0, 'unrecorded' => 0, 'sub_total' => 0, 'discount' => 0, 'vat' => 0, 'formula' => 0, 'invoices' => 0, 'unrecorded_invoices' => 0, 'formula_mismatch' => 0);

        foreach ($invoices as $invoice) {
            $g_total = (float)$invoice->total_payable;
            $paid = 0;
            $paid_by_method = array();
            $payment_parts = array();
            if (isset($payments_by_sale[$invoice->id])) {
                foreach ($payments_by_sale[$invoice->id] as $payment) {
                    $amount = (float)$payment->amount;
                    $paid += $amount;
                    $paid_by_method[$payment->payment_id] = (isset($paid_by_method[$payment->payment_id]) ? $paid_by_method[$payment->payment_id] : 0) + $amount;
                    $payment_name = $payment->payment_name != '' ? $payment->payment_name : lang('ssr_unknown_payment');
                    if (!isset($methods[$payment->payment_id])) {
                        $methods[$payment->payment_id] = array('name' => $payment_name, 'group' => $this->salesSummaryPaymentGroup($payment_name), 'amount' => 0, 'invoices' => 0);
                    }
                    $txt_point = $payment->payment_id == 5 ? " (Usage Point:".$payment->usage_point.")" : '';
                    $payment_parts[] = array($payment_name.$txt_point, $amount);
                }
            }
            foreach ($paid_by_method as $payment_id => $amount) {
                $methods[$payment_id]['amount'] += $amount;
                if ($amount > $epsilon) {
                    $methods[$payment_id]['invoices']++;
                }
            }

            //G.Total = Subtotal (after item discount) - Subtotal Discount + Charge + Tips + Rounding, + Tax only when tax is on top
            $formula = (float)$invoice->sub_total - (float)$invoice->sub_total_discount_amount + (float)$invoice->delivery_charge_actual_charge + (float)$invoice->tips_amount_actual_charge + ($tax_on_top ? (float)$invoice->vat : 0) + (float)$invoice->rounding_amount_hidden;
            $formula_diff = $formula - $g_total;
            $gap = $g_total - $paid;
            $has_gap = !$is_zero($gap);
            $has_formula_diff = !$is_zero($formula_diff);

            $total['g_total'] += $g_total;
            $total['collected'] += $paid;
            $total['unrecorded'] += $gap;
            $total['sub_total'] += (float)$invoice->sub_total;
            $total['discount'] += (float)$invoice->total_discount_amount;
            $total['vat'] += (float)$invoice->vat;
            $total['formula'] += $formula;
            $total['invoices']++;
            if ($has_gap) {
                $total['unrecorded_invoices']++;
            }
            if ($has_formula_diff) {
                $total['formula_mismatch']++;
            }
            $sale_no_count[$invoice->sale_no] = (isset($sale_no_count[$invoice->sale_no]) ? $sale_no_count[$invoice->sale_no] : 0) + 1;

            if (!isset($days[$invoice->sale_date])) {
                $days[$invoice->sale_date] = array('methods' => array(), 'collected' => 0, 'g_total' => 0, 'unrecorded' => 0, 'invoices' => 0, 'problem_invoices' => 0);
            }
            foreach ($paid_by_method as $payment_id => $amount) {
                $days[$invoice->sale_date]['methods'][$payment_id] = (isset($days[$invoice->sale_date]['methods'][$payment_id]) ? $days[$invoice->sale_date]['methods'][$payment_id] : 0) + $amount;
            }
            $days[$invoice->sale_date]['collected'] += $paid;
            $days[$invoice->sale_date]['g_total'] += $g_total;
            $days[$invoice->sale_date]['unrecorded'] += $gap;
            $days[$invoice->sale_date]['invoices']++;
            if ($has_gap) {
                $days[$invoice->sale_date]['problem_invoices']++;
            }

            //issues of this invoice
            $issue_text = array();
            $action_text = array();
            if ($is_zero($g_total) && $is_zero($paid)) {
                $issue_text[] = lang('ssr_issue_zero_bill');
                $action_text[] = lang('ssr_action_zero_bill');
            } elseif (empty($paid_by_method)) {
                $issue_text[] = lang('ssr_issue_no_payment');
                $action_text[] = lang('ssr_action_no_payment');
            } elseif ($has_gap && $gap > 0) {
                $issue_text[] = lang('ssr_issue_short_paid')." ".$this->salesSummaryAmount($gap);
                $action_text[] = lang('ssr_action_short_paid');
            } elseif ($has_gap) {
                $issue_text[] = lang('ssr_issue_over_paid')." ".$this->salesSummaryAmount(abs($gap));
                $action_text[] = lang('ssr_action_over_paid');
            }
            if ($has_formula_diff) {
                $issue_text[] = lang('ssr_issue_formula')." ".$this->salesSummaryAmount($formula_diff);
                $action_text[] = lang('ssr_action_formula');
            }
            $payment_text = array();
            foreach ($payment_parts as $part) {
                $payment_text[] = $part[0].":".$this->salesSummaryAmount($part[1]);
            }
            $payment_text = $payment_text ? implode(" - ", $payment_text) : '('.lang('ssr_blank').')';
            if ($issue_text) {
                $issues[] = (object)array(
                    'sale_date' => $invoice->sale_date,
                    'sale_no' => $invoice->sale_no,
                    'g_total' => $g_total,
                    'paid' => $paid,
                    'gap' => $gap,
                    'payment_method' => $payment_text,
                    'issue' => implode("; ", $issue_text),
                    'action' => implode("; ", $action_text),
                );
            }

            //one row of Invoice Detail Report, charge shown under delivery or service as per its charge type
            $charge = (float)$invoice->delivery_charge_actual_charge;
            $invoice_rows[] = (object)array(
                'sale_date' => $invoice->sale_date,
                'sale_no' => $invoice->sale_no,
                'items' => isset($items_by_sale[$invoice->id]) ? $items_by_sale[$invoice->id] : 0,
                'sub_total' => (float)$invoice->sub_total,
                'delivery' => $invoice->charge_type == 'service' ? 0 : $charge,
                'service' => $invoice->charge_type == 'service' ? $charge : 0,
                'tips' => (float)$invoice->tips_amount_actual_charge,
                'discount' => (float)$invoice->total_discount_amount,
                'vat' => (float)$invoice->vat,
                'g_total' => $g_total,
                'payment_method' => $payment_text,
                'paid_by_method' => $paid_by_method,
                'paid' => $paid,
                'paid_diff' => $paid - $g_total,
                'has_gap' => $has_gap,
                'calc_check' => $formula_diff,
                'has_calc_diff' => $has_formula_diff,
                'issue' => implode("; ", $issue_text),
            );
        }
        ksort($days);

        //payment methods: card, cash, online platforms, other - bigger amount first inside a group
        $group_order = array('card' => 1, 'cash' => 2, 'online' => 3, 'other' => 4);
        uasort($methods, function ($a, $b) use ($group_order) {
            if ($group_order[$a['group']] != $group_order[$b['group']]) {
                return $group_order[$a['group']] - $group_order[$b['group']];
            }
            if ($a['amount'] == $b['amount']) {
                return 0;
            }
            return $a['amount'] > $b['amount'] ? -1 : 1;
        });

        $unrecorded = $total['unrecorded'];

        $groups = array(
            'card' => array('label' => lang('ssr_card_sale'), 'amount' => 0, 'names' => array()),
            'cash' => array('label' => lang('ssr_cash_sale'), 'amount' => 0, 'names' => array()),
            'online' => array('label' => lang('ssr_online_platforms'), 'amount' => 0, 'names' => array()),
            'other' => array('label' => lang('ssr_other_payments'), 'amount' => 0, 'names' => array()),
        );
        foreach ($methods as $method) {
            $groups[$method['group']]['amount'] += $method['amount'];
            $groups[$method['group']]['names'][] = $method['name'];
        }
        foreach (array('online', 'other') as $group) {
            if ($groups[$group]['names']) {
                $groups[$group]['label'] .= ' ('.implode('+', $groups[$group]['names']).')';
            }
        }
        if (!$groups['other']['names']) {
            unset($groups['other']);
        }
        $groups['not_recorded'] = array('label' => lang('ssr_not_recorded'), 'amount' => $unrecorded, 'names' => array());

        //day wise reconciliation with Daily Sale Report
        $matched_days = 0;
        foreach ($days as $date => $day) {
            $days[$date]['daily_report'] = isset($daily_report[$date]) ? $daily_report[$date] : 0;
            $days[$date]['difference'] = $days[$date]['daily_report'] - $day['g_total'];
            $days[$date]['match'] = $is_zero($days[$date]['difference']);
            if ($days[$date]['match']) {
                $matched_days++;
            }
        }

        $duplicate_sale_no = 0;
        foreach ($sale_no_count as $count) {
            if ($count > 1) {
                $duplicate_sale_no++;
            }
        }

        $checks = array(
            array('label' => lang('ssr_check_invoice_sum'), 'a' => $total['g_total'], 'b' => (float)$db_totals->total_payable, 'is_count' => false),
            array('label' => lang('ssr_check_detailed_daily'), 'a' => $total['g_total'], 'b' => $daily_report_total, 'is_count' => false),
            array('label' => lang('ssr_check_subtotal'), 'a' => $total['sub_total'], 'b' => (float)$db_totals->sub_total, 'is_count' => false),
            array('label' => lang('ssr_check_discount'), 'a' => $total['discount'], 'b' => (float)$db_totals->total_discount, 'is_count' => false),
            array('label' => lang('ssr_check_tax'), 'a' => $total['vat'], 'b' => (float)$db_totals->total_vat, 'is_count' => false),
            array('label' => lang($tax_on_top ? 'ssr_check_formula' : 'ssr_check_formula_inclusive'), 'a' => $total['formula'], 'b' => $total['g_total'], 'is_count' => false, 'mismatch_invoices' => $total['formula_mismatch']),
            array('label' => lang('ssr_check_collected'), 'a' => $total['collected'], 'b' => $total['g_total'], 'is_count' => false),
            array('label' => lang('ssr_check_days'), 'a' => $matched_days, 'b' => count($days), 'is_count' => true),
            array('label' => lang('ssr_check_duplicate'), 'a' => $duplicate_sale_no, 'b' => 0, 'is_count' => true),
        );
        foreach ($checks as $key => $check) {
            $checks[$key]['difference'] = $check['a'] - $check['b'];
            $checks[$key]['ok'] = $is_zero($checks[$key]['difference']) && empty($check['mismatch_invoices']);
        }

        return array(
            'methods' => $methods,
            'groups' => $groups,
            'unrecorded' => $unrecorded,
            'total' => $total,
            'checks' => $checks,
            'issues' => $issues,
            'invoices' => $invoice_rows,
            'days' => $days,
            'daily_report_total' => $daily_report_total,
            'tax_on_top' => $tax_on_top,
        );
    }

}

