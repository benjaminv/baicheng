<?php

/**
 * 店铺的控制器文件
 * ============================================================================
 * * 版权所有 2017-2020 月梦网络，并保留所有权利。
 * 月梦网络: http://dm299.taobao.com  开发QQ:124861234  禁止倒卖 一经发现停止任何服务
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: index.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);
require(dirname(__FILE__) . '/includes/init_supplier.php');
require(ROOT_PATH . 'includes/lib_order.php');

if($_GET['suppId']<=0){
	ecs_header("Location: index.php");
    exit;
}
$user_id = isset($_SESSION['user_id'])?intval($_SESSION['user_id']):0;
if(empty($user_id)){
	//立即登录
	setcookie('callback','http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'],time()+3600);
	header("location: user.php?act=login");exit;
	exit;
}


$sql="SELECT s.*,sr.rank_name FROM ". $ecs->table("supplier") . " as s left join ". $ecs->table("supplier_rank") ." as sr ON s.rank_id=sr.rank_id
 WHERE s.supplier_id=".$_GET['suppId']." AND s.status=1";
$suppinfo=$db->getRow($sql);

$smarty->assign('suppid', $suppinfo['supplier_id']);

if(empty($suppinfo['supplier_id']) || $_GET['suppId'] != $suppinfo['supplier_id'])
{
	show_message('不存在的商家！', array('会员中心'), array('user.php'), 'info');
	 //ecs_header("Location: index.php");
     exit;
}

//$default_supplier_id = get_user_default_supplier(intval($user_id));
//if($default_supplier_id !=$_GET['suppId']){
	//ecs_header("Location: supplier_rebind.php?old_supplier=".$default_supplier_id."&new_supplier=".$_GET['suppId']);
    //exit;
//}
  
/**/

//会员进入店铺之后保存店铺id在cookie,下次进入时默认跳转到该店铺，直到cookie失效或者更换店铺 edit yhy2019/5/8
setcookie('u_supplier_id', intval($_GET['suppId']), gmtime() + 3600 * 24); // 过期时间为 1 天

//更新用户-店铺关联表 yhy edit 2019/6/24
	update_user_supplier_relate($user_id,$_GET['suppId']);
//end
if ((DEBUG_MODE & 2) != 2)
{
    $smarty->caching = true;
}

$typeinfo = array('index','category','search','article','other','about','activity','technican');
$go = (isset($_GET['go']) && !empty($_GET['go'])) ? $_GET['go'] : 'index';


if(!in_array($go,$typeinfo)){
	ecs_header("Location: index.php");
    exit;
}else{
	
	require(dirname(__FILE__) . '/supplier_'.$go.'.php');
}

?>