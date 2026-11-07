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
  # This is Inventory_model Model
  ###########################################################
 */
class Inventory_model extends CI_Model {

    /**
     * get Data By Cat Id
     * @access public
     * @return object
     * @param int
     * @param string
     */
    public function getDataByCatId($cat_id, $table_name) {
        $this->db->select("id,name,code");
        $this->db->from($table_name);
        $this->db->where("category_id", $cat_id);
        $this->db->order_by("name", "ASC");
        $this->db->where("del_status", 'Live');
        return $this->db->get()->result();
    }
    /**
     * get Inventory
     * @access public
     * @return object
     * @param string
     * @param string
     * @param string
     */
    public function getInventory($category_id = "", $ingredient_id = "", $food_id = "") {

        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');

        $where = '';
        $where1 = '';
        if($food_id!=''){
            $food = foodMenuRow($food_id);
            if($food->product_type==2){
                $getFMIds = $food->combo_ids;
            }else{
                $getFMIds = $food_id;
            }
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
            $result = $this->db->query("SELECT ingr_tbl.*,i.food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name, ingr_unit_tbl2.unit_name as unit_name2, 
                    (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
                    (select SUM(consumption) from tbl_sale_consumptions_of_menus where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND del_status='Live') total_consumption,
                    (select SUM(consumption) from tbl_sale_consumptions_of_modifiers_of_menus where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  del_status='Live') total_modifiers_consumption,
                    (select SUM(waste_amount) from tbl_waste_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND tbl_waste_ingredients.del_status='Live') total_waste,
                    (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Plus') total_consumption_plus,
                    (select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.ingredient_id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Minus') total_consumption_minus,
                    (select SUM(quantity_amount) from tbl_production_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_production_ingredients.del_status='Live' AND tbl_production_ingredients.status=1) total_production,
                    (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND  tbl_transfer_ingredients.status=1 AND tbl_transfer_ingredients.transfer_type=1) total_transfer_plus,
                    (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=1) total_transfer_minus,
                    (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND  tbl_transfer_received_ingredients.status=1) total_transfer_plus_2,
                    (select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND (tbl_transfer_received_ingredients.status=1)) total_transfer_minus_2
                    FROM tbl_food_menus_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.ingredient_id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id  LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id WHERE FIND_IN_SET(`food_menu_id`, '$getFMIds') AND i.company_id= '$company_id' AND i.del_status='Live' $where  GROUP BY i.ingredient_id")->result();
            return $result;
        }else{
            $result = $this->db->query("SELECT ingr_tbl.*,i.id as food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name, ingr_unit_tbl2.unit_name as unit_name2, 
                    (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
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
                    FROM tbl_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id  WHERE i.company_id= '$company_id' AND i.del_status='Live' $where1 GROUP BY i.id")->result();

            return $result;
        }

    }
    public function getCurrentInventory($ingredient_id) {

        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');

        $where1 = '';
        if($ingredient_id!=''){
            $where1.= "  AND i.id = '$ingredient_id'";
        }
        $value = $this->db->query("SELECT ingr_tbl.*,i.id as food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name, ingr_unit_tbl2.unit_name as unit_name2, (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
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
        FROM tbl_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id  WHERE i.company_id= '$company_id' AND i.del_status='Live' $where1 GROUP BY i.id")->row();

        $conversion_rate = (int)$value->conversion_rate?$value->conversion_rate:1;
        $new_stock = 0;
        if($value->id):
            $totalStock = ($value->total_purchase*$value->conversion_rate)  - $value->total_consumption - $value->total_modifiers_consumption - $value->total_waste + $value->total_consumption_plus - $value->total_consumption_minus + ($value->total_transfer_plus*$value->conversion_rate) - ($value->total_transfer_minus*$value->conversion_rate)  +  ($value->total_transfer_plus_2*$value->conversion_rate) -  ($value->total_transfer_minus_2*$value->conversion_rate)+ ($value->total_production*$value->conversion_rate);
            if($value->conversion_rate==0 || $value->conversion_rate==''){
                $total_sale_unit = isset($value->conversion_rate) && (int)$value->conversion_rate?(int)($totalStock/1):'0';
            }else{
                $total_sale_unit = isset($value->conversion_rate) && (int)$value->conversion_rate?(int)($totalStock/$value->conversion_rate):'0';
            }
            $new_stock = (float)($total_sale_unit.".".$totalStock%$conversion_rate);
        endif;
        $return_array = array();
        $return_array['total_stock'] = $new_stock;
        $return_array['stock_unit'] = $value->unit_name2;
        return $return_array;

    }
    public function getInventoryFoodMenu($food_id = "",$category_id='') {

        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');
        $where = '';
        $where1 = '';
        if($food_id!=''){
            $getFMIds = $food_id;
        }else{
            $getFMIds = getFMIds($outlet_id);
        }
        if($category_id!=''){
            $where1.= "  AND i.category_id = '$category_id'";
        }
        //get selected food menu ids
        $result = $this->db->query("SELECT i.*,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND  tbl_transfer_ingredients.status=1 AND tbl_transfer_ingredients.transfer_type=2) total_transfer_plus_2,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=2) total_transfer_minus_2,
        (select SUM(qty) from tbl_sales_details  where food_menu_id=i.id AND outlet_id=$outlet_id AND del_status='Live') sale_total
         FROM tbl_food_menus i  WHERE FIND_IN_SET(`id`, '$getFMIds') AND i.company_id= '$company_id' AND i.del_status='Live' $where1")->result();
        return $result;
    }
    public function checkInventory($food_id = "") {

        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');
        //get selected food menu ids
        $result = $this->db->query("SELECT i.*,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND  tbl_transfer_ingredients.status=1 AND tbl_transfer_ingredients.transfer_type=2) total_transfer_plus_2,
        (select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=2) total_transfer_minus_2,
        (select SUM(qty) from tbl_sales_details  where food_menu_id=i.id AND outlet_id=$outlet_id AND del_status='Live') sale_total
         FROM tbl_food_menus i  WHERE id='$food_id' AND i.company_id= '$company_id' AND i.del_status='Live'")->row();
        return $result;
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

        $result = $this->db->query("SELECT ingr_tbl.*,i.id as food_menu_id,ingr_cat_tbl.category_name,ingr_unit_tbl.unit_name,ingr_unit_tbl2.unit_name as unit_name2, (select SUM(quantity_amount) from tbl_purchase_ingredients where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_purchase, 
(select SUM(consumption) from tbl_sale_consumptions_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND del_status='Live') total_consumption,
(select SUM(consumption) from tbl_sale_consumptions_of_modifiers_of_menus where ingredient_id=i.id AND outlet_id=$outlet_id AND  del_status='Live') total_modifiers_consumption,
(select SUM(waste_amount) from tbl_waste_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND tbl_waste_ingredients.del_status='Live') total_waste,
(select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Plus') total_consumption_plus,
(select SUM(consumption_amount) from tbl_inventory_adjustment_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_inventory_adjustment_ingredients.del_status='Live' AND  tbl_inventory_adjustment_ingredients.consumption_status='Minus') total_consumption_minus,
(select SUM(quantity_amount) from tbl_production_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_production_ingredients.del_status='Live' AND tbl_production_ingredients.status=1) total_production,
(select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND  tbl_transfer_ingredients.status=1 AND tbl_transfer_ingredients.transfer_type=1) total_transfer_plus,
(select SUM(quantity_amount) from tbl_production_ingredients  where ingredient_id=i.id AND outlet_id=$outlet_id AND  tbl_production_ingredients.del_status='Live' AND tbl_production_ingredients.status=1) total_production,
(select SUM(quantity_amount) from tbl_transfer_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_ingredients.del_status='Live' AND (tbl_transfer_ingredients.status=1) AND tbl_transfer_ingredients.transfer_type=1) total_transfer_minus,
(select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND to_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND  tbl_transfer_received_ingredients.status=1) total_transfer_plus_2,
(select SUM(quantity_amount) from tbl_transfer_received_ingredients  where ingredient_id=i.id AND from_outlet_id=$outlet_id AND  tbl_transfer_received_ingredients.del_status='Live' AND (tbl_transfer_received_ingredients.status=1)) total_transfer_minus_2

FROM tbl_ingredients i  LEFT JOIN (select * from tbl_ingredients where del_status='Live') ingr_tbl ON ingr_tbl.id = i.id LEFT JOIN (select * from tbl_ingredient_categories where del_status='Live') ingr_cat_tbl ON ingr_cat_tbl.id = ingr_tbl.category_id LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl ON ingr_unit_tbl.id = ingr_tbl.unit_id  LEFT JOIN (select * from tbl_units where del_status='Live') ingr_unit_tbl2 ON ingr_unit_tbl2.id = ingr_tbl.purchase_unit_id WHERE  i.company_id= '$company_id' AND i.del_status='Live' $where  GROUP BY i.id")->result();

        return $result;
    }
    /**
     * Opening Stock needs Update/inventory_stock_updates_migration.sql
     * @access public
     * @return bool
     * @param no
     */
    public function openingEnabled() {
        return $this->db->field_exists('is_opening', 'tbl_inventory_adjustment');
    }
    /**
     * stock of one getInventory() row in consumption units, same formula as the stock page
     * @access public
     * @return float
     * @param object
     */
    public function stockInConsumptionUnits($value) {
        return ($value->total_purchase*$value->conversion_rate) - $value->total_consumption - $value->total_modifiers_consumption - $value->total_waste + $value->total_consumption_plus - $value->total_consumption_minus + ($value->total_transfer_plus*$value->conversion_rate) - ($value->total_transfer_minus*$value->conversion_rate) + ($value->total_transfer_plus_2*$value->conversion_rate) - ($value->total_transfer_minus_2*$value->conversion_rate) + ($value->total_production*$value->conversion_rate);
    }
    /**
     * live opening stock rows of an outlet, amounts are in consumption units
     * @access public
     * @return array ingredient_id => row with consumption_amount and date
     * @param int
     */
    public function getOpeningStockRows($outlet_id) {
        $rows = $this->db->query("SELECT c.ingredient_id, c.consumption_amount, a.date
            FROM tbl_inventory_adjustment_ingredients c
            JOIN tbl_inventory_adjustment a ON a.id = c.inventory_adjustment_id
            WHERE a.is_opening = 1 AND a.outlet_id = ? AND c.del_status = 'Live'", array((int)$outlet_id))->result();
        $result = array();
        foreach ($rows as $row) {
            $result[$row->ingredient_id] = $row;
        }
        return $result;
    }
    /**
     * save changed opening stock rows. An ingredient keeps one live opening row per outlet: the
     * old row goes, and a zero quantity only removes it.
     * @access public
     * @return bool
     * @param array ingredient_id => amount in consumption units
     * @param string
     * @param int
     * @param int
     * @param string
     */
    public function saveOpeningStock($rows, $date, $outlet_id, $user_id, $reference_no) {
        $outlet_id = (int)$outlet_id;
        $ingredient_ids = implode(',', array_map('intval', array_keys($rows)));

        $this->db->trans_start();
        $this->db->query("UPDATE tbl_inventory_adjustment_ingredients c
            JOIN tbl_inventory_adjustment a ON a.id = c.inventory_adjustment_id
            SET c.del_status = 'Deleted'
            WHERE a.is_opening = 1 AND a.outlet_id = $outlet_id AND c.del_status = 'Live' AND c.ingredient_id IN ($ingredient_ids)");
        $this->db->query("UPDATE tbl_inventory_adjustment a
            SET a.del_status = 'Deleted'
            WHERE a.is_opening = 1 AND a.outlet_id = $outlet_id AND a.del_status = 'Live'
            AND NOT EXISTS (SELECT 1 FROM tbl_inventory_adjustment_ingredients c WHERE c.inventory_adjustment_id = a.id AND c.del_status = 'Live')");

        $new_rows = array_filter($rows, function ($amount) {
            return $amount > 0;
        });
        if ($new_rows) {
            $this->db->insert('tbl_inventory_adjustment', array(
                'reference_no' => $reference_no,
                'date' => $date,
                'note' => 'Opening Stock',
                'employee_id' => (int)$user_id,
                'user_id' => (int)$user_id,
                'outlet_id' => $outlet_id,
                'del_status' => 'Live',
                'is_opening' => 1,
            ));
            $adjustment_id = $this->db->insert_id();
            foreach ($new_rows as $ingredient_id => $amount) {
                $this->db->insert('tbl_inventory_adjustment_ingredients', array(
                    'ingredient_id' => (int)$ingredient_id,
                    'consumption_amount' => $amount,
                    'consumption_status' => 'Plus',
                    'inventory_adjustment_id' => $adjustment_id,
                    'outlet_id' => $outlet_id,
                    'del_status' => 'Live',
                ));
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    /**
     * get All By Company Id For Dropdown
     * @access public
     * @return object
     * @param int
     * @param string
     */
    public function getAllByCompanyIdForDropdown($company_id, $table_name) {
        $result = $this->db->query("SELECT * 
          FROM $table_name 
          WHERE company_id=$company_id AND del_status = 'Live'  
          ORDER BY name ASC")->result();
        return $result;
    }

}

?>
