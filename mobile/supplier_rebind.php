<?php
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_order.php');
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):"";
//error_reporting(E_ALL);
if($act == 'rebind'){
	$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
	if(empty($user_id)){
		//立即登录
		setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
		header("location: user.php?act=login");
		exit;
	}
	$suppId = intval($_REQUEST['suppId']);
	if(!$suppId){
		show_message('参数错误', '返回个人中心', 'user.php', 'info');
	}
	$sql = "update ". $GLOBALS['ecs']->table('users') ." set supplierId='". $suppId ."' where user_id='".$user_id."' ";
	$GLOBALS['db']->query($sql);
	header("location: supplier.php?suppId=".$suppId);exit;
	exit;
}elseif($act == 'rebind_index'){
	$new_supplier = intval($_REQUEST['new_supplier']);
	$old_supplier = intval($_REQUEST['old_supplier']);
	$new_supplier_info = getSupplierInfoBySupplierId($new_supplier);
	$old_supplier_info = getSupplierInfoBySupplierId($old_supplier);
	$smarty->assign('new_supplier_info', $new_supplier_info);
	$smarty->assign('old_supplier_info', $old_supplier_info);
	$smarty->assign('act', $act);
	$smarty->display('supplier_rebind.dwt');
}
elseif($act == 'circle'){
	//商圈列表 2019/7/1
	$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
	if(empty($user_id)){
		//立即登录
		setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
		header("location: user.php?act=login");
		exit;
	}
	//获取用户所属店铺的商圈，如果没有商圈跳转到店铺首页
	$circle_list = getCircleInfoByUserId($user_id);
	$smarty->assign('supplier_list', $circle_list);
	$smarty->assign('act', $act);
	$smarty->assign('title', '联盟商圈');
	$smarty->display('supplier_rebind.dwt');
}
else{
	$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
	if(empty($user_id)){
		//立即登录
		setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
		header("location: user.php?act=login");
		exit;
	}
	//切换登录的店铺列表
	//如果用户只在一家店铺浏览过,直接跳转到该店铺,否则展示最近消费的店铺列表
	$user_supplierid_list = userSupplieridList($user_id);
	if(count($user_supplierid_list) == 1){
		header("location: supplier.php?suppId=".$user_supplierid_list[0]);
		exit;
	}
	$supplier_list = getSupplierInfoBySupplierIds($user_supplierid_list);
	$smarty->assign('supplier_list', $supplier_list);
	$smarty->assign('act', $act);
	$smarty->assign('title', '店铺列表');
	$smarty->display('supplier_rebind.dwt');
}
function getSupplierInfoBySupplierId($supplier_id){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$supplier_info = $db->getRow('SELECT supplier_id,supplier_name FROM '.$ecs->table("supplier").' WHERE supplier_id='.$supplier_id." AND status=1");
	$supplier_info['template'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="template" AND  parent_id > 0 AND supplier_id='.$supplier_id);
	$supplier_info['shop_logo'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_logo" AND  parent_id > 0 AND supplier_id='.$supplier_id);
	if(empty($supplier_info['shop_logo'])){
		$supplier_info['shop_logo'] = '../themes/'.$supplier_info['template'].'/images/dianpu.jpg';
	}else{
		$supplier_info['shop_logo'] = '../'.$supplier_info['shop_logo'];
	}
	return $supplier_info;
}

function userSupplieridList($user_id){
	$default_supplier_id = get_user_default_supplier($user_id,true);
	//获取用户进过的店铺
	$data = get_user_supplier_relate($user_id);
	$supplier_ids = array_column($data,'supplier_id');
	return array_values(array_unique(array_merge(array($default_supplier_id),$supplier_ids)));
}

function getSupplierInfoBySupplierIds($ids=array()){
	if(empty($ids)){
		return false;
	}
	$supplier_where = implode(",",$ids);
	$res = $GLOBALS['db']->getAll('SELECT supplier_id,supplier_name FROM '.$GLOBALS['ecs']->table("supplier").' WHERE status=1 AND supplier_id IN ('.$supplier_where.') ');
	foreach($res as $k=>$v){
		$res[$k]['template'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="template" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		$res[$k]['shop_logo'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_logo" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		if(empty($res[$k]['shop_logo'])){
			$res[$k]['shop_logo'] = '../themes/'.$res[$k]['template'].'/images/dianpu.jpg';
		}else{
			$res[$k]['shop_logo'] = '../'.$res[$k]['shop_logo'];
		}
	}
	return $res;
}


function getCircleInfoByUserId($user_id){
	$default_supplier_id = get_user_default_supplier($user_id,true);
	$circle_id =$GLOBALS['db']->getOne('SELECT circle_id FROM '.$GLOBALS['ecs']->table('supplier_circle_list').' WHERE supplier_id='.$default_supplier_id); 
	if($circle_id){
		$circle_list = getCircleSupplierList($circle_id);
	}else{
		$circle_list = array();
	}
	return $circle_list;
}

function getCircleSupplierList($circle_id){
	$res = $GLOBALS['db']->getAll('SELECT a.circle_id,b.supplier_id,b.supplier_name FROM '.$GLOBALS['ecs']->table("supplier_circle_list").' a LEFT JOIN '.$GLOBALS['ecs']->table("supplier").'  b ON a.supplier_id=b.supplier_id WHERE a.circle_id='.$circle_id);
	foreach($res as $k=>$v){
		$res[$k]['template'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="template" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		$res[$k]['shop_logo'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_logo" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		if(empty($res[$k]['shop_logo'])){
			$res[$k]['shop_logo'] = '../themes/'.$res[$k]['template'].'/images/dianpu.jpg';
		}else{
			$res[$k]['shop_logo'] = '../'.$res[$k]['shop_logo'];
		}
	}
	return $res;
}