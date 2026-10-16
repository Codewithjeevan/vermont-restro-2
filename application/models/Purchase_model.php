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
  # This is Purchase_model Model
  ###########################################################
 */
class Purchase_model extends CI_Model {
 /**
     * generate Purchase Ref No
     * @access public
     * @return string
     * @param int
     */
    public function generatePurRefNo($outlet_id) {
        $purchase_count = $this->db->query("SELECT count(id) as purchase_count
               FROM tbl_purchase where outlet_id=$outlet_id")->row('purchase_count');
        $ingredient_code = str_pad($purchase_count + 1, 6, '0', STR_PAD_LEFT);
        return $ingredient_code;
    }
 /**
     * is Update/purchase_tax_migration.sql applied; without it purchases work as before, no tax
     * @access public
     * @return bool
     */
    public function taxEnabled() {
        return $this->db->field_exists('tax_amount', 'tbl_purchase');
    }
 /**
     * default purchase tax from the company tax settings: the global tax type and the sum of
     * the rates that apply, the way the POS picks them (GST: CGST + SGST without IGST,
     * no GST: none of the three). Zero when the company does not collect tax.
     * @access public
     * @return array
     * @param int
     */
    public function getDefaultPurchaseTax($company_id) {
        $company = $this->db->query("SELECT collect_tax, tax_is_gst, tax_type, tax_setting FROM tbl_companies WHERE id=?", array($company_id))->row();
        $tax_percent = 0;
        if ($company && $company->collect_tax == "Yes") {
            foreach ((array)json_decode($company->tax_setting) as $tax) {
                $is_gst_tax = in_array($tax->tax, array("CGST", "SGST", "IGST"));
                if ($is_gst_tax && ($company->tax_is_gst != "Yes" || $tax->tax == "IGST")) {
                    continue;
                }
                $tax_percent += (float)$tax->tax_rate;
            }
        }
        return array(
            'tax_type' => $company && $company->tax_type == 2 ? 2 : 1,
            'tax_percent' => $tax_percent,
        );
    }
 /**
     * get Ingredient List With Unit And Price
     * @access public
     * @return object
     * @param int
     */
    public function getIngredientListWithUnitAndPrice($company_id) {
        $result = $this->db->query("SELECT tbl_ingredients.id, tbl_ingredients.name, tbl_ingredients.code, tbl_ingredients.purchase_price, tbl_ingredients.consumption_unit_cost, tbl_units.unit_name
          FROM tbl_ingredients 
          left JOIN tbl_units ON tbl_ingredients.purchase_unit_id = tbl_units.id
          WHERE tbl_ingredients.company_id=$company_id AND tbl_ingredients.del_status = 'Live' AND tbl_ingredients.ing_type = 'Plain Ingredient'  
          ORDER BY tbl_ingredients.name ASC")->result();
        return $result;
    }
    /**
     * get Purchase Ingredients
     * @access public
     * @return object
     * @param int
     */
    public function getPurchaseIngredients($id) {
        $this->db->select("*");
        $this->db->from("tbl_purchase_ingredients");
        $this->db->order_by('id', 'ASC');
        $this->db->where("purchase_id", $id);
        $this->db->where("del_status", 'Live');
        return $this->db->get()->result();
    }

}

