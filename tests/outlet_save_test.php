<?php
// Run: php -d max_input_vars=1000 tests/outlet_save_test.php
// Controller regression checks with in-memory collaborators; no database writes.
define('BASEPATH', __DIR__);
class Cl_Controller {
    public $input, $custom, $session, $security, $form_validation, $Common_model;
}
class OutletRedirect extends Exception {}
class OutletRequestError extends Exception {}
function isServiceAccessOnly($service) { return false; }
function lang($key) { return $key; }
function htmlspecialcharscustom($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function getLanguageManifesto() { return array('', 'Outlet/outlets'); }
function redirect($url) { throw new OutletRedirect($url); }
function show_error($message, $status) { throw new OutletRequestError($message, $status); }
require __DIR__.'/../application/controllers/Outlet.php';

function check($condition, $message) {
    if (!$condition) { throw new Exception($message); }
}
function controllerFor($post, $multiOutlet = true) {
    $controller = (new ReflectionClass('Outlet'))->newInstanceWithoutConstructor();
    $controller->input = new class($post) {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function method() { return 'post'; }
        public function post($name = null) { return $name === null ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : null); }
    };
    $controller->custom = new class {
        public function encrypt_decrypt($id, $mode) { return $id; }
    };
    $controller->session = new class($multiOutlet) {
        public $data;
        public function __construct($multi) { $this->data = array('company_id' => 1, 'user_id' => 1, 'language_manifesto' => str_rot13($multi ? 'eriutoeri' : 'fgjgldkfg')); }
        public function userdata($key) { return isset($this->data[$key]) ? $this->data[$key] : null; }
        public function set_userdata($data) { $this->data = array_merge($this->data, $data); }
        public function set_flashdata($key, $value) { $this->data[$key] = $value; }
    };
    $controller->security = new class {
        public function xss_clean($value) { return $value; }
    };
    $controller->form_validation = new class {
        public function set_rules($field, $label, $rules) {}
        public function run() { return true; }
    };
    $controller->Common_model = new class {
        public $writes = array();
        public function updateInformation($data, $id, $table) { $this->writes[] = array($data, $id, $table); }
        public function insertInformation($data, $table) { $this->writes[] = array($data, 2, $table); return 2; }
        public function getDataById($id, $table) { return (object) array('outlets' => '1'); }
    };
    return $controller;
}
function saveOutlet($controller, $id = '1') {
    try { $controller->addEditOutlet($id); } catch (OutletRedirect $e) { return; }
    throw new Exception('Expected successful redirect');
}

$base = array('outlet_name' => 'Updated outlet', 'outlet_code' => 'NEW01', 'phone' => '1234567890', 'address' => "New\naddress", 'email' => 'outlet@example.test', 'online_order_module' => '2', 'online_self_order_receiving_id' => '5', 'default_waiter' => '6', 'active_status' => 'active');
$menu = array('item_check' => array());
for ($id = 1; $id <= 600; $id++) {
    $menu['item_check'][] = (string) $id;
    $menu['price_'.$id] = '10.00';
    $menu['price_ta_'.$id] = '0';
    $menu['price_de_'.$id] = '12.00';
    $menu['delivery_person'.$id] = array('7', '8');
    $menu['sale_price_delivery_json'.$id] = array('15.00', '0');
}
// Reproduce the original missing-submit path at PHP's default input limit.
$inputLimitWarning = false;
set_error_handler(function ($level, $message) use (&$inputLimitWarning) {
    if (strpos($message, 'Input variables exceeded') !== false) {
        $inputLimitWarning = true;
        return true;
    }
    return false;
});
parse_str(http_build_query($base + $menu + array('submit' => 'submit')), $truncated);
restore_error_handler();
check($inputLimitWarning, 'Expected PHP input-limit warning for the legacy form');
check(!isset($truncated['submit']), 'Large legacy form should lose the trailing submit value');

$post = $base + array('outlet_menu_data' => json_encode($menu), 'outlet_form_complete' => '1');
parse_str(http_build_query($post), $parsed);
$controller = controllerFor($parsed);
saveOutlet($controller); // Deliberately no submit button value (also covers Enter).
$writes = $controller->Common_model->writes;
check($writes[0][0]['outlet_name'] === 'Updated outlet', 'Name must update');
check($writes[0][0]['outlet_code'] === 'NEW01', 'Code must update on edit');
check($writes[0][0]['address'] === 'New address', 'Address must update');
check($writes[0][1] === '1', 'Must update the requested outlet');
$prices = json_decode($writes[1][0]['food_menu_prices'], true);
check(count($prices) === 600 && $prices['tmp600'] === '10.00||0||12.00', 'All prices, including zero and the final item, must survive');
$delivery = json_decode($writes[1][0]['delivery_price'], true);
check(json_decode($delivery['index_600'], true) === array('index_7' => '15.00', 'index_8' => '0'), 'Delivery partner prices must survive');

$controller = controllerFor($base + array('outlet_menu_data' => '{"item_check":[]}', 'outlet_form_complete' => '1'));
saveOutlet($controller);
check($controller->Common_model->writes[1][0]['food_menus'] === '', 'Unselecting all must clear the menu');

$controller = controllerFor($base + array('item_check' => array('9'), 'price_9' => '22', 'outlet_form_complete' => '1'));
saveOutlet($controller);
check(json_decode($controller->Common_model->writes[1][0]['food_menu_prices'], true)['tmp9'] === '22||||', 'Small forms without JavaScript must still save');

$badPosts = array($base);
foreach (array('{invalid', '{}', '{"item_check":"9"}', '{"item_check":["9"],"price_9":[]}', array('unexpected')) as $badPayload) {
    $badPosts[] = $base + array('outlet_form_complete' => '1', 'outlet_menu_data' => $badPayload);
}
foreach ($badPosts as $badPost) {
    $controller = controllerFor($badPost);
    try {
        saveOutlet($controller);
        throw new Exception('Incomplete/invalid requests must be rejected');
    } catch (OutletRequestError $e) {
        check($e->getCode() === 400, 'Expected a clear request error');
        check(count($controller->Common_model->writes) === 0, 'Rejected requests must not modify data');
    }
}
$controller = controllerFor($post, false);
saveOutlet($controller);
check(count($controller->Common_model->writes) === 1, 'Single-outlet settings must not rewrite menu assignments');

$controller = controllerFor($post);
saveOutlet($controller, '');
check($controller->Common_model->writes[0][0]['outlet_code'] === 'NEW01', 'Creation must retain outlet code');
check($controller->Common_model->writes[2][1] === 2, 'Created outlet must receive its menus');
echo "PASS: large form, missing submit, edit/create, prices, clearing selections, fallback and incomplete requests\n";
