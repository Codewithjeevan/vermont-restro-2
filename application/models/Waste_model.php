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
  # This is Waste_model Model
  ###########################################################
 */
class Waste_model extends CI_Model {

    /**
     * get Ingredient List
     * @access public
     * @return object
     * @param no
     */
    public function getIngredientList() {
        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');
        $this->db->select("tbl_ingredients.id,tbl_ingredients.conversion_rate, tbl_ingredients.name, tbl_ingredients.code, tbl_purchase_ingredients.unit_price as purchase_price, tbl_units.unit_name");
        $this->db->from("tbl_ingredients");
        $this->db->join("tbl_purchase_ingredients", 'tbl_purchase_ingredients.ingredient_id = tbl_ingredients.id', 'left');
        $this->db->join("tbl_units", 'tbl_units.id = tbl_ingredients.unit_id', 'left');
        $this->db->order_by("tbl_ingredients.name", "ASC");
        $this->db->where("tbl_ingredients.company_id", $company_id);
        $this->db->where("tbl_purchase_ingredients.del_status", 'Live');
        $this->db->where("tbl_purchase_ingredients.outlet_id", $outlet_id);
        $result = $this->db->get()->result();
        //an ingredient with Opening Stock has stock too, even before its first purchase
        if ($this->db->field_exists('is_opening', 'tbl_inventory_adjustment')) {
            $opening = $this->db->query("SELECT i.id, i.conversion_rate, i.name, i.code, i.purchase_price, u.unit_name
                FROM tbl_inventory_adjustment_ingredients c
                JOIN tbl_inventory_adjustment a ON a.id = c.inventory_adjustment_id
                JOIN tbl_ingredients i ON i.id = c.ingredient_id
                LEFT JOIN tbl_units u ON u.id = i.unit_id
                WHERE a.is_opening = 1 AND a.outlet_id = ? AND c.del_status = 'Live' AND i.company_id = ? AND i.del_status = 'Live'
                GROUP BY i.id", array((int)$outlet_id, (int)$company_id))->result();
            $result = array_merge($result, $opening);
            usort($result, function ($a, $b) {
                return strcasecmp((string)$a->name, (string)$b->name);
            });
        }
        return $result;
    }
    /**
     * ingredients one portion of a food menu uses, worked out the way the POS deducts stock on a
     * sale: the recipe, the ingredient behind a direct item, or the recipes of a combo's items
     * @access public
     * @return array rows with ingredient_id, name, code, unit_name, consumption_unit_cost, consumption
     * @param int
     */
    public function getFoodMenuIngredients($food_menu_id) {
        $food_menu_id = (int)$food_menu_id;
        $company_id = (int)$this->session->userdata('company_id');
        $food_menu = $this->db->query("SELECT product_type FROM tbl_food_menus WHERE id = $food_menu_id AND company_id = $company_id")->row();
        if (!$food_menu) {
            return array();
        }

        $amounts = array();
        if ($food_menu->product_type == 1) {
            $this->addRecipeToAmounts($amounts, $food_menu_id, 1);
        } elseif ($food_menu->product_type == 3) {
            $direct_ingredients = $this->db->query("SELECT id FROM tbl_ingredients WHERE food_id = $food_menu_id AND del_status = 'Live'")->result();
            foreach ($direct_ingredients as $ingredient) {
                $amounts[$ingredient->id] = (isset($amounts[$ingredient->id]) ? $amounts[$ingredient->id] : 0) + 1;
            }
        } else {
            $combo_items = $this->db->query("SELECT added_food_menu_id, quantity FROM tbl_combo_food_menus WHERE food_menu_id = $food_menu_id AND del_status = 'Live'")->result();
            foreach ($combo_items as $combo_item) {
                $this->addRecipeToAmounts($amounts, (int)$combo_item->added_food_menu_id, (float)$combo_item->quantity);
            }
        }
        if (!$amounts) {
            return array();
        }

        $rows = $this->db->query("SELECT i.id AS ingredient_id, i.name, i.code, i.consumption_unit_cost, u.unit_name
            FROM tbl_ingredients i
            LEFT JOIN tbl_units u ON u.id = i.unit_id
            WHERE i.id IN (" . implode(',', array_map('intval', array_keys($amounts))) . ")
            ORDER BY i.name")->result();
        foreach ($rows as $row) {
            $row->consumption = $amounts[$row->ingredient_id];
        }
        return $rows;
    }
    /**
     * add one food menu's recipe to a running ingredient_id => amount list
     * @access private
     * @return void
     * @param array
     * @param int
     * @param float
     */
    private function addRecipeToAmounts(&$amounts, $food_menu_id, $multiply_by) {
        $recipe = $this->db->query("SELECT ingredient_id, consumption FROM tbl_food_menus_ingredients WHERE food_menu_id = $food_menu_id AND del_status = 'Live'")->result();
        foreach ($recipe as $line) {
            $amount = (float)$line->consumption * $multiply_by;
            $amounts[$line->ingredient_id] = (isset($amounts[$line->ingredient_id]) ? $amounts[$line->ingredient_id] : 0) + $amount;
        }
    }
    public function getPurchaseIng($in_id) {
        $outlet_id = $this->session->userdata('outlet_id');
        $this->db->select("tbl_purchase_ingredients.*,tbl_ingredients.conversion_rate");
        $this->db->from("tbl_purchase_ingredients");
        $this->db->join("tbl_ingredients", 'tbl_ingredients.id = tbl_purchase_ingredients.ingredient_id', 'left');
        $this->db->order_by("tbl_purchase_ingredients.id", "DESC");
        $this->db->where("tbl_purchase_ingredients.del_status", 'Live');
        $this->db->where("tbl_purchase_ingredients.outlet_id", $outlet_id);
        $this->db->where("tbl_purchase_ingredients.ingredient_id", $in_id);
        $result = $this->db->get()->row();
        return $result;
    }
    /**
     * get Food Menu List
     * @access public
     * @return object
     * @param no
     */
    public function getFoodMenuList() {
        $outlet_id = $this->session->userdata('outlet_id');
        $company_id = $this->session->userdata('company_id');
        $this->db->select("*");
        $this->db->from("tbl_food_menus");
        $this->db->order_by("tbl_food_menus.name", "ASC");
        $this->db->where("tbl_food_menus.company_id", $company_id);
        $this->db->where("tbl_food_menus.del_status", 'Live');

        $result = $this->db->get()->result();
        return $result;
    }
    /**
     * get Waste Ingredients
     * @access public
     * @return object
     * @param int
     */
    public function getWasteIngredients($id) {
        $this->db->select("*");
        $this->db->from("tbl_waste_ingredients");
        $this->db->order_by('id', 'ASC');
        $this->db->where("waste_id", $id);
        $this->db->where("del_status", 'Live');
        return $this->db->get()->result();
    }
    /**
     * generate Waste Ref No
     * @access public
     * @return object
     * @param int
     */
    public function generateWasteRefNo($outlet_id) {
        $waste_count = $this->db->query("SELECT count(id) as waste_count
               FROM tbl_wastes where outlet_id=$outlet_id")->row('waste_count');
        $ingredient_code = str_pad($waste_count + 1, 6, '0', STR_PAD_LEFT);
        return $ingredient_code;
    }

}

