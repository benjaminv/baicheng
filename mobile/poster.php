<?php

/**
 *推广海报获取接口
*/
define('IN_ECS', true);
error_reporting(E_ALL);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/cls_json.php');
require('../includes/lib_user_share.php');

$type   = !empty($_POST['type']) ? trim($_POST['type']) : '';
$json = new JSON;
switch($type){
	case "goods":
		$goods_id = !empty($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
		$post = getPosterByGoodsId($goods_id,$_SESSION['user_id']);
		break;

}
echo $json->encode($post);
?>