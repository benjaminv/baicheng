<?php 
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');
require(ROOT_PATH . 'includes/lib_pay_fencheng.php');
//technican_supplier_commission(1036);
//get_pay_fencheng(1035);
log_supplier_commission(1036);
//error_reporting(E_ALL);
//$order = $GLOBALS['db']->getRow("SELECT extension_id,extension_code,extension_id,goods_amount,order_id FROM ".$GLOBALS['ecs']->table('order_info')." WHERE order_id=986");
//echo "<pre>";print_r($order);
//$integral = integral_to_give($order);
//echo "<pre>";print_r($integral);