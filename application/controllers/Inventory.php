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
  # This is Inventory Controller
  ###########################################################
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends Cl_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Authentication_model');
        $this->load->model('Common_model');
        $this->load->model('Inventory_model');
        $this->load->model('Master_model');
        $this->load->model('Sale_model');
        $this->Common_model->setDefaultTimezone();
        $this->load->library('form_validation');
        
        if (!$this->session->has_userdata('user_id')) {
            redirect('Authentication/index');
        }
        if (!$this->session->has_userdata('outlet_id')) {
            $this->session->set_flashdata('exception_2', lang('please_click_green_button'));

            rememberClickedRedirect();
            redirect('Outlet/outlets');
        }


        $login_session['active_menu_tmp'] = '';
        $this->session->set_userdata($login_session);
    }

     /**
     * inventory info
     * @access public
     * @return void
     * @param no
     */
    public function index() {
        //start check access function
        $segment_1 = $this->uri->segment(1);
        $segment_2 = $this->uri->segment(2);

        $controller = "129";
        $function = "";

        if($segment_1=="Inventory" || $segment_2=="index"){
            $function = "view";
        }else{
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }

        if(!checkAccess($controller,$function)){
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        //end check access function


        $data = array();
        $ingredient_id = $this->input->post('ingredient_id');
        $category_id = $this->input->post('category_id');
        $food_id = $this->input->post('food_id');
        $data['ingredient_id'] = $ingredient_id;
        $company_id = $this->session->userdata('company_id');
        $data['ingredient_categories'] = $this->Common_model->getAllByCompanyIdForDropdown($company_id, "tbl_ingredient_categories");
        $data['ingredients'] = $this->Common_model->getAllByCompanyIdForDropdown($company_id, "tbl_ingredients");

        $data['foodMenus'] = $this->Sale_model->getAllFoodMenus();
        $data['inventory'] = $this->Inventory_model->getInventory($category_id, $ingredient_id, $food_id);
        $data['ingredient_prices'] = getIngredientPricesAsOf($this->session->userdata('outlet_id'));
        $data['main_content'] = $this->load->view('inventory/inventory', $data, TRUE);
        $this->load->view('userHome', $data);
    }
    /**
     * Opening Stock of the current outlet. Saved as an Inventory Adjustment (Plus) row, so every
     * stock formula already counts it; is_opening keeps it out of Usage on the monthly report.
     * @access public
     * @return void
     * @param no
     */
    public function openingStock() {
        if(!checkAccess("131","add")){
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }

        $outlet_id = $this->session->userdata('outlet_id');
        $opening_enabled = $this->Inventory_model->openingEnabled();
        $inventory = $this->Inventory_model->getInventory();

        if ($opening_enabled && htmlspecialcharscustom($this->input->post('submit'))) {
            $this->saveOpeningStockForm($inventory, $outlet_id);
            redirect('Inventory/openingStock');
        }

        $opening_rows = $opening_enabled ? $this->Inventory_model->getOpeningStockRows($outlet_id) : array();
        $rows = array();
        $categories = array();
        foreach ($inventory as $value) {
            if (!$value->id) {
                continue;
            }
            $conversion_rate = (float)$value->conversion_rate;
            $divide_by = $conversion_rate > 0 ? $conversion_rate : 1;
            $opening = isset($opening_rows[$value->id]) ? $opening_rows[$value->id] : null;
            $rows[] = (object)array(
                'id' => $value->id,
                'name' => $value->name,
                'code' => $value->code,
                'category_name' => $value->category_name,
                'purchase_unit' => $value->unit_name2,
                'conversion_rate' => $conversion_rate,
                'current_stock' => round($this->Inventory_model->stockInConsumptionUnits($value) / $divide_by, 3),
                'opening_qty' => $opening ? round($opening->consumption_amount / $divide_by, 3) : '',
                'opening_date' => $opening ? $opening->date : '',
            );
            $categories[$value->category_name] = $value->category_name;
        }
        ksort($categories);

        $data = array();
        $data['opening_enabled'] = $opening_enabled;
        $data['rows'] = $rows;
        $data['categories'] = $categories;
        $data['main_content'] = $this->load->view('inventory/opening_stock', $data, TRUE);
        $this->load->view('userHome', $data);
    }
    /**
     * check and save the opening stock form; the browser posts only the rows that changed
     * @access private
     * @return void
     * @param array getInventory() rows
     * @param int
     */
    private function saveOpeningStockForm($inventory, $outlet_id) {
        $date_time = strtotime((string)$this->input->post('opening_date'));
        if (!$date_time) {
            $this->session->set_flashdata('exception_er', lang('opening_stock_invalid'));
            return;
        }

        $ingredients = array();
        foreach ($inventory as $value) {
            if ($value->id) {
                $ingredients[$value->id] = $value;
            }
        }

        $rows = array();
        foreach ((array)json_decode((string)$this->input->post('opening_rows'), true) as $row) {
            $ingredient_id = isset($row['id']) ? (int)$row['id'] : 0;
            $qty = isset($row['qty']) ? $row['qty'] : '';
            if (!isset($ingredients[$ingredient_id]) || !is_numeric($qty) || $qty < 0 || (float)$ingredients[$ingredient_id]->conversion_rate <= 0) {
                $this->session->set_flashdata('exception_er', lang('opening_stock_invalid'));
                return;
            }
            $rows[$ingredient_id] = (float)$qty * (float)$ingredients[$ingredient_id]->conversion_rate;
        }
        if (!$rows) {
            $this->session->set_flashdata('exception_er', lang('opening_stock_no_changes'));
            return;
        }

        $this->load->model('Inventory_adjustment_model');
        $saved = $this->Inventory_model->saveOpeningStock(
            $rows,
            date('Y-m-d', $date_time),
            $outlet_id,
            $this->session->userdata('user_id'),
            $this->Inventory_adjustment_model->generateReferenceNo($outlet_id)
        );
        if ($saved) {
            $this->session->set_flashdata('exception', lang('opening_stock_saved'));
        } else {
            $this->session->set_flashdata('exception_er', lang('opening_stock_invalid'));
        }
    }
    /**
     * inventory info
     * @access public
     * @return void
     * @param no
     */
    public function inventoryFoodMenu() {
        //start check access function
        $segment_1 = $this->uri->segment(1);
        $segment_2 = $this->uri->segment(2);

        $controller = "346";
        $function = "";

        if($segment_1=="Inventory" || $segment_2=="inventoryFoodMenu"){
            $function = "view";
        }else{
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }

        if(!checkAccess($controller,$function)){
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        //end check access function

        $data = array();
        $food_id = $this->input->post('food_id');
        $company_id = $this->session->userdata('company_id');
        $data['foodMenus'] = $this->Sale_model->getAllFoodMenus();
        $category_id = $this->input->post('category_id');
        $data['ingredient_categories'] = $this->Common_model->getAllByCompanyIdForDropdown($company_id, "tbl_food_menu_categories");
        $data['inventory'] = $this->Inventory_model->getInventoryFoodMenu($food_id,$category_id);
        $data['main_content'] = $this->load->view('inventory/inventory_food_menu', $data, TRUE);
        $this->load->view('userHome', $data);
    }
     /**
     * inventory info
     * @access public
     * @return void
     * @param no
     */
     /**
     * get Inventory Alert List
     * @access public
     * @return void
     * @param no
     */
    public function getInventoryAlertList() {
        //start check access function
        $segment_1 = $this->uri->segment(1);
        $segment_2 = $this->uri->segment(2);

        $controller = "173";
        $function = "";

        if($segment_1=="Inventory" || $segment_2=="getInventoryAlertList"){
            $function = "view";
        }else{
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }

        if(!checkAccess($controller,$function)){
            $this->session->set_flashdata('exception_er', lang('menu_not_permit_access'));
            redirect('Authentication/userProfile');
        }
        //end check access function

        $data = array();
        $data['inventory'] = $this->Inventory_model->getInventoryAlertList();
        $data['main_content'] = $this->load->view('inventory/inventoryAlertList', $data, TRUE);
        $this->load->view('userHome', $data);
    }
     /**
     * get Ingredient Info Ajax
     * @access public
     * @return object
     * @param no
     */
    public function getIngredientInfoAjax() {
        $cat_id = $_GET['category_id'];
        $outlet_id = $this->session->userdata('outlet_id');
        if ($cat_id) {
            $results = $this->Inventory_model->getDataByCatId($cat_id, "tbl_ingredients");
        } else {
            $results = $this->Inventory_model->getAllByOutletIdForDropdown($outlet_id, "tbl_ingredients");
        }
        echo json_encode($results);
    }
    public function getCurrentInventory() {
        $ing_id = $_POST['ing_id'];
        $data =  $this->Inventory_model->getCurrentInventory($ing_id);
        echo json_encode($data);
    }

}
