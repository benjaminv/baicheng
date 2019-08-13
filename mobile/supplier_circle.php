<?php
/*联盟商圈入口*/
define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init.php');
$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
/* 路由 */
$function_name = 'action_' . $action;
if(! function_exists($function_name))
{
	$function_name = "action_default";
}
call_user_func($function_name);

//商圈入口页面
function action_default(){
	//根据店铺id获取该店铺的商圈id
	$circle_info = $GLOBALS['db']->getRow("SELECT a.id,a.name,a.remark,a.logo FROM ".$GLOBALS['ecs']->table('supplier_circle')." a LEFT JOIN ".$GLOBALS['ecs']->table('supplier_circle_list')." b ON a.id=b.circle_id WHERE b.supplier_id = ".intval($_GET['suppId']));
	if(empty($circle_info)){
		show_message('不存在的商圈', array('会员中心'), array('user.php'), 'info');
	}
	$circle_info['logo'] = "../".$circle_info['logo'];
	$circle_info['circle_list'] = getCircleSupplierList(intval($circle_info['id']));
	$GLOBALS['smarty']->assign('circle_info', $circle_info);
	$GLOBALS['smarty'] -> display('supplier_circle.dwt');
}




function getCircleSupplierList($circle_id){
	$res = $GLOBALS['db']->getAll('SELECT a.circle_id,b.supplier_id,b.supplier_name FROM '.$GLOBALS['ecs']->table("supplier_circle_list").' a LEFT JOIN '.$GLOBALS['ecs']->table("supplier").'  b ON a.supplier_id=b.supplier_id WHERE b.status = 1 AND a.circle_id='.$circle_id);
	foreach($res as $k=>$v){
		$res[$k]['template'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="template" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		$res[$k]['shop_logo'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_logo" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
		if(empty($res[$k]['shop_logo'])){
			$res[$k]['shop_logo'] = '../themes/'.$res[$k]['template'].'/images/dianpu.jpg';
		}else{
			$res[$k]['shop_logo'] = '../'.$res[$k]['shop_logo'];
		}
		$res[$k]['shop_desc'] = $GLOBALS['db']->getOne('SELECT value FROM ' . $GLOBALS['ecs']->table('supplier_shop_config') . ' WHERE code="shop_desc" AND  parent_id > 0 AND supplier_id='.$v['supplier_id']);
	}
	return $res;
}

