<?php
/*纹绣师店铺入口*/
define('IN_ECS', true);
require (dirname(__FILE__) . '/includes/init.php');

$action = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
/* 路由 */
$function_name = 'action_' . $action;
//print_r($function_name);die();
if(! function_exists($function_name))
{
	$function_name = "action_default";
}
call_user_func($function_name);

//技师入口页面
function action_default(){
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];

	$smarty -> display('technican_list.dwt');
}

function action_ajax_search(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	include_once ('includes/cls_json.php');
	$json = new JSON();
	if(!isset($_REQUEST['city']) || empty($_REQUEST['city'] || $_REQUEST['city'] == '定位中')){
		$cityWhere = '';
		//die($json->encode(array('code'=>400,'msg'=>'获取定位失败,请手动选择定位')));
	}else{
		$city = city_suffix_handle(trim($_REQUEST['city']));
		$cityWhere = " AND c.`region_name` LIKE '%".trim($city)."' ";
	}
	
	$page = intval($_REQUEST['page']);
	$pagesize = 8;
	$start = ($page - 1)*$pagesize;
	$sql = "SELECT a.supplier_id,a.supplier_name,a.is_ipc_shopping,a.opus_remark,a.technican_bodyimg,a.contacts_name FROM ".$ecs->table("supplier")." a LEFT JOIN ".$ecs->table("supplier_shop_config")." b ON a.supplier_id=b.supplier_id LEFT JOIN ".$ecs->table("region")." c ON b.`value`=c.region_id WHERE a.`is_ipc_shopping` = 2 AND a.`status`=1 AND c.`region_type` = 2  ".$cityWhere." ORDER BY a.supplier_id DESC LIMIT ".$start.",".$pagesize;
	
	$list = $db->getAll($sql);
	foreach($list as $k=>$v){
		$list[$k]['technican_bodyimg'] = '../data/supplier/'.$v['technican_bodyimg'];
	}
	
	die($json->encode(array('code'=>200,'list'=>$list)));
}


function action_opus_list(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$smarty = $GLOBALS['smarty'];
	$opus_list = get_opus_list();
	$smarty->assign('suppId',intval($_GET['suppId']));
	$smarty->assign('opus_list',$opus_list);
	$smarty->display('technican_opus.dwt');
}

function action_ajax_search_opus(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	include_once ('includes/cls_json.php');
	$json = new JSON();
	$opus_list = get_opus_list();
	die($json->encode($opus_list));
}


/*通用函数列表*/
function city_suffix_handle($city){
	$suffix = mb_substr($city,-1,1,'utf-8');
	if($suffix == '市' ||$suffix == '州' ||$suffix == '盟' ||$suffix == '县'){
		return mb_substr($city,0,-1,'utf-8');
	}
	$re_suffix = mb_substr($city,-2,2,'utf-8');
	if($suffix == '地区' ||$suffix == '林区'){
		return mb_substr($city,0,-2,'utf-8');
	}
	return $city;
}

function get_opus_list(){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$page = (isset($_REQUEST['page'])&&!empty($_REQUEST['page']))?intval($_REQUEST['page']):1;
	$pagesize = 3;
	$start = ($page-1) * $pagesize;
	$list = $db->getAll("SELECT id,opus_img,opus_title,opus_desc FROM ".$ecs->table('technican_opus')." WHERE supplier_id = ".intval($_REQUEST['suppId'])." ORDER BY add_time DESC LIMIT ".$start.",".$pagesize);
	foreach($list as $k=>$v){
		$opus_img = explode(",",$v['opus_img']);
		foreach($opus_img as $kk=>$vv){
			$opus_img[$kk] = "supplier/".$vv;
		}

		$list[$k]['opus_img'] = $opus_img;
	}
	return $list;
}